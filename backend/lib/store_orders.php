<?php
declare(strict_types=1);

function generate_order_number(): string
{
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function checkout(array $data): array
{
    $userId = clean_int($data['user_id'] ?? 0);
    if ($userId <= 0) {
        throw new InvalidArgumentException('User ID is required.');
    }

    $cart = get_cart($userId);
    if ($cart['items'] === []) {
        throw new InvalidArgumentException('Cart is empty.');
    }

    foreach (['full_name', 'phone', 'address', 'city', 'province'] as $field) {
        if (clean_string($data[$field] ?? '') === '') {
            throw new InvalidArgumentException(ucwords(str_replace('_', ' ', $field)) . ' is required.');
        }
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $totalAmount = 0.0;
        $lockedVariants = [];

        foreach ($cart['items'] as $item) {
            $variant = fetch_one_row(
                'SELECT pv.*, p.name AS product_name
                 FROM product_variants pv
                 INNER JOIN products p ON p.id = pv.product_id
                 WHERE pv.id = :id FOR UPDATE',
                [':id' => $item['variant_id']]
            );

            if ($variant === null) {
                throw new RuntimeException('A selected product variant no longer exists.');
            }

            if ((int) $variant['stock'] < (int) $item['quantity']) {
                throw new RuntimeException('Insufficient stock for ' . $variant['product_name'] . ' (' . $variant['size'] . ' / ' . $variant['color'] . ').');
            }

            $lockedVariants[(int) $variant['id']] = $variant;
            $totalAmount += (float) $item['subtotal'];
        }

        $orderNumber = generate_order_number();
        execute_sql(
            'INSERT INTO orders
                (user_id, order_number, total_amount, payment_method, payment_status, order_status, full_name, phone, address, city, province, zip_code, landmark, notes)
             VALUES
                (:user_id, :order_number, :total_amount, :payment_method, :payment_status, :order_status, :full_name, :phone, :address, :city, :province, :zip_code, :landmark, :notes)',
            [
                ':user_id' => $userId,
                ':order_number' => $orderNumber,
                ':total_amount' => round($totalAmount, 2),
                ':payment_method' => strtoupper(clean_string($data['payment_method'] ?? 'COD', 'COD')),
                ':payment_status' => 'Pending',
                ':order_status' => 'Pending',
                ':full_name' => clean_string($data['full_name'] ?? ''),
                ':phone' => clean_string($data['phone'] ?? ''),
                ':address' => clean_string($data['address'] ?? ''),
                ':city' => clean_string($data['city'] ?? ''),
                ':province' => clean_string($data['province'] ?? ''),
                ':zip_code' => clean_string($data['zip_code'] ?? ''),
                ':landmark' => clean_string($data['landmark'] ?? ''),
                ':notes' => clean_string($data['notes'] ?? ''),
            ]
        );

        $orderId = (int) $pdo->lastInsertId();

        foreach ($cart['items'] as $item) {
            $variant = $lockedVariants[(int) $item['variant_id']] ?? null;
            if ($variant === null) {
                throw new RuntimeException('Unable to finalize stock.');
            }

            $newStock = (int) $variant['stock'] - (int) $item['quantity'];

            execute_sql(
                'UPDATE product_variants SET stock = :stock WHERE id = :id',
                [
                    ':id' => $variant['id'],
                    ':stock' => $newStock,
                ]
            );

            execute_sql(
                'INSERT INTO order_items
                    (order_id, product_id, variant_id, product_name, variant_label, quantity, price, subtotal)
                 VALUES
                    (:order_id, :product_id, :variant_id, :product_name, :variant_label, :quantity, :price, :subtotal)',
                [
                    ':order_id' => $orderId,
                    ':product_id' => $item['product_id'],
                    ':variant_id' => $item['variant_id'],
                    ':product_name' => $item['product_name'],
                    ':variant_label' => 'Size ' . $item['size'] . ' / ' . $item['color'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price'],
                    ':subtotal' => $item['subtotal'],
                ]
            );

            execute_sql(
                'INSERT INTO stock_history
                    (product_id, variant_id, user_id, change_type, quantity_before, quantity_change, quantity_after, notes)
                 VALUES
                    (:product_id, :variant_id, :user_id, :change_type, :quantity_before, :quantity_change, :quantity_after, :notes)',
                [
                    ':product_id' => $item['product_id'],
                    ':variant_id' => $item['variant_id'],
                    ':user_id' => $userId,
                    ':change_type' => 'checkout',
                    ':quantity_before' => $variant['stock'],
                    ':quantity_change' => -1 * (int) $item['quantity'],
                    ':quantity_after' => $newStock,
                    ':notes' => 'Order ' . $orderNumber,
                ]
            );
        }

        clear_cart($userId);
        $pdo->commit();

        $order = get_order($orderId);
        if ($order === null) {
            throw new RuntimeException('Order was created but could not be loaded.');
        }

        return $order;
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function list_orders(?int $userId = null): array
{
    $sql = "
        SELECT
            o.*,
            u.name AS customer_name,
            COUNT(oi.id) AS item_count
        FROM orders o
        INNER JOIN users u ON u.id = o.user_id
        LEFT JOIN order_items oi ON oi.order_id = o.id
    ";

    $params = [];
    if ($userId !== null) {
        $sql .= ' WHERE o.user_id = :user_id';
        $params[':user_id'] = $userId;
    }

    $sql .= ' GROUP BY o.id ORDER BY o.created_at DESC';

    return fetch_all_rows($sql, $params);
}

function get_order(int $id): ?array
{
    $order = fetch_one_row(
        "SELECT
            o.*,
            u.name AS customer_name,
            u.email AS customer_email
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         WHERE o.id = :id",
        [':id' => $id]
    );

    if ($order === null) {
        return null;
    }

    $order['items'] = fetch_all_rows(
        'SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC',
        [':order_id' => $id]
    );

    return $order;
}

function update_order_status(int $orderId, string $orderStatus, ?string $paymentStatus = null): void
{
    $params = [
        ':id' => $orderId,
        ':order_status' => normalize_status($orderStatus, 'Pending'),
    ];

    $sql = 'UPDATE orders SET order_status = :order_status';

    if ($paymentStatus !== null && $paymentStatus !== '') {
        $sql .= ', payment_status = :payment_status';
        $params[':payment_status'] = normalize_status($paymentStatus, 'Pending');
    }

    $sql .= ' WHERE id = :id';

    execute_sql($sql, $params);
}

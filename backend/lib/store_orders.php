<?php
declare(strict_types=1);

function cancellation_requested_status(): string
{
    return 'Cancellation Requested';
}

function customer_cancellable_statuses(): array
{
    return ['Pending', 'Confirmed'];
}

function generate_order_number(): string
{
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function validated_checkout_data(array $data): array
{
    $paymentMethod = strtoupper(clean_string($data['payment_method'] ?? 'COD', 'COD'));
    if ($paymentMethod !== 'COD') {
        throw new InvalidArgumentException('Only Cash on Delivery is accepted.');
    }

    $fullName = clean_string($data['full_name'] ?? '');
    if (strlen($fullName) < 2 || strlen($fullName) > 150) {
        throw new InvalidArgumentException('Full name must be 2 to 150 characters.');
    }

    $phone = clean_string($data['phone'] ?? '');
    $phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($phoneDigits) < 7 || strlen($phoneDigits) > 15) {
        throw new InvalidArgumentException('Phone number must contain 7 to 15 digits.');
    }

    $address = clean_string($data['address'] ?? '');
    if (strlen($address) < 8) {
        throw new InvalidArgumentException('Delivery address must be at least 8 characters.');
    }

    $city = clean_string($data['city'] ?? '');
    if (strlen($city) < 2 || strlen($city) > 100) {
        throw new InvalidArgumentException('City must be 2 to 100 characters.');
    }

    $province = clean_string($data['province'] ?? '');
    if (strlen($province) < 2 || strlen($province) > 100) {
        throw new InvalidArgumentException('Province must be 2 to 100 characters.');
    }

    $zipCode = clean_string($data['zip_code'] ?? '');
    if ($zipCode !== '' && (!preg_match('/^[A-Za-z0-9 -]{1,20}$/', $zipCode))) {
        throw new InvalidArgumentException('ZIP code can only contain letters, numbers, spaces, and dashes.');
    }

    $landmark = clean_string($data['landmark'] ?? '');
    if (strlen($landmark) > 150) {
        throw new InvalidArgumentException('Landmark must be 150 characters or less.');
    }

    $notes = clean_string($data['notes'] ?? '');
    if (strlen($notes) > 1000) {
        throw new InvalidArgumentException('Order notes must be 1000 characters or less.');
    }

    return [
        'payment_method' => $paymentMethod,
        'full_name' => $fullName,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'province' => $province,
        'zip_code' => $zipCode,
        'landmark' => $landmark,
        'notes' => $notes,
    ];
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

    $checkoutData = validated_checkout_data($data);

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
                ':payment_method' => $checkoutData['payment_method'],
                ':payment_status' => 'Pending',
                ':order_status' => 'Pending',
                ':full_name' => $checkoutData['full_name'],
                ':phone' => $checkoutData['phone'],
                ':address' => $checkoutData['address'],
                ':city' => $checkoutData['city'],
                ':province' => $checkoutData['province'],
                ':zip_code' => $checkoutData['zip_code'],
                ':landmark' => $checkoutData['landmark'],
                ':notes' => $checkoutData['notes'],
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
            COALESCE(item_counts.item_count, 0) AS item_count,
            COALESCE(item_counts.item_summary, '') AS item_summary,
            ofb.id AS feedback_id,
            ofb.rating AS feedback_rating,
            ofb.feedback AS feedback_text,
            ofb.created_at AS feedback_created_at
        FROM orders o
        INNER JOIN users u ON u.id = o.user_id
        LEFT JOIN (
            SELECT
                order_id,
                COUNT(*) AS item_count,
                GROUP_CONCAT(CONCAT(product_name, ' - ', variant_label, ' x', quantity) SEPARATOR ', ') AS item_summary
            FROM order_items
            GROUP BY order_id
        ) item_counts ON item_counts.order_id = o.id
        LEFT JOIN order_feedback ofb ON ofb.order_id = o.id
    ";

    $params = [];
    if ($userId !== null) {
        $sql .= ' WHERE o.user_id = :user_id';
        $params[':user_id'] = $userId;
    }

    $sql .= ' ORDER BY o.created_at DESC';

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

    $order['feedback'] = fetch_one_row(
        'SELECT id, rating, feedback, created_at, updated_at
         FROM order_feedback
         WHERE order_id = :order_id',
        [':order_id' => $id]
    );

    return $order;
}

function save_order_feedback(array $data): array
{
    $orderId = clean_int($data['order_id'] ?? $data['id'] ?? 0);
    $userId = clean_int($data['user_id'] ?? 0);
    $rating = clean_int($data['rating'] ?? 0);
    $feedback = clean_string($data['feedback'] ?? '');

    if ($orderId <= 0 || $userId <= 0) {
        throw new InvalidArgumentException('Order ID and user ID are required.');
    }

    if ($rating < 1 || $rating > 5) {
        throw new InvalidArgumentException('Rating must be from 1 to 5.');
    }

    if (strlen($feedback) > 1000) {
        throw new InvalidArgumentException('Feedback must be 1000 characters or less.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $order = fetch_one_row(
            'SELECT * FROM orders WHERE id = :id AND user_id = :user_id FOR UPDATE',
            [
                ':id' => $orderId,
                ':user_id' => $userId,
            ]
        );

        if ($order === null) {
            throw new RuntimeException('Order not found.');
        }

        if (normalize_status((string) ($order['order_status'] ?? ''), 'Pending') !== 'Delivered') {
            throw new RuntimeException('You can rate an order only after it is delivered.');
        }

        execute_sql(
            'INSERT INTO order_feedback (order_id, user_id, rating, feedback)
             VALUES (:order_id, :user_id, :rating, :feedback)
             ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                feedback = VALUES(feedback),
                updated_at = CURRENT_TIMESTAMP',
            [
                ':order_id' => $orderId,
                ':user_id' => $userId,
                ':rating' => $rating,
                ':feedback' => $feedback,
            ]
        );

        $pdo->commit();

        $updatedOrder = get_order($orderId);
        if ($updatedOrder === null) {
            throw new RuntimeException('Feedback was saved but the order could not be reloaded.');
        }

        return $updatedOrder;
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function request_order_cancellation(int $orderId, int $userId): array
{
    if ($orderId <= 0 || $userId <= 0) {
        throw new InvalidArgumentException('Order and user are required.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $order = fetch_one_row(
            'SELECT * FROM orders WHERE id = :id AND user_id = :user_id FOR UPDATE',
            [
                ':id' => $orderId,
                ':user_id' => $userId,
            ]
        );

        if ($order === null) {
            throw new RuntimeException('Order not found.');
        }

        $currentStatus = normalize_status((string) ($order['order_status'] ?? 'Pending'), 'Pending');
        if ($currentStatus === 'Cancelled') {
            throw new RuntimeException('This order is already cancelled.');
        }

        if ($currentStatus === cancellation_requested_status()) {
            throw new RuntimeException('Cancellation request already sent.');
        }

        if (!in_array($currentStatus, customer_cancellable_statuses(), true)) {
            throw new RuntimeException('Only pending or confirmed orders can be cancelled.');
        }

        execute_sql(
            'UPDATE orders SET order_status = :order_status WHERE id = :id',
            [
                ':id' => $orderId,
                ':order_status' => cancellation_requested_status(),
            ]
        );

        $pdo->commit();

        $updatedOrder = get_order($orderId);
        if ($updatedOrder === null) {
            throw new RuntimeException('Cancellation request was saved but the order could not be reloaded.');
        }

        return $updatedOrder;
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function restore_cancelled_order_stock(array $order, ?int $actorUserId = null): void
{
    $items = fetch_all_rows(
        'SELECT
            oi.*,
            pv.id AS existing_variant_id,
            pv.product_id AS existing_product_id,
            pv.stock AS current_stock
         FROM order_items oi
         LEFT JOIN product_variants pv ON pv.id = oi.variant_id
         WHERE oi.order_id = :order_id
         ORDER BY oi.id ASC
         FOR UPDATE',
        [':order_id' => $order['id']]
    );

    foreach ($items as $item) {
        if (empty($item['existing_variant_id'])) {
            throw new RuntimeException('Unable to restore stock because one of the ordered variants no longer exists.');
        }

        $quantityBefore = (int) ($item['current_stock'] ?? 0);
        $quantityChange = (int) ($item['quantity'] ?? 0);
        $quantityAfter = $quantityBefore + $quantityChange;

        execute_sql(
            'UPDATE product_variants SET stock = :stock WHERE id = :id',
            [
                ':id' => $item['existing_variant_id'],
                ':stock' => $quantityAfter,
            ]
        );

        execute_sql(
            'INSERT INTO stock_history
                (product_id, variant_id, user_id, change_type, quantity_before, quantity_change, quantity_after, notes)
             VALUES
                (:product_id, :variant_id, :user_id, :change_type, :quantity_before, :quantity_change, :quantity_after, :notes)',
            [
                ':product_id' => clean_int($item['product_id'] ?? 0) > 0 ? $item['product_id'] : $item['existing_product_id'],
                ':variant_id' => $item['existing_variant_id'],
                ':user_id' => $actorUserId,
                ':change_type' => 'cancel_restore',
                ':quantity_before' => $quantityBefore,
                ':quantity_change' => $quantityChange,
                ':quantity_after' => $quantityAfter,
                ':notes' => 'Order ' . ($order['order_number'] ?? $order['id']) . ' cancelled',
            ]
        );
    }
}

function update_order_status(int $orderId, string $orderStatus, ?string $paymentStatus = null, ?string $shippingLocation = null): void
{
    if ($orderId <= 0) {
        throw new InvalidArgumentException('Order ID is required.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $order = fetch_one_row('SELECT * FROM orders WHERE id = :id FOR UPDATE', [':id' => $orderId]);
        if ($order === null) {
            throw new RuntimeException('Order not found.');
        }

        $currentStatus = normalize_status((string) ($order['order_status'] ?? 'Pending'), 'Pending');
        $nextStatus = normalize_status($orderStatus, 'Pending');

        if ($currentStatus === 'Cancelled' && $nextStatus !== 'Cancelled') {
            throw new RuntimeException('Cancelled orders cannot be reopened because stock was already returned.');
        }

        if ($currentStatus === 'Delivered' && $nextStatus === 'Cancelled') {
            throw new RuntimeException('Delivered orders cannot be cancelled.');
        }

        $resolvedPaymentStatus = null;
        if ($paymentStatus !== null && $paymentStatus !== '') {
            $resolvedPaymentStatus = normalize_status($paymentStatus, 'Pending');
        } elseif ($nextStatus === 'Cancelled') {
            $resolvedPaymentStatus = 'Cancelled';
        }

        $resolvedShippingLocation = null;
        $updateShippingLocation = $shippingLocation !== null;
        if ($updateShippingLocation) {
            $resolvedShippingLocation = trim($shippingLocation);
        }

        $sql = 'UPDATE orders SET order_status = :order_status';
        $params = [
            ':id' => $orderId,
            ':order_status' => $nextStatus,
        ];

        if ($resolvedPaymentStatus !== null) {
            $sql .= ', payment_status = :payment_status';
            $params[':payment_status'] = $resolvedPaymentStatus;
        }

        if ($updateShippingLocation) {
            $sql .= ', shipping_location = :shipping_location, shipping_location_updated_at = :shipping_location_updated_at';
            $params[':shipping_location'] = $resolvedShippingLocation !== '' ? $resolvedShippingLocation : null;
            $params[':shipping_location_updated_at'] = $resolvedShippingLocation !== '' ? date('Y-m-d H:i:s') : null;
        }

        $sql .= ' WHERE id = :id';

        execute_sql($sql, $params);

        if ($currentStatus !== 'Cancelled' && $nextStatus === 'Cancelled') {
            restore_cancelled_order_stock($order, current_user_id());
        }

        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

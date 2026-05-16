<?php
declare(strict_types=1);

function get_or_create_cart_id(int $userId): int
{
    $cart = fetch_one_row('SELECT id FROM carts WHERE user_id = :user_id', [':user_id' => $userId]);
    if ($cart !== null) {
        return (int) $cart['id'];
    }

    execute_sql('INSERT INTO carts (user_id) VALUES (:user_id)', [':user_id' => $userId]);
    return (int) db()->lastInsertId();
}

function get_cart(int $userId): array
{
    $cartId = get_or_create_cart_id($userId);
    $items = fetch_all_rows(
        "SELECT
            ci.id,
            ci.cart_id,
            ci.product_id,
            ci.variant_id,
            ci.quantity,
            ci.price,
            ci.subtotal,
            p.name AS product_name,
            p.image,
            pv.size,
            pv.color,
            pv.stock AS available_stock
         FROM cart_items ci
         INNER JOIN products p ON p.id = ci.product_id
         INNER JOIN product_variants pv ON pv.id = ci.variant_id
         WHERE ci.cart_id = :cart_id
         ORDER BY ci.created_at DESC",
        [':cart_id' => $cartId]
    );

    $total = 0.0;
    foreach ($items as &$item) {
        $item['image_url'] = public_image_url($item['image'] ?? null);
        $item['price'] = (float) $item['price'];
        $item['subtotal'] = (float) $item['subtotal'];
        $total += $item['subtotal'];
    }
    unset($item);

    return [
        'cart_id' => $cartId,
        'user_id' => $userId,
        'items' => $items,
        'total' => round($total, 2),
    ];
}

function add_to_cart(int $userId, int $productId, int $variantId, int $quantity): array
{
    if ($quantity <= 0) {
        throw new InvalidArgumentException('Quantity must be at least 1.');
    }

    $variant = get_variant($variantId);
    if ($variant === null || (int) $variant['product_id'] !== $productId) {
        throw new InvalidArgumentException('Selected variant is invalid.');
    }

    if ((int) $variant['stock'] < $quantity) {
        throw new InvalidArgumentException('Not enough stock available for this variant.');
    }

    $cartId = get_or_create_cart_id($userId);
    $existing = fetch_one_row(
        'SELECT * FROM cart_items WHERE cart_id = :cart_id AND variant_id = :variant_id',
        [
            ':cart_id' => $cartId,
            ':variant_id' => $variantId,
        ]
    );

    $price = (float) $variant['price'];
    if ($existing !== null) {
        $newQuantity = (int) $existing['quantity'] + $quantity;
        if ($newQuantity > (int) $variant['stock']) {
            throw new InvalidArgumentException('Requested cart quantity exceeds available stock.');
        }

        execute_sql(
            'UPDATE cart_items SET quantity = :quantity, price = :price, subtotal = :subtotal WHERE id = :id',
            [
                ':id' => $existing['id'],
                ':quantity' => $newQuantity,
                ':price' => $price,
                ':subtotal' => $newQuantity * $price,
            ]
        );
    } else {
        execute_sql(
            'INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price, subtotal)
             VALUES (:cart_id, :product_id, :variant_id, :quantity, :price, :subtotal)',
            [
                ':cart_id' => $cartId,
                ':product_id' => $productId,
                ':variant_id' => $variantId,
                ':quantity' => $quantity,
                ':price' => $price,
                ':subtotal' => $quantity * $price,
            ]
        );
    }

    return get_cart($userId);
}

function update_cart_item(int $userId, int $cartItemId, int $quantity): array
{
    $item = fetch_one_row(
        'SELECT ci.*, c.user_id, pv.stock, p.price
         FROM cart_items ci
         INNER JOIN carts c ON c.id = ci.cart_id
         INNER JOIN product_variants pv ON pv.id = ci.variant_id
         INNER JOIN products p ON p.id = ci.product_id
         WHERE ci.id = :id',
        [':id' => $cartItemId]
    );

    if ($item === null || (int) $item['user_id'] !== $userId) {
        throw new InvalidArgumentException('Cart item not found.');
    }

    if ($quantity <= 0) {
        return remove_cart_item($userId, $cartItemId);
    }

    if ($quantity > (int) $item['stock']) {
        throw new InvalidArgumentException('Requested quantity exceeds available stock.');
    }

    $price = (float) $item['price'];
    execute_sql(
        'UPDATE cart_items SET quantity = :quantity, price = :price, subtotal = :subtotal WHERE id = :id',
        [
            ':id' => $cartItemId,
            ':quantity' => $quantity,
            ':price' => $price,
            ':subtotal' => $quantity * $price,
        ]
    );

    return get_cart($userId);
}

function remove_cart_item(int $userId, int $cartItemId): array
{
    $item = fetch_one_row(
        'SELECT ci.id, c.user_id
         FROM cart_items ci
         INNER JOIN carts c ON c.id = ci.cart_id
         WHERE ci.id = :id',
        [':id' => $cartItemId]
    );

    if ($item === null || (int) $item['user_id'] !== $userId) {
        throw new InvalidArgumentException('Cart item not found.');
    }

    execute_sql('DELETE FROM cart_items WHERE id = :id', [':id' => $cartItemId]);

    return get_cart($userId);
}

function clear_cart(int $userId): void
{
    $cartId = get_or_create_cart_id($userId);
    execute_sql('DELETE FROM cart_items WHERE cart_id = :cart_id', [':cart_id' => $cartId]);
}

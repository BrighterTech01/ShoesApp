<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $data = request_data();
    $cart = add_to_cart(
        clean_int($data['user_id'] ?? 0),
        clean_int($data['product_id'] ?? 0),
        clean_int($data['variant_id'] ?? 0),
        clean_int($data['quantity'] ?? 1, 1)
    );

    success_response($cart, 'Item added to cart.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

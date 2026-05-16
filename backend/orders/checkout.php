<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $order = checkout(request_data());
    success_response($order, 'Order placed successfully.', 201);
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

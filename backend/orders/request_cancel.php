<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $data = request_data();
    $orderId = clean_int($data['order_id'] ?? $data['id'] ?? 0);
    $userId = clean_int($data['user_id'] ?? 0);

    if ($orderId <= 0 || $userId <= 0) {
        error_response('Order ID and user ID are required.', 422);
    }

    $order = request_order_cancellation($orderId, $userId);
    success_response($order, 'Cancellation request sent.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

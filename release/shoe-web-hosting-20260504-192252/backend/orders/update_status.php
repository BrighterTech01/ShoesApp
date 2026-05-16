<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_auth(['admin', 'seller'], true);

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $data = request_data();
    $orderId = clean_int($data['id'] ?? 0);
    if ($orderId <= 0) {
        error_response('Order ID is required.', 422);
    }

    update_order_status(
        $orderId,
        clean_string($data['order_status'] ?? 'Pending', 'Pending'),
        clean_string($data['payment_status'] ?? '')
    );

    success_response(get_order($orderId), 'Order status updated.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

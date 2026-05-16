<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    $id = clean_int(query_param('id', 0));
    if ($id <= 0) {
        error_response('Order ID is required.', 422);
    }

    $order = get_order($id);
    if ($order === null) {
        error_response('Order not found.', 404);
    }

    success_response($order, 'Order loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    $userId = clean_int(query_param('user_id', 0));
    $orders = list_orders($userId > 0 ? $userId : null);
    success_response($orders, 'Orders loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

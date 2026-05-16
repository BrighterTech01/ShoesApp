<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    $userId = clean_int(query_param('user_id', 0));
    if ($userId <= 0) {
        error_response('User ID is required.', 422);
    }

    success_response(get_cart($userId), 'Cart loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

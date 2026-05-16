<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_auth(['admin', 'seller'], true);

try {
    success_response(list_users(clean_string(query_param('role', '')) ?: null), 'Users loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

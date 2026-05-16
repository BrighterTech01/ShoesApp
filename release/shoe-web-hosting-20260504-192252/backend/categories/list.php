<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    $activeOnly = clean_bool(query_param('active_only', false));
    success_response(list_categories($activeOnly), 'Categories loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

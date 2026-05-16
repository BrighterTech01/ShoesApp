<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_auth(['admin', 'seller'], true);

try {
    success_response(get_dashboard_metrics(), 'Dashboard loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

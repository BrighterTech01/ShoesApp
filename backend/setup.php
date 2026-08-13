<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    db();

    success_response([
        'database' => app_config('db.name'),
        'status' => 'ready',
    ], 'Database connection succeeded and tables are ready.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

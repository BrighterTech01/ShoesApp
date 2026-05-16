<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

success_response([
    'status' => 'healthy',
    'database' => app_config('db.name'),
    'time' => date(DATE_ATOM),
]);

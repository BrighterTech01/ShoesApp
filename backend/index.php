<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

success_response([
    'name' => app_config('app.name'),
    'database' => app_config('db.name'),
    'status' => 'online',
], 'Backend is running.');

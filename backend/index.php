<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

success_response([
    'name' => app_config('app.name'),
    'database' => app_config('db.name'),
    'default_admin_email' => app_config('auth.default_admin_email'),
    'default_password' => app_config('auth.default_password'),
], 'Backend is running.');

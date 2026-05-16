<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/lib/bootstrap.php';

function require_admin_panel(): void
{
    require_auth(['admin', 'seller'], false);
}

function admin_path(string $path = ''): string
{
    return app_url('web-admin/' . ltrim($path, '/'));
}

function admin_current_user(): ?array
{
    return current_user();
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

$user = current_user();
if ($user === null) {
    error_response('No active session.', 401);
}

success_response($user, 'Current user loaded.');

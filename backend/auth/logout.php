<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

logout_user();
success_response(null, 'Logged out successfully.');

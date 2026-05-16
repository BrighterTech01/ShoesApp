<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

logout_user();
flash_set('success', 'You have been logged out.');
redirect_to(admin_path('login.php'));

<?php
declare(strict_types=1);

$autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

require_once __DIR__ . '/config.php';
require_once base_path('config/database.php');
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/store_common.php';
require_once __DIR__ . '/store_users.php';
require_once __DIR__ . '/store_catalog.php';
require_once __DIR__ . '/store_cart.php';
require_once __DIR__ . '/store_orders.php';
require_once __DIR__ . '/store_reports.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/auth.php';

date_default_timezone_set('Asia/Manila');
ensure_session_started();

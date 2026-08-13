<?php
declare(strict_types=1);

$httpHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocalHost = $httpHost === '' || (
    str_starts_with($httpHost, '127.0.0.1')
    || str_starts_with($httpHost, 'localhost')
    || str_starts_with($httpHost, '192.168.')
);

$defaultBaseUrl = $isLocalHost ? 'http://127.0.0.1/shoesS' : 'http://fitmeinshoesia.atwebpages.com';
$defaultBackendUrl = $defaultBaseUrl . '/backend';
$defaultUploadsUrl = $defaultBackendUrl . '/uploads';
$defaultDbHost = $isLocalHost ? '127.0.0.1' : 'fdb1032.awardspace.net';
$defaultDbName = $isLocalHost ? 'shoesap' : '4759786_shoes';
$defaultDbUser = $isLocalHost ? 'root' : '4759786_shoes';
$defaultDbPass = $isLocalHost ? '' : '';
$mailUsername = (string) (getenv('MAIL_USERNAME') ?: '');
$defaultMailFrom = $isLocalHost ? 'no-reply@localhost' : 'no-reply@fitmeinshoesia.atwebpages.com';
$mailOverrides = [];
$mailLocalPath = __DIR__ . '/mail.local.php';
if (is_file($mailLocalPath)) {
    $loadedMailOverrides = require $mailLocalPath;
    if (is_array($loadedMailOverrides)) {
        $mailOverrides = $loadedMailOverrides;
    }
}

$autoCreateDatabase = filter_var(getenv('DB_AUTO_CREATE_DATABASE'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

return [
    'app' => [
        'name' => 'FIt Me In Sho Esia',
        'base_url' => getenv('APP_BASE_URL') ?: $defaultBaseUrl,
        'backend_url' => getenv('BACKEND_URL') ?: $defaultBackendUrl,
        'uploads_url' => getenv('UPLOADS_URL') ?: $defaultUploadsUrl,
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: $defaultDbHost,
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: $defaultDbName,
        'username' => getenv('DB_USER') ?: $defaultDbUser,
        'password' => getenv('DB_PASS') ?: $defaultDbPass,
        'charset' => 'utf8mb4',
        'auto_create_database' => $autoCreateDatabase ?? false,
    ],
    'auth' => [
        'default_admin_name' => 'System Admin',
        'default_admin_email' => 'admin@shoes.local',
        'default_seller_name' => 'Default Seller',
        'default_seller_email' => 'seller@shoes.local',
        'default_customer_name' => 'Sample Customer',
        'default_customer_email' => 'customer@shoes.local',
        'default_password' => 'admin12345',
        'jwt_secret' => getenv('JWT_SECRET') ?: 'change-this-jwt-secret-before-online-deployment',
        'jwt_issuer' => getenv('JWT_ISSUER') ?: 'fit-me-in-sho-esia',
        'jwt_ttl_seconds' => (int) (getenv('JWT_TTL_SECONDS') ?: 604800),
        'email_verification_ttl_minutes' => (int) (getenv('EMAIL_VERIFICATION_TTL_MINUTES') ?: 1440),
        'password_reset_ttl_minutes' => (int) (getenv('PASSWORD_RESET_TTL_MINUTES') ?: 60),
    ],
    'mail' => [
        'from_email' => getenv('MAIL_FROM_EMAIL')
            ?: ($mailOverrides['from_email'] ?? ($mailUsername !== '' ? $mailUsername : $defaultMailFrom)),
        'from_name' => getenv('MAIL_FROM_NAME') ?: ($mailOverrides['from_name'] ?? 'Fit Me In Sho Esia'),
        'php_mail_from_email' => getenv('MAIL_PHP_FROM_EMAIL')
            ?: ($mailOverrides['php_mail_from_email'] ?? $defaultMailFrom),
        'php_mail_extra_parameters' => getenv('MAIL_PHP_EXTRA_PARAMETERS')
            ?: ($mailOverrides['php_mail_extra_parameters'] ?? ''),
        'smtp_host' => getenv('MAIL_HOST') ?: ($mailOverrides['smtp_host'] ?? ''),
        'smtp_port' => (int) (getenv('MAIL_PORT') ?: ($mailOverrides['smtp_port'] ?? 587)),
        'smtp_username' => getenv('MAIL_USERNAME') ?: ($mailOverrides['smtp_username'] ?? $mailUsername),
        'smtp_password' => str_replace(' ', '', (string) (getenv('MAIL_PASSWORD') ?: ($mailOverrides['smtp_password'] ?? ''))),
        'smtp_encryption' => getenv('MAIL_ENCRYPTION') ?: ($mailOverrides['smtp_encryption'] ?? 'tls'),
        'smtp_timeout' => (int) (getenv('MAIL_TIMEOUT') ?: ($mailOverrides['smtp_timeout'] ?? 15)),
        'allow_php_mail' => filter_var(
            getenv('MAIL_ALLOW_PHP_MAIL') ?: ($mailOverrides['allow_php_mail'] ?? 'true'),
            FILTER_VALIDATE_BOOL
        ),
        'log_emails' => filter_var(
            getenv('MAIL_LOG_EMAILS') ?: ($mailOverrides['log_emails'] ?? 'true'),
            FILTER_VALIDATE_BOOL
        ),
    ],
    'inventory' => [
        'low_stock_threshold' => 5,
    ],
];

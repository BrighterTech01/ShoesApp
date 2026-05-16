<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'FIt Me In Sho Esia',
        'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost/shoesS',
        'backend_url' => getenv('BACKEND_URL') ?: 'http://localhost/shoesS/backend',
        'uploads_url' => getenv('UPLOADS_URL') ?: 'http://localhost/shoesS/backend/uploads',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'shoesap',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: 'Barrientos90',
        'charset' => 'utf8mb4',
        'auto_create_database' => getenv('DB_AUTO_CREATE') !== '0',
    ],
    'auth' => [
        'default_admin_name' => 'System Admin',
        'default_admin_email' => 'admin@shoes.local',
        'default_seller_name' => 'Default Seller',
        'default_seller_email' => 'seller@shoes.local',
        'default_customer_name' => 'Sample Customer',
        'default_customer_email' => 'customer@shoes.local',
        'default_password' => 'admin12345',
    ],
    'inventory' => [
        'low_stock_threshold' => 5,
    ],
];

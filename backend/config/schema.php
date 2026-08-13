<?php
declare(strict_types=1);

function database_engine(): string
{
    $configuredEngine = strtoupper((string) app_config('db.storage_engine', ''));
    if ($configuredEngine !== '') {
        return $configuredEngine;
    }

    $host = strtolower((string) app_config('db.host', ''));
    if (str_contains($host, 'infinityfree')) {
        return 'MYISAM';
    }

    return 'INNODB';
}

function database_supports_foreign_keys(): bool
{
    $configured = app_config('db.support_foreign_keys');
    if (is_bool($configured)) {
        return $configured;
    }

    return database_engine() === 'INNODB';
}

function schema_table_suffix(): string
{
    return 'ENGINE=' . database_engine() . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
}

function optional_constraints(array $constraints): string
{
    if (!database_supports_foreign_keys() || $constraints === []) {
        return '';
    }

    return ",\n            " . implode(",\n            ", $constraints);
}

function database_schema_statements(): array
{
    $suffix = schema_table_suffix();

    return [
        "CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(120) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'customer',
            phone VARCHAR(30) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            province VARCHAR(100) DEFAULT NULL,
            zip_code VARCHAR(20) DEFAULT NULL,
            email_verified_at TIMESTAMP NULL DEFAULT NULL,
            email_verification_token_hash VARCHAR(64) DEFAULT NULL,
            email_verification_expires_at TIMESTAMP NULL DEFAULT NULL,
            password_reset_token_hash VARCHAR(64) DEFAULT NULL,
            password_reset_expires_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS categories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS brands (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS products (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id BIGINT UNSIGNED DEFAULT NULL,
            brand_id BIGINT UNSIGNED DEFAULT NULL,
            seller_id BIGINT UNSIGNED DEFAULT NULL,
            name VARCHAR(180) NOT NULL,
            description TEXT DEFAULT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            image VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            featured TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" .
            optional_constraints([
                'CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL',
                'CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL',
                'CONSTRAINT fk_products_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL',
            ]) . "
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS product_variants (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            size VARCHAR(30) NOT NULL,
            color VARCHAR(60) NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_product_variant (product_id, size, color)" .
            optional_constraints([
                'CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE',
            ]) . "
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS carts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" .
            optional_constraints([
                'CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
            ]) . "
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS cart_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cart_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_cart_variant (cart_id, variant_id)" .
            optional_constraints([
                'CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE',
                'CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE',
                'CONSTRAINT fk_cart_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE',
            ]) . "
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            order_number VARCHAR(40) NOT NULL UNIQUE,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_method VARCHAR(30) NOT NULL DEFAULT 'COD',
            payment_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
            order_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
            full_name VARCHAR(150) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            province VARCHAR(100) NOT NULL,
            zip_code VARCHAR(20) DEFAULT NULL,
            landmark VARCHAR(150) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            shipping_location VARCHAR(190) DEFAULT NULL,
            shipping_location_updated_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" .
            optional_constraints([
                'CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
            ]) . "
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS order_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED DEFAULT NULL,
            variant_id BIGINT UNSIGNED DEFAULT NULL,
            product_name VARCHAR(180) NOT NULL,
            variant_label VARCHAR(120) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" .
            optional_constraints([
                'CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE',
                'CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL',
                'CONSTRAINT fk_order_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL',
            ]) . "
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS order_feedback (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            feedback TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_order_feedback (order_id),
            KEY idx_order_feedback_user (user_id)" .
            optional_constraints([
                'CONSTRAINT fk_order_feedback_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE',
                'CONSTRAINT fk_order_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
            ]) . "
        ) {$suffix}",
        "CREATE TABLE IF NOT EXISTS stock_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            change_type VARCHAR(30) NOT NULL,
            quantity_before INT NOT NULL DEFAULT 0,
            quantity_change INT NOT NULL DEFAULT 0,
            quantity_after INT NOT NULL DEFAULT 0,
            notes VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" .
            optional_constraints([
                'CONSTRAINT fk_stock_history_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE',
                'CONSTRAINT fk_stock_history_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE',
                'CONSTRAINT fk_stock_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL',
            ]) . "
        ) {$suffix}",
    ];
}

function schema_column_definitions(): array
{
    return [
        'users' => [
            'email_verified_at' => 'email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER zip_code',
            'email_verification_token_hash' => 'email_verification_token_hash VARCHAR(64) DEFAULT NULL AFTER email_verified_at',
            'email_verification_expires_at' => 'email_verification_expires_at TIMESTAMP NULL DEFAULT NULL AFTER email_verification_token_hash',
            'password_reset_token_hash' => 'password_reset_token_hash VARCHAR(64) DEFAULT NULL AFTER email_verification_expires_at',
            'password_reset_expires_at' => 'password_reset_expires_at TIMESTAMP NULL DEFAULT NULL AFTER password_reset_token_hash',
        ],
        'orders' => [
            'shipping_location' => 'shipping_location VARCHAR(190) DEFAULT NULL AFTER notes',
            'shipping_location_updated_at' => 'shipping_location_updated_at TIMESTAMP NULL DEFAULT NULL AFTER shipping_location',
        ],
    ];
}

function seed_default_data(PDO $pdo): void
{
    $defaultPassword = password_hash((string) app_config('auth.default_password', 'admin12345'), PASSWORD_DEFAULT);
    $users = [
        [
            'name' => (string) app_config('auth.default_admin_name', 'System Admin'),
            'email' => (string) app_config('auth.default_admin_email', 'admin@shoes.local'),
            'role' => 'admin',
        ],
        [
            'name' => (string) app_config('auth.default_seller_name', 'Default Seller'),
            'email' => (string) app_config('auth.default_seller_email', 'seller@shoes.local'),
            'role' => 'seller',
        ],
        [
            'name' => (string) app_config('auth.default_customer_name', 'Sample Customer'),
            'email' => (string) app_config('auth.default_customer_email', 'customer@shoes.local'),
            'role' => 'customer',
        ],
    ];

    $statement = $pdo->prepare(
        'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)
         ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role)'
    );

    foreach ($users as $user) {
        $statement->execute([
            ':name' => $user['name'],
            ':email' => $user['email'],
            ':password' => $defaultPassword,
            ':role' => $user['role'],
        ]);
    }
}

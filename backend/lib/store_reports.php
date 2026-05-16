<?php
declare(strict_types=1);

function table_exists(string $table): bool
{
    static $cache = [];

    $key = strtolower(trim($table));
    if ($key === '') {
        return false;
    }

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $row = fetch_one_row(
            'SELECT COUNT(*) AS total
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name',
            [':table_name' => $table]
        );
        $cache[$key] = $row !== null && (int) ($row['total'] ?? 0) > 0;
    } catch (Throwable $throwable) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function safe_scalar_query(string $sql, array $params, string $field, int|float $fallback = 0): int|float
{
    try {
        $row = fetch_one_row($sql, $params);
        if ($row === null || !array_key_exists($field, $row)) {
            return $fallback;
        }

        return is_float($fallback) ? (float) $row[$field] : (int) $row[$field];
    } catch (Throwable $throwable) {
        return $fallback;
    }
}

function get_dashboard_metrics(): array
{
    $hasProducts = table_exists('products');
    $hasOrders = table_exists('orders');
    $hasUsers = table_exists('users');
    $hasVariants = table_exists('product_variants');
    $hasCategories = table_exists('categories');
    $hasBrands = table_exists('brands');

    return [
        'total_products' => $hasProducts
            ? safe_scalar_query('SELECT COUNT(*) AS total FROM products', [], 'total', 0)
            : 0,
        'active_products' => $hasProducts
            ? safe_scalar_query("SELECT COUNT(*) AS total FROM products WHERE status = 'active'", [], 'total', 0)
            : 0,
        'total_orders' => $hasOrders
            ? safe_scalar_query('SELECT COUNT(*) AS total FROM orders', [], 'total', 0)
            : 0,
        'pending_orders' => $hasOrders
            ? safe_scalar_query("SELECT COUNT(*) AS total FROM orders WHERE order_status = 'Pending'", [], 'total', 0)
            : 0,
        'total_customers' => $hasUsers
            ? safe_scalar_query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'", [], 'total', 0)
            : 0,
        'total_categories' => $hasCategories
            ? safe_scalar_query('SELECT COUNT(*) AS total FROM categories', [], 'total', 0)
            : 0,
        'total_brands' => $hasBrands
            ? safe_scalar_query('SELECT COUNT(*) AS total FROM brands', [], 'total', 0)
            : 0,
        'total_stock' => $hasVariants
            ? safe_scalar_query('SELECT COALESCE(SUM(stock), 0) AS total FROM product_variants', [], 'total', 0)
            : 0,
        'total_sales' => $hasOrders
            ? safe_scalar_query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE order_status <> 'Cancelled'", [], 'total', 0.0)
            : 0.0,
        'low_stock_items' => $hasVariants
            ? safe_scalar_query(
                'SELECT COUNT(*) AS total FROM product_variants WHERE stock <= :threshold',
                [':threshold' => (int) app_config('inventory.low_stock_threshold', 5)],
                'total',
                0
            )
            : 0,
        'pending_deliveries' => $hasOrders
            ? safe_scalar_query("SELECT COUNT(*) AS total FROM orders WHERE order_status IN ('Pending', 'Confirmed', 'Shipped')", [], 'total', 0)
            : 0,
        'latest_orders' => ($hasOrders && $hasUsers && table_exists('order_items')) ? list_orders() : [],
        'low_stock_list' => ($hasVariants && $hasProducts) ? array_slice(list_inventory(), 0, 10) : [],
    ];
}

function get_sales_report(): array
{
    return fetch_all_rows(
        "SELECT
            DATE(created_at) AS order_date,
            COUNT(*) AS total_orders,
            SUM(total_amount) AS total_sales
         FROM orders
         WHERE order_status <> 'Cancelled'
         GROUP BY DATE(created_at)
         ORDER BY order_date DESC"
    );
}

function get_inventory_report(): array
{
    return list_inventory();
}

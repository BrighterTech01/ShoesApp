<?php
declare(strict_types=1);

function get_dashboard_metrics(): array
{
    return [
        'total_products' => (int) (fetch_one_row('SELECT COUNT(*) AS total FROM products')['total'] ?? 0),
        'total_orders' => (int) (fetch_one_row('SELECT COUNT(*) AS total FROM orders')['total'] ?? 0),
        'total_customers' => (int) (fetch_one_row("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'")['total'] ?? 0),
        'total_sales' => (float) (fetch_one_row("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE order_status <> 'Cancelled'")['total'] ?? 0),
        'low_stock_items' => (int) (fetch_one_row(
            'SELECT COUNT(*) AS total FROM product_variants WHERE stock <= :threshold',
            [':threshold' => (int) app_config('inventory.low_stock_threshold', 5)]
        )['total'] ?? 0),
        'pending_deliveries' => (int) (fetch_one_row("SELECT COUNT(*) AS total FROM orders WHERE order_status IN ('Pending', 'Confirmed', 'Shipped')")['total'] ?? 0),
        'latest_orders' => list_orders(),
        'low_stock_list' => array_slice(list_inventory(), 0, 10),
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

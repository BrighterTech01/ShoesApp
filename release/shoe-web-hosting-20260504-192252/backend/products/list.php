<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    $products = list_products([
        'search' => query_param('search', ''),
        'category_id' => query_param('category_id', 0),
        'brand_id' => query_param('brand_id', 0),
        'size' => query_param('size', ''),
        'active_only' => clean_bool(query_param('active_only', true)),
        'featured_only' => clean_bool(query_param('featured_only', false)),
    ]);

    success_response($products, 'Products loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

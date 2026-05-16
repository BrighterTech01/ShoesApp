<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    $id = clean_int(query_param('id', 0));
    if ($id <= 0) {
        error_response('Product ID is required.', 422);
    }

    $product = get_product($id);
    if ($product === null) {
        error_response('Product not found.', 404);
    }

    success_response($product, 'Product loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

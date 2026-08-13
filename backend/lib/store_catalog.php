<?php
declare(strict_types=1);

function list_categories(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM categories';
    $params = [];

    if ($activeOnly) {
        $sql .= ' WHERE status = :status';
        $params[':status'] = 'active';
    }

    $sql .= ' ORDER BY name ASC';

    return fetch_all_rows($sql, $params);
}

function get_category(int $id): ?array
{
    return fetch_one_row('SELECT * FROM categories WHERE id = :id', [':id' => $id]);
}

function save_category(array $data): int
{
    $id = clean_int($data['id'] ?? 0);
    $name = clean_string($data['name'] ?? '');

    if ($name === '') {
        throw new InvalidArgumentException('Category name is required.');
    }

    $params = [
        ':name' => $name,
        ':description' => clean_string($data['description'] ?? ''),
        ':status' => strtolower(clean_string($data['status'] ?? 'active', 'active')),
    ];

    if ($id > 0) {
        $params[':id'] = $id;
        execute_sql(
            'UPDATE categories SET name = :name, description = :description, status = :status WHERE id = :id',
            $params
        );
        return $id;
    }

    execute_sql(
        'INSERT INTO categories (name, description, status) VALUES (:name, :description, :status)',
        $params
    );

    return (int) db()->lastInsertId();
}

function delete_category(int $id): void
{
    execute_sql('DELETE FROM categories WHERE id = :id', [':id' => $id]);
}

function list_brands(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM brands';
    $params = [];

    if ($activeOnly) {
        $sql .= ' WHERE status = :status';
        $params[':status'] = 'active';
    }

    $sql .= ' ORDER BY name ASC';

    return fetch_all_rows($sql, $params);
}

function get_brand(int $id): ?array
{
    return fetch_one_row('SELECT * FROM brands WHERE id = :id', [':id' => $id]);
}

function save_brand(array $data): int
{
    $id = clean_int($data['id'] ?? 0);
    $name = clean_string($data['name'] ?? '');

    if ($name === '') {
        throw new InvalidArgumentException('Brand name is required.');
    }

    $params = [
        ':name' => $name,
        ':description' => clean_string($data['description'] ?? ''),
        ':status' => strtolower(clean_string($data['status'] ?? 'active', 'active')),
    ];

    if ($id > 0) {
        $params[':id'] = $id;
        execute_sql(
            'UPDATE brands SET name = :name, description = :description, status = :status WHERE id = :id',
            $params
        );
        return $id;
    }

    execute_sql(
        'INSERT INTO brands (name, description, status) VALUES (:name, :description, :status)',
        $params
    );

    return (int) db()->lastInsertId();
}

function delete_brand(int $id): void
{
    execute_sql('DELETE FROM brands WHERE id = :id', [':id' => $id]);
}

function product_review_summary_sql(): string
{
    return "
        SELECT
            product_reviews.product_id,
            AVG(product_reviews.rating) AS average_rating,
            COUNT(*) AS rating_count
        FROM (
            SELECT DISTINCT
                oi.product_id,
                ofb.order_id,
                ofb.rating
            FROM order_feedback ofb
            INNER JOIN order_items oi ON oi.order_id = ofb.order_id
        ) product_reviews
        GROUP BY product_reviews.product_id
    ";
}

function get_product_review_summary(int $productId): array
{
    $summary = fetch_one_row(
        'SELECT
            AVG(product_reviews.rating) AS average_rating,
            COUNT(*) AS rating_count
         FROM (
            SELECT DISTINCT
                ofb.order_id,
                ofb.rating
            FROM order_feedback ofb
            INNER JOIN order_items oi ON oi.order_id = ofb.order_id
            WHERE oi.product_id = :product_id
         ) product_reviews',
        [':product_id' => $productId]
    );

    return [
        'average_rating' => $summary !== null ? round((float) ($summary['average_rating'] ?? 0), 1) : 0.0,
        'rating_count' => $summary !== null ? (int) ($summary['rating_count'] ?? 0) : 0,
    ];
}

function list_product_reviews(int $productId, int $limit = 20): array
{
    $limit = max(1, min($limit, 50));

    return fetch_all_rows(
        "SELECT
            ofb.id,
            ofb.rating,
            ofb.feedback,
            ofb.created_at,
            o.order_number,
            u.name AS customer_name,
            GROUP_CONCAT(CONCAT(oi.product_name, ' - ', oi.variant_label, ' x', oi.quantity) SEPARATOR ', ') AS item_summary
         FROM order_feedback ofb
         INNER JOIN orders o ON o.id = ofb.order_id
         INNER JOIN users u ON u.id = ofb.user_id
         INNER JOIN order_items oi ON oi.order_id = ofb.order_id AND oi.product_id = :product_id
         GROUP BY
            ofb.id,
            ofb.rating,
            ofb.feedback,
            ofb.created_at,
            o.order_number,
            u.name
         ORDER BY ofb.created_at DESC
         LIMIT {$limit}",
        [':product_id' => $productId]
    );
}

function list_products(array $filters = []): array
{
    $where = [];
    $params = [];

    if (($filters['active_only'] ?? false) === true) {
        $where[] = 'p.status = :active_status';
        $params[':active_status'] = 'active';
    }

    if (($filters['featured_only'] ?? false) === true) {
        $where[] = 'p.featured = 1';
    }

    $search = clean_string($filters['search'] ?? '');
    if ($search !== '') {
        $where[] = '(
            p.name LIKE :search_name
            OR p.description LIKE :search_description
            OR c.name LIKE :search_category
            OR b.name LIKE :search_brand
        )';
        $searchTerm = '%' . $search . '%';
        $params[':search_name'] = $searchTerm;
        $params[':search_description'] = $searchTerm;
        $params[':search_category'] = $searchTerm;
        $params[':search_brand'] = $searchTerm;
    }

    $categoryId = clean_int($filters['category_id'] ?? 0);
    if ($categoryId > 0) {
        $where[] = 'p.category_id = :category_id';
        $params[':category_id'] = $categoryId;
    }

    $brandId = clean_int($filters['brand_id'] ?? 0);
    if ($brandId > 0) {
        $where[] = 'p.brand_id = :brand_id';
        $params[':brand_id'] = $brandId;
    }

    $size = clean_string($filters['size'] ?? '');
    if ($size !== '') {
        $where[] = 'EXISTS (
            SELECT 1
            FROM product_variants pv2
            WHERE pv2.product_id = p.id AND pv2.size = :size AND pv2.stock > 0
        )';
        $params[':size'] = $size;
    }

    $sql = "
        SELECT
            p.*,
            c.name AS category_name,
            b.name AS brand_name,
            COALESCE(stock_summary.total_stock, 0) AS total_stock,
            COALESCE(stock_summary.variant_count, 0) AS variant_count,
            COALESCE(review_summary.average_rating, 0) AS average_rating,
            COALESCE(review_summary.rating_count, 0) AS rating_count
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN brands b ON b.id = p.brand_id
        LEFT JOIN (
            SELECT
                product_id,
                COALESCE(SUM(stock), 0) AS total_stock,
                COUNT(id) AS variant_count
            FROM product_variants
            GROUP BY product_id
        ) stock_summary ON stock_summary.product_id = p.id
        LEFT JOIN (" . product_review_summary_sql() . ") review_summary ON review_summary.product_id = p.id
    ";

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY p.featured DESC, p.created_at DESC';

    $products = fetch_all_rows($sql, $params);
    foreach ($products as &$product) {
        $product['image_url'] = public_image_url($product['image'] ?? null);
        $product['average_rating'] = round((float) ($product['average_rating'] ?? 0), 1);
        $product['rating_count'] = (int) ($product['rating_count'] ?? 0);
    }
    unset($product);

    return $products;
}

function get_product(int $id): ?array
{
    $product = fetch_one_row(
        "SELECT
            p.*,
            c.name AS category_name,
            b.name AS brand_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN brands b ON b.id = p.brand_id
         WHERE p.id = :id",
        [':id' => $id]
    );

    if ($product === null) {
        return null;
    }

    $product['variants'] = list_variants_by_product($id);
    $product['image_url'] = public_image_url($product['image'] ?? null);
    $reviewSummary = get_product_review_summary($id);
    $product['average_rating'] = $reviewSummary['average_rating'];
    $product['rating_count'] = $reviewSummary['rating_count'];
    $product['reviews'] = list_product_reviews($id);

    return $product;
}

function save_product(array $data): int
{
    $id = clean_int($data['id'] ?? 0);
    $name = clean_string($data['name'] ?? '');

    if ($name === '') {
        throw new InvalidArgumentException('Product name is required.');
    }

    $image = clean_string($data['image'] ?? '');
    if ($id > 0 && $image === '') {
        $existing = get_product($id);
        $image = $existing['image'] ?? '';
    }

    $categoryId = clean_int($data['category_id'] ?? 0);
    $brandId = clean_int($data['brand_id'] ?? 0);
    $sellerId = clean_int($data['seller_id'] ?? current_user_id() ?? 0);

    $params = [
        ':category_id' => $categoryId > 0 ? $categoryId : null,
        ':brand_id' => $brandId > 0 ? $brandId : null,
        ':seller_id' => $sellerId > 0 ? $sellerId : null,
        ':name' => $name,
        ':description' => clean_string($data['description'] ?? ''),
        ':price' => clean_float($data['price'] ?? 0),
        ':image' => $image,
        ':status' => strtolower(clean_string($data['status'] ?? 'active', 'active')),
        ':featured' => clean_bool($data['featured'] ?? false) ? 1 : 0,
    ];

    if ($id > 0) {
        $params[':id'] = $id;
        execute_sql(
            'UPDATE products
             SET category_id = :category_id,
                 brand_id = :brand_id,
                 seller_id = :seller_id,
                 name = :name,
                 description = :description,
                 price = :price,
                 image = :image,
                 status = :status,
                 featured = :featured
             WHERE id = :id',
            $params
        );
        return $id;
    }

    execute_sql(
        'INSERT INTO products
            (category_id, brand_id, seller_id, name, description, price, image, status, featured)
         VALUES
            (:category_id, :brand_id, :seller_id, :name, :description, :price, :image, :status, :featured)',
        $params
    );

    return (int) db()->lastInsertId();
}

function delete_product(int $id): void
{
    execute_sql('DELETE FROM products WHERE id = :id', [':id' => $id]);
}

function list_variants_by_product(int $productId): array
{
    return fetch_all_rows(
        'SELECT * FROM product_variants WHERE product_id = :product_id ORDER BY size ASC, color ASC',
        [':product_id' => $productId]
    );
}

function get_variant(int $id): ?array
{
    return fetch_one_row(
        'SELECT pv.*, p.name AS product_name, p.price, p.image
         FROM product_variants pv
         INNER JOIN products p ON p.id = pv.product_id
         WHERE pv.id = :id',
        [':id' => $id]
    );
}

function save_variant(array $data): int
{
    $id = clean_int($data['id'] ?? 0);
    $productId = clean_int($data['product_id'] ?? 0);
    $size = clean_string($data['size'] ?? '');
    $color = clean_string($data['color'] ?? '');
    $stock = clean_int($data['stock'] ?? 0);

    if ($productId <= 0 || $size === '' || $color === '') {
        throw new InvalidArgumentException('Product, size, and color are required.');
    }

    $beforeStock = 0;
    if ($id > 0) {
        $existing = get_variant($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Variant not found.');
        }

        $beforeStock = (int) $existing['stock'];
        execute_sql(
            'UPDATE product_variants
             SET size = :size, color = :color, stock = :stock, status = :status
             WHERE id = :id',
            [
                ':id' => $id,
                ':size' => $size,
                ':color' => $color,
                ':stock' => $stock,
                ':status' => strtolower(clean_string($data['status'] ?? 'active', 'active')),
            ]
        );
    } else {
        execute_sql(
            'INSERT INTO product_variants (product_id, size, color, stock, status)
             VALUES (:product_id, :size, :color, :stock, :status)',
            [
                ':product_id' => $productId,
                ':size' => $size,
                ':color' => $color,
                ':stock' => $stock,
                ':status' => strtolower(clean_string($data['status'] ?? 'active', 'active')),
            ]
        );
        $id = (int) db()->lastInsertId();
    }

    execute_sql(
        'INSERT INTO stock_history
            (product_id, variant_id, user_id, change_type, quantity_before, quantity_change, quantity_after, notes)
         VALUES
            (:product_id, :variant_id, :user_id, :change_type, :quantity_before, :quantity_change, :quantity_after, :notes)',
        [
            ':product_id' => $productId,
            ':variant_id' => $id,
            ':user_id' => current_user_id(),
            ':change_type' => 'adjustment',
            ':quantity_before' => $beforeStock,
            ':quantity_change' => $stock - $beforeStock,
            ':quantity_after' => $stock,
            ':notes' => 'Manual stock update',
        ]
    );

    return $id;
}

function delete_variant(int $id): void
{
    execute_sql('DELETE FROM product_variants WHERE id = :id', [':id' => $id]);
}

function list_inventory(?int $lowStockThreshold = null): array
{
    $threshold = $lowStockThreshold ?? (int) app_config('inventory.low_stock_threshold', 5);

    return fetch_all_rows(
        "SELECT
            pv.id,
            pv.product_id,
            pv.size,
            pv.color,
            pv.stock,
            pv.status,
            p.name AS product_name,
            p.price,
            c.name AS category_name,
            b.name AS brand_name,
            CASE WHEN pv.stock <= :threshold THEN 1 ELSE 0 END AS is_low_stock
         FROM product_variants pv
         INNER JOIN products p ON p.id = pv.product_id
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN brands b ON b.id = p.brand_id
         ORDER BY is_low_stock DESC, pv.stock ASC, p.name ASC",
        [':threshold' => $threshold]
    );
}

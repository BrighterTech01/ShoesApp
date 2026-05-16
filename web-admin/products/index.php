<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

try {
    if (is_post() && clean_string($_POST['action'] ?? '') === 'delete') {
        delete_product(clean_int($_POST['id'] ?? 0));
        flash_set('success', 'Product deleted successfully.');
        redirect_to(admin_path('products/index.php'));
    }
} catch (Throwable $throwable) {
    flash_set('error', $throwable->getMessage());
    redirect_to(admin_path('products/index.php'));
}

$products = list_products([
    'search' => query_param('search', ''),
    'category_id' => query_param('category_id', 0),
    'brand_id' => query_param('brand_id', 0),
    'active_only' => false,
]);
$categories = list_categories();
$brands = list_brands();

admin_layout_start('Products', 'products');
?>
<section class="card">
    <form class="form-grid" method="get">
        <div class="field">
            <label for="search">Search</label>
            <input id="search" name="search" value="<?= htmlspecialchars((string) query_param('search', '')) ?>" placeholder="Nike, running, casual...">
        </div>
        <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="0">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= clean_int(query_param('category_id', 0)) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="brand_id">Brand</label>
            <select id="brand_id" name="brand_id">
                <option value="0">All brands</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= (int) $brand['id'] ?>" <?= clean_int(query_param('brand_id', 0)) === (int) $brand['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($brand['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="align-self: end;">
            <button type="submit">Filter Products</button>
        </div>
    </form>
</section>

<section class="card" style="margin-top: 22px;">
    <div class="page-header" style="margin-bottom: 10px;">
        <div>
            <p class="eyebrow">Catalog</p>
            <h2 style="font-size: 24px;">Manage products and stock variants</h2>
        </div>
        <a class="primary-btn" href="<?= htmlspecialchars(admin_path('products/form.php')) ?>">Create Product</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($products === []): ?>
                <?php table_empty_state('No products found.', 7); ?>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <img class="thumb" src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>"><br><br>
                            <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                            <small><?= htmlspecialchars((string) $product['description']) ?></small>
                        </td>
                        <td><?= htmlspecialchars((string) ($product['category_name'] ?? 'Uncategorized')) ?></td>
                        <td><?= htmlspecialchars((string) ($product['brand_name'] ?? 'Unbranded')) ?></td>
                        <td><?= htmlspecialchars(format_currency($product['price'])) ?></td>
                        <td>
                            <strong><?= (int) $product['total_stock'] ?></strong><br>
                            <small><?= (int) $product['variant_count'] ?> variants</small>
                        </td>
                        <td><?= status_badge($product['status']) ?></td>
                        <td>
                            <div class="inline-actions">
                                <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('products/form.php?id=' . $product['id'])) ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_layout_end(); ?>

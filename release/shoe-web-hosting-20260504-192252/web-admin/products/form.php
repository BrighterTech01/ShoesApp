<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

$productId = clean_int(query_param('id', 0));

try {
    if (is_post()) {
        $action = clean_string($_POST['action'] ?? 'save_product');

        if ($action === 'save_product') {
            $payload = $_POST;
            $payload['id'] = $productId;
            $uploadedImage = save_uploaded_image('image');
            if ($uploadedImage !== null) {
                $payload['image'] = $uploadedImage;
            }

            $savedId = save_product($payload);
            flash_set('success', 'Product saved successfully.');
            redirect_to(admin_path('products/form.php?id=' . $savedId));
        }

        if ($action === 'save_variant') {
            $payload = $_POST;
            $payload['product_id'] = $productId;
            save_variant($payload);
            flash_set('success', 'Variant saved successfully.');
            redirect_to(admin_path('products/form.php?id=' . $productId));
        }

        if ($action === 'delete_variant') {
            delete_variant(clean_int($_POST['variant_id'] ?? 0));
            flash_set('success', 'Variant deleted successfully.');
            redirect_to(admin_path('products/form.php?id=' . $productId));
        }
    }
} catch (Throwable $throwable) {
    flash_set('error', $throwable->getMessage());
    redirect_to(admin_path('products/form.php' . ($productId > 0 ? '?id=' . $productId : '')));
}

$product = $productId > 0 ? get_product($productId) : null;
$categories = list_categories();
$brands = list_brands();
$editingVariant = null;

if ($product !== null && clean_int(query_param('edit_variant', 0)) > 0) {
    foreach ($product['variants'] as $variant) {
        if ((int) $variant['id'] === clean_int(query_param('edit_variant', 0))) {
            $editingVariant = $variant;
            break;
        }
    }
}

admin_layout_start($product ? 'Edit Product' : 'Create Product', 'products');
?>
<section class="page-grid two-col">
    <article class="card">
        <p class="eyebrow"><?= $product ? 'Update Product' : 'New Product' ?></p>
        <h3><?= $product ? 'Edit product information' : 'Create your next catalog item' ?></h3>
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <input type="hidden" name="action" value="save_product">
            <div class="field full">
                <label for="name">Product name</label>
                <input id="name" name="name" required value="<?= htmlspecialchars((string) ($product['name'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="0">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="brand_id">Brand</label>
                <select id="brand_id" name="brand_id">
                    <option value="0">Select brand</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int) $brand['id'] ?>" <?= (int) ($product['brand_id'] ?? 0) === (int) $brand['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($brand['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="price">Price</label>
                <input id="price" name="price" type="number" step="0.01" min="0" required value="<?= htmlspecialchars((string) ($product['price'] ?? '0.00')) ?>">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?= (($product['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= (($product['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="field">
                <label for="featured">Featured</label>
                <select id="featured" name="featured">
                    <option value="0" <?= ((int) ($product['featured'] ?? 0) === 0) ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= ((int) ($product['featured'] ?? 0) === 1) ? 'selected' : '' ?>>Yes</option>
                </select>
            </div>
            <div class="field">
                <label for="image">Product image</label>
                <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.gif">
            </div>
            <div class="field full">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= htmlspecialchars((string) ($product['description'] ?? '')) ?></textarea>
            </div>
            <div class="field" style="align-self: end;">
                <button type="submit"><?= $product ? 'Update Product' : 'Save Product' ?></button>
            </div>
        </form>
    </article>

    <article class="card">
        <p class="eyebrow">Preview</p>
        <h3>Current product snapshot</h3>
        <img class="thumb" style="width: 100%; height: 220px; margin-bottom: 14px;" src="<?= htmlspecialchars($product['image_url'] ?? app_url('web-admin/assets/placeholder-shoe.svg')) ?>" alt="Product preview">
        <p><strong>Name:</strong> <?= htmlspecialchars((string) ($product['name'] ?? 'Not saved yet')) ?></p>
        <p><strong>Price:</strong> <?= htmlspecialchars(format_currency($product['price'] ?? 0)) ?></p>
        <p><strong>Status:</strong> <?= status_badge((string) ($product['status'] ?? 'draft')) ?></p>
        <p><strong>Variants:</strong> <?= count($product['variants'] ?? []) ?></p>
        <p class="muted" style="color: #6c625a;">Save the product first, then add its sizes, colors, and stock below.</p>
    </article>
</section>

<section class="page-grid two-col" style="margin-top: 22px;">
    <article class="card">
        <p class="eyebrow">Variant Manager</p>
        <h3><?= $editingVariant ? 'Update size and stock' : 'Add size and color variant' ?></h3>
        <?php if ($productId <= 0 || $product === null): ?>
            <p class="muted" style="color: #6c625a;">Create the product first before adding size and color combinations.</p>
        <?php else: ?>
            <form method="post" class="form-grid">
                <input type="hidden" name="action" value="save_variant">
                <input type="hidden" name="id" value="<?= (int) ($editingVariant['id'] ?? 0) ?>">
                <div class="field">
                    <label for="size">Size</label>
                    <input id="size" name="size" required value="<?= htmlspecialchars((string) ($editingVariant['size'] ?? '')) ?>" placeholder="39">
                </div>
                <div class="field">
                    <label for="color">Color</label>
                    <input id="color" name="color" required value="<?= htmlspecialchars((string) ($editingVariant['color'] ?? '')) ?>" placeholder="Black">
                </div>
                <div class="field">
                    <label for="stock">Stock</label>
                    <input id="stock" name="stock" type="number" min="0" required value="<?= htmlspecialchars((string) ($editingVariant['stock'] ?? '0')) ?>">
                </div>
                <div class="field">
                    <label for="variant_status">Status</label>
                    <select id="variant_status" name="status">
                        <option value="active" <?= (($editingVariant['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($editingVariant['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="field" style="align-self: end;">
                    <button type="submit"><?= $editingVariant ? 'Update Variant' : 'Save Variant' ?></button>
                </div>
            </form>
        <?php endif; ?>
    </article>

    <article class="card">
        <p class="eyebrow">Saved Variants</p>
        <h3>Sizes, colors, and current stock</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Variant</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (($product['variants'] ?? []) === []): ?>
                    <?php table_empty_state('No variants added yet.', 4); ?>
                <?php else: ?>
                    <?php foreach ($product['variants'] as $variant): ?>
                        <tr>
                            <td>Size <?= htmlspecialchars($variant['size']) ?> / <?= htmlspecialchars($variant['color']) ?></td>
                            <td><?= (int) $variant['stock'] ?></td>
                            <td><?= status_badge($variant['status']) ?></td>
                            <td>
                                <div class="inline-actions">
                                    <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('products/form.php?id=' . $productId . '&edit_variant=' . $variant['id'])) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this variant?');">
                                        <input type="hidden" name="action" value="delete_variant">
                                        <input type="hidden" name="variant_id" value="<?= (int) $variant['id'] ?>">
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
    </article>
</section>
<?php admin_layout_end(); ?>

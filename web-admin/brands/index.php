<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

try {
    if (is_post()) {
        $action = clean_string($_POST['action'] ?? 'save');

        if ($action === 'delete') {
            delete_brand(clean_int($_POST['id'] ?? 0));
            flash_set('success', 'Brand deleted successfully.');
        } else {
            save_brand($_POST);
            flash_set('success', 'Brand saved successfully.');
        }

        redirect_to(admin_path('brands/index.php'));
    }
} catch (Throwable $throwable) {
    flash_set('error', $throwable->getMessage());
    redirect_to(admin_path('brands/index.php'));
}

$editBrand = clean_int(query_param('edit', 0)) > 0 ? get_brand(clean_int(query_param('edit', 0))) : null;
$brands = list_brands();

admin_layout_start('Brands', 'brands');
?>
<section class="page-grid two-col">
    <article class="card">
        <p class="eyebrow"><?= $editBrand ? 'Edit Brand' : 'Add Brand' ?></p>
        <h3><?= $editBrand ? 'Update brand' : 'Create a new brand' ?></h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="id" value="<?= (int) ($editBrand['id'] ?? 0) ?>">
            <div class="field full">
                <label for="name">Brand name</label>
                <input id="name" name="name" required value="<?= htmlspecialchars((string) ($editBrand['name'] ?? '')) ?>">
            </div>
            <div class="field full">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= htmlspecialchars((string) ($editBrand['description'] ?? '')) ?></textarea>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?= (($editBrand['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= (($editBrand['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="field" style="align-self: end;">
                <button type="submit"><?= $editBrand ? 'Update Brand' : 'Save Brand' ?></button>
            </div>
        </form>
    </article>

    <article class="card">
        <p class="eyebrow">Brand List</p>
        <h3>Track all shoe labels</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($brands === []): ?>
                    <?php table_empty_state('No brands created yet.', 3); ?>
                <?php else: ?>
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($brand['name']) ?></strong><br>
                                <small><?= htmlspecialchars((string) $brand['description']) ?></small>
                            </td>
                            <td><?= status_badge($brand['status']) ?></td>
                            <td>
                                <div class="inline-actions">
                                    <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('brands/index.php?edit=' . $brand['id'])) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this brand?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $brand['id'] ?>">
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

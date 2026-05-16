<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

try {
    if (is_post()) {
        $action = clean_string($_POST['action'] ?? 'save');

        if ($action === 'delete') {
            delete_category(clean_int($_POST['id'] ?? 0));
            flash_set('success', 'Category deleted successfully.');
        } else {
            save_category($_POST);
            flash_set('success', 'Category saved successfully.');
        }

        redirect_to(admin_path('categories/index.php'));
    }
} catch (Throwable $throwable) {
    flash_set('error', $throwable->getMessage());
    redirect_to(admin_path('categories/index.php'));
}

$editCategory = clean_int(query_param('edit', 0)) > 0 ? get_category(clean_int(query_param('edit', 0))) : null;
$categories = list_categories();

admin_layout_start('Categories', 'categories');
?>
<section class="page-grid two-col">
    <article class="card">
        <p class="eyebrow"><?= $editCategory ? 'Edit Category' : 'Add Category' ?></p>
        <h3><?= $editCategory ? 'Update category' : 'Create a new category' ?></h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="id" value="<?= (int) ($editCategory['id'] ?? 0) ?>">
            <div class="field full">
                <label for="name">Category name</label>
                <input id="name" name="name" required value="<?= htmlspecialchars((string) ($editCategory['name'] ?? '')) ?>">
            </div>
            <div class="field full">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= htmlspecialchars((string) ($editCategory['description'] ?? '')) ?></textarea>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?= (($editCategory['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= (($editCategory['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="field" style="align-self: end;">
                <button type="submit"><?= $editCategory ? 'Update Category' : 'Save Category' ?></button>
            </div>
        </form>
    </article>

    <article class="card">
        <p class="eyebrow">Category List</p>
        <h3>Organize your catalog</h3>
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
                <?php if ($categories === []): ?>
                    <?php table_empty_state('No categories created yet.', 3); ?>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($category['name']) ?></strong><br>
                                <small><?= htmlspecialchars((string) $category['description']) ?></small>
                            </td>
                            <td><?= status_badge($category['status']) ?></td>
                            <td>
                                <div class="inline-actions">
                                    <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('categories/index.php?edit=' . $category['id'])) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this category?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
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

<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

$inventory = list_inventory();

admin_layout_start('Inventory', 'inventory');
?>
<section class="card">
    <div class="page-header" style="margin-bottom: 10px;">
        <div>
            <p class="eyebrow">Stock Monitor</p>
            <h2 style="font-size: 24px;">Track sizes, colors, and low stock</h2>
        </div>
        <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('products/index.php')) ?>">Back to Products</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Product</th>
                <th>Brand</th>
                <th>Variant</th>
                <th>Stock</th>
                <th>Price</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($inventory === []): ?>
                <?php table_empty_state('Inventory is empty.', 6); ?>
            <?php else: ?>
                <?php foreach ($inventory as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= htmlspecialchars((string) ($item['brand_name'] ?? 'Unbranded')) ?></td>
                        <td>Size <?= htmlspecialchars($item['size']) ?> / <?= htmlspecialchars($item['color']) ?></td>
                        <td><?= status_badge((string) $item['stock']) ?></td>
                        <td><?= htmlspecialchars(format_currency($item['price'])) ?></td>
                        <td><?= status_badge($item['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_layout_end(); ?>

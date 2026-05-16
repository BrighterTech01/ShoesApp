<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

$sales = get_sales_report();
$inventory = array_slice(get_inventory_report(), 0, 12);

admin_layout_start('Reports', 'reports');
?>
<section class="page-grid two-col">
    <article class="card">
        <p class="eyebrow">Sales Report</p>
        <h3>Daily order totals</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Orders</th>
                    <th>Total Sales</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($sales === []): ?>
                    <?php table_empty_state('No sales report data yet.', 3); ?>
                <?php else: ?>
                    <?php foreach ($sales as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $row['order_date']) ?></td>
                            <td><?= (int) $row['total_orders'] ?></td>
                            <td><?= htmlspecialchars(format_currency($row['total_sales'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <p class="eyebrow">Inventory Report</p>
        <h3>Lowest stock variants</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>Stock</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($inventory === []): ?>
                    <?php table_empty_state('No inventory data yet.', 3); ?>
                <?php else: ?>
                    <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td>Size <?= htmlspecialchars($item['size']) ?> / <?= htmlspecialchars($item['color']) ?></td>
                            <td><?= status_badge((string) $item['stock']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php admin_layout_end(); ?>

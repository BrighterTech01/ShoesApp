<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/layout.php';

require_admin_panel();

$metrics = get_dashboard_metrics();
$latestOrders = array_slice($metrics['latest_orders'], 0, 6);
$lowStockList = $metrics['low_stock_list'];

admin_layout_start('Dashboard', 'dashboard');
?>
<section class="grid-stats">
    <article class="metric-card">
        <p class="eyebrow">Products</p>
        <strong><?= (int) $metrics['total_products'] ?></strong>
        <span>Items currently listed</span>
    </article>
    <article class="metric-card">
        <p class="eyebrow">Orders</p>
        <strong><?= (int) $metrics['total_orders'] ?></strong>
        <span>All recorded customer orders</span>
    </article>
    <article class="metric-card">
        <p class="eyebrow">Sales</p>
        <strong><?= htmlspecialchars(format_currency($metrics['total_sales'])) ?></strong>
        <span>Completed and active order value</span>
    </article>
    <article class="metric-card">
        <p class="eyebrow">Customers</p>
        <strong><?= (int) $metrics['total_customers'] ?></strong>
        <span>Registered customer accounts</span>
    </article>
    <article class="metric-card">
        <p class="eyebrow">Low Stock</p>
        <strong><?= (int) $metrics['low_stock_items'] ?></strong>
        <span>Variants at or below threshold</span>
    </article>
    <article class="metric-card">
        <p class="eyebrow">Pending Delivery</p>
        <strong><?= (int) $metrics['pending_deliveries'] ?></strong>
        <span>Orders still in the pipeline</span>
    </article>
</section>

<section class="page-grid two-col" style="margin-top: 22px;">
    <article class="card">
        <div class="page-header" style="margin-bottom: 10px;">
            <div>
                <p class="eyebrow">Recent Orders</p>
                <h2 style="font-size: 24px;">Latest activity</h2>
            </div>
            <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('orders/index.php')) ?>">Manage Orders</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($latestOrders === []): ?>
                    <?php table_empty_state('No orders yet.', 4); ?>
                <?php else: ?>
                    <?php foreach ($latestOrders as $order): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($order['order_number']) ?></strong><br>
                                <small><?= htmlspecialchars((string) $order['created_at']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= status_badge($order['order_status']) ?></td>
                            <td><?= htmlspecialchars(format_currency($order['total_amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <div class="page-header" style="margin-bottom: 10px;">
            <div>
                <p class="eyebrow">Inventory Alerts</p>
                <h2 style="font-size: 24px;">Low stock variants</h2>
            </div>
            <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('inventory/index.php')) ?>">Open Inventory</a>
        </div>
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
                <?php if ($lowStockList === []): ?>
                    <?php table_empty_state('No low stock items right now.', 3); ?>
                <?php else: ?>
                    <?php foreach ($lowStockList as $item): ?>
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

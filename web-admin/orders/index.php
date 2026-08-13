<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

try {
    if (is_post()) {
        update_order_status(
            clean_int($_POST['id'] ?? 0),
            clean_string($_POST['order_status'] ?? 'Pending', 'Pending'),
            clean_string($_POST['payment_status'] ?? ''),
            array_key_exists('shipping_location', $_POST) ? clean_string($_POST['shipping_location'] ?? '') : null
        );
        flash_set('success', 'Order updated successfully.');
        redirect_to(admin_path('orders/index.php'));
    }
} catch (Throwable $throwable) {
    flash_set('error', $throwable->getMessage());
    redirect_to(admin_path('orders/index.php'));
}

$orders = list_orders();

admin_layout_start('Orders', 'orders');
?>
<section class="card">
    <div class="page-header" style="margin-bottom: 10px;">
        <div>
            <p class="eyebrow">Order Pipeline</p>
            <h2 style="font-size: 24px;">Process COD orders and deliveries</h2>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Rating</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($orders === []): ?>
                <?php table_empty_state('No orders found.', 8); ?>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($order['order_number']) ?></strong><br>
                            <small><?= htmlspecialchars((string) $order['created_at']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= (int) $order['item_count'] ?></td>
                        <td><?= htmlspecialchars(format_currency($order['total_amount'])) ?></td>
                        <td><?= status_badge($order['order_status']) ?></td>
                        <td>
                            <?php if (!empty($order['feedback_rating'])): ?>
                                <strong><?= (int) $order['feedback_rating'] ?>/5</strong>
                            <?php else: ?>
                                <small>No rating yet</small>
                            <?php endif; ?>
                        </td>
                        <td><?= status_badge($order['payment_status']) ?></td>
                        <td>
                            <?php $isCancellationRequested = $order['order_status'] === cancellation_requested_status(); ?>
                            <form method="post" class="form-grid">
                                <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                <div class="field">
                                    <select name="order_status">
                                        <?php foreach (['Pending', 'Confirmed', 'Shipped', 'Delivered', cancellation_requested_status(), 'Cancelled'] as $status): ?>
                                            <option value="<?= htmlspecialchars($status) ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <select name="payment_status">
                                        <?php foreach (['Pending', 'Paid', 'Cancelled'] as $paymentStatus): ?>
                                            <option value="<?= htmlspecialchars($paymentStatus) ?>" <?= $order['payment_status'] === $paymentStatus ? 'selected' : '' ?>><?= htmlspecialchars($paymentStatus) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field full">
                                    <input
                                        type="text"
                                        name="shipping_location"
                                        placeholder="Current shipping location or rider update"
                                        value="<?= htmlspecialchars((string) ($order['shipping_location'] ?? '')) ?>"
                                    >
                                </div>
                                <div class="inline-actions">
                                    <button type="submit">Update</button>
                                    <?php if ($isCancellationRequested): ?>
                                        <button type="submit" name="order_status" value="Cancelled">Confirm Cancel</button>
                                    <?php endif; ?>
                                    <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('orders/view.php?id=' . $order['id'])) ?>">View</a>
                                </div>
                                <?php if ($isCancellationRequested): ?>
                                    <div class="field" style="grid-column: 1 / -1;">
                                        <small style="color: #ad6800; font-weight: 700;">Customer requested cancellation. Confirm cancel to return the stock.</small>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_layout_end(); ?>

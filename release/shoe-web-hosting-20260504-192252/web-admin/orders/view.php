<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

$orderId = clean_int(query_param('id', 0));
$order = get_order($orderId);

if ($order === null) {
    flash_set('error', 'Order not found.');
    redirect_to(admin_path('orders/index.php'));
}

admin_layout_start('Order Details', 'orders');
?>
<section class="page-grid two-col">
    <article class="card">
        <p class="eyebrow">Customer Details</p>
        <h3><?= htmlspecialchars($order['order_number']) ?></h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($order['full_name']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
        <p><strong>City/Province:</strong> <?= htmlspecialchars($order['city'] . ', ' . $order['province']) ?></p>
        <p><strong>ZIP:</strong> <?= htmlspecialchars((string) $order['zip_code']) ?></p>
        <p><strong>Landmark:</strong> <?= htmlspecialchars((string) $order['landmark']) ?></p>
        <p><strong>Notes:</strong> <?= htmlspecialchars((string) $order['notes']) ?></p>
    </article>

    <article class="card">
        <p class="eyebrow">Order Summary</p>
        <h3>Fulfillment info</h3>
        <p><strong>Order status:</strong> <?= status_badge($order['order_status']) ?></p>
        <p><strong>Payment:</strong> <?= status_badge($order['payment_status']) ?></p>
        <p><strong>Payment method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
        <p><strong>Total amount:</strong> <?= htmlspecialchars(format_currency($order['total_amount'])) ?></p>
        <p><strong>Created at:</strong> <?= htmlspecialchars((string) $order['created_at']) ?></p>
        <a class="secondary-btn" href="<?= htmlspecialchars(admin_path('orders/index.php')) ?>">Back to Orders</a>
    </article>
</section>

<section class="card" style="margin-top: 22px;">
    <p class="eyebrow">Order Items</p>
    <h3>Purchased products</h3>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Product</th>
                <th>Variant</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= htmlspecialchars($item['variant_label']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td><?= htmlspecialchars(format_currency($item['price'])) ?></td>
                    <td><?= htmlspecialchars(format_currency($item['subtotal'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_layout_end(); ?>

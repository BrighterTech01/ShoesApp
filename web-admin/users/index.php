<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_panel();

try {
    if (is_post()) {
        create_user($_POST);
        flash_set('success', 'User account created successfully.');
        redirect_to(admin_path('users/index.php'));
    }
} catch (Throwable $throwable) {
    flash_set('error', $throwable->getMessage());
    redirect_to(admin_path('users/index.php'));
}

$users = list_users();

admin_layout_start('Users', 'users');
?>
<section class="page-grid two-col">
    <article class="card">
        <p class="eyebrow">Create Account</p>
        <h3>Add admin, seller, or customer</h3>
        <form method="post" class="form-grid">
            <div class="field">
                <label for="name">Full name</label>
                <input id="name" name="name" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="admin">Admin</option>
                    <option value="seller">Seller</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
            <div class="field">
                <label for="phone">Phone</label>
                <input id="phone" name="phone">
            </div>
            <div class="field">
                <label for="city">City</label>
                <input id="city" name="city">
            </div>
            <div class="field full">
                <label for="address">Address</label>
                <textarea id="address" name="address"></textarea>
            </div>
            <div class="field">
                <label for="province">Province</label>
                <input id="province" name="province">
            </div>
            <div class="field">
                <label for="zip_code">ZIP Code</label>
                <input id="zip_code" name="zip_code">
            </div>
            <div class="field" style="align-self: end;">
                <button type="submit">Create User</button>
            </div>
        </form>
    </article>

    <article class="card">
        <p class="eyebrow">User Directory</p>
        <h3>Current system users</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Contact</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($users === []): ?>
                    <?php table_empty_state('No users found.', 4); ?>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($user['name']) ?></strong><br>
                                <small><?= htmlspecialchars($user['email']) ?></small>
                            </td>
                            <td><?= status_badge($user['role']) ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($user['phone'] ?: 'No phone')) ?><br>
                                <small><?= htmlspecialchars(trim(($user['city'] ?? '') . ', ' . ($user['province'] ?? ''), ' ,')) ?></small>
                            </td>
                            <td><?= htmlspecialchars((string) $user['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php admin_layout_end(); ?>

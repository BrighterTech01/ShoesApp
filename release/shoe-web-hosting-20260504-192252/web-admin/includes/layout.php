<?php
declare(strict_types=1);

function admin_navigation_items(): array
{
    return [
        'dashboard' => ['label' => 'Dashboard', 'href' => admin_path('dashboard.php')],
        'products' => ['label' => 'Products', 'href' => admin_path('products/index.php')],
        'categories' => ['label' => 'Categories', 'href' => admin_path('categories/index.php')],
        'brands' => ['label' => 'Brands', 'href' => admin_path('brands/index.php')],
        'inventory' => ['label' => 'Inventory', 'href' => admin_path('inventory/index.php')],
        'orders' => ['label' => 'Orders', 'href' => admin_path('orders/index.php')],
        'users' => ['label' => 'Users', 'href' => admin_path('users/index.php')],
        'reports' => ['label' => 'Reports', 'href' => admin_path('reports/index.php')],
    ];
}

function render_flash_messages(): void
{
    $success = flash_get('success');
    $error = flash_get('error');

    if ($success !== null) {
        echo '<div class="alert success">' . htmlspecialchars($success) . '</div>';
    }

    if ($error !== null) {
        echo '<div class="alert error">' . htmlspecialchars($error) . '</div>';
    }
}

function admin_layout_start(string $title, string $active = 'dashboard'): void
{
    $user = admin_current_user();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> | FIt Me In Sho Esia Admin</title>
        <link rel="stylesheet" href="<?= htmlspecialchars(admin_path('assets/style.css')) ?>">
    </head>
    <body>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand-block">
                <p class="eyebrow">FIt Me In Sho Esia</p>
                <h1>Seller Console</h1>
                <p class="muted">Manage products, stock, orders, and COD fulfillment.</p>
            </div>
            <nav class="nav-list">
                <?php foreach (admin_navigation_items() as $key => $item): ?>
                    <a class="nav-link <?= $active === $key ? 'is-active' : '' ?>" href="<?= htmlspecialchars($item['href']) ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <p class="muted">Signed in as</p>
                <strong><?= htmlspecialchars($user['name'] ?? 'Guest') ?></strong>
                <span class="role-chip"><?= htmlspecialchars(strtoupper($user['role'] ?? 'guest')) ?></span>
                <a class="ghost-link" href="<?= htmlspecialchars(admin_path('logout.php')) ?>">Logout</a>
            </div>
        </aside>
        <main class="content">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Web Admin Panel</p>
                    <h2><?= htmlspecialchars($title) ?></h2>
                </div>
                <a class="primary-btn" href="<?= htmlspecialchars(admin_path('products/form.php')) ?>">Add Product</a>
            </header>
            <?php render_flash_messages(); ?>
    <?php
}

function admin_layout_end(): void
{
    ?>
        </main>
    </div>
    </body>
    </html>
    <?php
}

function status_badge(string $value): string
{
    $normalized = strtolower(trim($value));
    $class = match ($normalized) {
        'active', 'delivered', 'paid' => 'success',
        'pending', 'confirmed', 'shipped' => 'warning',
        'cancelled', 'inactive', 'out of stock' => 'danger',
        default => 'neutral',
    };

    return '<span class="status-badge ' . $class . '">' . htmlspecialchars($value) . '</span>';
}

function table_empty_state(string $message, int $colspan = 6): void
{
    echo '<tr><td colspan="' . $colspan . '" class="table-empty">' . htmlspecialchars($message) . '</td></tr>';
}

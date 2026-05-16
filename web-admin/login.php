<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (admin_current_user() !== null && in_array(admin_current_user()['role'], ['admin', 'seller'], true)) {
    redirect_to(admin_path('dashboard.php'));
}

if (is_post()) {
    try {
        $email = clean_string($_POST['email'] ?? '');
        $password = clean_string($_POST['password'] ?? '');
        $user = attempt_login($email, $password);

        if ($user === null || !in_array($user['role'], ['admin', 'seller'], true)) {
            logout_user();
            flash_set('error', 'Use an admin or seller account to access the panel.');
            redirect_to(admin_path('login.php'));
        }

        flash_set('success', 'Welcome back, ' . $user['name'] . '.');
        redirect_to(admin_path('dashboard.php'));
    } catch (Throwable $throwable) {
        flash_set('error', $throwable->getMessage());
        redirect_to(admin_path('login.php'));
    }
}

$success = flash_get('success');
$error = flash_get('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | FIt Me In Sho Esia</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(admin_path('assets/style.css')) ?>">
</head>
<body>
<div class="auth-shell">
    <div class="auth-card">
        <p class="eyebrow">Web Admin Panel</p>
        <h2>Welcome Back</h2>
        <p class="auth-card-copy">Sign in using an admin or seller account.</p>

        <?php if ($success !== null): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="admin@shoes.local">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required value="admin12345">
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</div>
</body>
</html>

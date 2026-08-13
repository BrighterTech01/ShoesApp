<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/lib/config.php';

$config = app_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) $config['app']['name']) ?></title>
    <style>
        body {
            margin: 0;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(139, 69, 19, 0.18), transparent 30%),
                linear-gradient(180deg, #fcf6ef 0%, #f0e2d1 100%);
            color: #25170f;
        }

        .shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px;
        }

        .card {
            width: min(900px, 100%);
            background: rgba(255, 253, 248, 0.96);
            border: 1px solid #e1d1bf;
            border-radius: 32px;
            box-shadow: 0 30px 60px rgba(80, 45, 20, 0.12);
            padding: 32px;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-size: 12px;
            color: #8b4513;
            font-weight: 700;
            margin: 0 0 10px;
        }

        h1 {
            font-family: Georgia, "Times New Roman", serif;
            margin: 0 0 14px;
            font-size: clamp(32px, 6vw, 54px);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .panel {
            background: #fffaf4;
            border: 1px solid #eadcc9;
            border-radius: 24px;
            padding: 20px;
        }

        .button {
            display: inline-block;
            margin-top: 12px;
            background: #8b4513;
            color: #fff7ef;
            padding: 12px 18px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
        }

        code {
            background: #f5ebdf;
            border-radius: 8px;
            padding: 2px 6px;
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="card">
        <p class="eyebrow">One System, Two Platforms</p>
        <h1><?= htmlspecialchars((string) $config['app']['name']) ?></h1>
        <p>This project includes a PHP admin website for sellers and admins, a PHP API backend, and a React Native customer shopping app.</p>

        <div class="grid">
            <section class="panel">
                <h2>Admin Website</h2>
                <p>Manage products, variants, stock, orders, users, and reports.</p>
                <a class="button" href="<?= htmlspecialchars(app_url('web-admin/login.php')) ?>">Open Admin Panel</a>
            </section>
            <section class="panel">
                <h2>Backend API</h2>
                <p>Shared endpoints for the web panel and the mobile shopping app.</p>
                <a class="button" href="<?= htmlspecialchars(app_url('backend/index.php')) ?>">Open API Info</a>
            </section>
            <section class="panel">
                <h2>Database Bootstrap</h2>
                <p>Use this after updating MySQL credentials to auto-create the database tables.</p>
                <a class="button" href="<?= htmlspecialchars(app_url('backend/setup.php')) ?>">Run Setup</a>
            </section>
            <section class="panel">
                <h2>React Native App</h2>
                <p>Customer app source is inside <code>mobile-app/</code>. Update the API base URL for real devices if needed.</p>
                <a class="button" href="<?= htmlspecialchars(app_url('README.md')) ?>">Open Setup Notes</a>
            </section>
        </div>

        <div class="grid">
            <section class="panel">
                <h2>Deployment Check</h2>
                <p>After uploading, open <code>backend/setup.php</code> once, then confirm <code>backend/health.php</code> returns JSON.</p>
            </section>
            <section class="panel">
                <h2>MySQL Setup</h2>
                <p>The database name is configured as <code><?= htmlspecialchars((string) $config['db']['name']) ?></code>.</p>
                <p>For online hosting, update <code>backend/config/app.php</code> with the hosting database host, username, and password before upload.</p>
            </section>
        </div>
    </div>
</div>
</body>
</html>

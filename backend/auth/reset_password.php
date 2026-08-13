<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

function reset_password_form(string $token, string $message = '', bool $success = false): never
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Reset Password</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f7f7f7;padding:32px;color:#111}.card{max-width:560px;margin:auto;background:#fff;border:1px solid #ddd;border-radius:18px;padding:28px}input,button{width:100%;box-sizing:border-box;padding:13px;margin-top:10px;border-radius:12px;border:1px solid #ccc}button{background:#111;color:#fff;font-weight:700}.msg{padding:12px;border-radius:12px;background:' . ($success ? '#e9f9ef' : '#fff4e5') . ';}</style>';
    echo '</head><body><div class="card"><h1>Reset Password</h1>';
    if ($message !== '') {
        echo '<p class="msg">' . htmlspecialchars($message) . '</p>';
    }
    if (!$success) {
        echo '<form method="post"><input type="hidden" name="token" value="' . htmlspecialchars($token) . '">';
        echo '<label>New Password</label><input type="password" name="password" required minlength="6">';
        echo '<label>Confirm Password</label><input type="password" name="confirm_password" required minlength="6">';
        echo '<button type="submit">Reset Password</button></form>';
    }
    echo '</div></body></html>';
    exit;
}

try {
    if (request_method() === 'GET') {
        reset_password_form(clean_string($_GET['token'] ?? ''));
    }

    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $data = request_data();
    $token = clean_string($data['token'] ?? '');
    $password = clean_string($data['password'] ?? '');
    $confirmPassword = clean_string($data['confirm_password'] ?? '');

    if ($token === '' || $password === '') {
        error_response('Token and password are required.', 422);
    }

    $user = reset_password_with_token($token, $password, $confirmPassword);
    if ($user === null) {
        if (($_POST ?? []) !== []) {
            reset_password_form($token, 'Reset link is invalid or expired.');
        }
        error_response('Reset link is invalid or expired.', 422);
    }

    if (($_POST ?? []) !== []) {
        reset_password_form('', 'Password reset successfully. You can now log in with your new password.', true);
    }

    success_response(user_payload($user), 'Password reset successfully.');
} catch (Throwable $throwable) {
    if (($_POST ?? []) !== []) {
        reset_password_form(clean_string($_POST['token'] ?? ''), $throwable->getMessage());
    }
    error_response($throwable->getMessage(), 422);
}

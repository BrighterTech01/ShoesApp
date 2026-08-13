<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

function email_verification_html(string $title, string $message): never
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f7f7f7;padding:32px;color:#111}.card{max-width:560px;margin:auto;background:#fff;border:1px solid #ddd;border-radius:18px;padding:28px}h1{margin-top:0}</style>';
    echo '</head><body><div class="card"><h1>' . htmlspecialchars($title) . '</h1><p>' . htmlspecialchars($message) . '</p></div></body></html>';
    exit;
}

try {
    $data = request_method() === 'POST' ? request_data() : $_GET;
    $token = clean_string($data['token'] ?? '');
    if ($token === '') {
        if (request_method() === 'POST') {
            error_response('Verification token is required.', 422);
        }
        email_verification_html('Verification failed', 'Verification token is required.');
    }

    $user = verify_user_email_token($token);
    if ($user === null) {
        if (request_method() === 'POST') {
            error_response('Verification link is invalid or expired.', 422);
        }
        email_verification_html('Verification failed', 'This verification link is invalid or expired.');
    }

    if (request_method() === 'POST') {
        success_response(user_payload($user), 'Email verified successfully.');
    }

    email_verification_html('Email verified', 'Your email has been verified successfully. You can now return to the mobile app.');
} catch (Throwable $throwable) {
    if (request_method() === 'POST') {
        error_response($throwable->getMessage(), 500);
    }
    email_verification_html('Verification failed', $throwable->getMessage());
}

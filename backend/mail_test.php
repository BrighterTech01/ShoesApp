<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    require_auth(['admin', 'seller'], true);

    $data = request_data();
    $user = current_user();
    $email = strtolower(clean_string($data['email'] ?? query_param('email', $user['email'] ?? '')));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_response('Please enter a valid test email address.', 422);
    }

    $subject = 'Fit Me In Sho Esia email test';
    $html = '<p>Hello,</p>'
        . '<p>This is a test email from the Fit Me In Sho Esia password reset mailer.</p>'
        . '<p>If you received this, forgot password emails can be sent from the server.</p>';
    $text = "Hello,\n\nThis is a test email from the Fit Me In Sho Esia password reset mailer.\n"
        . "If you received this, forgot password emails can be sent from the server.\n";

    if (!send_app_email($email, (string) ($user['name'] ?? 'Customer'), $subject, $html, $text)) {
        error_response(
            'Email test failed. Check storage/mail.log and backend/config/app.php mail settings.',
            500
        );
    }

    success_response([
        'email' => $email,
        'mail_log' => 'storage/mail.log',
    ], 'Test email sent successfully.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

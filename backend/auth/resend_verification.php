<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $data = request_data();
    $email = strtolower(clean_string($data['email'] ?? current_user()['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_response('Please enter a valid email address.', 422);
    }

    $user = get_user_by_email($email);
    if ($user !== null && empty($user['email_verified_at'])) {
        $token = create_email_verification_token((int) $user['id']);
        $user = get_user_by_id((int) $user['id']) ?? $user;
        send_verification_email($user, $token);
    }

    success_response(null, 'If the account exists and is not verified, a verification email has been sent.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

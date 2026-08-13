<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $email = strtolower(clean_string(request_data()['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_response('Please enter a valid email address.', 422);
    }

    $password = clean_string(request_data()['password'] ?? '');
    $confirmPassword = clean_string(request_data()['confirm_password'] ?? '');

    if (strlen($password) < 6) {
        error_response('Password must be at least 6 characters long.', 422);
    }

    if ($confirmPassword !== '' && $confirmPassword !== $password) {
        error_response('Password confirmation does not match.', 422);
    }

    $user = get_user_by_email($email);
    if ($user !== null) {
        execute_sql(
            'UPDATE users
             SET password = :password,
                 password_reset_token_hash = NULL,
                 password_reset_expires_at = NULL
             WHERE id = :id',
            [
                ':id' => (int) $user['id'],
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]
        );
    }

    success_response(null, 'If that email exists, the password has been updated.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $data = request_data();
    $email = clean_string($data['email'] ?? '');
    $password = clean_string($data['password'] ?? '');

    if ($email === '' || $password === '') {
        error_response('Email and password are required.', 422);
    }

    $user = attempt_login($email, $password);
    if ($user === null) {
        error_response('Invalid email or password.', 401);
    }

    success_response(auth_response_payload($user), 'Login successful.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}

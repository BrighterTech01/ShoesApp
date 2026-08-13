<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $user = register_account(request_data());
    success_response(
        auth_response_payload($user),
        'Account created successfully. Please check your email to verify your account.',
        201
    );
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $data = request_data();
    $userId = clean_int($data['user_id'] ?? 0);
    if ($userId <= 0) {
        error_response('User ID is required.', 422);
    }

    update_user_profile($userId, $data);
    $user = get_user_by_id($userId);
    if ($user === null) {
        error_response('User not found.', 404);
    }

    success_response(user_payload($user), 'Profile updated successfully.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

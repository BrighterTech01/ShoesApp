<?php
declare(strict_types=1);

function user_payload(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'phone' => $user['phone'] ?? null,
        'address' => $user['address'] ?? null,
        'city' => $user['city'] ?? null,
        'province' => $user['province'] ?? null,
        'zip_code' => $user['zip_code'] ?? null,
        'email_verified_at' => $user['email_verified_at'] ?? null,
        'email_verified' => !empty($user['email_verified_at']),
        'created_at' => $user['created_at'] ?? null,
    ];
}

function current_user(): ?array
{
    ensure_session_started();
    if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
        return $_SESSION['auth_user'];
    }

    $token = bearer_token_from_request();
    if ($token === null) {
        return null;
    }

    $payload = jwt_verify($token);
    if ($payload === null) {
        return null;
    }

    $userId = clean_int($payload['sub'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $user = get_user_by_id($userId);
    return $user ? user_payload($user) : null;
}

function current_user_id(): ?int
{
    $user = current_user();
    return $user ? (int) $user['id'] : null;
}

function login_user(array $user): void
{
    ensure_session_started();
    $_SESSION['auth_user'] = user_payload($user);
}

function logout_user(): void
{
    ensure_session_started();
    unset($_SESSION['auth_user']);
}

function require_auth(array $roles = [], bool $json = false): void
{
    $user = current_user();

    if ($user === null) {
        if ($json) {
            error_response('Authentication required.', 401);
        }

        flash_set('error', 'Please log in first.');
        redirect_to(app_url('web-admin/login.php'));
    }

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        if ($json) {
            error_response('You do not have permission to access this resource.', 403);
        }

        flash_set('error', 'Use an admin or seller account to access the panel.');
        redirect_to(app_url('web-admin/login.php'));
    }
}

function attempt_login(string $email, string $password): ?array
{
    $user = get_user_by_email($email);

    if ($user === null || !password_verify($password, $user['password'])) {
        return null;
    }

    login_user($user);

    return $user;
}

function auth_response_payload(array $user): array
{
    $payload = user_payload($user);
    $payload['token'] = jwt_for_user($user);
    $payload['token_type'] = 'Bearer';
    $payload['expires_in'] = (int) app_config('auth.jwt_ttl_seconds', 604800);

    return $payload;
}

function register_account(array $data): array
{
    $name = clean_string($data['name'] ?? '');
    $email = strtolower(clean_string($data['email'] ?? ''));
    $password = clean_string($data['password'] ?? '');
    $confirmPassword = clean_string($data['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        throw new InvalidArgumentException('Name, email, and password are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    if (strlen($password) < 6) {
        throw new InvalidArgumentException('Password must be at least 6 characters long.');
    }

    if ($confirmPassword !== '' && $confirmPassword !== $password) {
        throw new InvalidArgumentException('Password confirmation does not match.');
    }

    if (get_user_by_email($email) !== null) {
        throw new InvalidArgumentException('Email address is already registered.');
    }

    $userId = create_user([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'role' => 'customer',
        'phone' => clean_string($data['phone'] ?? ''),
        'address' => clean_string($data['address'] ?? ''),
        'city' => clean_string($data['city'] ?? ''),
        'province' => clean_string($data['province'] ?? ''),
        'zip_code' => clean_string($data['zip_code'] ?? ''),
    ]);

    $user = get_user_by_id($userId);
    if ($user === null) {
        throw new RuntimeException('Unable to create account.');
    }

    get_or_create_cart_id($userId);

    $verificationToken = create_email_verification_token($userId);
    $user = get_user_by_id($userId);
    if ($user === null) {
        throw new RuntimeException('Unable to create account.');
    }

    try {
        send_verification_email($user, $verificationToken);
    } catch (Throwable) {
        // Account creation should still work even when SMTP/mail is unavailable.
    }

    login_user($user);

    return $user;
}

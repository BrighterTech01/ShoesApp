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
        'created_at' => $user['created_at'] ?? null,
    ];
}

function current_user(): ?array
{
    ensure_session_started();
    return $_SESSION['auth_user'] ?? null;
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

    if (mb_strlen($password) < 6) {
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
    login_user($user);

    return $user;
}

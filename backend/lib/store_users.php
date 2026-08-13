<?php
declare(strict_types=1);

function list_users(?string $role = null): array
{
    $sql = 'SELECT id, name, email, role, phone, address, city, province, zip_code, email_verified_at, created_at FROM users';
    $params = [];

    if ($role !== null && $role !== '') {
        $sql .= ' WHERE role = :role';
        $params[':role'] = $role;
    }

    $sql .= ' ORDER BY created_at DESC';

    return fetch_all_rows($sql, $params);
}

function token_hash(string $token): string
{
    return hash('sha256', $token);
}

function generate_secure_token(): string
{
    return bin2hex(random_bytes(32));
}

function get_user_by_email(string $email): ?array
{
    return fetch_one_row('SELECT * FROM users WHERE email = :email', [':email' => strtolower(trim($email))]);
}

function get_user_by_id(int $id): ?array
{
    return fetch_one_row('SELECT * FROM users WHERE id = :id', [':id' => $id]);
}

function create_user(array $data): int
{
    $password = clean_string($data['password'] ?? '');
    if ($password === '') {
        throw new InvalidArgumentException('Password is required.');
    }

    execute_sql(
        'INSERT INTO users (name, email, password, role, phone, address, city, province, zip_code)
         VALUES (:name, :email, :password, :role, :phone, :address, :city, :province, :zip_code)',
        [
            ':name' => clean_string($data['name'] ?? ''),
            ':email' => strtolower(clean_string($data['email'] ?? '')),
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => strtolower(clean_string($data['role'] ?? 'customer', 'customer')),
            ':phone' => clean_string($data['phone'] ?? ''),
            ':address' => clean_string($data['address'] ?? ''),
            ':city' => clean_string($data['city'] ?? ''),
            ':province' => clean_string($data['province'] ?? ''),
            ':zip_code' => clean_string($data['zip_code'] ?? ''),
        ]
    );

    return (int) db()->lastInsertId();
}

function update_user_profile(int $id, array $data): void
{
    execute_sql(
        'UPDATE users
         SET name = :name,
             phone = :phone,
             address = :address,
             city = :city,
             province = :province,
             zip_code = :zip_code
         WHERE id = :id',
        [
            ':id' => $id,
            ':name' => clean_string($data['name'] ?? ''),
            ':phone' => clean_string($data['phone'] ?? ''),
            ':address' => clean_string($data['address'] ?? ''),
            ':city' => clean_string($data['city'] ?? ''),
            ':province' => clean_string($data['province'] ?? ''),
            ':zip_code' => clean_string($data['zip_code'] ?? ''),
        ]
    );
}

function create_email_verification_token(int $userId): string
{
    $token = generate_secure_token();
    $expiresAt = date('Y-m-d H:i:s', time() + ((int) app_config('auth.email_verification_ttl_minutes', 1440) * 60));

    execute_sql(
        'UPDATE users
         SET email_verification_token_hash = :token_hash,
             email_verification_expires_at = :expires_at
         WHERE id = :id',
        [
            ':id' => $userId,
            ':token_hash' => token_hash($token),
            ':expires_at' => $expiresAt,
        ]
    );

    return $token;
}

function verify_user_email_token(string $token): ?array
{
    $user = fetch_one_row(
        'SELECT *
         FROM users
         WHERE email_verification_token_hash = :token_hash
           AND email_verification_expires_at IS NOT NULL
           AND email_verification_expires_at >= NOW()
         LIMIT 1',
        [':token_hash' => token_hash($token)]
    );

    if ($user === null) {
        return null;
    }

    execute_sql(
        'UPDATE users
         SET email_verified_at = NOW(),
             email_verification_token_hash = NULL,
             email_verification_expires_at = NULL
         WHERE id = :id',
        [':id' => (int) $user['id']]
    );

    return get_user_by_id((int) $user['id']);
}

function create_password_reset_token_for_email(string $email): ?array
{
    $user = get_user_by_email($email);
    if ($user === null) {
        return null;
    }

    $token = generate_secure_token();
    $expiresAt = date('Y-m-d H:i:s', time() + ((int) app_config('auth.password_reset_ttl_minutes', 60) * 60));

    execute_sql(
        'UPDATE users
         SET password_reset_token_hash = :token_hash,
             password_reset_expires_at = :expires_at
         WHERE id = :id',
        [
            ':id' => (int) $user['id'],
            ':token_hash' => token_hash($token),
            ':expires_at' => $expiresAt,
        ]
    );

    return [
        'user' => $user,
        'token' => $token,
    ];
}

function reset_password_with_token(string $token, string $password, string $confirmPassword = ''): ?array
{
    if (strlen($password) < 6) {
        throw new InvalidArgumentException('Password must be at least 6 characters long.');
    }

    if ($confirmPassword !== '' && $confirmPassword !== $password) {
        throw new InvalidArgumentException('Password confirmation does not match.');
    }

    $user = fetch_one_row(
        'SELECT *
         FROM users
         WHERE password_reset_token_hash = :token_hash
           AND password_reset_expires_at IS NOT NULL
           AND password_reset_expires_at >= NOW()
         LIMIT 1',
        [':token_hash' => token_hash($token)]
    );

    if ($user === null) {
        return null;
    }

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

    return get_user_by_id((int) $user['id']);
}

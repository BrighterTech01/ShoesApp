<?php
declare(strict_types=1);

function list_users(?string $role = null): array
{
    $sql = 'SELECT id, name, email, role, phone, address, city, province, zip_code, created_at FROM users';
    $params = [];

    if ($role !== null && $role !== '') {
        $sql .= ' WHERE role = :role';
        $params[':role'] = $role;
    }

    $sql .= ' ORDER BY created_at DESC';

    return fetch_all_rows($sql, $params);
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

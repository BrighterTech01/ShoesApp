<?php
declare(strict_types=1);

function ensure_session_started(): void
{
    $sessionDirectory = root_path('storage/sessions');
    if (!is_dir($sessionDirectory)) {
        @mkdir($sessionDirectory, 0777, true);
    }

    if (is_dir($sessionDirectory) && is_writable($sessionDirectory) && session_save_path() !== $sessionDirectory) {
        session_save_path($sessionDirectory);
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function request_data(): array
{
    static $payload = null;

    if ($payload !== null) {
        return $payload;
    }

    $payload = $_POST;
    if ($payload !== []) {
        return $payload;
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        $payload = [];
        return $payload;
    }

    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $payload = $decoded;
        return $payload;
    }

    parse_str($rawBody, $formData);
    $payload = is_array($formData) ? $formData : [];

    return $payload;
}

function query_param(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function json_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function success_response(mixed $data = null, string $message = 'OK', int $statusCode = 200): never
{
    json_response([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $statusCode);
}

function error_response(string $message, int $statusCode = 400, array $extra = []): never
{
    json_response(array_merge([
        'success' => false,
        'message' => $message,
    ], $extra), $statusCode);
}

function redirect_to(string $path, array $query = []): never
{
    $url = $path;
    if ($query !== []) {
        $url .= (str_contains($path, '?') ? '&' : '?') . http_build_query($query);
    }

    header('Location: ' . $url);
    exit;
}

function flash_set(string $key, string $message): void
{
    ensure_session_started();
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    ensure_session_started();
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

function clean_string(mixed $value, string $default = ''): string
{
    $value = trim((string) $value);
    return $value === '' ? $default : $value;
}

function clean_int(mixed $value, int $default = 0): int
{
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    return $filtered === false ? $default : (int) $filtered;
}

function clean_float(mixed $value, float $default = 0.0): float
{
    $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
    return $filtered === false ? $default : (float) $filtered;
}

function clean_bool(mixed $value): bool
{
    return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true);
}

function format_currency(float|int|string $amount): string
{
    return 'PHP ' . number_format((float) $amount, 2);
}

function uploads_disk_path(string $path = ''): string
{
    $base = base_path('uploads');
    if ($path === '') {
        return $base;
    }

    return $base . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
}

function save_uploaded_image(string $fieldName = 'image'): ?string
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        throw new RuntimeException('Only JPG, PNG, WEBP, and GIF files are allowed.');
    }

    $directory = uploads_disk_path();
    if (!is_dir($directory)) {
        @mkdir($directory, 0777, true);
    }

    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Upload directory is not writable. Check backend/uploads permissions on the hosting server.');
    }

    $filename = uniqid('product_', true) . '.' . $extension;
    $destination = uploads_disk_path($filename);

    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to move the uploaded image.');
    }

    return $filename;
}

function public_image_url(?string $filename): string
{
    if ($filename === null || trim($filename) === '') {
        return app_url('web-admin/assets/placeholder-shoe.svg');
    }

    return uploads_url($filename);
}

function normalize_status(string $value, string $fallback = 'active'): string
{
    return ucfirst(strtolower(clean_string($value, $fallback)));
}

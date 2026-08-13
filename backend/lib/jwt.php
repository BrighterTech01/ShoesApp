<?php
declare(strict_types=1);

function base64_url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64_url_decode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function jwt_secret(): string
{
    return (string) app_config('auth.jwt_secret', 'change-this-jwt-secret');
}

function jwt_issue(array $claims, ?int $ttlSeconds = null): string
{
    $now = time();
    $ttl = $ttlSeconds ?? (int) app_config('auth.jwt_ttl_seconds', 604800);
    $payload = array_merge($claims, [
        'iss' => (string) app_config('auth.jwt_issuer', 'fit-me-in-sho-esia'),
        'iat' => $now,
        'exp' => $now + $ttl,
    ]);

    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $encodedHeader = base64_url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
    $encodedPayload = base64_url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, jwt_secret(), true);

    return $encodedHeader . '.' . $encodedPayload . '.' . base64_url_encode($signature);
}

function jwt_verify(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, jwt_secret(), true);
    $signature = base64_url_decode($encodedSignature);

    if (!is_string($signature) || !hash_equals($expectedSignature, $signature)) {
        return null;
    }

    $payloadJson = base64_url_decode($encodedPayload);
    $payload = is_string($payloadJson) ? json_decode($payloadJson, true) : null;
    if (!is_array($payload)) {
        return null;
    }

    if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
        return null;
    }

    return $payload;
}

function bearer_token_from_request(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', trim((string) $header), $matches)) {
        return null;
    }

    return trim($matches[1]);
}

function jwt_for_user(array $user): string
{
    return jwt_issue([
        'sub' => (int) $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
    ]);
}

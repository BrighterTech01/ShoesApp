<?php
declare(strict_types=1);

function base_path(string $path = ''): string
{
    static $basePath = null;

    if ($basePath === null) {
        $basePath = dirname(__DIR__);
    }

    if ($path === '') {
        return $basePath;
    }

    return $basePath . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
}

function root_path(string $path = ''): string
{
    static $rootPath = null;

    if ($rootPath === null) {
        $rootPath = dirname(base_path());
    }

    if ($path === '') {
        return $rootPath;
    }

    return $rootPath . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require base_path('config/app.php');
    }

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function request_scheme(): string
{
    if (PHP_SAPI === 'cli') {
        return 'http';
    }

    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return 'https';
    }

    return (string) ($_SERVER['REQUEST_SCHEME'] ?? 'http');
}

function detected_base_url(): ?string
{
    if (PHP_SAPI === 'cli') {
        return null;
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if ($host === '' || $scriptName === '') {
        return null;
    }

    $basePath = preg_replace('#/(backend|web-admin)(?:/.*)?$#', '', $scriptName);
    if ($basePath === null) {
        $basePath = '';
    }

    return request_scheme() . '://' . $host . rtrim($basePath, '/');
}

function app_url(string $path = ''): string
{
    $baseUrl = rtrim((string) (detected_base_url() ?? app_config('app.base_url', '')), '/');
    return $path === '' ? $baseUrl : $baseUrl . '/' . ltrim($path, '/');
}

function backend_url(string $path = ''): string
{
    $baseUrl = rtrim((string) (detected_base_url() !== null ? app_url('backend') : app_config('app.backend_url', '')), '/');
    return $path === '' ? $baseUrl : $baseUrl . '/' . ltrim($path, '/');
}

function uploads_url(string $path = ''): string
{
    $baseUrl = rtrim((string) (detected_base_url() !== null ? app_url('backend/uploads') : app_config('app.uploads_url', '')), '/');
    return $path === '' ? $baseUrl : $baseUrl . '/' . ltrim($path, '/');
}

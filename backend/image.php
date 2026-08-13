<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    $requested = clean_string(query_param('file', ''));
    $filename = basename($requested);

    if ($filename === '') {
        http_response_code(404);
        exit;
    }

    $path = uploads_disk_path($filename);
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . image_mime_type($filename));
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: public, max-age=86400');
    readfile($path);
    exit;
} catch (Throwable $throwable) {
    http_response_code(500);
    exit;
}

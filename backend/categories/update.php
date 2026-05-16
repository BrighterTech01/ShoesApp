<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_auth(['admin', 'seller'], true);

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $payload = request_data();
    $payload['id'] = $payload['id'] ?? query_param('id', 0);
    $id = save_category($payload);
    success_response(get_category($id), 'Category updated successfully.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

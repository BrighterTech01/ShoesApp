<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_auth(['admin', 'seller'], true);

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $id = clean_int(request_data()['id'] ?? query_param('id', 0));
    if ($id <= 0) {
        error_response('Category ID is required.', 422);
    }

    delete_category($id);
    success_response(null, 'Category deleted successfully.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

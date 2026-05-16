<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_auth(['admin', 'seller'], true);

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $id = save_brand(request_data());
    success_response(get_brand($id), 'Brand saved successfully.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_auth(['admin', 'seller'], true);

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $id = save_variant(request_data());
    success_response(get_variant($id), 'Variant saved successfully.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

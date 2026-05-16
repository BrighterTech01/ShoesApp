<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_auth(['admin', 'seller'], true);

try {
    if (!is_post()) {
        error_response('Method not allowed.', 405);
    }

    $payload = request_data();
    $uploadedImage = save_uploaded_image('image');
    if ($uploadedImage !== null) {
        $payload['image'] = $uploadedImage;
    }

    $id = save_product($payload);
    success_response(get_product($id), 'Product saved successfully.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 422);
}

<?php

declare(strict_types=1);

/*
 * Entrega una imagen de storage/uploads. Las imágenes no son accesibles por URL
 * directa: solo a través de este script, que exige sesión iniciada.
 */

require __DIR__ . '/../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/images.php';

require_role('admin', 'operator');

$imageId = to_id(query_string('id'));
$image = $imageId === null ? null : find_equipment_image($imageId);
$path = $image === null ? null : image_file_path($image['stored_name']);

if ($path === null || !is_file($path)) {
    render_error_page(404, 'La imagen no existe.');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $image['mime_type']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline');
header('Cache-Control: private, max-age=86400');

readfile($path);

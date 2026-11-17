<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/images.php';

require_role('admin');
require_post();
csrf_verify();

$imageId = to_id(post_string('id'));
$image = $imageId === null ? null : find_equipment_image($imageId);

if ($image === null) {
    render_error_page(404, 'La imagen no existe.');
}

// Primero se borra el registro; el archivo solo si el borrado en la base tuvo éxito.
db_query('DELETE FROM equipment_images WHERE id = :id', [':id' => $imageId]);

$path = image_file_path($image['stored_name']);
if (is_file($path)) {
    unlink($path);
}

audit_log('delete', 'equipment_images', $imageId, [
    'equipment_id'  => $image['equipment_id'],
    'original_name' => $image['original_name'],
]);

flash('success', 'Imagen eliminada correctamente.');
redirect('pages/equipment/show.php?id=' . $image['equipment_id']);

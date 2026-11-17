<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';
require_once ROOT_PATH . '/includes/images.php';

require_role('admin');
require_post();

// El ID va en la URL para poder volver al equipo aunque PHP descarte el POST por tamaño.
$equipmentId = to_id(query_string('id'));
if ($equipmentId === null || find_equipment($equipmentId) === null) {
    render_error_page(404, 'El equipo no existe o fue eliminado.');
}
$back = 'pages/equipment/show.php?id=' . $equipmentId;

if (post_too_large()) {
    flash('error', 'Los archivos enviados superan el tamaño permitido por el servidor.');
    redirect($back);
}

csrf_verify();

$files = isset($_FILES['images']) && is_array($_FILES['images'])
    ? normalize_uploaded_files($_FILES['images'])
    : [];
$files = array_values(array_filter(
    $files,
    static fn (array $file) => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
));

if ($files === []) {
    flash('error', 'Seleccione al menos una imagen.');
    redirect($back);
}

if (count($files) > MAX_IMAGES_PER_UPLOAD) {
    flash('error', 'Puede subir como máximo ' . MAX_IMAGES_PER_UPLOAD . ' imágenes a la vez.');
    redirect($back);
}

if (count_equipment_images($equipmentId) + count($files) > MAX_IMAGES_PER_EQUIPMENT) {
    flash('error', 'Cada equipo puede tener como máximo ' . MAX_IMAGES_PER_EQUIPMENT . ' imágenes.');
    redirect($back);
}

// Cada archivo se valida por separado: los válidos se guardan y los inválidos se informan.
$saved = 0;
foreach ($files as $file) {
    $result = validate_image_upload($file);

    if (is_string($result)) {
        flash('error', $result);
        continue;
    }

    [$extension, $mimeType] = $result;
    $imageId = store_equipment_image($equipmentId, $file, $extension, $mimeType);
    audit_log('create', 'equipment_images', $imageId, null, [
        'equipment_id'  => $equipmentId,
        'original_name' => (string) $file['name'],
    ]);
    $saved++;
}

if ($saved > 0) {
    flash('success', $saved === 1 ? 'Se agregó 1 imagen.' : "Se agregaron {$saved} imágenes.");
}

redirect($back);

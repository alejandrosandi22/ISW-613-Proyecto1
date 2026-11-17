<?php

declare(strict_types=1);

/*
 * Imágenes de equipos.
 * Los archivos se guardan en storage/uploads (fuera de public/) con un nombre
 * generado por el servidor, y se entregan mediante public/image.php.
 */

const MAX_IMAGES_PER_EQUIPMENT = 10;
const MAX_IMAGES_PER_UPLOAD = 5;

/**
 * Convierte la estructura de $_FILES de un campo múltiple (name="images[]")
 * en una lista de archivos individuales.
 */
function normalize_uploaded_files(array $field): array
{
    if (!is_array($field['name'] ?? null)) {
        return [$field];
    }

    $files = [];
    foreach (array_keys($field['name']) as $i) {
        $files[] = [
            'name'     => $field['name'][$i],
            'tmp_name' => $field['tmp_name'][$i],
            'error'    => $field['error'][$i],
            'size'     => $field['size'][$i],
        ];
    }

    return $files;
}

/**
 * Indica si la petición superó post_max_size (PHP descarta $_POST y $_FILES).
 */
function post_too_large(): bool
{
    $limit = ini_size_to_bytes((string) ini_get('post_max_size'));

    return $limit > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $limit;
}

/**
 * Convierte un tamaño de php.ini (p. ej. '40M') a bytes.
 */
function ini_size_to_bytes(string $value): int
{
    $value = trim($value);
    $number = (int) $value;

    return match (strtoupper(substr($value, -1))) {
        'G'     => $number * 1024 ** 3,
        'M'     => $number * 1024 ** 2,
        'K'     => $number * 1024,
        default => $number,
    };
}

/**
 * Valida un archivo subido. Devuelve [extensión, tipo MIME] o un mensaje de error.
 * Controles: error de subida, tamaño, lista blanca de extensiones y tipo MIME real con finfo.
 */
function validate_image_upload(array $file): array|string
{
    $name = is_string($file['name'] ?? null) ? $file['name'] : '';
    $label = $name === '' ? 'El archivo' : '«' . mb_substr($name, 0, 60) . '»';
    $maxBytes = (int) config('uploads.max_size_bytes');
    $maxMb = round($maxBytes / 1024 / 1024, 1);

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return "{$label} supera el tamaño máximo de {$maxMb} MB.";
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
        return "{$label} no se pudo subir.";
    }

    $size = (int) $file['size'];
    if ($size <= 0 || $size > $maxBytes) {
        return "{$label} supera el tamaño máximo de {$maxMb} MB o está vacío.";
    }

    $allowedTypes = config('uploads.allowed_types', []);
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!array_key_exists($extension, $allowedTypes)) {
        return "{$label} no tiene una extensión permitida (" . implode(', ', array_keys($allowedTypes)) . ').';
    }

    // El tipo MIME se obtiene del contenido real del archivo, no del que envía el navegador.
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    if ($mimeType !== $allowedTypes[$extension]) {
        return "{$label} no es una imagen válida o su contenido no coincide con la extensión.";
    }

    return [$extension === 'jpeg' ? 'jpg' : $extension, $mimeType];
}

/**
 * Guarda un archivo ya validado y registra la imagen. Devuelve el ID de la imagen.
 */
function store_equipment_image(int $equipmentId, array $file, string $extension, string $mimeType): int
{
    // Nombre generado por el servidor: el nombre original nunca se usa como ruta.
    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = rtrim((string) config('uploads.path'), '/\\') . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        throw new RuntimeException('No se pudo mover el archivo subido a ' . $destination);
    }

    try {
        db_query(
            'INSERT INTO equipment_images (equipment_id, stored_name, original_name, mime_type, size_bytes)
             VALUES (:equipment_id, :stored_name, :original_name, :mime_type, :size_bytes)',
            [
                ':equipment_id'  => $equipmentId,
                ':stored_name'   => $storedName,
                ':original_name' => mb_substr((string) $file['name'], 0, 255),
                ':mime_type'     => $mimeType,
                ':size_bytes'    => (int) $file['size'],
            ]
        );
    } catch (Throwable $e) {
        unlink($destination);
        throw $e;
    }

    return (int) db()->lastInsertId();
}

function count_equipment_images(int $equipmentId): int
{
    return (int) db_query(
        'SELECT COUNT(*) FROM equipment_images WHERE equipment_id = :id',
        [':id' => $equipmentId]
    )->fetchColumn();
}

function list_equipment_images(int $equipmentId): array
{
    return db_query(
        'SELECT id, original_name, size_bytes FROM equipment_images WHERE equipment_id = :id ORDER BY id',
        [':id' => $equipmentId]
    )->fetchAll();
}

/**
 * Imagen de un equipo vigente (no eliminado).
 */
function find_equipment_image(int $imageId): ?array
{
    $st = db_query(
        'SELECT i.id, i.equipment_id, i.stored_name, i.original_name, i.mime_type, i.size_bytes
         FROM equipment_images i
         JOIN equipment e ON e.id = i.equipment_id
         WHERE i.id = :id AND e.deleted_at IS NULL',
        [':id' => $imageId]
    );

    return $st->fetch() ?: null;
}

/**
 * Ruta absoluta del archivo. basename() impide salir de storage/uploads.
 */
function image_file_path(string $storedName): string
{
    return rtrim((string) config('uploads.path'), '/\\') . DIRECTORY_SEPARATOR . basename($storedName);
}

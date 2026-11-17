<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';

require_role('admin');

$equipmentId = to_id(query_string('id'));
$existing = $equipmentId === null ? null : find_equipment($equipmentId);

if ($existing === null) {
    render_error_page(404, 'El equipo no existe o fue eliminado.');
}

$errors = pull_form_errors();
$equipment = pull_old_input() ?: [
    'code'        => $existing['code'],
    'name'        => $existing['name'],
    'category_id' => (string) $existing['category_id'],
    'status'      => $existing['status'],
    'description' => $existing['description'] ?? '',
];
// La versión siempre es la actual de la base: identifica qué datos vio el usuario.
$version = $existing['version'];
$categoryOptions = list_category_options();

$formAction = url('actions/equipment/update.php');
$submitLabel = 'Guardar cambios';
$pageTitle = 'Editar equipo';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Editar equipo</h1>
<?php require ROOT_PATH . '/includes/templates/equipment/form.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

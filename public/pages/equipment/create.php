<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';

require_role('admin');

$errors = pull_form_errors();
$equipment = pull_old_input() + [
    'code'        => '',
    'name'        => '',
    'category_id' => '',
    'status'      => 'available',
    'description' => '',
];
$categoryOptions = list_category_options();

$formAction = url('actions/equipment/store.php');
$submitLabel = 'Guardar';
$pageTitle = 'Nuevo equipo';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Nuevo equipo</h1>
<?php require ROOT_PATH . '/includes/templates/equipment/form.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

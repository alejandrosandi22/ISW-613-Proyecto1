<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/users.php';

require_role('admin');

$errors = pull_form_errors();
$formUser = pull_old_input() + ['full_name' => '', 'email' => '', 'role_id' => '2', 'is_active' => '1'];
$roles = list_roles();
$isSelf = false;

$formAction = url('actions/users/store.php');
$submitLabel = 'Guardar';
$pageTitle = 'Nuevo usuario';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Nuevo usuario</h1>
<?php require ROOT_PATH . '/includes/templates/users/form.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

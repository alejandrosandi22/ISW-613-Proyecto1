<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/users.php';

$currentAdmin = require_role('admin');

$userId = to_id(query_string('id'));
$existing = $userId === null ? null : find_user($userId);

if ($existing === null) {
    render_error_page(404, 'El usuario no existe o fue eliminado.');
}

$errors = pull_form_errors();
// Las contraseñas nunca se devuelven al formulario.
$formUser = pull_old_input() ?: [
    'full_name' => $existing['full_name'],
    'email'     => $existing['email'],
    'role_id'   => (string) $existing['role_id'],
    'is_active' => (string) $existing['is_active'],
];
$roles = list_roles();
$isSelf = $userId === $currentAdmin['id'];

$formAction = url('actions/users/update.php');
$submitLabel = 'Guardar cambios';
$pageTitle = 'Editar usuario';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Editar usuario</h1>
<?php require ROOT_PATH . '/includes/templates/users/form.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

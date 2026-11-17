<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/users.php';

$currentAdmin = require_role('admin');
require_post();
csrf_verify();

$id = to_id(post_string('id'));
$existing = $id === null ? null : find_user($id);

if ($existing === null) {
    render_error_page(404, 'El usuario no existe o fue eliminado.');
}

$input = [
    'full_name'             => post_string('full_name'),
    'email'                 => post_string('email'),
    'role_id'               => post_string('role_id'),
    'is_active'             => post_string('is_active'),
    'password'              => post_string('password', false),
    'password_confirmation' => post_string('password_confirmation', false),
];

[$data, $errors] = validate_user_input($input, false);

if (!isset($errors['email']) && user_email_exists($data['email'], $id)) {
    $errors['email'] = 'Ya existe un usuario con ese correo.';
}

// Se valida en el servidor aunque el formulario deshabilite estos campos.
if ($id === $currentAdmin['id']) {
    if ($data['role_id'] !== $existing['role_id']) {
        $errors['role_id'] = 'No puede cambiar su propio rol.';
    }
    if ($data['is_active'] !== 1) {
        $errors['is_active'] = 'No puede desactivar su propia cuenta.';
    }
}

$oldInput = array_diff_key($input, ['password' => true, 'password_confirmation' => true]);

if ($errors !== []) {
    set_old_input($oldInput);
    set_form_errors($errors);
    redirect('pages/users/edit.php?id=' . $id);
}

try {
    update_user($id, $data);
} catch (PDOException $e) {
    if (!is_duplicate_key($e)) {
        throw $e;
    }
    set_old_input($oldInput);
    set_form_errors(['email' => 'Ya existe un usuario con ese correo.']);
    redirect('pages/users/edit.php?id=' . $id);
}

$passwordChanged = $data['password'] !== '';

// Cambios sensibles invalidan las sesiones persistentes («Recordarme») del usuario.
if ($passwordChanged || $data['is_active'] !== 1 || $data['role_id'] !== $existing['role_id']) {
    revoke_user_tokens($id);
}

audit_log(
    'update',
    'users',
    $id,
    user_audit_values($existing),
    user_audit_values($data) + ['password_changed' => $passwordChanged]
);

flash('success', 'Usuario actualizado correctamente.');
redirect('pages/users/index.php');

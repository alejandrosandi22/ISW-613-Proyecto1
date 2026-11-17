<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/users.php';

require_role('admin');
require_post();
csrf_verify();

$input = [
    'full_name'             => post_string('full_name'),
    'email'                 => post_string('email'),
    'role_id'               => post_string('role_id'),
    'is_active'             => post_string('is_active'),
    'password'              => post_string('password', false),
    'password_confirmation' => post_string('password_confirmation', false),
];

[$data, $errors] = validate_user_input($input, true);

if (!isset($errors['email']) && user_email_exists($data['email'])) {
    $errors['email'] = 'Ya existe un usuario con ese correo.';
}

// Las contraseñas no se guardan en la sesión para volver a mostrarlas.
$oldInput = array_diff_key($input, ['password' => true, 'password_confirmation' => true]);

if ($errors !== []) {
    set_old_input($oldInput);
    set_form_errors($errors);
    redirect('pages/users/create.php');
}

try {
    $id = create_user($data);
} catch (PDOException $e) {
    if (!is_duplicate_key($e)) {
        throw $e;
    }
    set_old_input($oldInput);
    set_form_errors(['email' => 'Ya existe un usuario con ese correo.']);
    redirect('pages/users/create.php');
}

audit_log('create', 'users', $id, null, user_audit_values($data));

flash('success', 'Usuario creado correctamente.');
redirect('pages/users/index.php');

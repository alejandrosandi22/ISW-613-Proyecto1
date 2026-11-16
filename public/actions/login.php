<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/bootstrap.php';

require_post();
csrf_verify();

$email = post_string('email');
$password = post_string('password', false);

$isValid = $email !== ''
    && $password !== ''
    && mb_strlen($email) <= 150
    && strlen($password) <= 255
    && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

if (!$isValid) {
    set_old_input(['email' => $email]);
    flash('error', 'Ingrese un correo electrónico y una contraseña válidos.');
    redirect('pages/login.php');
}

if (!attempt_login($email, $password)) {
    set_old_input(['email' => $email]);
    // Mensaje genérico: no revela si el correo existe ni si la cuenta está bloqueada.
    flash('error', 'Credenciales incorrectas o cuenta bloqueada temporalmente.');
    redirect('pages/login.php');
}

redirect('pages/dashboard.php');

<?php

declare(strict_types=1);

/**
 * Token CSRF por sesión. Se incluye en todo formulario que modifica estado.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Valida el token recibido. hash_equals() compara en tiempo constante.
 */
function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';

    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        render_error_page(403, 'La solicitud no es válida o la página expiró. Vuelva a intentarlo.');
    }
}

/**
 * Genera un token nuevo (se usa al iniciar sesión).
 */
function csrf_regenerate(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

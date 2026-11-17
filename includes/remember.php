<?php

declare(strict_types=1);

/*
 * Sesión persistente «Recordarme» con el esquema selector:validador.
 *
 * - La cookie contiene "selector:validador" (ambos aleatorios, generados con random_bytes()).
 * - La tabla auth_tokens guarda el selector, el hash SHA-256 del validador y la expiración.
 *   Si alguien roba la base de datos, con el hash no puede construir una cookie válida.
 * - En cada uso: se busca por selector, se compara el hash con hash_equals(),
 *   se verifica la expiración y se rota el token (uno nuevo reemplaza al usado).
 */

function remember_cookie_name(): string
{
    return (string) config('cookies.remember', 'sigel_remember');
}

/**
 * Genera un token nuevo para el usuario y envía la cookie.
 */
function issue_remember_token(int $userId): void
{
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $expires = time() + 60 * 60 * 24 * (int) config('security.remember_me_days', 14);

    db_query(
        'INSERT INTO auth_tokens (user_id, selector, validator_hash, expires_at)
         VALUES (:user_id, :selector, :validator_hash, :expires_at)',
        [
            ':user_id'        => $userId,
            ':selector'       => $selector,
            ':validator_hash' => hash('sha256', $validator),
            ':expires_at'     => date('Y-m-d H:i:s', $expires),
        ]
    );

    // La cookie solo contiene selector:validador; nunca el ID del usuario.
    set_app_cookie(remember_cookie_name(), $selector . ':' . $validator, $expires);
}

/**
 * Lee la cookie. Es entrada no confiable: debe tener exactamente el formato esperado.
 * Devuelve [selector, validador] o null.
 */
function read_remember_cookie(): ?array
{
    $value = $_COOKIE[remember_cookie_name()] ?? null;

    if (!is_string($value) || preg_match('/^([a-f0-9]{18}):([a-f0-9]{64})$/', $value, $matches) !== 1) {
        return null;
    }

    return [$matches[1], $matches[2]];
}

/**
 * Inicia sesión a partir de la cookie «Recordarme». Devuelve true si lo logró.
 */
function login_from_remember_cookie(): bool
{
    if (!isset($_COOKIE[remember_cookie_name()])) {
        return false;
    }

    $parts = read_remember_cookie();
    if ($parts === null) {
        clear_app_cookie(remember_cookie_name());
        return false;
    }

    [$selector, $validator] = $parts;

    $token = db_query(
        'SELECT t.id, t.user_id, t.validator_hash, t.expires_at > NOW() AS is_valid,
                (u.is_active = 1 AND u.deleted_at IS NULL) AS user_ok
         FROM auth_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.selector = :selector',
        [':selector' => $selector]
    )->fetch();

    if ($token === false) {
        clear_app_cookie(remember_cookie_name());
        return false;
    }

    $userId = (int) $token['user_id'];

    // Comparación en tiempo constante del hash del validador.
    if (!hash_equals($token['validator_hash'], hash('sha256', $validator))) {
        // Selector correcto con validador incorrecto: posible cookie robada o manipulada.
        // Se revocan todos los tokens del usuario.
        db_query('DELETE FROM auth_tokens WHERE user_id = :user_id', [':user_id' => $userId]);
        clear_app_cookie(remember_cookie_name());
        audit_log('remember_token_mismatch', 'users', $userId, null, null, $userId);
        return false;
    }

    // El token se usa una sola vez: se elimina siempre (rotación o expiración).
    db_query('DELETE FROM auth_tokens WHERE id = :id', [':id' => $token['id']]);

    if ((int) $token['is_valid'] !== 1 || (int) $token['user_ok'] !== 1) {
        clear_app_cookie(remember_cookie_name());
        return false;
    }

    start_authenticated_session($userId);
    issue_remember_token($userId);
    audit_log('login_remember', 'users', $userId, null, null, $userId);

    return true;
}

/**
 * Revoca el token de la cookie actual y elimina la cookie (cierre de sesión).
 */
function forget_remember_token(): void
{
    $parts = read_remember_cookie();

    if ($parts !== null) {
        db_query('DELETE FROM auth_tokens WHERE selector = :selector', [':selector' => $parts[0]]);
    }

    if (isset($_COOKIE[remember_cookie_name()])) {
        clear_app_cookie(remember_cookie_name());
    }
}

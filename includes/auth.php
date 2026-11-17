<?php

declare(strict_types=1);

/*
 * Hash de una clave aleatoria. Se verifica cuando el correo no existe para que
 * la respuesta tarde lo mismo y no revele qué correos están registrados.
 */
const DUMMY_PASSWORD_HASH = '$2y$10$CfYIAOF5BuT/qkBmkxsqfO2HV8tzsdJ8sMqP8lVzLGNL/XWkHNjpC';

/**
 * Inicia la sesión con una cookie segura y aplica la expiración por inactividad.
 */
function start_session(): void
{
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    session_name((string) config('session.name', 'SIGEL_SESSION'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => cookie_path(),
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    $timeout = (int) config('session.idle_timeout', 1800);
    $lastActivity = $_SESSION['last_activity'] ?? null;

    if (isset($_SESSION['user_id'], $lastActivity) && time() - (int) $lastActivity > $timeout) {
        destroy_session();
        restart_session();
        // Con «Recordarme» la sesión se renueva sola en current_user(); no se muestra aviso.
        if (!isset($_COOKIE[(string) config('cookies.remember', 'sigel_remember')])) {
            flash('error', 'Su sesión expiró por inactividad. Inicie sesión de nuevo.');
        }
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Vacía la sesión, elimina la cookie del navegador y destruye los datos en el servidor.
 */
function destroy_session(): void
{
    $_SESSION = [];

    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);

    session_destroy();
}

/**
 * Abre una sesión nueva con un identificador distinto (tras destruir la anterior).
 */
function restart_session(): void
{
    session_id(session_create_id());
    session_start();
}

/**
 * Usuario autenticado, consultado en cada petición: si fue desactivado,
 * eliminado o cambió de rol, el cambio aplica de inmediato.
 */
function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    // Sin sesión activa se intenta el inicio de sesión persistente («Recordarme»).
    if (!isset($_SESSION['user_id']) && !login_from_remember_cookie()) {
        return $user = null;
    }

    $st = db()->prepare(
        'SELECT u.id, u.full_name, u.email, r.name AS role, r.label AS role_label
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id AND u.is_active = 1 AND u.deleted_at IS NULL'
    );
    $st->execute([':id' => (int) $_SESSION['user_id']]);
    $user = $st->fetch() ?: null;

    if ($user === null) {
        destroy_session();
        restart_session();
    }

    return $user;
}

/**
 * Valida credenciales. Devuelve false ante cualquier fallo, sin indicar la causa.
 */
function attempt_login(string $email, string $password): bool
{
    $st = db()->prepare(
        'SELECT id, password_hash, is_active, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked
         FROM users
         WHERE email = :email AND deleted_at IS NULL'
    );
    $st->execute([':email' => $email]);
    $user = $st->fetch();

    if ($user === false) {
        password_verify($password, DUMMY_PASSWORD_HASH);
        audit_log('login_failed', 'users', null, null, ['email' => $email]);
        return false;
    }

    $userId = (int) $user['id'];

    if ((int) $user['is_locked'] === 1 || (int) $user['is_active'] !== 1) {
        password_verify($password, DUMMY_PASSWORD_HASH);
        audit_log('login_blocked', 'users', $userId, null, null, $userId);
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        register_failed_attempt($userId);
        audit_log('login_failed', 'users', $userId, null, null, $userId);
        return false;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
            ->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $userId]);
    }

    db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id')
        ->execute([':id' => $userId]);

    start_authenticated_session($userId);
    audit_log('login', 'users', $userId);

    return true;
}

/**
 * Inicia la sesión de un usuario ya autenticado (por contraseña o por «Recordarme»).
 */
function start_authenticated_session(int $userId): void
{
    // Nuevo identificador de sesión al autenticar (evita la fijación de sesión).
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['last_activity'] = time();
    csrf_regenerate();

    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute([':id' => $userId]);
}

/**
 * Suma un intento fallido y bloquea la cuenta al alcanzar el máximo permitido.
 */
function register_failed_attempt(int $userId): void
{
    db()->prepare('UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = :id')
        ->execute([':id' => $userId]);

    $lockedUntil = date('Y-m-d H:i:s', time() + 60 * (int) config('security.lockout_minutes', 15));

    db()->prepare(
        'UPDATE users SET failed_attempts = 0, locked_until = :locked_until
         WHERE id = :id AND failed_attempts >= :max_attempts'
    )->execute([
        ':locked_until' => $lockedUntil,
        ':id'           => $userId,
        ':max_attempts' => (int) config('security.max_login_attempts', 5),
    ]);
}

function logout_user(): void
{
    if (isset($_SESSION['user_id'])) {
        audit_log('logout', 'users', (int) $_SESSION['user_id']);
    }

    // Revoca el token «Recordarme» en la base y elimina la cookie.
    forget_remember_token();
    destroy_session();
}

/**
 * Exige un usuario autenticado; si no hay, redirige al inicio de sesión.
 */
function require_login(): array
{
    $user = current_user();

    if ($user === null) {
        flash('error', 'Debe iniciar sesión para continuar.');
        redirect('pages/login.php');
    }

    return $user;
}

/**
 * Exige uno de los roles indicados. Se llama al inicio de cada página o acción
 * protegida, por lo que también cubre el acceso directo por URL.
 */
function require_role(string ...$roles): array
{
    $user = require_login();

    if (!in_array($user['role'], $roles, true)) {
        audit_log('access_denied', 'page', null, null, ['uri' => (string) ($_SERVER['REQUEST_URI'] ?? '')]);
        render_error_page(403, 'No tiene permisos para acceder a esta página.');
    }

    return $user;
}

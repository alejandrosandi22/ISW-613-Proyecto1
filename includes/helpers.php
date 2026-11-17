<?php

declare(strict_types=1);

/**
 * Lee un valor de config/config.php usando notación de puntos (ej.: 'db.host').
 */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require ROOT_PATH . '/config/config.php';
    }

    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

/**
 * Escapa texto para imprimirlo en HTML (protección contra XSS).
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Construye una URL interna a partir de la ruta base configurada.
 */
function url(string $path = ''): string
{
    return rtrim((string) config('app.base_path', ''), '/') . '/' . ltrim($path, '/');
}

/**
 * Ruta de las cookies de la aplicación.
 */
function cookie_path(): string
{
    $base = rtrim((string) config('app.base_path', ''), '/');

    return $base === '' ? '/' : $base;
}

/**
 * Crea o reemplaza una cookie de la aplicación con atributos seguros:
 * HttpOnly (JavaScript no la lee), SameSite=Lax y Secure cuando hay HTTPS.
 */
function set_app_cookie(string $name, string $value, int $expires): void
{
    setcookie($name, $value, [
        'expires'  => $expires,
        'path'     => cookie_path(),
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $value;
}

/**
 * Elimina una cookie de la aplicación (fecha de expiración en el pasado).
 */
function clear_app_cookie(string $name): void
{
    set_app_cookie($name, '', time() - 3600);
    unset($_COOKIE[$name]);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Obtiene un campo de texto de $_POST. Si no existe o no es texto, devuelve ''.
 */
function post_string(string $key, bool $trim = true): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }

    return $trim ? trim($value) : $value;
}

/**
 * Obtiene un parámetro de texto de $_GET. Si no existe o no es texto, devuelve ''.
 */
function query_string(string $key): string
{
    $value = $_GET[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

/**
 * Convierte un parámetro en un ID entero positivo, o null si no es válido.
 */
function to_id(string $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $id === false ? null : $id;
}

/**
 * Las acciones solo aceptan POST.
 */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        render_error_page(405, 'Método no permitido.');
    }
}

/**
 * Mensajes de una sola lectura que sobreviven a la redirección (patrón PRG).
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

/**
 * Guarda los datos del formulario para volver a mostrarlos tras un error.
 */
function set_old_input(array $data): void
{
    $_SESSION['old_input'] = $data;
}

function pull_old_input(): array
{
    $data = $_SESSION['old_input'] ?? [];
    unset($_SESSION['old_input']);

    return $data;
}

/**
 * Errores de validación por campo, para mostrarlos tras la redirección.
 */
function set_form_errors(array $errors): void
{
    $_SESSION['form_errors'] = $errors;
}

function pull_form_errors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);

    return $errors;
}

function field_error(array $errors, string $field): string
{
    if (!isset($errors[$field])) {
        return '';
    }

    return '<span class="field-error">' . e($errors[$field]) . '</span>';
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'");
    header('Cache-Control: no-store');
}

/**
 * Muestra una página de error sin detalles internos y termina la ejecución.
 */
function render_error_page(int $status, string $message): never
{
    http_response_code($status);
    require ROOT_PATH . '/includes/templates/error.php';
    exit;
}

<?php

declare(strict_types=1);

/*
 * Punto de arranque común: toda página y acción de public/ lo incluye primero.
 * Carga la configuración y las funciones, configura errores y abre la sesión.
 */

define('ROOT_PATH', dirname(__DIR__));

if (!is_file(ROOT_PATH . '/config/config.php')) {
    http_response_code(500);
    exit('Falta el archivo config/config.php. Consulte el README.');
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

date_default_timezone_set((string) config('app.timezone', 'UTC'));

// Errores: nunca se muestran al usuario; se registran en storage/logs.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', (string) config('log.path'));

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    render_error_page(500, 'Ocurrió un error inesperado. Intente de nuevo más tarde.');
});

ob_start();
send_security_headers();
start_session();

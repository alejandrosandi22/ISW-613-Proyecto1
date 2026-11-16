<?php

declare(strict_types=1);

/**
 * Conexión PDO única por petición.
 * ERRMODE_EXCEPTION: todo error SQL lanza una excepción.
 * EMULATE_PREPARES = false: las sentencias preparadas las resuelve el servidor,
 * de modo que la estructura de la consulta y los datos viajan por separado.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            config('db.host'),
            (int) config('db.port'),
            config('db.name'),
            config('db.charset', 'utf8mb4')
        );

        $pdo = new PDO($dsn, (string) config('db.user'), (string) config('db.password'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // La base de datos usa la misma zona horaria que PHP.
        $pdo->prepare('SET time_zone = :tz')->execute([':tz' => date('P')]);
    }

    return $pdo;
}

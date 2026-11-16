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

/**
 * Prepara y ejecuta una consulta enlazando cada parámetro con su tipo.
 * Necesario para LIMIT/OFFSET, que deben enviarse como enteros.
 */
function db_query(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);

    foreach ($params as $name => $value) {
        $type = match (true) {
            is_int($value)  => PDO::PARAM_INT,
            $value === null => PDO::PARAM_NULL,
            default         => PDO::PARAM_STR,
        };
        $st->bindValue($name, $value, $type);
    }

    $st->execute();

    return $st;
}

/**
 * Indica si la excepción corresponde a una clave única duplicada (error 1062).
 */
function is_duplicate_key(PDOException $e): bool
{
    return (int) ($e->errorInfo[1] ?? 0) === 1062;
}

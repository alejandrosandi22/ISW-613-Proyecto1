<?php

declare(strict_types=1);

/*
 * Página de error independiente: no consulta la base de datos para poder
 * mostrarse incluso cuando la falla es de conexión.
 * Variables: $status (int) y $message (string).
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= e($status) ?> · SIGEL</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/styles.css')) ?>">
</head>
<body>
<main>
    <h1>Error <?= e($status) ?></h1>
    <p><?= e($message) ?></p>
    <p><a href="<?= e(url()) ?>">Volver al inicio</a></p>
</main>
</body>
</html>

<?php

declare(strict_types=1);

$currentUser = current_user();
$flashes = pull_flashes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'SIGEL') ?> · SIGEL</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/styles.css')) ?>">
</head>
<body>
<header class="topbar">
    <strong>SIGEL</strong>
    <?php if ($currentUser !== null): ?>
        <nav>
            <a href="<?= e(url('pages/dashboard.php')) ?>">Inicio</a>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="<?= e(url('pages/categories/index.php')) ?>">Categorías</a>
            <?php endif; ?>
            <a href="<?= e(url('pages/equipment/index.php')) ?>">Equipos</a>
        </nav>
        <div class="session-info">
            <span><?= e($currentUser['full_name']) ?> (<?= e($currentUser['role_label']) ?>)</span>
            <form method="post" action="<?= e(url('actions/logout.php')) ?>" class="inline">
                <?= csrf_field() ?>
                <button type="submit">Cerrar sesión</button>
            </form>
        </div>
    <?php endif; ?>
</header>
<main>
    <?php foreach ($flashes as $flash): ?>
        <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
    <?php endforeach; ?>

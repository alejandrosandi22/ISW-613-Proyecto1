<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/bootstrap.php';

if (current_user() !== null) {
    redirect('pages/dashboard.php');
}

$old = pull_old_input();
$pageTitle = 'Iniciar sesión';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Iniciar sesión</h1>

<form method="post" action="<?= e(url('actions/login.php')) ?>" class="form">
    <?= csrf_field() ?>

    <label for="email">Correo electrónico</label>
    <input type="email" id="email" name="email" maxlength="150" required autofocus
           autocomplete="username" value="<?= e($old['email'] ?? '') ?>">

    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" maxlength="255" required
           autocomplete="current-password">

    <label>
        <input type="checkbox" name="remember" value="1">
        Recordarme en este equipo (<?= e(config('security.remember_me_days', 14)) ?> días)
    </label>

    <button type="submit">Ingresar</button>
</form>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

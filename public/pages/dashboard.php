<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/bootstrap.php';

$user = require_login();
$pageTitle = 'Inicio';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Bienvenido, <?= e($user['full_name']) ?></h1>

<p>Rol: <strong><?= e($user['role_label']) ?></strong></p>

<?php if ($user['role'] === 'admin'): ?>
    <p>Puede gestionar usuarios, categorías y equipos, y consultar los reportes.</p>
<?php else: ?>
    <p>Puede registrar y gestionar préstamos, y consultar los equipos.</p>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

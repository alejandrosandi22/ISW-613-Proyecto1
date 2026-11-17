<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/users.php';

$currentAdmin = require_role('admin');

$userId = to_id(query_string('id'));
$targetUser = $userId === null ? null : find_user($userId);

if ($targetUser === null) {
    render_error_page(404, 'El usuario no existe o fue eliminado.');
}

$pageTitle = 'Eliminar usuario';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Eliminar usuario</h1>

<?php if ($userId === $currentAdmin['id']): ?>
    <p class="alert alert-error">No puede eliminar su propia cuenta.</p>
    <p><a href="<?= e(url('pages/users/index.php')) ?>">Volver</a></p>
<?php else: ?>
    <p>¿Está seguro de eliminar al usuario «<?= e($targetUser['full_name']) ?>» (<?= e($targetUser['email']) ?>)?</p>
    <form method="post" action="<?= e(url('actions/users/delete.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($userId) ?>">
        <button type="submit">Sí, eliminar</button>
        <a href="<?= e(url('pages/users/index.php')) ?>">Cancelar</a>
    </form>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

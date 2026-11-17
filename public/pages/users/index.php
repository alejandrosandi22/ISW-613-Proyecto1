<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/users.php';

$currentAdmin = require_role('admin');

$search = mb_substr(query_string('q'), 0, 100);
$perPage = resolve_per_page('users');
$pagination = paginate(count_users($search), $perPage);
$users = list_users($search, $perPage, $pagination['offset']);

$listPath = 'pages/users/index.php';
$listParams = ['q' => $search, 'per_page' => $perPage];
$pageTitle = 'Usuarios';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Usuarios</h1>

<p><a href="<?= e(url('pages/users/create.php')) ?>">+ Nuevo usuario</a></p>

<form method="get" action="<?= e(url($listPath)) ?>" class="search">
    <label for="q">Buscar por nombre o correo</label>
    <input type="search" id="q" name="q" maxlength="100" value="<?= e($search) ?>">
    <?php require ROOT_PATH . '/includes/templates/per_page_select.php'; ?>
    <button type="submit">Buscar</button>
    <?php if ($search !== ''): ?>
        <a href="<?= e(url($listPath)) ?>">Limpiar</a>
    <?php endif; ?>
</form>

<?php if ($users === []): ?>
    <p>No se encontraron usuarios.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Último ingreso</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $listedUser): ?>
            <?php $isSelf = $listedUser['id'] === $currentAdmin['id']; ?>
            <tr>
                <td><?= e($listedUser['full_name']) ?><?= $isSelf ? ' (usted)' : '' ?></td>
                <td><?= e($listedUser['email']) ?></td>
                <td><?= e($listedUser['role_label']) ?></td>
                <td>
                    <?= $listedUser['is_active'] === 1 ? 'Activo' : 'Inactivo' ?>
                    <?php if ($listedUser['is_locked'] === 1): ?>
                        · Bloqueado
                        <form method="post" action="<?= e(url('actions/users/unlock.php')) ?>" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($listedUser['id']) ?>">
                            <button type="submit">Desbloquear</button>
                        </form>
                    <?php endif; ?>
                </td>
                <td><?= e($listedUser['last_login_at'] ?? 'Nunca') ?></td>
                <td>
                    <a href="<?= e(url('pages/users/edit.php?id=' . $listedUser['id'])) ?>">Editar</a>
                    <?php if (!$isSelf): ?>
                        <a href="<?= e(url('pages/users/delete.php?id=' . $listedUser['id'])) ?>">Eliminar</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require ROOT_PATH . '/includes/templates/pagination.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

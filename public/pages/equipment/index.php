<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';

$user = require_role('admin', 'operator');
$isAdmin = $user['role'] === 'admin';

$search = mb_substr(query_string('q'), 0, 100);
$categoryId = to_id(query_string('category'));
$perPage = resolve_per_page('equipment');
$pagination = paginate(count_equipment($search, $categoryId), $perPage);
$equipmentList = list_equipment($search, $categoryId, $perPage, $pagination['offset']);
$categoryOptions = list_category_options();
$recentEquipment = list_recent_equipment();

$listPath = 'pages/equipment/index.php';
$listParams = ['q' => $search, 'category' => $categoryId, 'per_page' => $perPage];
$pageTitle = 'Equipos';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Equipos</h1>

<?php if ($isAdmin): ?>
    <p><a href="<?= e(url('pages/equipment/create.php')) ?>">+ Nuevo equipo</a></p>
<?php endif; ?>

<form method="get" action="<?= e(url($listPath)) ?>" class="search">
    <label for="q">Buscar por código o nombre</label>
    <input type="search" id="q" name="q" maxlength="100" value="<?= e($search) ?>">

    <label for="category">Categoría</label>
    <select id="category" name="category">
        <option value="">Todas</option>
        <?php foreach ($categoryOptions as $option): ?>
            <option value="<?= e($option['id']) ?>" <?= $option['id'] === $categoryId ? 'selected' : '' ?>>
                <?= e($option['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php require ROOT_PATH . '/includes/templates/per_page_select.php'; ?>
    <button type="submit">Buscar</button>
    <?php if ($search !== '' || $categoryId !== null): ?>
        <a href="<?= e(url($listPath)) ?>">Limpiar</a>
    <?php endif; ?>
</form>

<?php if ($recentEquipment !== []): ?>
    <p class="recent">
        Consultados recientemente:
        <?php foreach ($recentEquipment as $recent): ?>
            <a href="<?= e(url('pages/equipment/show.php?id=' . $recent['id'])) ?>">
                <?= e($recent['code']) ?> · <?= e($recent['name']) ?>
            </a>
        <?php endforeach; ?>
    </p>
<?php endif; ?>

<?php if ($equipmentList === []): ?>
    <p>No se encontraron equipos.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($equipmentList as $item): ?>
            <tr>
                <td><?= e($item['code']) ?></td>
                <td><?= e($item['name']) ?></td>
                <td><?= e($item['category_name']) ?></td>
                <td><?= e(EQUIPMENT_STATUSES[$item['status']]) ?></td>
                <td>
                    <a href="<?= e(url('pages/equipment/show.php?id=' . $item['id'])) ?>">Ver</a>
                    <?php if ($isAdmin): ?>
                        <a href="<?= e(url('pages/equipment/edit.php?id=' . $item['id'])) ?>">Editar</a>
                        <a href="<?= e(url('pages/equipment/delete.php?id=' . $item['id'])) ?>">Eliminar</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require ROOT_PATH . '/includes/templates/pagination.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

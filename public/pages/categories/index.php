<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/categories.php';

require_role('admin');

$search = mb_substr(query_string('q'), 0, 100);
$perPage = resolve_per_page('categories');
$pagination = paginate(count_categories($search), $perPage);
$categories = list_categories($search, $perPage, $pagination['offset']);

$listPath = 'pages/categories/index.php';
$listParams = ['q' => $search, 'per_page' => $perPage];
$pageTitle = 'Categorías';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Categorías</h1>

<p><a href="<?= e(url('pages/categories/create.php')) ?>">+ Nueva categoría</a></p>

<form method="get" action="<?= e(url($listPath)) ?>" class="search">
    <label for="q">Buscar por nombre</label>
    <input type="search" id="q" name="q" maxlength="100" value="<?= e($search) ?>">
    <?php require ROOT_PATH . '/includes/templates/per_page_select.php'; ?>
    <button type="submit">Buscar</button>
    <?php if ($search !== ''): ?>
        <a href="<?= e(url($listPath)) ?>">Limpiar</a>
    <?php endif; ?>
</form>

<?php if ($categories === []): ?>
    <p>No se encontraron categorías.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Días máx.</th>
            <th>Equipos</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= e($category['name']) ?></td>
                <td><?= e($category['description'] ?? '') ?></td>
                <td><?= e($category['max_loan_days']) ?></td>
                <td><?= e($category['equipment_count']) ?></td>
                <td>
                    <a href="<?= e(url('pages/categories/edit.php?id=' . $category['id'])) ?>">Editar</a>
                    <a href="<?= e(url('pages/categories/delete.php?id=' . $category['id'])) ?>">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require ROOT_PATH . '/includes/templates/pagination.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

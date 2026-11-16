<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/categories.php';

require_role('admin');

$categoryId = to_id(query_string('id'));
$category = $categoryId === null ? null : find_category($categoryId);

if ($category === null) {
    render_error_page(404, 'La categoría no existe o fue eliminada.');
}

$equipmentCount = count_category_equipment($categoryId);
$pageTitle = 'Eliminar categoría';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Eliminar categoría</h1>

<?php if ($equipmentCount > 0): ?>
    <p class="alert alert-error">
        No se puede eliminar «<?= e($category['name']) ?>» porque tiene <?= e($equipmentCount) ?> equipo(s) asociados.
        Elimine o cambie de categoría esos equipos primero.
    </p>
    <p><a href="<?= e(url('pages/categories/index.php')) ?>">Volver</a></p>
<?php else: ?>
    <p>¿Está seguro de eliminar la categoría «<?= e($category['name']) ?>»?</p>
    <form method="post" action="<?= e(url('actions/categories/delete.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($categoryId) ?>">
        <button type="submit">Sí, eliminar</button>
        <a href="<?= e(url('pages/categories/index.php')) ?>">Cancelar</a>
    </form>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

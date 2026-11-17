<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';

require_role('admin');

$equipmentId = to_id(query_string('id'));
$equipment = $equipmentId === null ? null : find_equipment($equipmentId);

if ($equipment === null) {
    render_error_page(404, 'El equipo no existe o fue eliminado.');
}

$activeLoanCount = count_active_loans_for_equipment($equipmentId);
$pageTitle = 'Eliminar equipo';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Eliminar equipo</h1>

<?php if ($activeLoanCount > 0): ?>
    <p class="alert alert-error">
        No se puede eliminar «<?= e($equipment['code']) ?>» porque tiene <?= e($activeLoanCount) ?> préstamo(s) activo(s).
    </p>
    <p><a href="<?= e(url('pages/equipment/index.php')) ?>">Volver</a></p>
<?php else: ?>
    <p>¿Está seguro de eliminar el equipo «<?= e($equipment['code']) ?> · <?= e($equipment['name']) ?>»?</p>
    <form method="post" action="<?= e(url('actions/equipment/delete.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($equipmentId) ?>">
        <button type="submit">Sí, eliminar</button>
        <a href="<?= e(url('pages/equipment/index.php')) ?>">Cancelar</a>
    </form>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';

$user = require_role('admin', 'operator');

$equipmentId = to_id(query_string('id'));
$equipment = $equipmentId === null ? null : find_equipment($equipmentId);

if ($equipment === null) {
    render_error_page(404, 'El equipo no existe o fue eliminado.');
}

$activeLoans = list_active_loans_for_equipment($equipmentId);
$pageTitle = $equipment['code'];

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1><?= e($equipment['code']) ?> · <?= e($equipment['name']) ?></h1>

<table>
    <tr><th>Categoría</th><td><?= e($equipment['category_name']) ?></td></tr>
    <tr><th>Estado</th><td><?= e(EQUIPMENT_STATUSES[$equipment['status']]) ?></td></tr>
    <tr><th>Días máximos de préstamo</th><td><?= e($equipment['max_loan_days']) ?></td></tr>
    <tr><th>Descripción</th><td><?= e($equipment['description'] ?? '') ?></td></tr>
</table>

<h2>Préstamos activos</h2>
<?php if ($activeLoans === []): ?>
    <p>El equipo no tiene préstamos activos.</p>
<?php else: ?>
    <table>
        <thead>
        <tr><th>Préstamo</th><th>Solicitante</th><th>Estado</th><th>Inicio</th><th>Fin previsto</th></tr>
        </thead>
        <tbody>
        <?php foreach ($activeLoans as $loan): ?>
            <tr>
                <td>#<?= e($loan['id']) ?></td>
                <td><?= e($loan['borrower_name']) ?></td>
                <td><?= e(LOAN_STATUSES[$loan['status']]) ?></td>
                <td><?= e($loan['start_at']) ?></td>
                <td><?= e($loan['end_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p>
    <a href="<?= e(url('pages/equipment/index.php')) ?>">Volver al listado</a>
    <?php if ($user['role'] === 'admin'): ?>
        · <a href="<?= e(url('pages/equipment/edit.php?id=' . $equipmentId)) ?>">Editar</a>
    <?php endif; ?>
</p>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

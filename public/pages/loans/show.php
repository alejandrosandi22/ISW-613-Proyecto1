<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/loans.php';

$user = require_role('admin', 'operator');

$loanId = to_id(query_string('id'));
$loan = $loanId === null ? null : find_loan($loanId);

if ($loan === null) {
    render_error_page(404, 'El préstamo no existe.');
}

$items = list_loan_items($loanId);
$transitions = $user['role'] === 'operator' ? (LOAN_TRANSITIONS[$loan['status']] ?? []) : [];
$pageTitle = 'Préstamo #' . $loanId;

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Préstamo #<?= e($loanId) ?></h1>

<table>
    <tr>
        <th>Estado</th>
        <td>
            <?= e(LOAN_STATUSES[$loan['status']]) ?>
            <?= $loan['is_overdue'] === 1 ? '<strong>(vencido: no se ha devuelto)</strong>' : '' ?>
        </td>
    </tr>
    <tr><th>Solicitante</th><td><?= e($loan['borrower_name']) ?></td></tr>
    <tr><th>Cédula o carné</th><td><?= e($loan['borrower_document']) ?></td></tr>
    <tr><th>Inicio</th><td><?= e($loan['start_at']) ?></td></tr>
    <tr><th>Fin previsto</th><td><?= e($loan['end_at']) ?></td></tr>
    <tr><th>Registrado por</th><td><?= e($loan['registered_by']) ?> (<?= e($loan['created_at']) ?>)</td></tr>
    <tr><th>Aprobado por</th><td><?= e($loan['operator_name'] ?? '—') ?></td></tr>
    <tr><th>Observaciones</th><td><?= e($loan['notes'] ?? '') ?></td></tr>
</table>

<h2>Equipos</h2>
<table>
    <thead>
    <tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Devuelto</th></tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><a href="<?= e(url('pages/equipment/show.php?id=' . $item['id'])) ?>"><?= e($item['code']) ?></a></td>
            <td><?= e($item['name']) ?></td>
            <td><?= e($item['category_name']) ?></td>
            <td><?= e($item['returned_at'] ?? '—') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($transitions !== []): ?>
    <h2>Acciones</h2>
    <?php foreach ($transitions as $target): ?>
        <form method="post" action="<?= e(url('actions/loans/transition.php')) ?>" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($loanId) ?>">
            <input type="hidden" name="status" value="<?= e($target) ?>">
            <button type="submit"><?= e(LOAN_TRANSITION_LABELS[$target]) ?></button>
        </form>
    <?php endforeach; ?>
<?php endif; ?>

<p><a href="<?= e(url('pages/loans/index.php')) ?>">Volver al listado</a></p>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

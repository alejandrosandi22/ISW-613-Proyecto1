<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/loans.php';

$user = require_role('admin', 'operator');

$search = mb_substr(query_string('q'), 0, 100);
$status = query_string('status');
$status = array_key_exists($status, LOAN_STATUSES) ? $status : '';
$perPage = resolve_per_page('loans');
$pagination = paginate(count_loans($search, $status), $perPage);
$loans = list_loans($search, $status, $perPage, $pagination['offset']);

$listPath = 'pages/loans/index.php';
$listParams = ['q' => $search, 'status' => $status, 'per_page' => $perPage];
$pageTitle = 'Préstamos';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Préstamos</h1>

<?php if ($user['role'] === 'operator'): ?>
    <p><a href="<?= e(url('pages/loans/create.php')) ?>">+ Nuevo préstamo</a></p>
<?php endif; ?>

<form method="get" action="<?= e(url($listPath)) ?>" class="search">
    <label for="q">Buscar por solicitante o cédula</label>
    <input type="search" id="q" name="q" maxlength="100" value="<?= e($search) ?>">

    <label for="status">Estado</label>
    <select id="status" name="status">
        <option value="">Todos</option>
        <?php foreach (LOAN_STATUSES as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $value === $status ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>

    <?php require ROOT_PATH . '/includes/templates/per_page_select.php'; ?>
    <button type="submit">Buscar</button>
    <?php if ($search !== '' || $status !== ''): ?>
        <a href="<?= e(url($listPath)) ?>">Limpiar</a>
    <?php endif; ?>
</form>

<?php if ($loans === []): ?>
    <p>No se encontraron préstamos.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Solicitante</th>
            <th>Cédula o carné</th>
            <th>Equipos</th>
            <th>Inicio</th>
            <th>Fin previsto</th>
            <th>Estado</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($loans as $loan): ?>
            <tr>
                <td><?= e($loan['id']) ?></td>
                <td><?= e($loan['borrower_name']) ?></td>
                <td><?= e($loan['borrower_document']) ?></td>
                <td><?= e($loan['item_count']) ?></td>
                <td><?= e($loan['start_at']) ?></td>
                <td><?= e($loan['end_at']) ?></td>
                <td>
                    <?= e(LOAN_STATUSES[$loan['status']]) ?>
                    <?= $loan['is_overdue'] === 1 ? '<strong>(vencido)</strong>' : '' ?>
                </td>
                <td><a href="<?= e(url('pages/loans/show.php?id=' . $loan['id'])) ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require ROOT_PATH . '/includes/templates/pagination.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

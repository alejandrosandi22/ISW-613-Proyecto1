<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/loans.php';

require_role('operator');

$errors = pull_form_errors();
$loan = pull_old_input() + [
    'borrower_name'     => '',
    'borrower_document' => '',
    'start_at'          => date('Y-m-d\TH:i'),
    'end_at'            => date('Y-m-d\TH:i', strtotime('+1 day')),
    'notes'             => '',
    'equipment_ids'     => [],
];
$selectedIds = array_map('intval', (array) $loan['equipment_ids']);
$loanableEquipment = list_loanable_equipment();

// Los equipos consultados recientemente que se pueden prestar se muestran primero.
$recentIds = array_column(
    array_filter(list_recent_equipment(), static fn (array $item) => $item['status'] === 'available'),
    'id'
);
$recentGroup = array_values(array_filter($loanableEquipment, static fn (array $item) => in_array($item['id'], $recentIds, true)));
usort($recentGroup, static fn (array $a, array $b) => array_search($a['id'], $recentIds, true) <=> array_search($b['id'], $recentIds, true));
$otherGroup = array_values(array_filter($loanableEquipment, static fn (array $item) => !in_array($item['id'], $recentIds, true)));
$pageTitle = 'Nuevo préstamo';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Nuevo préstamo</h1>

<form method="post" action="<?= e(url('actions/loans/store.php')) ?>" class="form">
    <?= csrf_field() ?>

    <label for="borrower_name">Nombre del solicitante *</label>
    <input type="text" id="borrower_name" name="borrower_name" maxlength="100" required
           value="<?= e($loan['borrower_name']) ?>">
    <?= field_error($errors, 'borrower_name') ?>

    <label for="borrower_document">Cédula o carné *</label>
    <input type="text" id="borrower_document" name="borrower_document" maxlength="30" required
           value="<?= e($loan['borrower_document']) ?>">
    <?= field_error($errors, 'borrower_document') ?>

    <label for="start_at">Inicio *</label>
    <input type="datetime-local" id="start_at" name="start_at" required value="<?= e($loan['start_at']) ?>">
    <?= field_error($errors, 'start_at') ?>

    <label for="end_at">Fin previsto *</label>
    <input type="datetime-local" id="end_at" name="end_at" required value="<?= e($loan['end_at']) ?>">
    <?= field_error($errors, 'end_at') ?>

    <fieldset>
        <legend>Equipos * (máximo <?= e(MAX_EQUIPMENT_PER_LOAN) ?>)</legend>
        <?= field_error($errors, 'equipment_ids') ?>
        <?php if ($loanableEquipment === []): ?>
            <p>No hay equipos disponibles.</p>
        <?php endif; ?>
        <?php foreach (['Consultados recientemente' => $recentGroup, 'Todos los disponibles' => $otherGroup] as $groupTitle => $group): ?>
            <?php if ($group === []) {
                continue;
            } ?>
            <p><strong><?= e($groupTitle) ?></strong></p>
            <?php foreach ($group as $item): ?>
                <label>
                    <input type="checkbox" name="equipment_ids[]" value="<?= e($item['id']) ?>"
                        <?= in_array($item['id'], $selectedIds, true) ? 'checked' : '' ?>>
                    <?= e($item['code']) ?> · <?= e($item['name']) ?>
                    (<?= e($item['category_name']) ?>, máx. <?= e($item['max_loan_days']) ?> días)
                </label>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </fieldset>

    <label for="notes">Observaciones</label>
    <textarea id="notes" name="notes" maxlength="500" rows="3"><?= e($loan['notes']) ?></textarea>
    <?= field_error($errors, 'notes') ?>

    <button type="submit">Registrar préstamo</button>
    <a href="<?= e(url('pages/loans/index.php')) ?>">Cancelar</a>
</form>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

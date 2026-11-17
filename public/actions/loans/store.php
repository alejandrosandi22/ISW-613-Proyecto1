<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/loans.php';

$user = require_role('operator');
require_post();
csrf_verify();

$input = [
    'borrower_name'     => post_string('borrower_name'),
    'borrower_document' => post_string('borrower_document'),
    'start_at'          => post_string('start_at'),
    'end_at'            => post_string('end_at'),
    'notes'             => post_string('notes'),
];
$equipmentIds = post_id_list('equipment_ids');

[$data, $errors] = validate_loan_input($input, $equipmentIds);

if ($errors === []) {
    $result = create_loan($user['id'], $data);
    $errors = $result['errors'] ?? [];
}

if ($errors !== []) {
    set_old_input($input + ['equipment_ids' => $equipmentIds ?? []]);
    set_form_errors($errors);
    redirect('pages/loans/create.php');
}

flash('success', 'Préstamo #' . $result['id'] . ' registrado como «Solicitado».');
redirect('pages/loans/show.php?id=' . $result['id']);

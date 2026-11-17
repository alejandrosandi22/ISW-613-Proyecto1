<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/loans.php';

$user = require_role('operator');
require_post();
csrf_verify();

$loanId = to_id(post_string('id'));
$target = post_string('status');

if ($loanId === null) {
    render_error_page(404, 'El préstamo no existe.');
}

if (!array_key_exists($target, LOAN_STATUSES)) {
    flash('error', 'Estado no válido.');
    redirect('pages/loans/show.php?id=' . $loanId);
}

$error = transition_loan($loanId, $target, $user['id']);

if ($error !== null) {
    flash('error', $error);
} else {
    flash('success', 'El préstamo pasó a «' . LOAN_STATUSES[$target] . '».');
}

redirect('pages/loans/show.php?id=' . $loanId);

<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';

require_role('admin');

$errors = pull_form_errors();
$category = pull_old_input() + ['name' => '', 'description' => '', 'max_loan_days' => '7'];

$formAction = url('actions/categories/store.php');
$submitLabel = 'Guardar';
$pageTitle = 'Nueva categoría';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Nueva categoría</h1>
<?php require ROOT_PATH . '/includes/templates/categories/form.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

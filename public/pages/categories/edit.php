<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/categories.php';

require_role('admin');

$categoryId = to_id(query_string('id'));
$existing = $categoryId === null ? null : find_category($categoryId);

if ($existing === null) {
    render_error_page(404, 'La categoría no existe o fue eliminada.');
}

$errors = pull_form_errors();
// Tras un error de validación se muestran los datos que el usuario envió.
$category = pull_old_input() ?: [
    'name'          => $existing['name'],
    'description'   => $existing['description'] ?? '',
    'max_loan_days' => (string) $existing['max_loan_days'],
];

$formAction = url('actions/categories/update.php');
$submitLabel = 'Guardar cambios';
$pageTitle = 'Editar categoría';

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1>Editar categoría</h1>
<?php require ROOT_PATH . '/includes/templates/categories/form.php'; ?>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>

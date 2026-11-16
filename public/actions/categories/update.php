<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/categories.php';

require_role('admin');
require_post();
csrf_verify();

$id = to_id(post_string('id'));
$existing = $id === null ? null : find_category($id);

if ($existing === null) {
    render_error_page(404, 'La categoría no existe o fue eliminada.');
}

$input = [
    'name'          => post_string('name'),
    'description'   => post_string('description'),
    'max_loan_days' => post_string('max_loan_days'),
];

[$data, $errors] = validate_category_input($input);

if ($errors === [] && category_name_exists($data['name'], $id)) {
    $errors['name'] = 'Ya existe una categoría con ese nombre.';
}

if ($errors !== []) {
    set_old_input($input);
    set_form_errors($errors);
    redirect('pages/categories/edit.php?id=' . $id);
}

try {
    update_category($id, $data);
} catch (PDOException $e) {
    if (!is_duplicate_key($e)) {
        throw $e;
    }
    set_old_input($input);
    set_form_errors(['name' => 'Ya existe una categoría con ese nombre.']);
    redirect('pages/categories/edit.php?id=' . $id);
}

audit_log('update', 'categories', $id, $existing, $data);

flash('success', 'Categoría actualizada correctamente.');
redirect('pages/categories/index.php');

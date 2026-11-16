<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/categories.php';

require_role('admin');
require_post();
csrf_verify();

$input = [
    'name'          => post_string('name'),
    'description'   => post_string('description'),
    'max_loan_days' => post_string('max_loan_days'),
];

[$data, $errors] = validate_category_input($input);

if ($errors === [] && category_name_exists($data['name'])) {
    $errors['name'] = 'Ya existe una categoría con ese nombre.';
}

if ($errors !== []) {
    set_old_input($input);
    set_form_errors($errors);
    redirect('pages/categories/create.php');
}

try {
    $id = create_category($data);
} catch (PDOException $e) {
    // Otro usuario pudo crear el mismo nombre entre la verificación y el INSERT.
    if (!is_duplicate_key($e)) {
        throw $e;
    }
    set_old_input($input);
    set_form_errors(['name' => 'Ya existe una categoría con ese nombre.']);
    redirect('pages/categories/create.php');
}

audit_log('create', 'categories', $id, null, $data);

flash('success', 'Categoría creada correctamente.');
redirect('pages/categories/index.php');

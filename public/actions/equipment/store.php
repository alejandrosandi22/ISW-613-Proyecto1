<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';

require_role('admin');
require_post();
csrf_verify();

$input = [
    'code'        => post_string('code'),
    'name'        => post_string('name'),
    'category_id' => post_string('category_id'),
    'status'      => post_string('status'),
    'description' => post_string('description'),
];

[$data, $errors] = validate_equipment_input($input);

if (!isset($errors['code']) && equipment_code_exists($data['code'])) {
    $errors['code'] = 'Ya existe un equipo con ese código.';
}

if ($errors !== []) {
    set_old_input($input);
    set_form_errors($errors);
    redirect('pages/equipment/create.php');
}

try {
    $id = create_equipment($data);
} catch (PDOException $e) {
    if (!is_duplicate_key($e)) {
        throw $e;
    }
    set_old_input($input);
    set_form_errors(['code' => 'Ya existe un equipo con ese código.']);
    redirect('pages/equipment/create.php');
}

audit_log('create', 'equipment', $id, null, $data);

flash('success', 'Equipo creado correctamente.');
redirect('pages/equipment/show.php?id=' . $id);

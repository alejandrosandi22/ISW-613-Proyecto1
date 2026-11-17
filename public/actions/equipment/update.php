<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';

require_role('admin');
require_post();
csrf_verify();

$id = to_id(post_string('id'));
$existing = $id === null ? null : find_equipment($id);

if ($existing === null) {
    render_error_page(404, 'El equipo no existe o fue eliminado.');
}

$version = to_id(post_string('version'));

$input = [
    'code'        => post_string('code'),
    'name'        => post_string('name'),
    'category_id' => post_string('category_id'),
    'status'      => post_string('status'),
    'description' => post_string('description'),
];

[$data, $errors] = validate_equipment_input($input);

if (!isset($errors['code']) && equipment_code_exists($data['code'], $id)) {
    $errors['code'] = 'Ya existe un equipo con ese código.';
}

if ($errors !== []) {
    set_old_input($input);
    set_form_errors($errors);
    redirect('pages/equipment/edit.php?id=' . $id);
}

try {
    $saved = $version !== null && update_equipment($id, $version, $data);
} catch (PDOException $e) {
    if (!is_duplicate_key($e)) {
        throw $e;
    }
    set_old_input($input);
    set_form_errors(['code' => 'Ya existe un equipo con ese código.']);
    redirect('pages/equipment/edit.php?id=' . $id);
}

if (!$saved) {
    // Otro usuario guardó cambios después de que se abrió el formulario.
    flash('error', 'El equipo fue modificado por otro usuario. Revise los datos actuales y vuelva a intentarlo.');
    redirect('pages/equipment/edit.php?id=' . $id);
}

audit_log('update', 'equipment', $id, $existing, $data);

flash('success', 'Equipo actualizado correctamente.');
redirect('pages/equipment/show.php?id=' . $id);

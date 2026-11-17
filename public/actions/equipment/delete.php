<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';

require_role('admin');
require_post();
csrf_verify();

$id = to_id(post_string('id'));
if ($id === null) {
    render_error_page(404, 'El equipo no existe o fue eliminado.');
}

$pdo = db();
$pdo->beginTransaction();

try {
    // FOR UPDATE bloquea el equipo: el registro de préstamos también lo bloquea,
    // así no se puede crear un préstamo mientras se elimina.
    $equipment = db_query(
        'SELECT id, category_id, code, name, description, status FROM equipment
         WHERE id = :id AND deleted_at IS NULL FOR UPDATE',
        [':id' => $id]
    )->fetch();

    if ($equipment === false) {
        $pdo->rollBack();
        render_error_page(404, 'El equipo no existe o fue eliminado.');
    }

    if (count_active_loans_for_equipment($id) > 0) {
        $pdo->rollBack();
        flash('error', 'No se puede eliminar un equipo con préstamos activos.');
        redirect('pages/equipment/index.php');
    }

    db_query('UPDATE equipment SET deleted_at = NOW() WHERE id = :id', [':id' => $id]);
    audit_log('delete', 'equipment', $id, $equipment);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

flash('success', 'Equipo eliminado correctamente.');
redirect('pages/equipment/index.php');

<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/categories.php';

require_role('admin');
require_post();
csrf_verify();

$id = to_id(post_string('id'));
if ($id === null) {
    render_error_page(404, 'La categoría no existe o fue eliminada.');
}

$pdo = db();
$pdo->beginTransaction();

try {
    // FOR UPDATE bloquea la fila hasta el COMMIT para que nadie la modifique mientras se verifica.
    $category = db_query(
        'SELECT id, name, description, max_loan_days FROM categories WHERE id = :id AND deleted_at IS NULL FOR UPDATE',
        [':id' => $id]
    )->fetch();

    if ($category === false) {
        $pdo->rollBack();
        render_error_page(404, 'La categoría no existe o fue eliminada.');
    }

    if (count_category_equipment($id) > 0) {
        $pdo->rollBack();
        flash('error', 'No se puede eliminar una categoría que tiene equipos asociados.');
        redirect('pages/categories/index.php');
    }

    db_query('UPDATE categories SET deleted_at = NOW() WHERE id = :id', [':id' => $id]);
    audit_log('delete', 'categories', $id, $category);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

flash('success', 'Categoría eliminada correctamente.');
redirect('pages/categories/index.php');

<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/users.php';

$currentAdmin = require_role('admin');
require_post();
csrf_verify();

$id = to_id(post_string('id'));
$targetUser = $id === null ? null : find_user($id);

if ($targetUser === null) {
    render_error_page(404, 'El usuario no existe o fue eliminado.');
}

if ($id === $currentAdmin['id']) {
    flash('error', 'No puede eliminar su propia cuenta.');
    redirect('pages/users/index.php');
}

db_query('UPDATE users SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL', [':id' => $id]);
revoke_user_tokens($id);
audit_log('delete', 'users', $id, user_audit_values($targetUser));

flash('success', 'Usuario eliminado correctamente.');
redirect('pages/users/index.php');

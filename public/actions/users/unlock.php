<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/users.php';

require_role('admin');
require_post();
csrf_verify();

$id = to_id(post_string('id'));
$targetUser = $id === null ? null : find_user($id);

if ($targetUser === null) {
    render_error_page(404, 'El usuario no existe o fue eliminado.');
}

db_query('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id', [':id' => $id]);
audit_log('unlock', 'users', $id);

flash('success', 'Cuenta de «' . $targetUser['full_name'] . '» desbloqueada.');
redirect('pages/users/index.php');

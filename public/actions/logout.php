<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/bootstrap.php';

require_post();
csrf_verify();

logout_user();
restart_session();

flash('success', 'Sesión cerrada correctamente.');
redirect('pages/login.php');

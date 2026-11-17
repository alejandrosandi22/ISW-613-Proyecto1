<?php

declare(strict_types=1);

/*
 * Listas blancas de estados con su nombre visible en español.
 */

/** Estado físico de un equipo. */
const EQUIPMENT_STATUSES = [
    'available'   => 'Disponible',
    'maintenance' => 'En mantenimiento',
    'retired'     => 'Retirado',
];

/** Estados de un préstamo. */
const LOAN_STATUSES = [
    'requested' => 'Solicitado',
    'approved'  => 'Aprobado',
    'delivered' => 'Entregado',
    'returned'  => 'Devuelto',
    'cancelled' => 'Cancelado',
];

/** Estados de préstamo que mantienen ocupado un equipo (valores fijos, no datos del usuario). */
const ACTIVE_LOAN_STATUSES_SQL = "'requested', 'approved', 'delivered'";

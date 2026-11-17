<?php

declare(strict_types=1);

/*
 * Cookie con los equipos consultados recientemente (hasta 5 IDs, el más reciente primero).
 * No es sensible, pero es entrada no confiable: el usuario puede editarla en el navegador.
 * Por eso cada valor se valida como ID y se confirma contra la base antes de mostrarlo.
 */

const MAX_RECENT_EQUIPMENT = 5;

function recent_equipment_cookie_name(): string
{
    return (string) config('cookies.recent_equipment', 'sigel_recent_equipment');
}

/**
 * IDs válidos de la cookie; descarta cualquier valor que no sea un entero positivo.
 */
function recent_equipment_ids(): array
{
    $value = $_COOKIE[recent_equipment_cookie_name()] ?? '';
    if (!is_string($value) || $value === '') {
        return [];
    }

    $ids = [];
    foreach (explode(',', $value) as $part) {
        $id = to_id($part);
        if ($id !== null && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
        if (count($ids) === MAX_RECENT_EQUIPMENT) {
            break;
        }
    }

    return $ids;
}

/**
 * Registra la consulta de un equipo: lo pone primero y conserva como máximo 5.
 */
function remember_recent_equipment(int $equipmentId): void
{
    $ids = array_values(array_diff(recent_equipment_ids(), [$equipmentId]));
    array_unshift($ids, $equipmentId);
    $ids = array_slice($ids, 0, MAX_RECENT_EQUIPMENT);

    set_app_cookie(recent_equipment_cookie_name(), implode(',', $ids), time() + 60 * 60 * 24 * 30);
}

/**
 * Equipos recientes que siguen existiendo, en el orden de la cookie.
 */
function list_recent_equipment(): array
{
    $ids = recent_equipment_ids();
    if ($ids === []) {
        return [];
    }

    $placeholders = [];
    $params = [];
    foreach ($ids as $i => $id) {
        $placeholders[] = ":id{$i}";
        $params[":id{$i}"] = $id;
    }

    $rows = db_query(
        'SELECT e.id, e.code, e.name, e.status, c.name AS category_name, c.max_loan_days
         FROM equipment e
         JOIN categories c ON c.id = e.category_id
         WHERE e.deleted_at IS NULL AND e.id IN (' . implode(', ', $placeholders) . ')',
        $params
    )->fetchAll();

    $byId = array_column($rows, null, 'id');

    return array_values(array_filter(array_map(static fn (int $id) => $byId[$id] ?? null, $ids)));
}

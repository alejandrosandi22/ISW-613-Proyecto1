<?php

declare(strict_types=1);

/*
 * Módulo de equipos: validación y acceso a datos.
 * Todas las consultas filtran deleted_at IS NULL (eliminación lógica).
 */

/**
 * Valida los datos del formulario. Devuelve [datos limpios, errores].
 */
function validate_equipment_input(array $input): array
{
    $errors = [];
    $code = strtoupper($input['code']);

    validate_text($errors, 'code', $code, 'Código', 2, 30);
    if (!isset($errors['code']) && preg_match('/^[A-Z0-9-]+$/', $code) !== 1) {
        $errors['code'] = 'El código solo puede contener letras, números y guiones.';
    }

    validate_text($errors, 'name', $input['name'], 'Nombre', 2, 100);
    validate_text($errors, 'description', $input['description'], 'Descripción', 0, 500);

    $categoryId = to_id($input['category_id']);
    if ($categoryId === null || find_active_category_name($categoryId) === null) {
        $errors['category_id'] = 'Seleccione una categoría válida.';
    }

    if (!array_key_exists($input['status'], EQUIPMENT_STATUSES)) {
        $errors['status'] = 'Seleccione un estado válido.';
    }

    $data = [
        'category_id' => $categoryId,
        'code'        => $code,
        'name'        => $input['name'],
        'description' => $input['description'] === '' ? null : $input['description'],
        'status'      => $input['status'],
    ];

    return [$data, $errors];
}

function find_active_category_name(int $categoryId): ?string
{
    $name = db_query(
        'SELECT name FROM categories WHERE id = :id AND deleted_at IS NULL',
        [':id' => $categoryId]
    )->fetchColumn();

    return $name === false ? null : $name;
}

/**
 * Categorías vigentes para los selectores.
 */
function list_category_options(): array
{
    return db_query('SELECT id, name FROM categories WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
}

function find_equipment(int $id): ?array
{
    $st = db_query(
        'SELECT e.id, e.category_id, e.code, e.name, e.description, e.status, e.version,
                c.name AS category_name, c.max_loan_days
         FROM equipment e
         JOIN categories c ON c.id = e.category_id
         WHERE e.id = :id AND e.deleted_at IS NULL',
        [':id' => $id]
    );

    return $st->fetch() ?: null;
}

/**
 * El código es único en toda la tabla, incluidos los equipos eliminados.
 */
function equipment_code_exists(string $code, ?int $exceptId = null): bool
{
    $st = db_query(
        'SELECT COUNT(*) FROM equipment WHERE code = :code AND id <> :except_id',
        [':code' => $code, ':except_id' => $exceptId ?? 0]
    );

    return (int) $st->fetchColumn() > 0;
}

function count_active_loans_for_equipment(int $equipmentId): int
{
    return (int) db_query(
        'SELECT COUNT(*)
         FROM loan_items li
         JOIN loans l ON l.id = li.loan_id
         WHERE li.equipment_id = :id AND l.status IN (' . ACTIVE_LOAN_STATUSES_SQL . ')',
        [':id' => $equipmentId]
    )->fetchColumn();
}

/**
 * Préstamos activos del equipo, para la página de detalle.
 */
function list_active_loans_for_equipment(int $equipmentId): array
{
    return db_query(
        'SELECT l.id, l.borrower_name, l.status, l.start_at, l.end_at
         FROM loan_items li
         JOIN loans l ON l.id = li.loan_id
         WHERE li.equipment_id = :id AND l.status IN (' . ACTIVE_LOAN_STATUSES_SQL . ')
         ORDER BY l.start_at',
        [':id' => $equipmentId]
    )->fetchAll();
}

/**
 * Condición WHERE y parámetros del listado según los filtros.
 */
function equipment_list_filter(string $search, ?int $categoryId): array
{
    $where = 'e.deleted_at IS NULL';
    $params = [];

    if ($search !== '') {
        // Con sentencias preparadas nativas un mismo marcador no puede repetirse.
        $where .= ' AND (e.code LIKE :search_code OR e.name LIKE :search_name)';
        $params[':search_code'] = like_pattern($search);
        $params[':search_name'] = like_pattern($search);
    }

    if ($categoryId !== null) {
        $where .= ' AND e.category_id = :category_id';
        $params[':category_id'] = $categoryId;
    }

    return [$where, $params];
}

function count_equipment(string $search, ?int $categoryId): int
{
    [$where, $params] = equipment_list_filter($search, $categoryId);

    return (int) db_query("SELECT COUNT(*) FROM equipment e WHERE {$where}", $params)->fetchColumn();
}

function list_equipment(string $search, ?int $categoryId, int $limit, int $offset): array
{
    [$where, $params] = equipment_list_filter($search, $categoryId);
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    return db_query(
        "SELECT e.id, e.code, e.name, e.status, c.name AS category_name
         FROM equipment e
         JOIN categories c ON c.id = e.category_id
         WHERE {$where}
         ORDER BY e.code
         LIMIT :limit OFFSET :offset",
        $params
    )->fetchAll();
}

function create_equipment(array $data): int
{
    db_query(
        'INSERT INTO equipment (category_id, code, name, description, status)
         VALUES (:category_id, :code, :name, :description, :status)',
        [
            ':category_id' => $data['category_id'],
            ':code'        => $data['code'],
            ':name'        => $data['name'],
            ':description' => $data['description'],
            ':status'      => $data['status'],
        ]
    );

    return (int) db()->lastInsertId();
}

/**
 * Actualiza con control de concurrencia optimista: solo se guarda si la versión
 * no cambió desde que se abrió el formulario. Devuelve false si hubo conflicto.
 */
function update_equipment(int $id, int $version, array $data): bool
{
    $st = db_query(
        'UPDATE equipment
         SET category_id = :category_id, code = :code, name = :name,
             description = :description, status = :status, version = version + 1
         WHERE id = :id AND version = :version AND deleted_at IS NULL',
        [
            ':category_id' => $data['category_id'],
            ':code'        => $data['code'],
            ':name'        => $data['name'],
            ':description' => $data['description'],
            ':status'      => $data['status'],
            ':id'          => $id,
            ':version'     => $version,
        ]
    );

    return $st->rowCount() === 1;
}

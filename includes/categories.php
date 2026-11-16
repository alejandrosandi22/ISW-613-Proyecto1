<?php

declare(strict_types=1);

/*
 * Módulo de categorías: validación y acceso a datos.
 * Todas las consultas filtran deleted_at IS NULL (eliminación lógica).
 */

/**
 * Valida los datos del formulario. Devuelve [datos limpios, errores].
 */
function validate_category_input(array $input): array
{
    $errors = [];

    validate_text($errors, 'name', $input['name'], 'Nombre', 2, 60);
    validate_text($errors, 'description', $input['description'], 'Descripción', 0, 255);
    $maxLoanDays = validate_int_range($errors, 'max_loan_days', $input['max_loan_days'], 'Días máximos de préstamo', 1, 60);

    $data = [
        'name'          => $input['name'],
        'description'   => $input['description'] === '' ? null : $input['description'],
        'max_loan_days' => $maxLoanDays,
    ];

    return [$data, $errors];
}

function find_category(int $id): ?array
{
    $st = db_query(
        'SELECT id, name, description, max_loan_days FROM categories WHERE id = :id AND deleted_at IS NULL',
        [':id' => $id]
    );

    return $st->fetch() ?: null;
}

/**
 * El nombre es único en toda la tabla, incluidas las categorías eliminadas.
 */
function category_name_exists(string $name, ?int $exceptId = null): bool
{
    $st = db_query(
        'SELECT COUNT(*) FROM categories WHERE name = :name AND id <> :except_id',
        [':name' => $name, ':except_id' => $exceptId ?? 0]
    );

    return (int) $st->fetchColumn() > 0;
}

function count_category_equipment(int $categoryId): int
{
    $st = db_query(
        'SELECT COUNT(*) FROM equipment WHERE category_id = :id AND deleted_at IS NULL',
        [':id' => $categoryId]
    );

    return (int) $st->fetchColumn();
}

/**
 * Condición WHERE y parámetros del listado según la búsqueda.
 */
function category_list_filter(string $search): array
{
    $where = 'c.deleted_at IS NULL';
    $params = [];

    if ($search !== '') {
        $where .= ' AND c.name LIKE :search';
        $params[':search'] = like_pattern($search);
    }

    return [$where, $params];
}

function count_categories(string $search): int
{
    [$where, $params] = category_list_filter($search);

    return (int) db_query("SELECT COUNT(*) FROM categories c WHERE {$where}", $params)->fetchColumn();
}

function list_categories(string $search, int $limit, int $offset): array
{
    [$where, $params] = category_list_filter($search);
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    return db_query(
        "SELECT c.id, c.name, c.description, c.max_loan_days,
                (SELECT COUNT(*) FROM equipment e WHERE e.category_id = c.id AND e.deleted_at IS NULL) AS equipment_count
         FROM categories c
         WHERE {$where}
         ORDER BY c.name
         LIMIT :limit OFFSET :offset",
        $params
    )->fetchAll();
}

function create_category(array $data): int
{
    db_query(
        'INSERT INTO categories (name, description, max_loan_days) VALUES (:name, :description, :max_loan_days)',
        [':name' => $data['name'], ':description' => $data['description'], ':max_loan_days' => $data['max_loan_days']]
    );

    return (int) db()->lastInsertId();
}

function update_category(int $id, array $data): void
{
    db_query(
        'UPDATE categories SET name = :name, description = :description, max_loan_days = :max_loan_days
         WHERE id = :id AND deleted_at IS NULL',
        [
            ':name'          => $data['name'],
            ':description'   => $data['description'],
            ':max_loan_days' => $data['max_loan_days'],
            ':id'            => $id,
        ]
    );
}

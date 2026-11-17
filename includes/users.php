<?php

declare(strict_types=1);

/*
 * Módulo de usuarios: validación y acceso a datos.
 * Todas las consultas filtran deleted_at IS NULL (eliminación lógica).
 * El hash de la contraseña nunca se devuelve en los listados ni se guarda en la bitácora.
 */

function list_roles(): array
{
    return db_query('SELECT id, name, label FROM roles ORDER BY id')->fetchAll();
}

/**
 * Valida los datos del formulario. Devuelve [datos limpios, errores].
 * Al editar ($isNew = false) la contraseña es opcional: vacía conserva la actual.
 */
function validate_user_input(array $input, bool $isNew): array
{
    $errors = [];

    validate_text($errors, 'full_name', $input['full_name'], 'Nombre completo', 2, 100);

    $email = mb_strtolower($input['email']);
    validate_text($errors, 'email', $email, 'Correo electrónico', 5, 150);
    if (!isset($errors['email']) && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Ingrese un correo electrónico válido.';
    }

    $roleId = to_id($input['role_id']);
    $validRoleIds = array_column(list_roles(), 'id');
    if ($roleId === null || !in_array($roleId, $validRoleIds, true)) {
        $errors['role_id'] = 'Seleccione un rol válido.';
    }

    if ($isNew || $input['password'] !== '') {
        validate_password($errors, $input['password'], $input['password_confirmation']);
    }

    $data = [
        'full_name' => $input['full_name'],
        'email'     => $email,
        'role_id'   => $roleId,
        'is_active' => $input['is_active'] === '1' ? 1 : 0,
        'password'  => $input['password'],
    ];

    return [$data, $errors];
}

/**
 * Política de contraseñas: 8 a 72 caracteres (límite de bcrypt), al menos una letra y un número.
 */
function validate_password(array &$errors, string $password, string $confirmation): void
{
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors['password'] = 'La contraseña debe tener entre 8 y 72 caracteres.';
    } elseif (preg_match('/\pL/u', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
        $errors['password'] = 'La contraseña debe incluir al menos una letra y un número.';
    } elseif (!hash_equals($password, $confirmation)) {
        $errors['password_confirmation'] = 'La confirmación no coincide con la contraseña.';
    }
}

function find_user(int $id): ?array
{
    $st = db_query(
        'SELECT u.id, u.role_id, u.full_name, u.email, u.is_active, u.failed_attempts, u.locked_until,
                (u.locked_until IS NOT NULL AND u.locked_until > NOW()) AS is_locked,
                r.name AS role, r.label AS role_label
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id AND u.deleted_at IS NULL',
        [':id' => $id]
    );

    return $st->fetch() ?: null;
}

/**
 * El correo es único en toda la tabla, incluidos los usuarios eliminados.
 */
function user_email_exists(string $email, ?int $exceptId = null): bool
{
    $st = db_query(
        'SELECT COUNT(*) FROM users WHERE email = :email AND id <> :except_id',
        [':email' => $email, ':except_id' => $exceptId ?? 0]
    );

    return (int) $st->fetchColumn() > 0;
}

function user_list_filter(string $search): array
{
    $where = 'u.deleted_at IS NULL';
    $params = [];

    if ($search !== '') {
        $where .= ' AND (u.full_name LIKE :search_name OR u.email LIKE :search_email)';
        $params[':search_name'] = like_pattern($search);
        $params[':search_email'] = like_pattern($search);
    }

    return [$where, $params];
}

function count_users(string $search): int
{
    [$where, $params] = user_list_filter($search);

    return (int) db_query("SELECT COUNT(*) FROM users u WHERE {$where}", $params)->fetchColumn();
}

function list_users(string $search, int $limit, int $offset): array
{
    [$where, $params] = user_list_filter($search);
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    return db_query(
        "SELECT u.id, u.full_name, u.email, u.is_active, u.last_login_at,
                (u.locked_until IS NOT NULL AND u.locked_until > NOW()) AS is_locked,
                r.label AS role_label
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE {$where}
         ORDER BY u.full_name
         LIMIT :limit OFFSET :offset",
        $params
    )->fetchAll();
}

function create_user(array $data): int
{
    db_query(
        'INSERT INTO users (role_id, full_name, email, password_hash, is_active)
         VALUES (:role_id, :full_name, :email, :password_hash, :is_active)',
        [
            ':role_id'       => $data['role_id'],
            ':full_name'     => $data['full_name'],
            ':email'         => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':is_active'     => $data['is_active'],
        ]
    );

    return (int) db()->lastInsertId();
}

/**
 * Actualiza el usuario. La contraseña solo cambia si se envió una nueva.
 */
function update_user(int $id, array $data): void
{
    $sql = 'UPDATE users SET role_id = :role_id, full_name = :full_name, email = :email, is_active = :is_active';
    $params = [
        ':role_id'   => $data['role_id'],
        ':full_name' => $data['full_name'],
        ':email'     => $data['email'],
        ':is_active' => $data['is_active'],
        ':id'        => $id,
    ];

    if ($data['password'] !== '') {
        $sql .= ', password_hash = :password_hash';
        $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    db_query($sql . ' WHERE id = :id AND deleted_at IS NULL', $params);
}

/**
 * Revoca todos los tokens «Recordarme» del usuario.
 */
function revoke_user_tokens(int $userId): void
{
    db_query('DELETE FROM auth_tokens WHERE user_id = :id', [':id' => $userId]);
}

/**
 * Datos del usuario para la bitácora (sin contraseña).
 */
function user_audit_values(array $user): array
{
    return [
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role_id'   => $user['role_id'],
        'is_active' => (int) $user['is_active'],
    ];
}

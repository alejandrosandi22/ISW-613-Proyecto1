<?php

declare(strict_types=1);

/*
 * Módulo de préstamos: validación, consultas, registro transaccional y cambios de estado.
 */

const MAX_EQUIPMENT_PER_LOAN = 10;

/** Cambios de estado permitidos: estado actual => estados a los que puede pasar. */
const LOAN_TRANSITIONS = [
    'requested' => ['approved', 'cancelled'],
    'approved'  => ['delivered', 'cancelled'],
    'delivered' => ['returned'],
];

/** Texto del botón de cada cambio de estado. */
const LOAN_TRANSITION_LABELS = [
    'approved'  => 'Aprobar',
    'delivered' => 'Registrar entrega',
    'returned'  => 'Registrar devolución',
    'cancelled' => 'Cancelar préstamo',
];

/**
 * Convierte el valor de un campo datetime-local ('2026-11-20T08:30') a 'Y-m-d H:i:s'.
 * Devuelve null si el formato o la fecha no son válidos.
 */
function parse_datetime_local(string $value): ?string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);

    if ($date === false || $date->format('Y-m-d\TH:i') !== $value) {
        return null;
    }

    return $date->format('Y-m-d H:i:s');
}

/**
 * Lista de IDs enviada como arreglo (name="equipment_ids[]"). Devuelve null si algún valor no es válido.
 */
function post_id_list(string $key): ?array
{
    $values = $_POST[$key] ?? [];
    if (!is_array($values)) {
        return null;
    }

    $ids = [];
    foreach ($values as $value) {
        $id = is_string($value) ? to_id($value) : null;
        if ($id === null) {
            return null;
        }
        $ids[] = $id;
    }

    return array_values(array_unique($ids));
}

/**
 * Validación de formato (sin consultar la base). Devuelve [datos limpios, errores].
 */
function validate_loan_input(array $input, ?array $equipmentIds): array
{
    $errors = [];

    validate_text($errors, 'borrower_name', $input['borrower_name'], 'Nombre del solicitante', 2, 100);
    validate_text($errors, 'borrower_document', $input['borrower_document'], 'Cédula o carné', 5, 30);
    if (!isset($errors['borrower_document']) && preg_match('/^[A-Za-z0-9-]+$/', $input['borrower_document']) !== 1) {
        $errors['borrower_document'] = 'La cédula o carné solo puede contener letras, números y guiones.';
    }
    validate_text($errors, 'notes', $input['notes'], 'Observaciones', 0, 500);

    $startAt = parse_datetime_local($input['start_at']);
    $endAt = parse_datetime_local($input['end_at']);

    if ($startAt === null) {
        $errors['start_at'] = 'Ingrese una fecha y hora de inicio válida.';
    } elseif (strtotime($startAt) < time() - 300) {
        $errors['start_at'] = 'La fecha de inicio no puede estar en el pasado.';
    }

    if ($endAt === null) {
        $errors['end_at'] = 'Ingrese una fecha y hora de fin válida.';
    } elseif ($startAt !== null && $endAt <= $startAt) {
        $errors['end_at'] = 'La fecha de fin debe ser posterior a la de inicio.';
    }

    if ($equipmentIds === null || $equipmentIds === []) {
        $errors['equipment_ids'] = 'Seleccione al menos un equipo.';
    } elseif (count($equipmentIds) > MAX_EQUIPMENT_PER_LOAN) {
        $errors['equipment_ids'] = 'Un préstamo puede incluir como máximo ' . MAX_EQUIPMENT_PER_LOAN . ' equipos.';
    }

    $data = [
        'borrower_name'     => $input['borrower_name'],
        'borrower_document' => strtoupper($input['borrower_document']),
        'start_at'          => $startAt,
        'end_at'            => $endAt,
        'notes'             => $input['notes'] === '' ? null : $input['notes'],
        'equipment_ids'     => $equipmentIds ?? [],
    ];

    return [$data, $errors];
}

/**
 * Registra un préstamo dentro de una transacción.
 * Devuelve ['id' => int] si se guardó o ['errors' => [...]] si se rechazó.
 */
function create_loan(int $userId, array $data): array
{
    $ids = $data['equipment_ids'];
    $placeholders = [];
    $idParams = [];
    foreach ($ids as $i => $id) {
        $placeholders[] = ":id{$i}";
        $idParams[":id{$i}"] = $id;
    }
    $inList = implode(', ', $placeholders);

    $pdo = db();
    $pdo->beginTransaction();

    try {
        // 1) Bloquea las filas de los equipos (en orden de ID para evitar interbloqueos).
        //    Otra transacción que intente prestar los mismos equipos espera aquí hasta el COMMIT,
        //    y al continuar ya ve el préstamo recién guardado en la verificación de traslape.
        $equipment = db_query(
            "SELECT e.id, e.code, e.status, c.max_loan_days
             FROM equipment e
             JOIN categories c ON c.id = e.category_id
             WHERE e.id IN ({$inList}) AND e.deleted_at IS NULL
             ORDER BY e.id
             FOR UPDATE",
            $idParams
        )->fetchAll();

        if (count($equipment) !== count($ids)) {
            $pdo->rollBack();
            return ['errors' => ['equipment_ids' => 'Uno o más equipos seleccionados no existen o fueron eliminados.']];
        }

        $unavailable = array_filter($equipment, static fn (array $item) => $item['status'] !== 'available');
        if ($unavailable !== []) {
            $pdo->rollBack();
            return ['errors' => ['equipment_ids' => 'No están disponibles: ' . implode(', ', array_column($unavailable, 'code')) . '.']];
        }

        // 2) Duración máxima: la de la categoría más restrictiva entre los equipos elegidos.
        $maxDays = min(array_column($equipment, 'max_loan_days'));
        if (strtotime($data['end_at']) - strtotime($data['start_at']) > $maxDays * 86400) {
            $pdo->rollBack();
            return ['errors' => ['end_at' => "La duración máxima para estos equipos es de {$maxDays} día(s)."]];
        }

        // 3) Traslape con préstamos activos del mismo equipo:
        //    existente.inicio < nuevo.fin AND existente.fin > nuevo.inicio
        $conflicts = db_query(
            "SELECT e.code, l.id AS loan_id, l.start_at, l.end_at
             FROM loan_items li
             JOIN loans l ON l.id = li.loan_id
             JOIN equipment e ON e.id = li.equipment_id
             WHERE li.equipment_id IN ({$inList})
               AND l.status IN (" . ACTIVE_LOAN_STATUSES_SQL . ")
               AND l.start_at < :new_end
               AND l.end_at > :new_start
             ORDER BY e.code",
            $idParams + [':new_start' => $data['start_at'], ':new_end' => $data['end_at']]
        )->fetchAll();

        if ($conflicts !== []) {
            $pdo->rollBack();
            $details = array_map(
                static fn (array $c) => "{$c['code']} (préstamo #{$c['loan_id']}: {$c['start_at']} a {$c['end_at']})",
                $conflicts
            );
            return ['errors' => ['equipment_ids' => 'Traslape con préstamos activos: ' . implode('; ', $details) . '.']];
        }

        // 4) Guarda el préstamo y su detalle.
        db_query(
            'INSERT INTO loans (user_id, borrower_name, borrower_document, status, start_at, end_at, notes)
             VALUES (:user_id, :borrower_name, :borrower_document, \'requested\', :start_at, :end_at, :notes)',
            [
                ':user_id'           => $userId,
                ':borrower_name'     => $data['borrower_name'],
                ':borrower_document' => $data['borrower_document'],
                ':start_at'          => $data['start_at'],
                ':end_at'            => $data['end_at'],
                ':notes'             => $data['notes'],
            ]
        );
        $loanId = (int) $pdo->lastInsertId();

        $itemStatement = db()->prepare('INSERT INTO loan_items (loan_id, equipment_id) VALUES (:loan_id, :equipment_id)');
        foreach ($ids as $equipmentId) {
            $itemStatement->execute([':loan_id' => $loanId, ':equipment_id' => $equipmentId]);
        }

        audit_log('create', 'loans', $loanId, null, $data);

        $pdo->commit();

        return ['id' => $loanId];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Cambia el estado de un préstamo si la transición está permitida.
 * Devuelve null si se aplicó o un mensaje de error.
 */
function transition_loan(int $loanId, string $target, int $userId): ?string
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // Bloquea el préstamo: dos operadores no pueden cambiar su estado al mismo tiempo.
        $loan = db_query('SELECT id, status FROM loans WHERE id = :id FOR UPDATE', [':id' => $loanId])->fetch();

        if ($loan === false) {
            $pdo->rollBack();
            return 'El préstamo no existe.';
        }

        $current = $loan['status'];
        if (!in_array($target, LOAN_TRANSITIONS[$current] ?? [], true)) {
            $pdo->rollBack();
            return 'No se puede pasar un préstamo de «' . LOAN_STATUSES[$current] . '» a «'
                . (LOAN_STATUSES[$target] ?? $target) . '».';
        }

        // Al aprobar o entregar, los equipos deben seguir disponibles.
        if ($target === 'approved' || $target === 'delivered') {
            $unavailable = db_query(
                "SELECT e.code
                 FROM loan_items li
                 JOIN equipment e ON e.id = li.equipment_id
                 WHERE li.loan_id = :id AND (e.status <> 'available' OR e.deleted_at IS NOT NULL)
                 ORDER BY e.id
                 FOR UPDATE",
                [':id' => $loanId]
            )->fetchAll(PDO::FETCH_COLUMN);

            if ($unavailable !== []) {
                $pdo->rollBack();
                return 'No están disponibles: ' . implode(', ', $unavailable) . '. Cancele el préstamo o espere a que estén disponibles.';
            }
        }

        if ($target === 'approved') {
            db_query(
                'UPDATE loans SET status = :status, operator_id = :operator_id WHERE id = :id',
                [':status' => $target, ':operator_id' => $userId, ':id' => $loanId]
            );
        } else {
            db_query('UPDATE loans SET status = :status WHERE id = :id', [':status' => $target, ':id' => $loanId]);
        }

        if ($target === 'returned') {
            db_query(
                'UPDATE loan_items SET returned_at = NOW() WHERE loan_id = :id AND returned_at IS NULL',
                [':id' => $loanId]
            );
        }

        audit_log('status_change', 'loans', $loanId, ['status' => $current], ['status' => $target]);

        $pdo->commit();

        return null;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function find_loan(int $id): ?array
{
    $st = db_query(
        'SELECT l.id, l.borrower_name, l.borrower_document, l.status, l.start_at, l.end_at, l.notes,
                l.created_at, u.full_name AS registered_by, o.full_name AS operator_name,
                (l.status = \'delivered\' AND l.end_at < NOW()) AS is_overdue
         FROM loans l
         JOIN users u ON u.id = l.user_id
         LEFT JOIN users o ON o.id = l.operator_id
         WHERE l.id = :id',
        [':id' => $id]
    );

    return $st->fetch() ?: null;
}

function list_loan_items(int $loanId): array
{
    return db_query(
        'SELECT e.id, e.code, e.name, c.name AS category_name, li.returned_at
         FROM loan_items li
         JOIN equipment e ON e.id = li.equipment_id
         JOIN categories c ON c.id = e.category_id
         WHERE li.loan_id = :id
         ORDER BY e.code',
        [':id' => $loanId]
    )->fetchAll();
}

/**
 * Equipos que se pueden incluir en un préstamo nuevo.
 */
function list_loanable_equipment(): array
{
    return db_query(
        "SELECT e.id, e.code, e.name, c.name AS category_name, c.max_loan_days
         FROM equipment e
         JOIN categories c ON c.id = e.category_id
         WHERE e.deleted_at IS NULL AND e.status = 'available'
         ORDER BY c.name, e.code"
    )->fetchAll();
}

function loan_list_filter(string $search, string $status): array
{
    $where = '1 = 1';
    $params = [];

    if ($search !== '') {
        $where .= ' AND (l.borrower_name LIKE :search_name OR l.borrower_document LIKE :search_document)';
        $params[':search_name'] = like_pattern($search);
        $params[':search_document'] = like_pattern($search);
    }

    if (array_key_exists($status, LOAN_STATUSES)) {
        $where .= ' AND l.status = :status';
        $params[':status'] = $status;
    }

    return [$where, $params];
}

function count_loans(string $search, string $status): int
{
    [$where, $params] = loan_list_filter($search, $status);

    return (int) db_query("SELECT COUNT(*) FROM loans l WHERE {$where}", $params)->fetchColumn();
}

function list_loans(string $search, string $status, int $limit, int $offset): array
{
    [$where, $params] = loan_list_filter($search, $status);
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    return db_query(
        "SELECT l.id, l.borrower_name, l.borrower_document, l.status, l.start_at, l.end_at,
                (l.status = 'delivered' AND l.end_at < NOW()) AS is_overdue,
                (SELECT COUNT(*) FROM loan_items li WHERE li.loan_id = l.id) AS item_count
         FROM loans l
         WHERE {$where}
         ORDER BY l.start_at DESC, l.id DESC
         LIMIT :limit OFFSET :offset",
        $params
    )->fetchAll();
}

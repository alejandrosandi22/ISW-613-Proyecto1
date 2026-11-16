<?php

declare(strict_types=1);

/**
 * Registra un evento en la bitácora (tabla de solo inserción).
 */
function audit_log(
    string $action,
    string $entity,
    ?int $entityId = null,
    ?array $oldValues = null,
    ?array $newValues = null,
    ?int $userId = null
): void {
    $userId ??= isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    $st = db()->prepare(
        'INSERT INTO audit_log (user_id, action, entity, entity_id, old_values, new_values, ip_address)
         VALUES (:user_id, :action, :entity, :entity_id, :old_values, :new_values, :ip)'
    );
    $st->execute([
        ':user_id'    => $userId,
        ':action'     => $action,
        ':entity'     => $entity,
        ':entity_id'  => $entityId,
        ':old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ':new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ':ip'         => client_ip(),
    ]);
}

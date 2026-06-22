<?php

declare(strict_types=1);

namespace App\Core;

class AuditLogger
{
    public function __construct(private Database $db) {}

    public function log(string $table, int $recordId, string $action = 'update', ?int $userId = null): void
    {
        $sql = "INSERT INTO `" . DB::AUDIT_LOG . "` (table_name, record_id, action, user_id, created_at)
                VALUES (:table_name, :record_id, :action, :user_id, NOW())";
        $this->db->execute($sql, [
            'table_name' => $table,
            'record_id' => $recordId,
            'action' => $action,
            'user_id' => $userId ?? 0,
        ]);
    }

    public function logEntityChange(string $entity, int $entityId, string $action, array $changes = []): void
    {
        $userId = 0;
        if (defined('PROJECT_PREFIX')) {
            $userId = (int)($_SESSION[PROJECT_PREFIX]['DASHBOARD']['user_id'] ?? $_SESSION[PROJECT_PREFIX]['DASHBOARD']['id'] ?? 0);
        }
        $sql = "INSERT INTO `" . DB::ENTITY_LOGS . "` (entity_type, entity_id, action, changes, user_id, created_at)
                VALUES (:entity_type, :entity_id, :action, :changes, :user_id, NOW())";
        $this->db->execute($sql, [
            'entity_type' => $entity,
            'entity_id' => $entityId,
            'action' => $action,
            'changes' => json_encode($changes),
            'user_id' => $userId,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use App\Core\DB;

final class SimpleCrudHandler
{
    private Database $db;
    private ?int $organizationId;

    public function __construct(Database $db, ?int $organizationId = null)
    {
        $this->db = $db;
        $this->organizationId = $organizationId;
    }

    public function findById(string $table, int $id): ?array
    {
        $sql = "SELECT * FROM `" . $table . "` WHERE id = :id";
        $params = ['id' => $id];

        if ($this->organizationId !== null && $this->tableHasColumn($table, 'organization_id')) {
            $sql .= " AND organization_id = :__org_id";
            $params['__org_id'] = $this->organizationId;
        }

        return $this->db->fetchOne($sql, $params);
    }

    public function create(string $table, array $columns, array $values, int $createdBy): string
    {
        if ($this->organizationId !== null && $this->tableHasColumn($table, 'organization_id')) {
            $columns[] = 'organization_id';
            $values[] = $this->organizationId;
        }
        $columns[] = 'created_by';
        $values[] = $createdBy;

        $placeholders = [];
        $params = [];
        foreach ($columns as $i => $col) {
            $key = 'c_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $values[$i];
        }

        $sql = "INSERT INTO `" . $table . "` (" . implode(', ', array_map(fn($c) => "`{$c}`", $columns)) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

        return $this->db->insert($sql, $params);
    }

    public function update(string $table, array $columns, array $values, int $id, int $updatedBy): bool
    {
        $columns[] = 'updated_by';
        $values[] = $updatedBy;

        if ($this->tableHasColumn($table, 'updated_at')) {
            $columns[] = 'updated_at';
            $values[] = date('Y-m-d H:i:s');
        }

        $sets = [];
        $params = [];
        foreach ($columns as $i => $col) {
            $key = 'u_' . $i;
            $sets[] = "`{$col}` = :{$key}";
            $params[$key] = $values[$i];
        }
        $params['__id'] = $id;

        $sql = "UPDATE `" . $table . "` SET " . implode(', ', $sets) . " WHERE id = :__id";

        try {
            $this->db->execute($sql, $params);
            return true;
        } catch (\Throwable $e) {
            error_log("SimpleCrudHandler: Update failed: " . $e->getMessage());
            return false;
        }
    }

    public function togglePublish(string $table, int $id, bool $publish, int $userId): bool
    {
        return $this->update($table, ['is_active'], [(int)$publish], $id, $userId);
    }

    public function toggleActive(string $table, int $id, bool $active, int $userId): bool
    {
        return $this->update($table, ['is_active'], [(int)$active], $id, $userId);
    }

    public function exists(string $table, string $column, string $value, ?int $excludeId = null, ?array $additionalConditions = null): bool
    {
        $sql = "SELECT 1 FROM `" . $table . "` WHERE `{$column}` = :val";
        $params = ['val' => $value];

        if ($excludeId !== null) {
            $sql .= " AND id != :__exclude_id";
            $params['__exclude_id'] = $excludeId;
        }

        if ($this->organizationId !== null && $this->tableHasColumn($table, 'organization_id')) {
            $sql .= " AND organization_id = :__org_id";
            $params['__org_id'] = $this->organizationId;
        }

        if (!empty($additionalConditions)) {
            foreach ($additionalConditions as $col => $val) {
                $key = 'cond_' . $col;
                $sql .= " AND `{$col}` = :{$key}";
                $params[$key] = $val;
            }
        }

        $sql .= " LIMIT 1";

        return $this->db->fetchOne($sql, $params) !== null;
    }

    public function count(string $table, array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM `" . $table . "`";
        $params = [];

        if ($this->organizationId !== null && $this->tableHasColumn($table, 'organization_id')) {
            $conditions['organization_id'] = $this->organizationId;
        }

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $col => $val) {
                $key = 'cond_' . $col;
                $clauses[] = "`{$col}` = :{$key}";
                $params[$key] = $val;
            }
            $sql .= " WHERE " . implode(' AND ', $clauses);
        }

        $row = $this->db->fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '.' . $column;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        try {
            $row = $this->db->fetchOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = :__table
                 AND COLUMN_NAME = :__column
                 LIMIT 1",
                ['__table' => $table, '__column' => $column]
            );
            $cache[$key] = $row !== null;
        } catch (\Throwable $e) {
            $cache[$key] = false;
        }

        return $cache[$key];
    }
}

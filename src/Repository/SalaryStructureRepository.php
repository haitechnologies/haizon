<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\SalaryStructure;

class SalaryStructureRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?SalaryStructure
    {
        $sql = "SELECT id, effective_from, description, created_by, created_at
                FROM `{DB::SALARY_STRUCTURES}` WHERE id = :id";
        $row = $this->db->fetchOne($sql, ['id' => $id]);
        return $row === null ? null : $this->mapRowToDto($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT id, effective_from, description, created_by, created_at
                FROM `{DB::SALARY_STRUCTURES}` ORDER BY effective_from ASC";
        return array_map($this->mapRowToDto(...), $this->db->fetchAll($sql));
    }

    public function exists(string $name, ?int $excludeId = null): bool
    {
        $sql = $excludeId !== null
            ? "SELECT id FROM `{DB::SALARY_STRUCTURES}` WHERE effective_from = :name AND id != :exclude_id LIMIT 1"
            : "SELECT id FROM `{DB::SALARY_STRUCTURES}` WHERE effective_from = :name LIMIT 1";
        $params = $excludeId !== null ? ['name' => $name, 'exclude_id' => $excludeId] : ['name' => $name];
        return $this->db->fetchOne($sql, $params) !== null;
    }

    public function insert(SalaryStructure $item): int
    {
        $sql = "INSERT INTO `{DB::SALARY_STRUCTURES}` (effective_from, description, created_by)
                VALUES (:effective_from, :description, :created_by)";
        return (int)$this->db->insert($sql, [
            'effective_from' => $item->effectiveFrom,
            'description' => $item->description,
            'created_by' => $item->createdBy,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $key = 'u_' . str_replace('.', '_', $col);
            $sets[] = "`{$col}` = :{$key}";
            $params[$key] = $val;
        }
        $params['id'] = $id;
        $sql = "UPDATE `{DB::SALARY_STRUCTURES}` SET " . implode(', ', $sets) . " WHERE id = :id";
        try {
            $this->db->execute($sql, $params);
            return true;
        } catch (\Throwable $e) {
            error_log("SalaryStructureRepository: Update failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $this->db->execute("DELETE FROM `{DB::SALARY_STRUCTURES}` WHERE id = :id", ['id' => $id]);
        return true;
    }

    private function mapRowToDto(array $row): SalaryStructure
    {
        return new SalaryStructure(
            id: (int)$row['id'],
            effectiveFrom: (string)($row['effective_from'] ?? ''),
            description: (string)($row['description'] ?? ''),
            createdBy: (int)($row['created_by'] ?? 0),
            createdAt: (string)($row['created_at'] ?? ''),
        );
    }
}

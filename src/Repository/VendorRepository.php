<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\Vendor;

class VendorRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?Vendor
    {
        $sql = "SELECT id, vendor_type, description, is_active, created_by, created_at
                FROM `{DB::VENDORS}` WHERE id = :id";
        $row = $this->db->fetchOne($sql, ['id' => $id]);
        return $row === null ? null : $this->mapRowToDto($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT id, vendor_type, description, is_active, created_by, created_at
                FROM `{DB::VENDORS}` ORDER BY vendor_type ASC";
        return array_map($this->mapRowToDto(...), $this->db->fetchAll($sql));
    }

    public function exists(string $name, ?int $excludeId = null): bool
    {
        $sql = $excludeId !== null
            ? "SELECT id FROM `{DB::VENDORS}` WHERE vendor_type = :name AND id != :exclude_id LIMIT 1"
            : "SELECT id FROM `{DB::VENDORS}` WHERE vendor_type = :name LIMIT 1";
        $params = $excludeId !== null ? ['name' => $name, 'exclude_id' => $excludeId] : ['name' => $name];
        return $this->db->fetchOne($sql, $params) !== null;
    }

    public function insert(Vendor $item): int
    {
        $sql = "INSERT INTO `{DB::VENDORS}` (vendor_type, description, is_active, created_by)
                VALUES (:vendor_type, :description, :is_active, :created_by)";
        return (int)$this->db->insert($sql, [
            'vendor_type' => $item->vendorType,
            'description' => $item->description,
            'is_active' => $item->isActive ? 1 : 0,
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
        $sql = "UPDATE `{DB::VENDORS}` SET " . implode(', ', $sets) . " WHERE id = :id";
        try {
            $this->db->execute($sql, $params);
            return true;
        } catch (\Throwable $e) {
            error_log("VendorRepository: Update failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $this->db->execute("DELETE FROM `{DB::VENDORS}` WHERE id = :id", ['id' => $id]);
        return true;
    }

    private function mapRowToDto(array $row): Vendor
    {
        return new Vendor(
            id: (int)$row['id'],
            vendorType: (string)($row['vendor_type'] ?? ''),
            description: (string)($row['description'] ?? ''),
            isActive: (bool)($row['is_active'] ?? true),
            createdBy: (int)($row['created_by'] ?? 0),
            createdAt: (string)($row['created_at'] ?? ''),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\Item;

class ItemRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?Item
    {
        $sql = "SELECT id, item_type, item_name, unit_price, is_excise, is_active, created_by, created_at
                FROM `" . DB::ITEMS . "` WHERE id = :id";
        $row = $this->db->fetchOne($sql, ['id' => $id]);
        return $row === null ? null : $this->mapRowToDto($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT id, item_type, item_name, unit_price, is_excise, is_active, created_by, created_at
                FROM `" . DB::ITEMS . "` ORDER BY item_name ASC";
        return array_map($this->mapRowToDto(...), $this->db->fetchAll($sql));
    }

    public function exists(string $name, ?int $excludeId = null): bool
    {
        $sql = $excludeId !== null
            ? "SELECT id FROM `" . DB::ITEMS . "` WHERE item_name = :name AND id != :exclude_id LIMIT 1"
            : "SELECT id FROM `" . DB::ITEMS . "` WHERE item_name = :name LIMIT 1";
        $params = $excludeId !== null ? ['name' => $name, 'exclude_id' => $excludeId] : ['name' => $name];
        return $this->db->fetchOne($sql, $params) !== null;
    }

    public function insert(Item $item): int
    {
        $sql = "INSERT INTO `" . DB::ITEMS . "` (item_type, item_name, unit_price, is_excise, is_active, created_by)
                VALUES (:item_type, :item_name, :unit_price, :is_excise, :is_active, :created_by)";
        return (int)$this->db->insert($sql, [
            'item_type' => $item->itemType,
            'item_name' => $item->itemName,
            'unit_price' => $item->unitPrice,
            'is_excise' => $item->isExcise ? 1 : 0,
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
        $sql = "UPDATE `" . DB::ITEMS . "` SET " . implode(', ', $sets) . " WHERE id = :id";
        try {
            $this->db->execute($sql, $params);
            return true;
        } catch (\Throwable $e) {
            error_log("ItemRepository: Update failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $this->db->execute("DELETE FROM `" . DB::ITEMS . "` WHERE id = :id", ['id' => $id]);
        return true;
    }

    private function mapRowToDto(array $row): Item
    {
        return new Item(
            id: (int)$row['id'],
            itemType: (string)($row['item_type'] ?? 'services'),
            itemName: (string)($row['item_name'] ?? ''),
            unitPrice: (string)($row['unit_price'] ?? '0'),
            isExcise: (bool)($row['is_excise'] ?? false),
            isActive: (bool)($row['is_active'] ?? true),
            createdBy: (int)($row['created_by'] ?? 0),
            createdAt: (string)($row['created_at'] ?? ''),
        );
    }
}

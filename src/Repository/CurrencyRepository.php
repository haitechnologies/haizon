<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\Currency;

class CurrencyRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id, int $orgId): ?Currency
    {
        $sql = "SELECT id, organization_id, currency, is_active, created_at, updated_at, created_by
                FROM `{DB::CURRENCIES}`
                WHERE id = :id AND organization_id = :org_id";

        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);
        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    public function findAll(int $orgId): array
    {
        $sql = "SELECT id, organization_id, currency, is_active, created_at, updated_at, created_by
                FROM `{DB::CURRENCIES}`
                WHERE organization_id = :org_id
                ORDER BY currency ASC";

        $rows = $this->db->fetchAll($sql, ['org_id' => $orgId]);
        $currencies = [];
        foreach ($rows as $row) {
            $currencies[] = $this->mapRowToDto($row);
        }

        return $currencies;
    }

    public function findAllActive(int $orgId): array
    {
        $sql = "SELECT id, organization_id, currency, is_active, created_at, updated_at, created_by
                FROM `{DB::CURRENCIES}`
                WHERE organization_id = :org_id AND is_active = 1
                ORDER BY currency ASC";

        $rows = $this->db->fetchAll($sql, ['org_id' => $orgId]);
        $currencies = [];
        foreach ($rows as $row) {
            $currencies[] = $this->mapRowToDto($row);
        }

        return $currencies;
    }

    public function existsByName(string $name, int $orgId, ?int $excludeId = null): bool
    {
        $params = ['name' => $name, 'org_id' => $orgId];
        if ($excludeId !== null) {
            $sql = "SELECT id FROM `{DB::CURRENCIES}` WHERE currency = :name AND organization_id = :org_id AND id != :exclude_id LIMIT 1";
            $params['exclude_id'] = $excludeId;
        } else {
            $sql = "SELECT id FROM `{DB::CURRENCIES}` WHERE currency = :name AND organization_id = :org_id LIMIT 1";
        }

        $row = $this->db->fetchOne($sql, $params);
        return $row !== null;
    }

    public function save(Currency $currency): Currency
    {
        if ($currency->id === null) {
            return $this->insert($currency);
        }
        return $this->update($currency);
    }

    private function insert(Currency $currency): Currency
    {
        $sql = "INSERT INTO `{DB::CURRENCIES}` (organization_id, currency, is_active, created_by)
                VALUES (:organization_id, :currency, :is_active, :created_by)";

        $params = [
            'organization_id' => $currency->organizationId,
            'currency' => $currency->currency,
            'is_active' => $currency->isActive ? 1 : 0,
            'created_by' => $currency->createdBy,
        ];

        $insertId = (int)$this->db->insert($sql, $params);
        return $this->find($insertId, $currency->organizationId);
    }

    private function update(Currency $currency): Currency
    {
        $sql = "UPDATE `{DB::CURRENCIES}`
                SET currency = :currency,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :organization_id";

        $params = [
            'currency' => $currency->currency,
            'is_active' => $currency->isActive ? 1 : 0,
            'id' => $currency->id,
            'organization_id' => $currency->organizationId,
        ];

        $this->db->execute($sql, $params);
        return $this->find((int)$currency->id, $currency->organizationId);
    }

    public function delete(int $id, int $orgId): bool
    {
        $stmt = $this->db->execute(
            "DELETE FROM `{DB::CURRENCIES}` WHERE id = :id AND organization_id = :org_id",
            ['id' => $id, 'org_id' => $orgId]
        );
        return $stmt->rowCount() > 0;
    }

    private function mapRowToDto(array $row): Currency
    {
        return new Currency(
            id: (int)$row['id'],
            organizationId: (int)$row['organization_id'],
            currency: (string)$row['currency'],
            isActive: (bool)($row['is_active'] ?? true),
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? ''),
            createdBy: (int)($row['created_by'] ?? 0)
        );
    }
}

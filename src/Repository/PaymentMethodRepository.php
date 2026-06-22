<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\PaymentMethod;

class PaymentMethodRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id, int $orgId): ?PaymentMethod
    {
        $sql = "SELECT id, organization_id, payment_method, is_active, created_at, updated_at, created_by
                FROM `{DB::PAYMENT_METHODS}`
                WHERE id = :id AND organization_id = :org_id";

        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);
        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    public function findAll(int $orgId): array
    {
        $sql = "SELECT id, organization_id, payment_method, is_active, created_at, updated_at, created_by
                FROM `{DB::PAYMENT_METHODS}`
                WHERE organization_id = :org_id
                ORDER BY payment_method ASC";

        $rows = $this->db->fetchAll($sql, ['org_id' => $orgId]);
        $methods = [];
        foreach ($rows as $row) {
            $methods[] = $this->mapRowToDto($row);
        }

        return $methods;
    }

    public function existsByName(string $name, int $orgId, ?int $excludeId = null): bool
    {
        $params = ['name' => $name, 'org_id' => $orgId];
        if ($excludeId !== null) {
            $sql = "SELECT id FROM `{DB::PAYMENT_METHODS}` WHERE payment_method = :name AND organization_id = :org_id AND id != :exclude_id LIMIT 1";
            $params['exclude_id'] = $excludeId;
        } else {
            $sql = "SELECT id FROM `{DB::PAYMENT_METHODS}` WHERE payment_method = :name AND organization_id = :org_id LIMIT 1";
        }

        $row = $this->db->fetchOne($sql, $params);
        return $row !== null;
    }

    public function save(PaymentMethod $paymentMethod): PaymentMethod
    {
        if ($paymentMethod->id === null) {
            return $this->insert($paymentMethod);
        }
        return $this->update($paymentMethod);
    }

    private function insert(PaymentMethod $paymentMethod): PaymentMethod
    {
        $sql = "INSERT INTO `{DB::PAYMENT_METHODS}` (organization_id, payment_method, is_active, created_by)
                VALUES (:organization_id, :payment_method, :is_active, :created_by)";

        $params = [
            'organization_id' => $paymentMethod->organizationId,
            'payment_method' => $paymentMethod->paymentMethod,
            'is_active' => $paymentMethod->isActive ? 1 : 0,
            'created_by' => $paymentMethod->createdBy,
        ];

        $insertId = (int)$this->db->insert($sql, $params);
        return $this->find($insertId, $paymentMethod->organizationId);
    }

    private function update(PaymentMethod $paymentMethod): PaymentMethod
    {
        $sql = "UPDATE `{DB::PAYMENT_METHODS}`
                SET payment_method = :payment_method,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :organization_id";

        $params = [
            'payment_method' => $paymentMethod->paymentMethod,
            'is_active' => $paymentMethod->isActive ? 1 : 0,
            'id' => $paymentMethod->id,
            'organization_id' => $paymentMethod->organizationId,
        ];

        $this->db->execute($sql, $params);
        return $this->find((int)$paymentMethod->id, $paymentMethod->organizationId);
    }

    public function delete(int $id, int $orgId): bool
    {
        $stmt = $this->db->execute(
            "DELETE FROM `{DB::PAYMENT_METHODS}` WHERE id = :id AND organization_id = :org_id",
            ['id' => $id, 'org_id' => $orgId]
        );
        return $stmt->rowCount() > 0;
    }

    private function mapRowToDto(array $row): PaymentMethod
    {
        return new PaymentMethod(
            id: (int)$row['id'],
            organizationId: (int)$row['organization_id'],
            paymentMethod: (string)$row['payment_method'],
            isActive: (bool)($row['is_active'] ?? true),
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? ''),
            createdBy: (int)($row['created_by'] ?? 0)
        );
    }
}

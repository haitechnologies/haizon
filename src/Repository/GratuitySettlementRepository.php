<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\GratuitySettlement;

/**
 * GratuitySettlement Repository
 *
 * Handles PDO-based data access for erp_gratuity_settlements table.
 * Enforces strict tenant isolation on organization_id.
 */
class GratuitySettlementRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a gratuity settlement by ID
     */
    public function find(int $id): ?GratuitySettlement
    {
        $sql = "SELECT id, organization_id, employee_id, total_tenure_years, total_tenure_days,
                       last_basic_salary, gratuity_amount, status, settlement_date, payment_date,
                       payment_reference, notes, created_by, approved_by, created_at, updated_at
                FROM `" . DB::GRATUITY_SETTLEMENTS . "`
                WHERE id = :id";

        $row = $this->db->fetchOne($sql, ['id' => $id]);

        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    /**
     * Find all gratuity settlements, optionally scoped by organization
     *
     * @return GratuitySettlement[]
     */
    public function findAll(?int $orgId = null): array
    {
        $sql = "SELECT id, organization_id, employee_id, total_tenure_years, total_tenure_days,
                       last_basic_salary, gratuity_amount, status, settlement_date, payment_date,
                       payment_reference, notes, created_by, approved_by, created_at, updated_at
                FROM `" . DB::GRATUITY_SETTLEMENTS . "`
                WHERE id > 0";

        $params = [];

        if ($orgId !== null) {
            $sql .= " AND organization_id = :organization_id";
            $params['organization_id'] = $orgId;
        }

        $sql .= " ORDER BY id DESC";

        $rows = $this->db->fetchAll($sql, $params);
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->mapRowToDto($row);
        }

        return $results;
    }

    /**
     * Find gratuity settlements by employee ID
     *
     * @return GratuitySettlement[]
     */
    public function findByEmployee(int $employeeId): array
    {
        $sql = "SELECT id, organization_id, employee_id, total_tenure_years, total_tenure_days,
                       last_basic_salary, gratuity_amount, status, settlement_date, payment_date,
                       payment_reference, notes, created_by, approved_by, created_at, updated_at
                FROM `" . DB::GRATUITY_SETTLEMENTS . "`
                WHERE employee_id = :employee_id
                ORDER BY id DESC";

        $rows = $this->db->fetchAll($sql, ['employee_id' => $employeeId]);
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->mapRowToDto($row);
        }

        return $results;
    }

    /**
     * Find gratuity settlements by status
     *
     * @return GratuitySettlement[]
     */
    public function findByStatus(string $status): array
    {
        $sql = "SELECT id, organization_id, employee_id, total_tenure_years, total_tenure_days,
                       last_basic_salary, gratuity_amount, status, settlement_date, payment_date,
                       payment_reference, notes, created_by, approved_by, created_at, updated_at
                FROM `" . DB::GRATUITY_SETTLEMENTS . "`
                WHERE status = :status
                ORDER BY id DESC";

        $rows = $this->db->fetchAll($sql, ['status' => $status]);
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->mapRowToDto($row);
        }

        return $results;
    }

    /**
     * Insert a new gratuity settlement
     */
    public function insert(GratuitySettlement $settlement): int
    {
        $sql = "INSERT INTO `" . DB::GRATUITY_SETTLEMENTS . "`
                (organization_id, employee_id, total_tenure_years, total_tenure_days,
                 last_basic_salary, gratuity_amount, status, settlement_date, payment_date,
                 payment_reference, notes, created_by, approved_by, created_at)
                VALUES (:organization_id, :employee_id, :total_tenure_years, :total_tenure_days,
                        :last_basic_salary, :gratuity_amount, :status, :settlement_date, :payment_date,
                        :payment_reference, :notes, :created_by, :approved_by, NOW())";

        $params = [
            'organization_id' => $settlement->organizationId,
            'employee_id' => $settlement->employeeId,
            'total_tenure_years' => $settlement->totalTenureYears,
            'total_tenure_days' => $settlement->totalTenureDays,
            'last_basic_salary' => $settlement->lastBasicSalary,
            'gratuity_amount' => $settlement->gratuityAmount,
            'status' => $settlement->status,
            'settlement_date' => $settlement->settlementDate,
            'payment_date' => $settlement->paymentDate,
            'payment_reference' => $settlement->paymentReference,
            'notes' => $settlement->notes,
            'created_by' => $settlement->createdBy,
            'approved_by' => $settlement->approvedBy,
        ];

        return (int)$this->db->insert($sql, $params);
    }

    /**
     * Update a gratuity settlement by ID
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'employee_id', 'total_tenure_years', 'total_tenure_days',
            'last_basic_salary', 'gratuity_amount', 'status',
            'settlement_date', 'payment_date', 'payment_reference',
            'notes', 'approved_by',
        ];

        $setClauses = [];
        $params = ['id' => $id];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields, true)) {
                $setClauses[] = "`{$field}` = :{$field}";
                $params[$field] = $value;
            }
        }

        if (empty($setClauses)) {
            return false;
        }

        // Always update updated_at
        $setClauses[] = "updated_at = NOW()";

        $sql = "UPDATE `" . DB::GRATUITY_SETTLEMENTS . "`
                SET " . implode(', ', $setClauses) . "
                WHERE id = :id";

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a gratuity settlement by ID
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM `" . DB::GRATUITY_SETTLEMENTS . "` WHERE id = :id";
        $stmt = $this->db->execute($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Map database row to GratuitySettlement DTO
     */
    private function mapRowToDto(array $row): GratuitySettlement
    {
        return new GratuitySettlement(
            id: (int)$row['id'],
            organizationId: (int)$row['organization_id'],
            employeeId: (int)$row['employee_id'],
            totalTenureYears: (float)($row['total_tenure_years'] ?? 0),
            totalTenureDays: (int)($row['total_tenure_days'] ?? 0),
            lastBasicSalary: (float)($row['last_basic_salary'] ?? 0),
            gratuityAmount: (float)($row['gratuity_amount'] ?? 0),
            status: (string)($row['status'] ?? 'calculated'),
            settlementDate: $row['settlement_date'] ?? null,
            paymentDate: $row['payment_date'] ?? null,
            paymentReference: (string)($row['payment_reference'] ?? ''),
            notes: (string)($row['notes'] ?? ''),
            createdBy: (int)($row['created_by'] ?? 0),
            approvedBy: $row['approved_by'] !== null ? (int)$row['approved_by'] : null,
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? '')
        );
    }
}

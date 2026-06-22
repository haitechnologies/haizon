<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\AnnualLeaveEntitlement;

/**
 * AnnualLeaveEntitlement Repository
 *
 * Handles PDO-based data access for erp_annual_leave_entitlements table.
 * Enforces strict tenant isolation on organization_id.
 */
class AnnualLeaveEntitlementRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find an entitlement record by ID
     */
    public function find(int $id, int $organizationId): ?AnnualLeaveEntitlement
    {
        $sql = "SELECT id, organization_id, employee_id, entitlement_year, 
                       total_leave_days, leave_availed, leave_balance, 
                       air_ticket_amount, air_ticket_availed, status, notes,
                       created_by, created_at, updated_at
                FROM `{DB::ANNUAL_LEAVE_ENTITLEMENTS}`
                WHERE id = :id AND organization_id = :organization_id";

        $row = $this->db->fetchOne($sql, [
            'id' => $id,
            'organization_id' => $organizationId,
        ]);

        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    /**
     * Find all entitlement records for an organization
     *
     * @return AnnualLeaveEntitlement[]
     */
    public function findAll(?int $orgId = null): array
    {
        $sql = "SELECT id, organization_id, employee_id, entitlement_year, 
                       total_leave_days, leave_availed, leave_balance, 
                       air_ticket_amount, air_ticket_availed, status, notes,
                       created_by, created_at, updated_at
                FROM `{DB::ANNUAL_LEAVE_ENTITLEMENTS}`";

        $params = [];
        if ($orgId !== null) {
            $sql .= " WHERE organization_id = :organization_id";
            $params['organization_id'] = $orgId;
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRowToDto($row);
        }

        return $items;
    }

    /**
     * Find entitlement records by employee ID
     *
     * @return AnnualLeaveEntitlement[]
     */
    public function findByEmployee(int $employeeId, ?int $organizationId = null): array
    {
        $sql = "SELECT id, organization_id, employee_id, entitlement_year, 
                       total_leave_days, leave_availed, leave_balance, 
                       air_ticket_amount, air_ticket_availed, status, notes,
                       created_by, created_at, updated_at
                FROM `{DB::ANNUAL_LEAVE_ENTITLEMENTS}`
                WHERE employee_id = :employee_id";

        $params = ['employee_id' => $employeeId];
        if ($organizationId !== null) {
            $sql .= " AND organization_id = :organization_id";
            $params['organization_id'] = $organizationId;
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRowToDto($row);
        }

        return $items;
    }

    /**
     * Find an entitlement record by employee, year, and organization
     */
    public function findByYear(int $employeeId, int $year, int $organizationId): ?AnnualLeaveEntitlement
    {
        $sql = "SELECT id, organization_id, employee_id, entitlement_year, 
                       total_leave_days, leave_availed, leave_balance, 
                       air_ticket_amount, air_ticket_availed, status, notes,
                       created_by, created_at, updated_at
                FROM `{DB::ANNUAL_LEAVE_ENTITLEMENTS}`
                WHERE employee_id = :employee_id
                  AND entitlement_year = :entitlement_year
                  AND organization_id = :organization_id
                LIMIT 1";

        $row = $this->db->fetchOne($sql, [
            'employee_id' => $employeeId,
            'entitlement_year' => $year,
            'organization_id' => $organizationId,
        ]);

        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    /**
     * Insert a new entitlement record
     */
    public function insert(AnnualLeaveEntitlement $item): int
    {
        $sql = "INSERT INTO `{DB::ANNUAL_LEAVE_ENTITLEMENTS}` 
                (organization_id, employee_id, entitlement_year, 
                 total_leave_days, leave_availed, leave_balance, 
                 air_ticket_amount, air_ticket_availed, status, notes, created_by)
                VALUES (:organization_id, :employee_id, :entitlement_year,
                        :total_leave_days, :leave_availed, :leave_balance,
                        :air_ticket_amount, :air_ticket_availed, :status, :notes, :created_by)";

        $params = [
            'organization_id' => $item->organizationId,
            'employee_id' => $item->employeeId,
            'entitlement_year' => $item->entitlementYear,
            'total_leave_days' => $item->totalLeaveDays,
            'leave_availed' => $item->leaveAvailed,
            'leave_balance' => $item->leaveBalance,
            'air_ticket_amount' => $item->airTicketAmount,
            'air_ticket_availed' => $item->airTicketAvailed ? 1 : 0,
            'status' => $item->status,
            'notes' => $item->notes,
            'created_by' => $item->createdBy,
        ];

        return (int)$this->db->insert($sql, $params);
    }

    /**
     * Update an existing entitlement record
     */
    public function update(int $id, array $data, int $organizationId): bool
    {
        $allowedFields = ['total_leave_days', 'leave_availed', 'leave_balance', 'air_ticket_amount', 'air_ticket_availed', 'status', 'notes'];
        $setClauses = [];
        $params = ['id' => $id, 'organization_id' => $organizationId];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields, true)) {
                $setClauses[] = "`{$field}` = :{$field}";
                $params[$field] = $value;
            }
        }

        if (empty($setClauses)) {
            return false;
        }

        $setClauses[] = "updated_at = NOW()";
        $setString = implode(', ', $setClauses);

        $sql = "UPDATE `{DB::ANNUAL_LEAVE_ENTITLEMENTS}` 
                SET {$setString}
                WHERE id = :id AND organization_id = :organization_id";

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete an entitlement record
     */
    public function delete(int $id, int $organizationId): bool
    {
        $sql = "DELETE FROM `{DB::ANNUAL_LEAVE_ENTITLEMENTS}` WHERE id = :id AND organization_id = :organization_id";
        $stmt = $this->db->execute($sql, [
            'id' => $id,
            'organization_id' => $organizationId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Map database row to AnnualLeaveEntitlement DTO
     */
    private function mapRowToDto(array $row): AnnualLeaveEntitlement
    {
        return new AnnualLeaveEntitlement(
            id: (int)$row['id'],
            organizationId: (int)$row['organization_id'],
            employeeId: (int)$row['employee_id'],
            entitlementYear: (int)$row['entitlement_year'],
            totalLeaveDays: (float)($row['total_leave_days'] ?? 30.0),
            leaveAvailed: (float)($row['leave_availed'] ?? 0.0),
            leaveBalance: (float)($row['leave_balance'] ?? 30.0),
            airTicketAmount: (float)($row['air_ticket_amount'] ?? 1250.00),
            airTicketAvailed: (bool)($row['air_ticket_availed'] ?? false),
            status: (string)$row['status'],
            notes: (string)($row['notes'] ?? ''),
            createdBy: (int)($row['created_by'] ?? 0),
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? '')
        );
    }
}

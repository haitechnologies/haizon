<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\LeaveType;

/**
 * LeaveType Repository
 *
 * Handles PDO-based data access for erp_leave_types table.
 * Enforces strict tenant isolation on organization_id.
 */
class LeaveTypeRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a leave type by ID
     */
    public function find(int $id, int $organizationId): ?LeaveType
    {
        $sql = "SELECT id, organization_id, leave_type, max_per_year, paid, paid_days, created_at, updated_at 
                FROM DB::LEAVE_TYPES 
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
     * Find all leave types for an organization
     *
     * @return LeaveType[]
     */
    public function findAll(int $organizationId): array
    {
        $sql = "SELECT id, organization_id, leave_type, max_per_year, paid, paid_days, created_at, updated_at 
                FROM DB::LEAVE_TYPES 
                WHERE organization_id = :organization_id 
                ORDER BY leave_type ASC";

        $rows = $this->db->fetchAll($sql, ['organization_id' => $organizationId]);
        $types = [];
        foreach ($rows as $row) {
            $types[] = $this->mapRowToDto($row);
        }

        return $types;
    }

    /**
     * Check if a leave type name exists in the organization, optionally excluding an ID
     */
    public function existsByName(string $name, int $organizationId, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $sql = "SELECT id FROM DB::LEAVE_TYPES 
                    WHERE leave_type = :name AND organization_id = :organization_id AND id != :exclude_id 
                    LIMIT 1";
            $params = [
                'name' => $name,
                'organization_id' => $organizationId,
                'exclude_id' => $excludeId,
            ];
        } else {
            $sql = "SELECT id FROM DB::LEAVE_TYPES 
                    WHERE leave_type = :name AND organization_id = :organization_id 
                    LIMIT 1";
            $params = [
                'name' => $name,
                'organization_id' => $organizationId,
            ];
        }

        $row = $this->db->fetchOne($sql, $params);
        return $row !== null;
    }

    /**
     * Save a leave type (Insert or Update)
     */
    public function save(LeaveType $leaveType): LeaveType
    {
        if ($leaveType->id === null) {
            return $this->insert($leaveType);
        }
        return $this->update($leaveType);
    }

    private function insert(LeaveType $type): LeaveType
    {
        $sql = "INSERT INTO DB::LEAVE_TYPES (organization_id, leave_type, max_per_year, paid, paid_days) 
                VALUES (:organization_id, :leave_type, :max_per_year, :paid, :paid_days)";

        $params = [
            'organization_id' => $type->organizationId,
            'leave_type' => $type->leaveType,
            'max_per_year' => $type->maxPerYear,
            'paid' => $type->paid ? 1 : 0,
            'paid_days' => $type->paidDays,
        ];

        $insertId = (int)$this->db->insert($sql, $params);
        return $this->find($insertId, $type->organizationId ?? 1);
    }

    private function update(LeaveType $type): LeaveType
    {
        $sql = "UPDATE DB::LEAVE_TYPES 
                SET leave_type = :leave_type, 
                    max_per_year = :max_per_year, 
                    paid = :paid, 
                    paid_days = :paid_days 
                WHERE id = :id AND organization_id = :organization_id";

        $params = [
            'leave_type' => $type->leaveType,
            'max_per_year' => $type->maxPerYear,
            'paid' => $type->paid ? 1 : 0,
            'paid_days' => $type->paidDays,
            'id' => $type->id,
            'organization_id' => $type->organizationId,
        ];

        $this->db->execute($sql, $params);
        return $this->find((int)$type->id, $type->organizationId ?? 1);
    }

    /**
     * Delete a leave type by ID
     */
    public function delete(int $id, int $organizationId): bool
    {
        $sql = "DELETE FROM DB::LEAVE_TYPES WHERE id = :id AND organization_id = :organization_id";
        $stmt = $this->db->execute($sql, [
            'id' => $id,
            'organization_id' => $organizationId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Map database row to LeaveType DTO
     */
    private function mapRowToDto(array $row): LeaveType
    {
        return new LeaveType(
            id: (int)$row['id'],
            organizationId: $row['organization_id'] !== null ? (int)$row['organization_id'] : null,
            leaveType: (string)$row['leave_type'],
            maxPerYear: (int)$row['max_per_year'],
            paid: (bool)$row['paid'],
            paidDays: (int)($row['paid_days'] ?? 3),
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? '')
        );
    }
}

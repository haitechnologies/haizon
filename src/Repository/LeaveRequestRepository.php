<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Model\LeaveRequest;

/**
 * LeaveRequest Repository
 *
 * Handles PDO-based data access for erp_leave_requests table.
 * Enforces strict tenant isolation on organization_id.
 */
class LeaveRequestRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a leave request by ID
     */
    public function find(int $id, int $organizationId): ?LeaveRequest
    {
        $sql = "SELECT id, organization_id, employee_id, leave_type_id, start_date, end_date, total_days, reason, status, approved_by, created_at, updated_at 
                FROM `erp_leave_requests` 
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
     * Find all leave requests for an organization
     *
     * @return LeaveRequest[]
     */
    public function findAll(int $organizationId): array
    {
        $sql = "SELECT id, organization_id, employee_id, leave_type_id, start_date, end_date, total_days, reason, status, approved_by, created_at, updated_at 
                FROM `erp_leave_requests` 
                WHERE organization_id = :organization_id 
                ORDER BY id DESC";

        $rows = $this->db->fetchAll($sql, ['organization_id' => $organizationId]);
        $requests = [];
        foreach ($rows as $row) {
            $requests[] = $this->mapRowToDto($row);
        }

        return $requests;
    }

    /**
     * Save a leave request (Insert or Update)
     */
    public function save(LeaveRequest $request): LeaveRequest
    {
        if ($request->id === null) {
            return $this->insert($request);
        }
        return $this->update($request);
    }

    private function insert(LeaveRequest $req): LeaveRequest
    {
        $sql = "INSERT INTO `erp_leave_requests` (organization_id, employee_id, leave_type_id, start_date, end_date, total_days, reason, status, approved_by) 
                VALUES (:organization_id, :employee_id, :leave_type_id, :start_date, :end_date, :total_days, :reason, :status, :approved_by)";

        $params = [
            'organization_id' => $req->organizationId,
            'employee_id' => $req->employeeId,
            'leave_type_id' => $req->leaveTypeId,
            'start_date' => $req->startDate,
            'end_date' => $req->endDate,
            'total_days' => $req->totalDays,
            'reason' => $req->reason,
            'status' => $req->status,
            'approved_by' => $req->approvedBy,
        ];

        $insertId = (int)$this->db->insert($sql, $params);
        return $this->find($insertId, $req->organizationId ?? 1);
    }

    private function update(LeaveRequest $req): LeaveRequest
    {
        $sql = "UPDATE `erp_leave_requests` 
                SET employee_id = :employee_id, 
                    leave_type_id = :leave_type_id, 
                    start_date = :start_date, 
                    end_date = :end_date, 
                    total_days = :total_days, 
                    reason = :reason, 
                    status = :status, 
                    approved_by = :approved_by 
                WHERE id = :id AND organization_id = :organization_id";

        $params = [
            'employee_id' => $req->employeeId,
            'leave_type_id' => $req->leaveTypeId,
            'start_date' => $req->startDate,
            'end_date' => $req->endDate,
            'total_days' => $req->totalDays,
            'reason' => $req->reason,
            'status' => $req->status,
            'approved_by' => $req->approvedBy,
            'id' => $req->id,
            'organization_id' => $req->organizationId,
        ];

        $this->db->execute($sql, $params);
        return $this->find((int)$req->id, $req->organizationId ?? 1);
    }

    /**
     * Delete a leave request by ID
     */
    public function delete(int $id, int $organizationId): bool
    {
        $sql = "DELETE FROM `erp_leave_requests` WHERE id = :id AND organization_id = :organization_id";
        $stmt = $this->db->execute($sql, [
            'id' => $id,
            'organization_id' => $organizationId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if there are any leave requests associated with a leave type ID
     */
    public function hasRequestsForType(int $leaveTypeId, int $organizationId): bool
    {
        $sql = "SELECT id FROM `erp_leave_requests` 
                WHERE leave_type_id = :leave_type_id AND organization_id = :organization_id 
                LIMIT 1";
        $row = $this->db->fetchOne($sql, [
            'leave_type_id' => $leaveTypeId,
            'organization_id' => $organizationId,
        ]);
        return $row !== null;
    }

    /**
     * Map database row to LeaveRequest DTO
     */
    private function mapRowToDto(array $row): LeaveRequest
    {
        return new LeaveRequest(
            id: (int)$row['id'],
            organizationId: $row['organization_id'] !== null ? (int)$row['organization_id'] : null,
            employeeId: (int)$row['employee_id'],
            leaveTypeId: (int)$row['leave_type_id'],
            startDate: (string)$row['start_date'],
            endDate: (string)$row['end_date'],
            totalDays: (float)$row['total_days'],
            reason: $row['reason'] !== null ? (string)$row['reason'] : null,
            status: (string)$row['status'],
            approvedBy: $row['approved_by'] !== null ? (int)$row['approved_by'] : null,
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? '')
        );
    }
}

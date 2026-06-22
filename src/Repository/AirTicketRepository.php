<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\AirTicket;

/**
 * AirTicket Repository
 *
 * Handles PDO-based data access for erp_air_tickets table.
 * Enforces strict tenant isolation on organization_id.
 */
class AirTicketRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find an air ticket by ID
     */
    public function find(int $id, int $organizationId): ?AirTicket
    {
        $sql = "SELECT id, organization_id, employee_id, entitlement_amount, status, 
                       eligibility_date, paid_date, payment_reference, notes, 
                       created_by, created_at, updated_at
                FROM " . DB::AIR_TICKETS . "
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
     * Find all air tickets for an organization
     *
     * @return AirTicket[]
     */
    public function findAll(?int $organizationId = null): array
    {
        $sql = "SELECT id, organization_id, employee_id, entitlement_amount, status, 
                       eligibility_date, paid_date, payment_reference, notes, 
                       created_by, created_at, updated_at
                FROM " . DB::AIR_TICKETS;

        $params = [];
        if ($organizationId !== null) {
            $sql .= " WHERE organization_id = :organization_id";
            $params['organization_id'] = $organizationId;
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);
        $types = [];
        foreach ($rows as $row) {
            $types[] = $this->mapRowToDto($row);
        }

        return $types;
    }

    /**
     * Find air tickets by employee ID
     *
     * @return AirTicket[]
     */
    public function findByEmployee(int $employeeId, ?int $organizationId = null): array
    {
        $sql = "SELECT id, organization_id, employee_id, entitlement_amount, status, 
                       eligibility_date, paid_date, payment_reference, notes, 
                       created_by, created_at, updated_at
                FROM " . DB::AIR_TICKETS . "
                WHERE employee_id = :employee_id";

        $params = ['employee_id' => $employeeId];
        if ($organizationId !== null) {
            $sql .= " AND organization_id = :organization_id";
            $params['organization_id'] = $organizationId;
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);
        $types = [];
        foreach ($rows as $row) {
            $types[] = $this->mapRowToDto($row);
        }

        return $types;
    }

    /**
     * Find air tickets by status
     *
     * @return AirTicket[]
     */
    public function findByStatus(string $status, ?int $organizationId = null): array
    {
        $sql = "SELECT id, organization_id, employee_id, entitlement_amount, status, 
                       eligibility_date, paid_date, payment_reference, notes, 
                       created_by, created_at, updated_at
                FROM " . DB::AIR_TICKETS . "
                WHERE status = :status";

        $params = ['status' => $status];
        if ($organizationId !== null) {
            $sql .= " AND organization_id = :organization_id";
            $params['organization_id'] = $organizationId;
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);
        $types = [];
        foreach ($rows as $row) {
            $types[] = $this->mapRowToDto($row);
        }

        return $types;
    }

    /**
     * Insert a new air ticket record
     */
    public function insert(AirTicket $ticket): int
    {
        $sql = "INSERT INTO " . DB::AIR_TICKETS . " 
                (organization_id, employee_id, entitlement_amount, status, 
                 eligibility_date, paid_date, payment_reference, notes, created_by)
                VALUES (:organization_id, :employee_id, :entitlement_amount, :status,
                        :eligibility_date, :paid_date, :payment_reference, :notes, :created_by)";

        $params = [
            'organization_id' => $ticket->organizationId,
            'employee_id' => $ticket->employeeId,
            'entitlement_amount' => $ticket->entitlementAmount,
            'status' => $ticket->status,
            'eligibility_date' => $ticket->eligibilityDate,
            'paid_date' => $ticket->paidDate,
            'payment_reference' => $ticket->paymentReference,
            'notes' => $ticket->notes,
            'created_by' => $ticket->createdBy,
        ];

        return (int)$this->db->insert($sql, $params);
    }

    /**
     * Update an existing air ticket record
     */
    public function update(int $id, array $data, int $organizationId): bool
    {
        $allowedFields = ['status', 'paid_date', 'payment_reference', 'notes', 'eligibility_date', 'entitlement_amount'];
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

        $sql = "UPDATE " . DB::AIR_TICKETS . " 
                SET {$setString}
                WHERE id = :id AND organization_id = :organization_id";

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete an air ticket record
     */
    public function delete(int $id, int $organizationId): bool
    {
        $sql = "DELETE FROM " . DB::AIR_TICKETS . " WHERE id = :id AND organization_id = :organization_id";
        $stmt = $this->db->execute($sql, [
            'id' => $id,
            'organization_id' => $organizationId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if employee has any active (non-cancelled) air ticket
     */
    public function hasActiveTicket(int $employeeId, int $organizationId): bool
    {
        $sql = "SELECT id FROM " . DB::AIR_TICKETS . "
                WHERE employee_id = :employee_id
                  AND organization_id = :organization_id
                  AND status != 'cancelled'
                LIMIT 1";

        $row = $this->db->fetchOne($sql, [
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
        ]);

        return $row !== null;
    }

    /**
     * Map database row to AirTicket DTO
     */
    private function mapRowToDto(array $row): AirTicket
    {
        return new AirTicket(
            id: (int)$row['id'],
            organizationId: (int)$row['organization_id'],
            employeeId: (int)$row['employee_id'],
            entitlementAmount: (float)($row['entitlement_amount'] ?? 1250.00),
            status: (string)$row['status'],
            eligibilityDate: $row['eligibility_date'] ?? null,
            paidDate: $row['paid_date'] ?? null,
            paymentReference: (string)($row['payment_reference'] ?? ''),
            notes: (string)($row['notes'] ?? ''),
            createdBy: (int)($row['created_by'] ?? 0),
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? '')
        );
    }
}

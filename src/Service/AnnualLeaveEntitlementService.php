<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;
use App\Model\AnnualLeaveEntitlement;
use App\Repository\AnnualLeaveEntitlementRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * AnnualLeave Service
 *
 * Implements business logic for managing annual leave entitlements.
 */
class AnnualLeaveEntitlementService
{
    private AnnualLeaveEntitlementRepository $repo;
    private Database $db;

    public function __construct(AnnualLeaveEntitlementRepository $repo, Database $db)
    {
        $this->repo = $repo;
        $this->db = $db;
    }

    /**
     * Get an entitlement record by ID
     */
    public function getById(int $id, int $organizationId): AnnualLeaveEntitlement
    {
        $item = $this->repo->find($id, $organizationId);
        if ($item === null) {
            throw new NotFoundException("Annual Leave Entitlement with ID {$id} not found.");
        }
        return $item;
    }

    /**
     * List all entitlement records
     *
     * @return AnnualLeaveEntitlement[]
     */
    public function list(?int $orgId = null): array
    {
        return $this->repo->findAll($orgId);
    }

    /**
     * Get entitlement records by employee
     *
     * @return AnnualLeaveEntitlement[]
     */
    public function getByEmployee(int $employeeId, ?int $organizationId = null): array
    {
        return $this->repo->findByEmployee($employeeId, $organizationId);
    }

    /**
     * Create a new entitlement record
     */
    public function create(array $data, int $createdBy, int $organizationId): int
    {
        if (empty($data['employee_id'])) {
            throw new ValidationException(['employee_id' => 'Employee is required.']);
        }

        if (empty($data['entitlement_year'])) {
            throw new ValidationException(['entitlement_year' => 'Entitlement year is required.']);
        }

        $item = new AnnualLeaveEntitlement(
            id: 0,
            organizationId: $organizationId,
            employeeId: (int)$data['employee_id'],
            entitlementYear: (int)$data['entitlement_year'],
            totalLeaveDays: (float)($data['total_leave_days'] ?? 30.0),
            leaveAvailed: (float)($data['leave_availed'] ?? 0.0),
            leaveBalance: (float)($data['leave_balance'] ?? (float)($data['total_leave_days'] ?? 30.0)),
            airTicketAmount: (float)($data['air_ticket_amount'] ?? 1250.00),
            airTicketAvailed: (bool)($data['air_ticket_availed'] ?? false),
            status: $data['status'] ?? 'active',
            notes: (string)($data['notes'] ?? ''),
            createdBy: $createdBy,
        );

        return $this->repo->insert($item);
    }

    /**
     * Update an existing entitlement record
     */
    public function update(int $id, array $data, int $updatedBy, int $organizationId): bool
    {
        $this->getById($id, $organizationId);

        $allowedFields = ['total_leave_days', 'leave_availed', 'leave_balance', 'air_ticket_amount', 'air_ticket_availed', 'status', 'notes'];
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw new ValidationException(['update' => 'No valid fields provided for update.']);
        }

        return $this->repo->update($id, $updateData, $organizationId);
    }

    /**
     * Delete an entitlement record
     */
    public function delete(int $id, int $organizationId): bool
    {
        $this->getById($id, $organizationId);
        return $this->repo->delete($id, $organizationId);
    }

    /**
     * Calculate entitlement for an employee
     *
     * Recalculates leave balance = totalLeaveDays - leaveAvailed,
     * auto-updates status to 'availed' if balance <= 0.
     *
     * @return array{entitlement: AnnualLeaveEntitlement, balance: float, status: string}
     */
    public function calculateEntitlement(int $employeeId, int $organizationId): array
    {
        $entitlements = $this->repo->findByEmployee($employeeId, $organizationId);

        if (empty($entitlements)) {
            throw new NotFoundException("No entitlement found for employee ID {$employeeId}.");
        }

        $entitlement = $entitlements[0];
        $balance = $entitlement->totalLeaveDays - $entitlement->leaveAvailed;
        $newStatus = $entitlement->status;

        if ($balance <= 0) {
            $newStatus = 'availed';
        }

        if ($newStatus !== $entitlement->status || abs($balance - $entitlement->leaveBalance) > 0.001) {
            $this->repo->update($entitlement->id, [
                'leave_balance' => max(0.0, $balance),
                'status' => $newStatus,
            ], $organizationId);

            $entitlement = $this->repo->find($entitlement->id, $organizationId);
        }

        return [
            'entitlement' => $entitlement,
            'balance' => max(0.0, $balance),
            'status' => $newStatus,
        ];
    }

    /**
     * Get employees eligible for new entitlements
     *
     * Finds employees with date_of_joining 12+ months ago who don't have
     * an active entitlement for the current year.
     *
     * @return array Array of eligible user records (id, full_name, date_of_joining)
     */
    public function getActiveEntitlements(int $organizationId): array
    {
        $currentYear = (int)date('Y');

        $sql = "SELECT u.id, u.full_name, u.date_of_joining
                FROM " . DB::USERS . " u
                WHERE u.organization_id = :organization_id
                  AND u.is_active = 1
                  AND u.date_of_joining IS NOT NULL
                  AND u.date_of_joining <= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  AND u.id NOT IN (
                      SELECT ale.employee_id
                      FROM " . DB::ANNUAL_LEAVE_ENTITLEMENTS . " ale
                      WHERE ale.organization_id = :org_id2
                        AND ale.entitlement_year = :current_year
                        AND ale.status = 'active'
                  )
                ORDER BY u.full_name ASC";

        return $this->db->fetchAll($sql, [
            'organization_id' => $organizationId,
            'org_id2' => $organizationId,
            'current_year' => $currentYear,
        ]);
    }

    /**
     * Check milestone for an employee
     *
     * Checks 6-month milestone (return HR todo recommendation)
     * and 12-month milestone (trigger entitlement creation).
     *
     * @return array{milestone_6mo: bool, milestone_12mo: bool, recommendation: string}
     */
    public function checkMilestone(int $employeeId, int $organizationId): array
    {
        $sql = "SELECT id, full_name, date_of_joining
                FROM " . DB::USERS . "
                WHERE id = :employee_id
                  AND organization_id = :organization_id
                  AND is_active = 1
                  AND date_of_joining IS NOT NULL
                LIMIT 1";

        $user = $this->db->fetchOne($sql, [
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
        ]);

        if ($user === null) {
            throw new NotFoundException("Employee ID {$employeeId} not found or inactive.");
        }

        $dateOfJoining = new \DateTime($user['date_of_joining']);
        $now = new \DateTime();
        $monthsDiff = (int)$dateOfJoining->diff($now)->m + ((int)$dateOfJoining->diff($now)->y * 12);

        $milestone6mo = $monthsDiff >= 6;
        $milestone12mo = $monthsDiff >= 12;

        $recommendation = '';
        if ($milestone6mo && !$milestone12mo) {
            $recommendation = 'Employee has completed 6 months. Consider creating a leave reminder task.';
        } elseif ($milestone12mo) {
            $recommendation = 'Employee has completed 12 months. Eligible for annual leave entitlement and air ticket.';
        }

        return [
            'milestone_6mo' => $milestone6mo,
            'milestone_12mo' => $milestone12mo,
            'recommendation' => $recommendation,
        ];
    }
}

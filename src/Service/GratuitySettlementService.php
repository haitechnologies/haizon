<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;
use App\Core\Session;
use App\Model\GratuitySettlement;
use App\Repository\GratuitySettlementRepository;
use App\Exception\ValidationException;

/**
 * GratuitySettlement Service
 *
 * Implements business logic for managing gratuity settlements
 * including UAE statutory gratuity calculation.
 */
class GratuitySettlementService
{
    private GratuitySettlementRepository $repo;
    private Database $db;

    public function __construct(GratuitySettlementRepository $repo, Database $db)
    {
        $this->repo = $repo;
        $this->db = $db;
    }

    /**
     * Get a gratuity settlement by ID
     */
    public function getById(int $id): ?GratuitySettlement
    {
        return $this->repo->find($id);
    }

    /**
     * List all gratuity settlements, optionally scoped by organization
     *
     * @return GratuitySettlement[]
     */
    public function list(?int $orgId = null): array
    {
        if ($orgId === null) {
            $orgId = Session::orgId();
        }
        return $this->repo->findAll($orgId);
    }

    /**
     * Get gratuity settlements for a specific employee
     *
     * @return GratuitySettlement[]
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->repo->findByEmployee($employeeId);
    }

    /**
     * Create a new gratuity settlement
     *
     * @param array $data Settlement data
     * @param int $createdBy User ID creating the record
     * @return int Inserted record ID
     */
    public function create(array $data, int $createdBy): int
    {
        $employeeId = (int)($data['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            throw new ValidationException(['employee_id' => 'Employee is required.']);
        }

        // Verify employee exists
        $employee = $this->db->fetchOne(
            "SELECT id FROM `" . DB::USERS . "` WHERE id = :id AND is_active = 1",
            ['id' => $employeeId]
        );
        if ($employee === null) {
            throw new ValidationException(['employee_id' => 'Selected employee not found or inactive.']);
        }

        $orgId = (int)($data['organization_id'] ?? Session::orgId());

        $settlement = new GratuitySettlement(
            id: 0,
            organizationId: $orgId,
            employeeId: $employeeId,
            totalTenureYears: (float)($data['total_tenure_years'] ?? 0),
            totalTenureDays: (int)($data['total_tenure_days'] ?? 0),
            lastBasicSalary: (float)($data['last_basic_salary'] ?? 0),
            gratuityAmount: (float)($data['gratuity_amount'] ?? 0),
            status: 'calculated',
            settlementDate: $data['settlement_date'] ?? null,
            paymentDate: $data['payment_date'] ?? null,
            paymentReference: (string)($data['payment_reference'] ?? ''),
            notes: (string)($data['notes'] ?? ''),
            createdBy: $createdBy,
            approvedBy: null,
        );

        return $this->repo->insert($settlement);
    }

    /**
     * Update an existing gratuity settlement
     *
     * @param int $id Settlement ID
     * @param array $data Fields to update
     * @param int $updatedBy User ID performing the update
     * @return bool True if successful
     */
    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            throw new ValidationException(['id' => 'Gratuity settlement not found.']);
        }

        // Only allow white-listed fields
        $allowed = [
            'employee_id', 'total_tenure_years', 'total_tenure_days',
            'last_basic_salary', 'gratuity_amount', 'status',
            'settlement_date', 'payment_date', 'payment_reference', 'notes',
        ];

        $updateData = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        // If status is being changed to 'approved', set approved_by
        if (isset($data['status']) && $data['status'] === 'approved') {
            $updateData['approved_by'] = $updatedBy;
        }

        return $this->repo->update($id, $updateData);
    }

    /**
     * Delete a gratuity settlement
     *
     * @param int $id Settlement ID
     * @return bool True if successful
     */
    public function delete(int $id): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        // Only allow deletion of 'calculated' status settlements
        if ($existing->status !== 'calculated') {
            throw new ValidationException(['status' => 'Only settlements with status "calculated" can be deleted.']);
        }

        return $this->repo->delete($id);
    }

    /**
     * Calculate UAE gratuity for an employee
     *
     * Implements UAE Labour Law gratuity calculation:
     * - First 5 years: 21 days' basic salary per year
     * - After 5 years: 30 days' basic salary per year
     * - Per day basic = last_basic_salary / 30
     *
     * @param int $employeeId Employee user ID
     * @return array Calculated values with keys: total_tenure_years, total_tenure_days, last_basic_salary, gratuity_amount
     */
    public function calculateGratuity(int $employeeId): array
    {
        // Fetch employee's date of joining
        $employee = $this->db->fetchOne(
            "SELECT id, date_of_joining, full_name FROM `" . DB::USERS . "` WHERE id = :id AND is_active = 1",
            ['id' => $employeeId]
        );

        if ($employee === null) {
            throw new ValidationException(['employee_id' => 'Employee not found or inactive.']);
        }

        $dateOfJoining = $employee['date_of_joining'] ?? '';
        if (empty($dateOfJoining) || $dateOfJoining === '0000-00-00') {
            throw new ValidationException(['date_of_joining' => 'Employee does not have a date of joining set.']);
        }

        // Calculate total tenure
        $joiningDate = new \DateTime($dateOfJoining);
        $today = new \DateTime('today');
        $interval = $joiningDate->diff($today);

        $totalTenureYears = (float)$interval->y + (float)$interval->m / 12.0 + (float)$interval->d / 365.0;
        $totalTenureDays = (int)$interval->days;

        // Fetch last basic salary from employee_salaries
        $salary = $this->db->fetchOne(
            "SELECT es.amount
             FROM `" . DB::EMPLOYEE_SALARIES . "` es
             INNER JOIN `" . DB::SALARY_STRUCTURES . "` ss ON es.salary_structure_id = ss.id
             WHERE es.employee_id = :employee_id
               AND ss.is_basic = 1
             ORDER BY es.id DESC
             LIMIT 1",
            ['employee_id' => $employeeId]
        );

        $lastBasicSalary = (float)($salary['amount'] ?? 0);

        if ($lastBasicSalary <= 0) {
            throw new ValidationException(['last_basic_salary' => 'No basic salary found for this employee. Please set up employee salary with a basic salary component.']);
        }

        // UAE gratuity calculation
        $perDayBasic = $lastBasicSalary / 30;

        $firstFiveYears = min($totalTenureYears, 5);
        $remainingYears = max($totalTenureYears - 5, 0);

        $gratuityAmount = ($perDayBasic * 21 * $firstFiveYears) + ($perDayBasic * 30 * $remainingYears);

        return [
            'total_tenure_years' => round($totalTenureYears, 2),
            'total_tenure_days' => $totalTenureDays,
            'last_basic_salary' => round($lastBasicSalary, 2),
            'gratuity_amount' => round($gratuityAmount, 2),
        ];
    }
}

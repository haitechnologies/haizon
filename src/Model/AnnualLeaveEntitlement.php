<?php

declare(strict_types=1);

namespace App\Model;

/**
 * AnnualLeaveEntitlement DTO
 *
 * Readonly data transfer object representing an annual leave entitlement record.
 */
readonly class AnnualLeaveEntitlement
{
    public function __construct(
        public int $id = 0,
        public int $organizationId = 0,
        public int $employeeId = 0,
        public int $entitlementYear = 0,
        public float $totalLeaveDays = 30.0,
        public float $leaveAvailed = 0.0,
        public float $leaveBalance = 30.0,
        public float $airTicketAmount = 1250.00,
        public bool $airTicketAvailed = false,
        public string $status = 'active',
        public string $notes = '',
        public int $createdBy = 0,
        public string $createdAt = '',
        public string $updatedAt = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'employee_id' => $this->employeeId,
            'entitlement_year' => $this->entitlementYear,
            'total_leave_days' => $this->totalLeaveDays,
            'leave_availed' => $this->leaveAvailed,
            'leave_balance' => $this->leaveBalance,
            'air_ticket_amount' => $this->airTicketAmount,
            'air_ticket_availed' => $this->airTicketAvailed,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

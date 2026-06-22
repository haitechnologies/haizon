<?php

declare(strict_types=1);

namespace App\Model;

/**
 * GratuitySettlement DTO
 *
 * Readonly data transfer object representing a gratuity settlement record.
 */
readonly class GratuitySettlement
{
    public function __construct(
        public int $id = 0,
        public int $organizationId = 0,
        public int $employeeId = 0,
        public float $totalTenureYears = 0.0,
        public int $totalTenureDays = 0,
        public float $lastBasicSalary = 0.0,
        public float $gratuityAmount = 0.0,
        public string $status = 'calculated',
        public ?string $settlementDate = null,
        public ?string $paymentDate = null,
        public string $paymentReference = '',
        public string $notes = '',
        public int $createdBy = 0,
        public ?int $approvedBy = null,
        public string $createdAt = '',
        public string $updatedAt = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'employee_id' => $this->employeeId,
            'total_tenure_years' => $this->totalTenureYears,
            'total_tenure_days' => $this->totalTenureDays,
            'last_basic_salary' => $this->lastBasicSalary,
            'gratuity_amount' => $this->gratuityAmount,
            'status' => $this->status,
            'settlement_date' => $this->settlementDate,
            'payment_date' => $this->paymentDate,
            'payment_reference' => $this->paymentReference,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
            'approved_by' => $this->approvedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

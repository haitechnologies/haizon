<?php

declare(strict_types=1);

namespace App\Model;

/**
 * AirTicket DTO
 *
 * Readonly data transfer object representing an air ticket entitlement record.
 */
readonly class AirTicket
{
    public function __construct(
        public int $id = 0,
        public int $organizationId = 0,
        public int $employeeId = 0,
        public float $entitlementAmount = 1250.00,
        public string $status = 'pending',
        public ?string $eligibilityDate = null,
        public ?string $paidDate = null,
        public string $paymentReference = '',
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
            'entitlement_amount' => $this->entitlementAmount,
            'status' => $this->status,
            'eligibility_date' => $this->eligibilityDate,
            'paid_date' => $this->paidDate,
            'payment_reference' => $this->paymentReference,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

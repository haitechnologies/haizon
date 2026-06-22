<?php

declare(strict_types=1);

namespace App\Model;

readonly class Journal
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public string $journalStatus,
        public string $journalNo,
        public string $journalDate,
        public ?string $referenceNo,
        public string $notes,
        public string $reportingMethod,
        public ?string $referenceType = null,
        public int $referenceId = 0,
        public string $currency = 'AED',
        public float $grandSubtotal = 0.0,
        public float $grandTotal = 0.0,
        public int $warehouseId = 0,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?int $updatedBy = null,
        public int $createdBy = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'journal_status' => $this->journalStatus,
            'journal_no' => $this->journalNo,
            'journal_date' => $this->journalDate,
            'reference_no' => $this->referenceNo,
            'notes' => $this->notes,
            'reporting_method' => $this->reportingMethod,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'currency' => $this->currency,
            'grand_subtotal' => $this->grandSubtotal,
            'grand_total' => $this->grandTotal,
            'warehouse_id' => $this->warehouseId,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'updated_by' => $this->updatedBy,
            'created_by' => $this->createdBy,
        ];
    }
}

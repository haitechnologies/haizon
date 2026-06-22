<?php

declare(strict_types=1);

namespace App\Model;

readonly class Expense
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public string $expenseDate,
        public int $paidThrough,
        public int $vendorId,
        public ?string $referenceNo,
        public int $customerId,
        public bool $billable,
        public float $grandTotal,
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
            'expense_date' => $this->expenseDate,
            'paid_through' => $this->paidThrough,
            'vendor_id' => $this->vendorId,
            'reference_no' => $this->referenceNo,
            'customer_id' => $this->customerId,
            'billable' => $this->billable ? 1 : 0,
            'grand_total' => $this->grandTotal,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'updated_by' => $this->updatedBy,
            'created_by' => $this->createdBy,
        ];
    }
}

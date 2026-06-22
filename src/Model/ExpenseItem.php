<?php

declare(strict_types=1);

namespace App\Model;

readonly class ExpenseItem
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public int $expenseId,
        public int $expenseAccount,
        public ?string $description = null,
        public float $total = 0.0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public int $createdBy = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'expense_id' => $this->expenseId,
            'expense_account' => $this->expenseAccount,
            'description' => $this->description,
            'total' => $this->total,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}

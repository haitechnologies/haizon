<?php

declare(strict_types=1);

namespace App\Model;

readonly class JournalItem
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public int $journalId,
        public int $account,
        public ?string $description = null,
        public float $debit = 0.0,
        public float $credit = 0.0,
        public ?string $referenceNo = null,
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
            'journal_id' => $this->journalId,
            'account' => $this->account,
            'description' => $this->description,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'reference_no' => $this->referenceNo,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}

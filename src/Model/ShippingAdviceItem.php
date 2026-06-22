<?php

declare(strict_types=1);

namespace App\Model;

readonly class ShippingAdviceItem
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public int $adviceId,
        public ?string $description = null,
        public int $coo = 0,
        public ?string $declarationNo = null,
        public ?string $hscode = null,
        public int $qty = 1,
        public float $rate = 0.0,
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
            'advice_id' => $this->adviceId,
            'description' => $this->description,
            'coo' => $this->coo,
            'declaration_no' => $this->declarationNo,
            'hscode' => $this->hscode,
            'qty' => $this->qty,
            'rate' => $this->rate,
            'total' => $this->total,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}

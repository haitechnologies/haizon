<?php

declare(strict_types=1);

namespace App\Model;

readonly class PaymentMethod
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public string $paymentMethod,
        public bool $isActive,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public int $createdBy = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'payment_method' => $this->paymentMethod,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}

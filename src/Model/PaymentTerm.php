<?php

declare(strict_types=1);

namespace App\Model;

readonly class PaymentTerm
{
    public function __construct(
        public int $id,
        public string $paymentTerm = '',
        public string $description = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

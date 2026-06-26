<?php

declare(strict_types=1);

namespace App\Model;

readonly class SalaryStructure
{
    public function __construct(
        public int $id,
        public int $organizationId,
        public int $employeeId,
        public int $componentId,
        public float $amount,
        public ?string $effectiveFrom,
        public ?string $effectiveTo,
        public bool $isBasic = false,
        public int $createdBy = 0,
        public ?string $createdAt = null,
    ) {}
}

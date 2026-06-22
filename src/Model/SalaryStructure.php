<?php

declare(strict_types=1);

namespace App\Model;

readonly class SalaryStructure
{
    public function __construct(
        public int $id,
        public string $effectiveFrom = "",
        public string $description = "",
        public int $createdBy = 0,
        public string $createdAt = "",
    ) {}
}

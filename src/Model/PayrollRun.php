<?php

declare(strict_types=1);

namespace App\Model;

readonly class PayrollRun
{
    public function __construct(
        public int $id,
        public string $periodStart = "",
        public string $description = "",
        public int $createdBy = 0,
        public string $createdAt = "",
    ) {}
}

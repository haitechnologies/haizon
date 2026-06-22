<?php

declare(strict_types=1);

namespace App\Model;

readonly class PayrollComponent
{
    public function __construct(
        public int $id,
        public string $componentName = '',
        public string $description = '',
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

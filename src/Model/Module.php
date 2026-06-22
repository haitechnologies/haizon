<?php

declare(strict_types=1);

namespace App\Model;

readonly class Module
{
    public function __construct(
        public int $id,
        public string $moduleName = '',
        public string $description = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

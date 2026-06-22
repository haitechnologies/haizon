<?php

declare(strict_types=1);

namespace App\Model;

readonly class Account
{
    public function __construct(
        public int $id,
        public int $parentId = 0,
        public string $accountType = '',
        public string $accountName = '',
        public string $accountCode = '',
        public string $description = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Model;

readonly class Item
{
    public function __construct(
        public int $id,
        public string $itemType = 'services',
        public string $itemName = '',
        public string $unitPrice = '0',
        public bool $isExcise = false,
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

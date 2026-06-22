<?php

declare(strict_types=1);

namespace App\Model;

readonly class StorageSubtype
{
    public function __construct(
        public int $id,
        public string $storageSubtype = '',
        public int $storageTypeId = 0,
        public string $description = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

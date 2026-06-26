<?php

declare(strict_types=1);

namespace App\Model;

readonly class DocumentCategory
{
    public function __construct(
        public int $id,
        public string $documentCategory = '',
        public string $documentCategoryType = '',
        public bool $isActive = true,
        public bool $isMandatory = false,
        public int $createdBy = 0,
        public int $updatedBy = 0,
        public string $createdAt = '',
        public string $updatedAt = '',
    ) {}
}

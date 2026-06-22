<?php

declare(strict_types=1);

namespace App\Model;

readonly class Category
{
    public function __construct(
        public int $id,
        public string $name = '',
        public string $slug = '',
        public string $description = '',
        public string $icon = '',
        public string $metaTitle = '',
        public string $metaDescription = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

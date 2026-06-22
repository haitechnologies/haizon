<?php

declare(strict_types=1);

namespace App\Model;

use App\Helper\SlugHelper;

readonly class SetupSource
{
    public function __construct(
        public int $id,
        public string $sourceName = '',
        public string $sourceType = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->sourceType,
            'value' => $this->sourceName,
            'key' => SlugHelper::slugify($this->sourceName),
            'is_active' => $this->isActive ? 1 : 0,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
        ];
    }
}

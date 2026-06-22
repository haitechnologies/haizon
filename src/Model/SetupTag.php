<?php

declare(strict_types=1);

namespace App\Model;

use App\Helper\SlugHelper;

readonly class SetupTag
{
    public function __construct(
        public int $id,
        public string $tagName = '',
        public string $tagType = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->tagType,
            'value' => $this->tagName,
            'key' => SlugHelper::slugify($this->tagName),
            'is_active' => $this->isActive ? 1 : 0,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
        ];
    }
}

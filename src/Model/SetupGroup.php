<?php

declare(strict_types=1);

namespace App\Model;

use App\Helper\SlugHelper;

readonly class SetupGroup
{
    public function __construct(
        public int $id,
        public string $groupName = '',
        public string $description = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'value' => $this->groupName,
            'key' => SlugHelper::slugify($this->groupName),
            'description' => $this->description,
            'is_active' => $this->isActive ? 1 : 0,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
        ];
    }
}

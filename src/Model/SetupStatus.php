<?php

declare(strict_types=1);

namespace App\Model;

use App\Helper\SlugHelper;

readonly class SetupStatus
{
    public function __construct(
        public int $id,
        public string $statusName = '',
        public string $statusType = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->statusType,
            'value' => $this->statusName,
            'key' => SlugHelper::slugify($this->statusName),
            'is_active' => $this->isActive ? 1 : 0,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
        ];
    }
}

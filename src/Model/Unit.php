<?php

declare(strict_types=1);

namespace App\Model;

readonly class Unit
{
    public function __construct(
        public int $id,
        public string $unitName = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unit' => $this->unitName,
            'publish' => $this->isActive ? 1 : 0,
            'is_active' => $this->isActive ? 1 : 0,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
        ];
    }
}

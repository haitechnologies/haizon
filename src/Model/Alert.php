<?php

declare(strict_types=1);

namespace App\Model;

readonly class Alert
{
    public function __construct(
        public int $id,
        public string $alertName = '',
        public string $description = '',
        public string $type = 'general',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

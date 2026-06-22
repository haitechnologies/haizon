<?php

declare(strict_types=1);

namespace App\Model;

readonly class Hscode
{
    public function __construct(
        public int $id,
        public string $code = '',
        public string $oldCode = '',
        public int $level = 0,
        public string $dutyRate = '',
        public bool $isActive = true,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

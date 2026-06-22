<?php

declare(strict_types=1);

namespace App\Model;

readonly class Attendance
{
    public function __construct(
        public int $id,
        public int $employeeId = 0,
        public string $workDate = '',
        public ?string $checkIn = null,
        public ?string $checkOut = null,
        public float $totalHours = 0,
        public string $status = 'present',
        public int $createdBy = 0,
        public ?string $createdAt = null,
    ) {}
}

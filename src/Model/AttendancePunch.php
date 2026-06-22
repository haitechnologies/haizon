<?php

declare(strict_types=1);

namespace App\Model;

readonly class AttendancePunch
{
    public function __construct(
        public int $id = 0,
        public int $organizationId = 0,
        public int $deviceId = 0,
        public int $employeeId = 0,
        public string $zkUserId = '',
        public string $punchTime = '',
        public int $punchType = 0,
        public int $verificationMode = 0,
        public int $status = 0,
        public int $isSynced = 0,
        public ?string $createdAt = null,
    ) {
    }
}

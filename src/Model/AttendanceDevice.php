<?php

declare(strict_types=1);

namespace App\Model;

readonly class AttendanceDevice
{
    public function __construct(
        public int $id = 0,
        public int $organizationId = 0,
        public string $deviceName = '',
        public string $ipAddress = '',
        public int $port = 4370,
        public string $serialNumber = '',
        public string $devicePassword = '0',
        public string $deviceModel = '',
        public string $location = '',
        public ?string $lastSyncAt = null,
        public ?string $lastPunchAt = null,
        public int $isActive = 1,
        public int $createdBy = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}

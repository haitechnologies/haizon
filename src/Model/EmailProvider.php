<?php

declare(strict_types=1);

namespace App\Model;

readonly class EmailProvider
{
    public function __construct(
        public int $id,
        public string $providerName = '',
        public string $emailEncryption = 'NONE',
        public string $smtpHost = '',
        public string $smtpPort = '',
        public string $email = '',
        public string $smtpUsername = '',
        public string $smtpPassword = '',
        public bool $isActive = true,
        public bool $isPrimary = false,
        public int $createdBy = 0,
        public string $createdAt = '',
    ) {}
}

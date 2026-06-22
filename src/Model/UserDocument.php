<?php

declare(strict_types=1);

namespace App\Model;

readonly class UserDocument
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public int $userId,
        public ?int $documentType,
        public ?string $filename,
        public ?string $originalFilename,
        public ?int $fileSize,
        public ?string $description,
        public ?string $issuedDate,
        public ?string $expiryDate,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public int $createdBy = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'user_id' => $this->userId,
            'document_type' => $this->documentType,
            'filename' => $this->filename,
            'original_filename' => $this->originalFilename,
            'file_size' => $this->fileSize,
            'description' => $this->description,
            'issued_date' => $this->issuedDate,
            'expiry_date' => $this->expiryDate,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}

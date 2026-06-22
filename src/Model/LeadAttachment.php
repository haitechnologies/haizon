<?php

declare(strict_types=1);

namespace App\Model;

readonly class LeadAttachment
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public int $leadId,
        public ?string $filename,
        public ?string $originalFilename,
        public ?int $fileSize,
        public ?string $description,
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
            'lead_id' => $this->leadId,
            'filename' => $this->filename,
            'original_filename' => $this->originalFilename,
            'file_size' => $this->fileSize,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}

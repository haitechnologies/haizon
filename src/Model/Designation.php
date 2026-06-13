<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Designation DTO
 *
 * Readonly data transfer object representing a designation record.
 */
readonly class Designation
{
    public function __construct(
        public ?int $id,
        public ?int $organizationId,
        public string $designation,
        public bool $publish,
        public bool $isActive = false,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public int $createdBy = 0
    ) {
    }

    /**
     * Convert DTO to a legacy-compatible array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'designation' => $this->designation,
            'publish' => $this->publish ? 1 : 0,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}

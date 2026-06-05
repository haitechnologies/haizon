<?php

declare(strict_types=1);

namespace App\Model;

/**
 * LeaveType DTO
 *
 * Readonly data transfer object representing a leave type record.
 */
readonly class LeaveType
{
    public function __construct(
        public ?int $id,
        public ?int $organizationId,
        public string $leaveType,
        public int $maxPerYear,
        public bool $paid,
        public ?string $createdAt = null,
        public ?string $updatedAt = null
    ) {
    }

    /**
     * Convert DTO to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'leave_type' => $this->leaveType,
            'max_per_year' => $this->maxPerYear,
            'paid' => $this->paid ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

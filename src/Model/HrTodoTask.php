<?php

declare(strict_types=1);

namespace App\Model;

/**
 * HrTodoTask DTO
 *
 * Readonly data transfer object representing an HR to-do task record.
 */
readonly class HrTodoTask
{
    public function __construct(
        public int $id = 0,
        public int $organizationId = 0,
        public int $employeeId = 0,
        public string $taskType = '',
        public string $description = '',
        public string $dueDate = '',
        public string $status = 'pending',
        public int $assignedTo = 0,
        public int $completedBy = 0,
        public string $completedAt = '',
        public string $notes = '',
        public int $createdBy = 0,
        public string $createdAt = '',
        public string $updatedAt = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'employee_id' => $this->employeeId,
            'task_type' => $this->taskType,
            'description' => $this->description,
            'due_date' => $this->dueDate,
            'status' => $this->status,
            'assigned_to' => $this->assignedTo,
            'completed_by' => $this->completedBy,
            'completed_at' => $this->completedAt,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

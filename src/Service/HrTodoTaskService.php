<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Model\HrTodoTask;
use App\Repository\HrTodoTaskRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * HrTodo Service
 *
 * Implements business logic for managing HR to-do tasks.
 */
class HrTodoTaskService
{
    private HrTodoTaskRepository $repo;
    private Database $db;

    public function __construct(HrTodoTaskRepository $repo, Database $db)
    {
        $this->repo = $repo;
        $this->db = $db;
    }

    /**
     * Get a task by ID
     */
    public function getById(int $id, int $organizationId): HrTodoTask
    {
        $task = $this->repo->find($id, $organizationId);
        if ($task === null) {
            throw new NotFoundException("HR Todo Task with ID {$id} not found.");
        }
        return $task;
    }

    /**
     * List all tasks
     *
     * @return HrTodoTask[]
     */
    public function list(?int $orgId = null, ?string $status = null): array
    {
        return $this->repo->findAll($orgId, $status);
    }

    /**
     * Get tasks by employee
     *
     * @return HrTodoTask[]
     */
    public function getByEmployee(int $employeeId, ?int $organizationId = null): array
    {
        return $this->repo->findByEmployee($employeeId, $organizationId);
    }

    /**
     * Create a new task
     */
    public function create(array $data, int $createdBy, int $organizationId): int
    {
        if (empty($data['employee_id'])) {
            throw new ValidationException(['employee_id' => 'Employee is required.']);
        }

        if (empty($data['task_type'])) {
            throw new ValidationException(['task_type' => 'Task type is required.']);
        }

        if (empty($data['description'])) {
            throw new ValidationException(['description' => 'Description is required.']);
        }

        $task = new HrTodoTask(
            id: 0,
            organizationId: $organizationId,
            employeeId: (int)$data['employee_id'],
            taskType: (string)$data['task_type'],
            description: (string)$data['description'],
            dueDate: (string)($data['due_date'] ?? ''),
            status: $data['status'] ?? 'pending',
            assignedTo: (int)($data['assigned_to'] ?? 0),
            completedBy: 0,
            completedAt: '',
            notes: (string)($data['notes'] ?? ''),
            createdBy: $createdBy,
        );

        return $this->repo->insert($task);
    }

    /**
     * Update an existing task
     */
    public function update(int $id, array $data, int $updatedBy, int $organizationId): bool
    {
        $this->getById($id, $organizationId);

        $allowedFields = ['task_type', 'description', 'due_date', 'status', 'assigned_to', 'notes'];
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw new ValidationException(['update' => 'No valid fields provided for update.']);
        }

        return $this->repo->update($id, $updateData, $organizationId);
    }

    /**
     * Mark a task as completed
     */
    public function markComplete(int $id, int $completedBy, int $organizationId): bool
    {
        $this->getById($id, $organizationId);

        return $this->repo->update($id, [
            'status' => 'completed',
            'completed_by' => $completedBy,
            'completed_at' => date('Y-m-d H:i:s'),
        ], $organizationId);
    }

    /**
     * Mark a task as archived
     */
    public function markArchived(int $id, int $organizationId): bool
    {
        $this->getById($id, $organizationId);

        return $this->repo->update($id, [
            'status' => 'archived',
        ], $organizationId);
    }

    /**
     * Delete a task
     */
    public function delete(int $id, int $organizationId): bool
    {
        $this->getById($id, $organizationId);
        return $this->repo->delete($id, $organizationId);
    }
}

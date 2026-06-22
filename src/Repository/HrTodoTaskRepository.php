<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\HrTodoTask;

/**
 * HrTodoTask Repository
 *
 * Handles PDO-based data access for erp_hr_todo_tasks table.
 * Enforces strict tenant isolation on organization_id.
 */
class HrTodoTaskRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a task by ID
     */
    public function find(int $id, int $organizationId): ?HrTodoTask
    {
        $sql = "SELECT id, organization_id, employee_id, task_type, 
                       description, due_date, status, assigned_to, 
                       completed_by, completed_at, notes,
                       created_by, created_at, updated_at
                FROM `{DB::HR_TODO_TASKS}`
                WHERE id = :id AND organization_id = :organization_id";

        $row = $this->db->fetchOne($sql, [
            'id' => $id,
            'organization_id' => $organizationId,
        ]);

        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    /**
     * Find all tasks, optionally filtered by organization and/or status
     *
     * @return HrTodoTask[]
     */
    public function findAll(?int $orgId = null, ?string $status = null): array
    {
        $sql = "SELECT id, organization_id, employee_id, task_type, 
                       description, due_date, status, assigned_to, 
                       completed_by, completed_at, notes,
                       created_by, created_at, updated_at
                FROM `{DB::HR_TODO_TASKS}`";

        $conditions = [];
        $params = [];

        if ($orgId !== null) {
            $conditions[] = "organization_id = :organization_id";
            $params['organization_id'] = $orgId;
        }

        if ($status !== null) {
            $conditions[] = "status = :status";
            $params['status'] = $status;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRowToDto($row);
        }

        return $items;
    }

    /**
     * Find tasks by employee ID
     *
     * @return HrTodoTask[]
     */
    public function findByEmployee(int $employeeId, ?int $organizationId = null): array
    {
        $sql = "SELECT id, organization_id, employee_id, task_type, 
                       description, due_date, status, assigned_to, 
                       completed_by, completed_at, notes,
                       created_by, created_at, updated_at
                FROM `{DB::HR_TODO_TASKS}`
                WHERE employee_id = :employee_id";

        $params = ['employee_id' => $employeeId];
        if ($organizationId !== null) {
            $sql .= " AND organization_id = :organization_id";
            $params['organization_id'] = $organizationId;
        }

        $sql .= " ORDER BY due_date ASC, created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRowToDto($row);
        }

        return $items;
    }

    /**
     * Find tasks by task type
     *
     * @return HrTodoTask[]
     */
    public function findByType(string $taskType, int $organizationId): array
    {
        $sql = "SELECT id, organization_id, employee_id, task_type, 
                       description, due_date, status, assigned_to, 
                       completed_by, completed_at, notes,
                       created_by, created_at, updated_at
                FROM `{DB::HR_TODO_TASKS}`
                WHERE task_type = :task_type
                  AND organization_id = :organization_id
                ORDER BY created_at DESC";

        $params = [
            'task_type' => $taskType,
            'organization_id' => $organizationId,
        ];

        $rows = $this->db->fetchAll($sql, $params);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRowToDto($row);
        }

        return $items;
    }

    /**
     * Insert a new task record
     */
    public function insert(HrTodoTask $item): int
    {
        $sql = "INSERT INTO `{DB::HR_TODO_TASKS}` 
                (organization_id, employee_id, task_type, 
                 description, due_date, status, assigned_to, 
                 completed_by, completed_at, notes, created_by)
                VALUES (:organization_id, :employee_id, :task_type,
                        :description, :due_date, :status, :assigned_to,
                        :completed_by, :completed_at, :notes, :created_by)";

        $params = [
            'organization_id' => $item->organizationId,
            'employee_id' => $item->employeeId,
            'task_type' => $item->taskType,
            'description' => $item->description,
            'due_date' => $item->dueDate,
            'status' => $item->status,
            'assigned_to' => $item->assignedTo,
            'completed_by' => $item->completedBy,
            'completed_at' => $item->completedAt,
            'notes' => $item->notes,
            'created_by' => $item->createdBy,
        ];

        return (int)$this->db->insert($sql, $params);
    }

    /**
     * Update an existing task record
     */
    public function update(int $id, array $data, int $organizationId): bool
    {
        $allowedFields = ['task_type', 'description', 'due_date', 'status', 'assigned_to', 'completed_by', 'completed_at', 'notes'];
        $setClauses = [];
        $params = ['id' => $id, 'organization_id' => $organizationId];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields, true)) {
                $setClauses[] = "`{$field}` = :{$field}";
                $params[$field] = $value;
            }
        }

        if (empty($setClauses)) {
            return false;
        }

        $setClauses[] = "updated_at = NOW()";
        $setString = implode(', ', $setClauses);

        $sql = "UPDATE `{DB::HR_TODO_TASKS}` 
                SET {$setString}
                WHERE id = :id AND organization_id = :organization_id";

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a task record
     */
    public function delete(int $id, int $organizationId): bool
    {
        $sql = "DELETE FROM `{DB::HR_TODO_TASKS}` WHERE id = :id AND organization_id = :organization_id";
        $stmt = $this->db->execute($sql, [
            'id' => $id,
            'organization_id' => $organizationId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Map database row to HrTodoTask DTO
     */
    private function mapRowToDto(array $row): HrTodoTask
    {
        return new HrTodoTask(
            id: (int)$row['id'],
            organizationId: (int)$row['organization_id'],
            employeeId: (int)$row['employee_id'],
            taskType: (string)($row['task_type'] ?? ''),
            description: (string)($row['description'] ?? ''),
            dueDate: (string)($row['due_date'] ?? ''),
            status: (string)$row['status'],
            assignedTo: (int)($row['assigned_to'] ?? 0),
            completedBy: (int)($row['completed_by'] ?? 0),
            completedAt: (string)($row['completed_at'] ?? ''),
            notes: (string)($row['notes'] ?? ''),
            createdBy: (int)($row['created_by'] ?? 0),
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? '')
        );
    }
}

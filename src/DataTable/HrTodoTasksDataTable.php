<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\ActionButtonHelper;
use App\Helper\BadgeHelper;

class HrTodoTasksDataTable extends BaseDataTable
{
    protected $table = DB::HR_TODO_TASKS;
    protected $searchFields = ['t.task_type', 't.description', 'u.full_name'];
    protected $sortableColumns = [
        0 => 't.id',
        1 => 'u.full_name',
        2 => 't.task_type',
        3 => 't.description',
        4 => 't.due_date',
        5 => 't.status',
        6 => 't.id',
    ];

    protected function buildBaseQuery($requestData)
    {
        return "SELECT t.*, u.full_name as employee_name
                FROM `" . $this->table . "` t
                LEFT JOIN `" . DB::USERS . "` u ON u.id = t.employee_id
                WHERE t.id > 0" . $this->getOrgIdWhereClause();
    }

    protected function getOrgIdWhereClause(): string
    {
        if ($this->organizationId === null) {
            return '';
        }
        $this->params['active_org_id'] = (int)$this->organizationId;
        return " AND t.`organization_id` = :active_org_id";
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $employeeName = (string)($row['employee_name'] ?? '');
        $taskType = (string)($row['task_type'] ?? '');
        $description = (string)($row['description'] ?? '');
        $dueDate = (string)($row['due_date'] ?? '');
        $status = (string)($row['status'] ?? 'pending');

        $statusBadge = match ($status) {
            'pending' => BadgeHelper::warning('Pending'),
            'completed' => BadgeHelper::success('Completed'),
            'archived' => BadgeHelper::secondary('Archived'),
            default => BadgeHelper::secondary(ucfirst($status)),
        };

        return [
            $this->rowNumber,
            htmlspecialchars($employeeName),
            htmlspecialchars($taskType),
            htmlspecialchars($description),
            !empty($dueDate) && $dueDate !== '0000-00-00' ? $this->formatDate($dueDate) : '-',
            $statusBadge,
            $this->getActionButtons($id, 'hr_todo_tasks'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'hr_todo_tasks.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

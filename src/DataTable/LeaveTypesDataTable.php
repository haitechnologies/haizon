<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class LeaveTypesDataTable extends BaseDataTable
{
    protected $table = DB::LEAVE_TYPES;
    protected $searchFields = ['leave_type'];
    protected $sortableColumns = [0 => 'id', 1 => 'leave_type', 2 => 'paid', 3 => 'id'];

    protected function getOrgIdWhereClause(): string
    {
        if ($this->organizationId === null) {
            return '';
        }
        $this->params['leave_types_org_id'] = $this->organizationId;
        return " AND organization_id = :leave_types_org_id";
    }

    protected function formatRow($row, $requestData = [])
    {
        $id       = (int)($row['id'] ?? 0);
        $type     = (string)($row['leave_type'] ?? '');
        $paid     = (int)($row['paid'] ?? 0);

        $paidHtml = $paid == 1 
            ? '<span class="badge bg-success">Yes</span>' 
            : '<span class="badge bg-secondary">No</span>';

        $rules = [
            'Annual Leave' => '12 months DOJ, 1 month paid + ticket',
            'Sick Leave'   => 'Paid per medical certificate',
            'Urgent Leave' => '3 days paid, 1x/year from DOJ',
        ];

        return [
            $this->rowNumber,
            htmlspecialchars($type),
            $paidHtml,
            '<span class="text-muted small">' . htmlspecialchars($rules[$type] ?? '') . '</span>',
            $this->getActionButtons($id, 'leave_types'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'leave_types.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

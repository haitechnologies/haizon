<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\ActionButtonHelper;

class LeaveRequestsDataTable extends BaseDataTable
{
    protected $table = DB::LEAVE_REQUESTS;
    protected $searchFields = ['status'];
    protected $sortableColumns = [
        0 => 'id',
        1 => 'employee_id',
        2 => 'leave_type_id',
        3 => 'start_date',
        4 => 'end_date',
        5 => 'total_days',
        6 => 'paid_days',
        7 => 'status',
        8 => 'id'
    ];

    protected function getOrgIdWhereClause(): string
    {
        return $this->organizationId ? " AND organization_id = " . (int)$this->organizationId : "";
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)$row['id'];
        $totalDays = (float)($row['total_days'] ?? 0);
        $paidDays = (float)($row['paid_days'] ?? 0);
        $unpaidDays = $totalDays - $paidDays;
        $paidDisplay = '<span class="badge bg-success">' . (int)$paidDays . ' Paid</span>';
        if ($unpaidDays > 0) {
            $paidDisplay .= ' / <span class="badge bg-secondary">' . (int)$unpaidDays . ' Unpaid</span>';
        }
        return [
            $this->rowNumber,
            getTableAttr('full_name', DB::USERS, $row['employee_id']),
            getTableAttr('leave_type', DB::LEAVE_TYPES, $row['leave_type_id']),
            s__($row['start_date']),
            s__($row['end_date']),
            s__($row['total_days']),
            $paidDisplay,
            s__($row['status']),
            $this->getActionButtons($id, 'leave_requests'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'leave_requests.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

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
    protected $sortableColumns = [0 => 'id', 1 => 'leave_type', 2 => 'max_per_year', 3 => 'paid', 4 => 'id'];

    protected function getOrgIdWhereClause(): string
    {
        return '';
    }

    protected function formatRow($row, $requestData = [])
    {
        $id      = (int)($row['id'] ?? 0);
        $type    = (string)($row['leave_type'] ?? '');
        $max     = (int)($row['max_per_year'] ?? 0);
        $paid    = (int)($row['paid'] ?? 0);

        $maxText = $max == 0 ? 'Unlimited' : (string)$max;
        $paidHtml = $paid == 1 
            ? '<span class="badge bg-success">Yes</span>' 
            : '<span class="badge bg-secondary">No</span>';

        return [
            $this->rowNumber,
            htmlspecialchars($type),
            htmlspecialchars($maxText),
            $paidHtml,
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

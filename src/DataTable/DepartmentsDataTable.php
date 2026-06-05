<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class DepartmentsDataTable extends BaseDataTable
{
    protected $table = DB::DEPARTMENT;
    protected $searchFields = ['department'];
    protected $sortableColumns = [0 => 'id', 1 => 'department', 2 => 'created_at', 3 => 'publish', 4 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id      = (int)($row['id'] ?? 0);
        $dept    = (string)($row['department'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($dept),
            0,
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'departments'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if (function_exists('granted_') && granted_('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'departments.php', $module, 'Edit', false);
        }
        if (function_exists('granted_') && granted_('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

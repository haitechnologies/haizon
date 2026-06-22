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
    protected $sortableColumns = [0 => 'id', 1 => 'department', 2 => 'created_at', 3 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id      = (int)($row['id'] ?? 0);
        $dept    = (string)($row['department'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        return [
            $this->rowNumber,
            htmlspecialchars($dept),
            0,
            $this->formatTimeAgo($created),
            $this->getActionButtons($id, 'departments'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'departments.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

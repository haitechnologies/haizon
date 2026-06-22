<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class DesignationsDataTable extends BaseDataTable
{
    protected $table = DB::DESIGNATIONS;
    protected $searchFields = ['designation'];
    protected $sortableColumns = [0 => 'id', 1 => 'designation', 2 => 'created_at', 3 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['designation'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        return [
            $this->rowNumber,
            htmlspecialchars($name),
            $this->formatTimeAgo($created),
            $this->getActionButtons($id, 'designations'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'designations.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

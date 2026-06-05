<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class StorageSubtypesDataTable extends BaseDataTable
{
    protected $table = DB::STORAGE_SUBTYPES;
    protected $searchFields = ['storage_subtype', 'storage_type'];
    protected $sortableColumns = [0 => 'id', 1 => 'storage_subtype', 2 => 'storage_type', 3 => 'created_at', 4 => 'publish', 5 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id      = (int)($row['id'] ?? 0);
        $sub     = (string)($row['storage_subtype'] ?? '');
        $type    = (string)($row['storage_type'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($sub),
            htmlspecialchars($type),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'storage_subtypes'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if (granted_('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'storage_subtypes.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

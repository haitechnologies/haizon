<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class ContainerTypesDataTable extends BaseDataTable
{
    protected $table = DB::CONTAINER_TYPES;
    protected $searchFields = ['container_type'];
    protected $sortableColumns = [0 => 'id', 1 => 'container_type', 2 => 'created_at', 3 => 'publish', 4 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['container_type'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'container_types'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if (granted_('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'container_types.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

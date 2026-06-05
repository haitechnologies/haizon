<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class PurchaseTypesDataTable extends BaseDataTable
{
    protected $table = DB::PURCHASE_TYPES;
    protected $searchFields = ['purchase_type', 'description'];
    protected $sortableColumns = [0 => 'id', 1 => 'purchase_type', 2 => 'description', 3 => 'created_at', 4 => 'publish', 5 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['purchase_type'] ?? '');
        $desc    = (string)($row['description'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars($desc),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'purchase_types'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if (granted_('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'purchase_types.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

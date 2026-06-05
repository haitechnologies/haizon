<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class PayrollComponentsDataTable extends BaseDataTable
{
    protected $table = DB::PAYROLL_COMPONENTS;
    protected $searchFields = ['component_name'];
    protected $sortableColumns = [0 => 'id', 1 => 'component_name', 2 => 'component_type', 3 => 'taxable', 4 => 'account_id', 5 => 'is_active', 6 => 'is_active', 7 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id       = (int)($row['id'] ?? 0);
        $name     = (string)($row['component_name'] ?? '');
        $type     = (string)($row['component_type'] ?? '');
        $taxable  = (int)($row['taxable'] ?? 0) ? 'Yes' : 'No';
        $accountId = (string)($row['account_id'] ?? '');
        $active   = (int)($row['is_active'] ?? 0);
        $badge    = $active ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars($type),
            $taxable,
            htmlspecialchars($accountId),
            $active ? 'Yes' : 'No',
            $badge,
            $this->getActionButtons($id, 'payroll_components'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if (granted_('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'payroll_components.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

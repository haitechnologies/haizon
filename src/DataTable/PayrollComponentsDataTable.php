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
    protected $sortableColumns = [0 => 'id', 1 => 'component_name', 2 => 'component_type', 3 => 'taxable', 4 => 'account_id', 5 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id       = (int)($row['id'] ?? 0);
        $name     = (string)($row['component_name'] ?? '');
        $type     = (string)($row['component_type'] ?? '');
        $taxable  = (int)($row['taxable'] ?? 0);
        $accountId = (string)($row['account_id'] ?? '');
        
        // Calculate usage
        $usageResult = $this->db->fetchOne("SELECT COUNT(*) as count FROM `" . DB::SALARY_STRUCTURES . "` WHERE component_id = $id");
        $usageCount = $usageResult ? (int)($usageResult['count'] ?? 0) : 0;
        $isInUse = $usageCount > 0;

        // Formats
        $typeHtml = $type == 'earning' 
            ? '<span class="badge bg-success bg-opacity-20 text-success"><i class="ph-plus-circle"></i> Earning</span>'
            : '<span class="badge bg-danger bg-opacity-20 text-danger"><i class="ph-minus-circle"></i> Deduction</span>';
        
        $taxableHtml = $taxable 
            ? '<span class="badge bg-info">Yes</span>' 
            : '<span class="text-muted">No</span>';

        $inUseHtml = $isInUse
            ? '<span class="badge bg-primary" title="Used by ' . $usageCount . ' employee(s)"><i class="ph-users"></i> ' . $usageCount . ' employee' . ($usageCount > 1 ? 's' : '') . '</span>'
            : '<span class="text-muted">-</span>';

        return [
            $this->rowNumber,
            '<span class="fw-semibold">' . htmlspecialchars($name) . '</span>',
            $typeHtml,
            $taxableHtml,
            htmlspecialchars($accountId),
            $inUseHtml,
            $this->getActionButtons($id, 'payroll_components', $isInUse, $usageCount, $name),
        ];
    }

    protected function getActionButtons($id, $module, $isInUse = false, $usageCount = 0, $name = '')
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'payroll_components.php', $module, 'Edit component', false);
        }
        if ($this->isGranted('delete', $module)) {
            if ($isInUse) {
                $a .= ' <button type="button" class="action-btn action-edit" style="color: #999;" disabled title="Cannot delete: In use by ' . $usageCount . ' employee(s)"><i class="ph-lock"></i></button>';
            } else {
                $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
            }
        }
        return ActionButtonHelper::group($a);
    }
}

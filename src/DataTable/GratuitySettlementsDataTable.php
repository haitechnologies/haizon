<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class GratuitySettlementsDataTable extends BaseDataTable
{
    protected $table = DB::GRATUITY_SETTLEMENTS;

    protected $searchFields = ['u.full_name'];

    protected $sortableColumns = [
        0 => 'gs.id',
        1 => 'u.full_name',
        2 => 'gs.last_basic_salary',
        3 => 'gs.total_tenure_years',
        4 => 'gs.gratuity_amount',
        5 => 'gs.status',
        6 => 'gs.settlement_date',
        7 => 'gs.id',
    ];

    protected function buildBaseQuery($requestData)
    {
        return "SELECT gs.*, u.full_name AS employee_name
                FROM `" . $this->table . "` gs
                LEFT JOIN `" . DB::USERS . "` u ON u.id = gs.employee_id
                WHERE gs.id > 0" . $this->getOrgIdWhereClause('gs');
    }

    protected function getOrgIdWhereClause(?string $alias = null): string
    {
        if ($this->organizationId === null) {
            return '';
        }

        $prefix = $alias !== null ? $alias . '.' : '';
        $this->params['active_org_id'] = (int)$this->organizationId;
        return " AND {$prefix}organization_id = :active_org_id";
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)$row['id'];
        $employeeName = htmlspecialchars((string)($row['employee_name'] ?? '-'));
        $lastBasicSalary = (float)($row['last_basic_salary'] ?? 0);
        $totalTenureYears = (float)($row['total_tenure_years'] ?? 0);
        $totalTenureDays = (int)($row['total_tenure_days'] ?? 0);
        $gratuityAmount = (float)($row['gratuity_amount'] ?? 0);
        $status = (string)($row['status'] ?? 'calculated');
        $settlementDate = (string)($row['settlement_date'] ?? '');

        $tenureText = $totalTenureYears . ' yrs (' . $totalTenureDays . ' days)';

        $statusBadge = match ($status) {
            'calculated' => BadgeHelper::info('Calculated'),
            'approved' => BadgeHelper::primary('Approved'),
            'paid' => BadgeHelper::success('Paid'),
            'cancelled' => BadgeHelper::danger('Cancelled'),
            default => BadgeHelper::secondary(ucfirst($status)),
        };

        $settlementDateDisplay = !empty($settlementDate) && $settlementDate !== '0000-00-00'
            ? date('d M Y', strtotime($settlementDate))
            : '-';

        return [
            $this->rowNumber,
            $employeeName ?: '-',
            number_format($lastBasicSalary, 2),
            $tenureText,
            number_format($gratuityAmount, 2),
            $statusBadge,
            $settlementDateDisplay,
            $this->getActionButtons($id, 'gratuity_settlements'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $buttons = [];

        if ($this->isGranted('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton((int)$id, 'gratuity_settlements.php', $module, 'Edit', false);
        }

        if ($this->isGranted('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton((int)$id, $module);
        }

        return implode(' ', array_filter($buttons));
    }
}

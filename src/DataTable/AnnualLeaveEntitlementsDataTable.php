<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\ActionButtonHelper;
use App\Helper\BadgeHelper;

class AnnualLeaveEntitlementsDataTable extends BaseDataTable
{
    protected $table = DB::ANNUAL_LEAVE_ENTITLEMENTS;
    protected $searchFields = ['e.entitlement_year', 'e.status', 'u.full_name'];
    protected $sortableColumns = [
        0 => 'e.id',
        1 => 'u.full_name',
        2 => 'e.entitlement_year',
        3 => 'e.total_leave_days',
        4 => 'e.leave_availed',
        5 => 'e.leave_balance',
        6 => 'e.air_ticket_amount',
        7 => 'e.status',
        8 => 'e.id',
    ];

    protected function buildBaseQuery($requestData)
    {
        return "SELECT e.*, u.full_name as employee_name
                FROM `" . $this->table . "` e
                LEFT JOIN `" . DB::USERS . "` u ON u.id = e.employee_id
                WHERE e.id > 0" . $this->getOrgIdWhereClause();
    }

    protected function getOrgIdWhereClause(): string
    {
        if ($this->organizationId === null) {
            return '';
        }
        $this->params['active_org_id'] = (int)$this->organizationId;
        return " AND e.`organization_id` = :active_org_id";
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $employeeName = (string)($row['employee_name'] ?? '');
        $year = (int)($row['entitlement_year'] ?? 0);
        $totalDays = (float)($row['total_leave_days'] ?? 0);
        $availed = (float)($row['leave_availed'] ?? 0);
        $balance = (float)($row['leave_balance'] ?? 0);
        $airTicketAmount = (float)($row['air_ticket_amount'] ?? 0);
        $airTicketAvailed = !empty($row['air_ticket_availed']);
        $status = (string)($row['status'] ?? 'active');

        $statusBadge = match ($status) {
            'active' => BadgeHelper::success('Active'),
            'exhausted' => BadgeHelper::warning('Exhausted'),
            'closed' => BadgeHelper::secondary('Closed'),
            default => BadgeHelper::secondary(ucfirst($status)),
        };

        $airTicketText = $airTicketAvailed
            ? BadgeHelper::success('Availed')
            : BadgeHelper::secondary(number_format($airTicketAmount, 2));

        return [
            $this->rowNumber,
            htmlspecialchars($employeeName),
            (string)$year,
            number_format($totalDays, 1),
            number_format($availed, 1),
            number_format($balance, 1),
            $airTicketText,
            $statusBadge,
            $this->getActionButtons($id, 'annual_leave_entitlements'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'annual_leave_entitlements.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

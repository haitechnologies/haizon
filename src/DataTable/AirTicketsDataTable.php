<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class AirTicketsDataTable extends BaseDataTable
{
    protected $table = DB::AIR_TICKETS;
    protected $searchFields = ['u.full_name'];
    protected $sortableColumns = [
        0 => 'at.id',
        1 => 'u.full_name',
        2 => 'at.entitlement_amount',
        3 => 'at.status',
        4 => 'at.eligibility_date',
        5 => 'at.paid_date',
        6 => 'at.id',
    ];

    protected function buildBaseQuery($requestData)
    {
        return "SELECT at.*, u.full_name as employee_name
                FROM `" . $this->table . "` at
                LEFT JOIN `" . DB::USERS . "` u ON u.id = at.employee_id
                WHERE at.id > 0" . $this->getOrgIdWhereClause();
    }

    protected function getOrgIdWhereClause(): string
    {
        if ($this->organizationId === null) {
            return '';
        }
        $this->params['active_org_id'] = (int)$this->organizationId;
        return " AND at.`organization_id` = :active_org_id";
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $employeeName = (string)($row['employee_name'] ?? '');
        $entitlementAmount = (float)($row['entitlement_amount'] ?? 0);
        $status = (string)($row['status'] ?? 'pending');
        $eligibilityDate = (string)($row['eligibility_date'] ?? '');
        $paidDate = (string)($row['paid_date'] ?? '');

        $statusBadge = match ($status) {
            'pending' => BadgeHelper::secondary('Pending'),
            'payable' => BadgeHelper::warning('Payable'),
            'paid' => BadgeHelper::success('Paid'),
            'cancelled' => BadgeHelper::danger('Cancelled'),
            default => BadgeHelper::secondary(ucfirst($status)),
        };

        $formattedEntitlement = number_format($entitlementAmount, 2);

        return [
            $this->rowNumber,
            htmlspecialchars($employeeName),
            htmlspecialchars($formattedEntitlement),
            $statusBadge,
            !empty($eligibilityDate) && $eligibilityDate !== '0000-00-00' ? $this->formatDate($eligibilityDate) : '-',
            !empty($paidDate) && $paidDate !== '0000-00-00' ? $this->formatDate($paidDate) : '-',
            $this->getActionButtons($id, 'air_tickets'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'air_tickets.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

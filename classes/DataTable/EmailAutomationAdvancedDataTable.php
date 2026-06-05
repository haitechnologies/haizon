<?php
/**
 * EmailAutomationAdvancedDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class EmailAutomationAdvancedDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_AUTOMATION_QUEUE;
    protected $searchFields = ['r.rule_name', 'r.trigger_event', 'q.email_to', 'q.status', 'q.company_id'];
    protected $sortableColumns = [
        0 => 'q.id',
        1 => 'r.rule_name',
        2 => 'r.trigger_event',
        3 => 'q.company_id',
        4 => 'q.email_to',
        5 => 'q.scheduled_for',
        6 => 'q.status',
        7 => 'q.sent_at',
        8 => 'q.created_at',
        9 => 'q.id'
    ];

    protected function buildBaseQuery($requestData) {
        return "SELECT q.id, q.rule_id, r.rule_name, r.trigger_event, q.company_id, q.email_to, q.scheduled_for, q.status, q.sent_at, q.created_at "
             . "FROM `" . DB::EMAIL_AUTOMATION_QUEUE . "` q "
             . "LEFT JOIN `" . DB::EMAIL_AUTOMATION_RULES . "` r ON r.id = q.rule_id "
             . "WHERE q.id > 0";
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $ruleName = $row['rule_name'] ?? '';
        $triggerEvent = $row['trigger_event'] ?? '';
        $companyId = (int)($row['company_id'] ?? 0);
        $emailTo = $row['email_to'] ?? '';
        $scheduledFor = $row['scheduled_for'] ?? '';
        $status = $row['status'] ?? '';
        $sentAt = $row['sent_at'] ?? '';
        $createdAt = $row['created_at'] ?? '';

        $statusBadge = match ($status) {
            'queued' => BadgeHelper::secondary('Queued'),
            'sent' => BadgeHelper::success('Sent'),
            'failed' => BadgeHelper::danger('Failed'),
            'cancelled' => BadgeHelper::warning('Cancelled'),
            default => BadgeHelper::secondary(ucfirst($status))
        };

        return [
            $id,
            htmlspecialchars($ruleName),
            htmlspecialchars($triggerEvent),
            $companyId,
            htmlspecialchars($emailTo),
            !empty($scheduledFor) ? dd_($scheduledFor) : '-',
            $statusBadge,
            !empty($sentAt) ? dd_($sentAt) : '-',
            !empty($createdAt) ? timeAgo($createdAt) : '-',
            $this->getActionButtons($id, 'email_automation_advanced')
        ];
    }    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? count($this->sortableColumns) - 2);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }



    protected function getActionButtons($id, $module) {
        $actions = '';
        if (granted_('delete', $module)) {
            $actions .= ActionButtonHelper::deleteButton($id, $module);
        }
        return $actions;
    }
}



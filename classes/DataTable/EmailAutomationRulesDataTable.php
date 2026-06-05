<?php
/**
 * EmailAutomationRulesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';

class EmailAutomationRulesDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_AUTOMATION_RULES;
    protected $searchFields = [];
    protected $sortableColumns = [
        0 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $ruleName = $row['rule_name'] ?? ($row['name'] ?? ($row['title'] ?? '-'));
        $trigger = $row['trigger_event'] ?? ($row['trigger'] ?? ($row['event'] ?? '-'));
        $templateId = $row['email_template_id'] ?? ($row['template_id'] ?? '-');
        $statusValue = $row['is_active'] ?? ($row['active'] ?? ($row['status'] ?? null));
        $createdAt = $row['created_at'] ?? '';

        $statusBadge = '-';
        if ($statusValue !== null && $statusValue !== '') {
            $isActive = false;
            if (is_numeric($statusValue)) {
                $isActive = (int)$statusValue === 1;
            } elseif (is_string($statusValue)) {
                $isActive = strtolower($statusValue) === 'active';
            }
            $statusBadge = $isActive
                ? '<span class="badge bg-success bg-opacity-20 text-success">Active</span>'
                : '<span class="badge bg-danger bg-opacity-20 text-danger">Inactive</span>';
        }

        return [
            $id,
            htmlspecialchars($ruleName),
            htmlspecialchars($trigger),
            $templateId !== '' ? htmlspecialchars((string)$templateId) : '-',
            $statusBadge,
            !empty($createdAt) ? timeAgo($createdAt) : '-'
        ];
    }
}

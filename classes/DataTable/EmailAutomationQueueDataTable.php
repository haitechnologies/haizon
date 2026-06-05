<?php
/**
 * EmailAutomationQueueDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';

class EmailAutomationQueueDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_AUTOMATION_QUEUE;
    protected $searchFields = ['recipient_email', 'status'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'rule_id', 2 => 'recipient_email', 3 => 'status', 4 => 'created_at'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $ruleId = (int)$row['rule_id'];
        $recipientEmail = htmlspecialchars($row['recipient_email'] ?? '');
        $status = s__($row['status'] ?? '');
        $errorMessage = htmlspecialchars($row['error_message'] ?? '');
        $createdAt = $row['created_at'] ?? '';

        $statusBadge = '-';
        if ($status !== '') {
            $statusBadge = match($status) {
                'queued' => '<span class="badge bg-secondary bg-opacity-20 text-secondary">Queued</span>',
                'sent' => '<span class="badge bg-success bg-opacity-20 text-success">Sent</span>',
                'failed' => '<span class="badge bg-danger bg-opacity-20 text-danger">Failed</span>',
                'cancelled' => '<span class="badge bg-warning bg-opacity-20 text-warning">Cancelled</span>',
                default => '<span class="badge bg-secondary bg-opacity-20 text-secondary">' . htmlspecialchars(ucfirst($status)) . '</span>'
            };
        }

        $errorText = htmlspecialchars($errorMessage);
        if (strlen($errorText) > 120) {
            $errorText = substr($errorText, 0, 117) . '...';
        }

        return [
            $id,
            $ruleId > 0 ? $ruleId : '-',
            $recipientEmail,
            $statusBadge,
            !empty($errorText) ? $errorText : '-',
            timeAgo($createdAt)
        ];
    }

    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? count($this->sortableColumns) - 2);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }
}
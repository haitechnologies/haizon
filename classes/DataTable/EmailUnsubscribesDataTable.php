<?php
/**
 * EmailUnsubscribesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';

class EmailUnsubscribesDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_UNSUBSCRIBES;
    protected $searchFields = ['recipient_email'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'recipient_email', 2 => 'reason', 3 => 'created_at'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $recipientEmail = htmlspecialchars($row['recipient_email'] ?? '');
        $reason = htmlspecialchars($row['reason'] ?? '');
        $createdAt = $row['created_at'] ?? '';

        return [
            $id,
            $recipientEmail,
            !empty($reason) ? $reason : '-',
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
<?php
/**
 * EmailBouncesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';

class EmailBouncesDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_BOUNCES;
    protected $searchFields = ['recipient_email', 'bounce_type'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'recipient_email', 2 => 'bounce_type', 3 => 'bounce_subtype', 4 => 'created_at'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $recipientEmail = htmlspecialchars($row['recipient_email'] ?? '');
        $bounceType = htmlspecialchars($row['bounce_type'] ?? '');
        $bounceSubtype = htmlspecialchars($row['bounce_subtype'] ?? '');
        $createdAt = $row['created_at'] ?? '';

        return [
            $id,
            $recipientEmail,
            $bounceType,
            $bounceSubtype,
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
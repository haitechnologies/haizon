<?php
/**
 * EmailEventsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';

class EmailEventsDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_EVENTS;
    protected $searchFields = ['email', 'event_type'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'email', 2 => 'event_type', 3 => 'created_at'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $email = htmlspecialchars($row['email'] ?? '');
        $eventType = htmlspecialchars($row['event_type'] ?? '');
        $createdAt = $row['created_at'] ?? '';

        return [
            $id,
            $email,
            $eventType,
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
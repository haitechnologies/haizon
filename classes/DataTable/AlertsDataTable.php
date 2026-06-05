<?php
/**
 * AlertsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class AlertsDataTable extends BaseDataTable {
    protected $table = DB::ALERTS;
    protected $searchFields = ['alert_name'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'alert_name', 2 => 'created_at', 3 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $alertName = $row['alert_name'] ?? '';
        $createdAt = $row['created_at'] ?? '';
        
        return [
            $id,
            htmlspecialchars($alertName),
            timeAgo($createdAt),
            $this->getActionButtons($id, 'alerts')
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
        $buttons = [];
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'alerts.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}



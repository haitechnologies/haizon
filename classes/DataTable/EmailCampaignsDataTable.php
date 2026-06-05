<?php
/**
 * EmailCampaignsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class EmailCampaignsDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_CAMPAIGNS;
    protected $searchFields = ['campaign_name', 'campaign_code', 'name', 'code'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'name', 2 => 'code', 3 => 'created_at', 4 => 'status', 5 => 'id'
    ];

    protected function buildBaseQuery($requestData) {
        return "SELECT * FROM `" . $this->table . "` WHERE id > 0";
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $campaignName = $row['campaign_name'] ?? ($row['name'] ?? '');
        $campaignCode = $row['campaign_code'] ?? ($row['code'] ?? ('CMP-' . $id));
        $statusRaw = strtolower((string)($row['status'] ?? 'draft'));
        $createdAt = $row['created_at'] ?? '';
        
        $statusBadge = match ($statusRaw) {
            'running', 'active' => BadgeHelper::success('Active'),
            'scheduled' => BadgeHelper::info('Scheduled'),
            'completed' => BadgeHelper::primary('Completed'),
            'paused' => BadgeHelper::warning('Paused'),
            default => BadgeHelper::secondary(ucfirst($statusRaw ?: 'Draft')),
        };
        
        return [
            $id,
            htmlspecialchars($campaignName),
            '<code>' . htmlspecialchars($campaignCode) . '</code>',
            timeAgo($createdAt),
            $statusBadge,
            $this->getActionButtons($id, 'email_campaigns')
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
            $buttons[] = ActionButtonHelper::editButton($id, 'email_campaigns.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}



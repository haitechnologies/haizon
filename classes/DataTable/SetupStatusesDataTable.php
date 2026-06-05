<?php
/**
 * SetupStatusesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class SetupStatusesDataTable extends BaseDataTable {
    protected $table = DB::SETUP_STATUSES;
    protected $searchFields = ['status'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'status', 2 => 'status_type', 3 => 'created_at', 4 => 'is_active', 5 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $status = $row['status'] ?? '';
        $statusType = $row['status_type'] ?? '';
        $publish = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';
        
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            $id,
            htmlspecialchars($status),
            ucwords($statusType),
            timeAgo($createdAt),
            $publishBadge,
            $this->getActionButtons($id, 'setup_statuses', $publish)
        ];
    }
    
    protected function getActionButtons($id, $module, $publish) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton($id, 'setup_statuses.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton($id, $module);
        }
        return $actions;
    }
}


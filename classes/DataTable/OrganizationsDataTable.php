<?php
/**
 * OrganizationsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class OrganizationsDataTable extends BaseDataTable {
    protected $table = DB::ORGANIZATIONS;
    protected $searchFields = ['warehouse_name'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'photo', 2 => 'warehouse_name', 3 => 'phone', 
        4 => 'email', 5 => 'created_at', 6 => 'is_active', 7 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $warehouseName = $row['warehouse_name'] ?? '';
        $phone = $row['phone'] ?? '';
        $email = $row['email'] ?? '';
        $publish = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';
        
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            $id,
            htmlspecialchars($warehouseName),
            htmlspecialchars($phone),
            htmlspecialchars($email),
            timeAgo($createdAt),
            $publishBadge,
            $this->getActionButtons($id, 'organizations', $publish)
        ];
    }
    
    protected function getActionButtons($id, $module, $publish) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton($id, 'organizations.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton($id, $module);
        }
        return $actions;
    }
}


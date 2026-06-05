<?php
/**
 * UsersDataTable Handler
 * 
 * Manages server-side DataTable processing for the Users (admin users) module
 * Handles: search by name/email/contact, sort, pagination, role lookup
 * 
 * @package DataTable
 * @subpackage Handlers
 * @version 1.0
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class UsersDataTable extends BaseDataTable {
    
    /**
     * Table name
     */
    protected $table = DB::USERS;
    
    /**
     * Search fields
     */
    protected $searchFields = [
        'full_name',
        'email',
        'contact1'
    ];
    
    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'id',
        1 => 'full_name',
        2 => 'email',
        3 => 'contact1',
        4 => 'role_id',
        5 => 'last_login',
        6 => 'is_active',
        7 => 'id'
    ];
    
    /**
     * OPTIMIZATION: Pre-fetch role names to prevent N+1 queries
     */
    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $roleIds = array_filter(array_map(fn($r) => (int)($r['role_id'] ?? 0), $rows));
        
        if (empty($roleIds)) {
            return;
        }

        $idList = implode(',', array_unique($roleIds));

        // OPTIMIZATION: Fetch all role names in ONE query
        $roleQuery = "
            SELECT id, role_name 
            FROM " . DB::ROLES . " 
            WHERE id IN ({$idList})
        ";
        
        $this->relatedDataCache['roles'] = [];
        $result = $this->mysqli->query($roleQuery);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['roles'][(int)$row['id']] = $row['role_name'] ?? '-';
            }
        }
    }
    
    /**
     * Format row data - OPTIMIZATION: Uses pre-fetched role names
     * 
     * @param array $row Database row
     * @param array $requestData Request data
     * @return array Formatted row
     */
    protected function formatRow($row, $requestData = []) {
        global $mysqli;
        
        $id = (int)$row['id'];
        $fullName = s__($row['full_name'] ?? '');
        $email = s__($row['email'] ?? '');
        $contact1 = s__($row['contact1'] ?? '');
        $roleId = (int)$row['role_id'];
        $lastLogin = $row['last_login'] ?? '';
        $isActive = (int)$row['is_active'];
        
        // OPTIMIZATION: Use pre-fetched role name instead of per-row query
        $roleName = $roleId > 0 && isset($this->relatedDataCache['roles'][$roleId]) 
            ? $this->relatedDataCache['roles'][$roleId]
            : '-';
        
        // Build active status badge
        $activeBadge = $isActive == 1
            ? BadgeHelper::success('Active')
            : BadgeHelper::danger('Inactive');
        
        // Format last login
        $lastLoginDisplay = !empty($lastLogin) ? timeAgo($lastLogin) : 'Never';
        
        return [
            $id,
            $fullName ?: '-',
            $email ?: '-',
            $contact1 ?: '-',
            $roleName,
            $lastLoginDisplay,
            $activeBadge,
            $this->getActionButtons($id, 'users')
        ];
    }
    
    /**
     * Get action buttons
     * 
     * @param int $id Record ID
     * @param string $module Module name
     * @return string HTML action buttons
     */
    protected function getActionButtons($id, $module) {
        $buttons = [];
        
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'users.php', $module, 'Edit', false);
        }
        
        if (granted_('delete', $module) && (int)$id !== 1) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        
        return implode(' ', array_filter($buttons));
    }
}


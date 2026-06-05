<?php

/**
     * Search fields
     */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class UsersDataTable extends BaseDataTable
{
    /**
     * Table name
     */
    protected $table = DB::USERS;

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

        $uniqueIds = array_unique($roleIds);
        $placeholders = [];
        $params = [];
        foreach ($uniqueIds as $index => $id) {
            $key = 'role_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $placeholdersStr = implode(',', $placeholders);

        $roleQuery = "
            SELECT id, role_name 
            FROM " . DB::ROLES . " 
            WHERE id IN ({$placeholdersStr})
        ";

        $this->relatedDataCache['roles'] = [];
        try {
            $roleRows = $this->db->fetchAll($roleQuery, $params);
            foreach ($roleRows as $roleRow) {
                $this->relatedDataCache['roles'][(int)$roleRow['id']] = $roleRow['role_name'] ?? '-';
            }
        } catch (\Throwable $e) {
            error_log("UsersDataTable::prepareRelatedData() failed: " . $e->getMessage());
        }
    }

    /**
     * Format row data
     *
     * @param array $row Database row
     * @param array $requestData Request data
     * @return array Formatted row
     */
    protected function formatRow($row, $requestData = [])
    {
        $id = (int)$row['id'];
        $fullName = (string)($row['full_name'] ?? '');
        $email = (string)($row['email'] ?? '');
        $contact1 = (string)($row['contact1'] ?? '');
        $roleId = (int)$row['role_id'];
        $lastLogin = $row['last_login'] ?? '';
        $isActive = (int)$row['is_active'];

        $roleName = $roleId > 0 && isset($this->relatedDataCache['roles'][$roleId])
            ? $this->relatedDataCache['roles'][$roleId]
            : '-';

        $activeBadge = $isActive === 1
            ? BadgeHelper::success('Active')
            : BadgeHelper::danger('Inactive');

        $lastLoginDisplay = !empty($lastLogin) && function_exists('timeAgo') ? timeAgo($lastLogin) : 'Never';

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
    protected function getActionButtons($id, $module)
    {
        $buttons = [];

        if (function_exists('granted_') && granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'users.php', $module, 'Edit', false);
        }

        if (function_exists('granted_') && granted_('delete', $module) && (int)$id !== 1) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }

        return implode(' ', array_filter($buttons));
    }
}

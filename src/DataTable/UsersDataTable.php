<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class UsersDataTable extends BaseDataTable
{
    protected $table = DB::USERS;

    protected function buildBaseQuery($requestData)
    {
        $orgWhere = $this->getOrgIdWhereClause();
        if ($orgWhere !== '') {
            $orgWhere = str_replace('`organization_id` = :active_org_id', '(`organization_id` = :active_org_id OR `organization_id` IS NULL)', $orgWhere);
        }
        return "SELECT * FROM `" . $this->table . "` WHERE id > 0" . $orgWhere;
    }

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

        $lastLoginDisplay = !empty($lastLogin) ? $this->formatTimeAgo($lastLogin) : 'Never';

        return [
            $this->rowNumber,
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

        if ($this->isGranted('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'users.php', $module, 'Edit', false);
        }

        if ($this->isGranted('delete', $module) && (int)$id !== 1) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }

        return implode(' ', array_filter($buttons));
    }
}

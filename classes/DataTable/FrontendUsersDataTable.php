<?php
/**
 * FrontendUsersDataTable Handler
 *
 * Manages server-side DataTable processing for public frontend users
 *
 * @package DataTable
 * @subpackage Handlers
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class FrontendUsersDataTable extends BaseDataTable
{
    /**
     * Table name
     */
    protected $table = DB::FRONTEND_USERS;

    /**
     * Searchable fields
     */
    protected $searchFields = [
        'full_name',
        'email',
        'mobile'
    ];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'id',
        1 => 'full_name',
        2 => 'email',
        3 => 'mobile',
        4 => 'email_verified',
        5 => 'is_active',
        6 => 'last_login',
        7 => 'created_at',
        8 => 'id'
    ];

    /**
     * Format row data
     *
     * @param array $row Database row
     * @param array $requestData Request data
     * @return array Formatted row
     */
    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $fullName = s__($row['full_name'] ?? '') ?: '-';
        $email = s__($row['email'] ?? '') ?: '-';
        $mobile = s__($row['mobile'] ?? '') ?: '-';
        $emailVerified = (int)($row['email_verified'] ?? 0);
        $isActive = isset($row['is_active']) ? (int)$row['is_active'] : (int)($row['is_active'] ?? 1);
        $lastLogin = $row['last_login'] ?? '';
        $createdAt = $row['created_at'] ?? '';

        $verifiedBadge = $emailVerified === 1
            ? BadgeHelper::success('Verified')
            : BadgeHelper::warning('Pending');

        $statusBadge = $isActive === 1
            ? BadgeHelper::success('Active')
            : BadgeHelper::danger('Inactive');

        $lastLoginDisplay = !empty($lastLogin) ? timeAgo($lastLogin) : 'Never';
        $createdDisplay = !empty($createdAt) ? dd_($createdAt, 'd M Y') : '-';

        return [
            $id,
            $fullName,
            $email,
            $mobile,
            $verifiedBadge,
            $statusBadge,
            $lastLoginDisplay,
            $createdDisplay,
            $this->getActionButtons($id, 'frontend_users')
        ];
    }

    /**
     * Build action buttons
     *
     * @param int $id Record ID
     * @param string $module Module name
     * @return string HTML action buttons
     */
    protected function getActionButtons($id, $module)
    {
        $buttons = [];

        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }

        return implode(' ', array_filter($buttons));
    }
}


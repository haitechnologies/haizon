<?php
/**
 * SystemSettingsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class SystemSettingsDataTable extends BaseDataTable {
    protected $table = DB::SYSTEM_SETTINGS;
    protected $searchFields = ['setting_slug', 'setting_name', 'setting_value', 'hint'];
    protected $sortableColumns = [
        0 => 'id',
        1 => 'setting_slug',
        2 => 'setting_name',
        3 => 'setting_value',
        4 => 'hint',
        5 => 'is_active',
        6 => 'updated_at',
        7 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $slug = $row['setting_slug'] ?? '';
        $name = $row['setting_name'] ?? '';
        $value = $this->truncateText($row['setting_value'] ?? '', 60);
        $hint = $this->truncateText($row['hint'] ?? '', 60);
        $publish = (int)($row['is_active'] ?? 0);
        $updatedAt = $row['updated_at'] ?? '';

        $publishBadge = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');

        return [
            $id,
            htmlspecialchars($slug),
            htmlspecialchars($name),
            $value,
            $hint,
            $publishBadge,
            !empty($updatedAt) ? timeAgo($updatedAt) : '-',
            $this->getActionButtons('system_settings', $id)
        ];
    }

    protected function getActionButtons($module, $id) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton($id, 'system_settings.php', $module, 'Edit', false);
        }
        return $actions;
    }
}


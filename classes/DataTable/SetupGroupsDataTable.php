<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class SetupGroupsDataTable extends BaseDataTable {
    protected $table = DB::SETUP_GROUPS;
    protected $searchFields = ['group_name', 'description'];
    protected $sortableColumns = [0 => 'id', 1 => 'group_name', 2 => 'description', 3 => 'created_at', 4 => 'publish', 5 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['group_name'] ?? '');
        $desc    = (string)($row['description'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars($desc),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'setup_groups'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'setup_groups.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

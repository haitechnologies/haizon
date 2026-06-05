<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class UnitsDataTable extends BaseDataTable {
    protected $table = DB::UNITS;
    protected $searchFields = ['unit'];
    protected $sortableColumns = [0 => 'id', 1 => 'unit', 2 => 'created_at', 3 => 'publish', 4 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['unit'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'units'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'units.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class WarehousesDataTable extends BaseDataTable {
    protected $table = DB::WAREHOUSES;
    protected $searchFields = ['warehouse_name', 'country', 'email'];
    protected $sortableColumns = [0 => 'id', 1 => 'id', 2 => 'warehouse_name', 3 => 'country', 4 => 'state', 5 => 'email', 6 => 'created_at', 7 => 'publish', 8 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id      = (int)($row['id'] ?? 0);
        $photo   = (string)($row['photo'] ?? '');
        $name    = (string)($row['warehouse_name'] ?? '');
        $country = (string)($row['country'] ?? '');
        $city    = (string)($row['state'] ?? '');
        $email   = (string)($row['email'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        $logo    = $photo ? '<img src="' . htmlspecialchars($photo) . '" style="height:30px">' : '';
        return [
            $id, $logo,
            htmlspecialchars($name),
            htmlspecialchars($country),
            htmlspecialchars($city),
            htmlspecialchars($email),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'warehouses'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'warehouses.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

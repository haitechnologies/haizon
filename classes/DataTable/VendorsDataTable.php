<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class VendorsDataTable extends BaseDataTable {
    protected $table = DB::VENDORS;
    protected $searchFields = ['display_name', 'company_name', 'email'];
    protected $sortableColumns = [0 => 'display_name', 1 => 'company_name', 2 => 'email', 3 => 'phone', 4 => 'id', 5 => 'id', 6 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['display_name'] ?? '');
        $company = (string)($row['company_name'] ?? '');
        $email   = (string)($row['email'] ?? '');
        $phone   = (string)($row['phone'] ?? '');
        return [
            htmlspecialchars($name),
            htmlspecialchars($company),
            htmlspecialchars($email),
            htmlspecialchars($phone),
            '',
            '',
            $this->getActionButtons($id, 'vendors'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'vendors.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

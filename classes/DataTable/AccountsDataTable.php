<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class AccountsDataTable extends BaseDataTable {
    protected $table = DB::ACCOUNTS;
    protected $searchFields = ['account_name', 'account_code'];
    protected $sortableColumns = [0 => 'account_type', 1 => 'account_code', 2 => 'account_name', 3 => 'description', 4 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id   = (int)($row['id'] ?? 0);
        $type = (string)($row['account_type'] ?? '');
        $code = (string)($row['account_code'] ?? '');
        $name = (string)($row['account_name'] ?? '');
        $desc = (string)($row['description'] ?? '');
        return [
            htmlspecialchars($type),
            htmlspecialchars($code),
            htmlspecialchars($name),
            htmlspecialchars($desc),
            $this->getActionButtons($id, 'accounts'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'accounts.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

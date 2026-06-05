<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class BanksDataTable extends BaseDataTable {
    protected $table = DB::BANKS;
    protected $searchFields = ['account_name', 'bank_name', 'account_code'];
    protected $sortableColumns = [0 => 'id', 1 => 'is_primary', 2 => 'account_name', 3 => 'currency', 4 => 'account_code', 5 => 'bank_name', 6 => 'routing_number', 7 => 'created_at', 8 => 'publish', 9 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id       = (int)($row['id'] ?? 0);
        $primary  = (int)($row['is_primary'] ?? 0) ? BadgeHelper::success('Primary') : '';
        $name     = (string)($row['account_name'] ?? '');
        $currency = (string)($row['currency'] ?? '');
        $code     = (string)($row['account_code'] ?? '');
        $bankName = (string)($row['bank_name'] ?? '');
        $routing  = (string)($row['routing_number'] ?? '');
        $created  = (string)($row['created_at'] ?? '');
        $publish  = (int)($row['publish'] ?? 0);
        $badge    = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id, $primary,
            htmlspecialchars($name),
            htmlspecialchars($currency),
            htmlspecialchars($code),
            htmlspecialchars($bankName),
            htmlspecialchars($routing),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'banks'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'banks.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

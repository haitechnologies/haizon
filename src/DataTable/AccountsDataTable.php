<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class AccountsDataTable extends BaseDataTable
{
    protected $table = DB::ACCOUNTS;
    protected $searchFields = ['account_name', 'account_code'];
    protected $sortableColumns = [0 => 'account_type', 1 => 'account_code', 2 => 'account_name', 3 => 'description', 4 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
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

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'accounts.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

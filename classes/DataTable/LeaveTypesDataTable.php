<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class LeaveTypesDataTable extends BaseDataTable {
    protected $table = DB::LEAVE_TYPES;
    protected $searchFields = ['leave_type'];
    protected $sortableColumns = [0 => 'id', 1 => 'leave_type', 2 => 'max_per_year', 3 => 'paid', 4 => 'id'];

    protected function getOrgIdWhereClause(): string { return ''; }

    protected function formatRow($row, $requestData = []) {
        $id      = (int)($row['id'] ?? 0);
        $type    = (string)($row['leave_type'] ?? '');
        $max     = (string)($row['max_per_year'] ?? '0');
        $paid    = (int)($row['paid'] ?? 0) ? 'Yes' : 'No';
        return [
            $id,
            htmlspecialchars($type),
            htmlspecialchars($max),
            $paid,
            $this->getActionButtons($id, 'leave_types'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'leave_types.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

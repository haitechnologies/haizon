<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class ExitPointsDataTable extends BaseDataTable {
    protected $table = DB::EXIT_POINTS;
    protected $searchFields = ['exit_point'];
    protected $sortableColumns = [0 => 'id', 1 => 'exit_point', 2 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        return [
            $id,
            htmlspecialchars((string)($row['exit_point'] ?? '')),
            $this->getActionButtons($id, 'exit_points'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton((int)$id, 'exit_points.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return trim($actions);
    }
}
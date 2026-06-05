<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class JournalsDataTable extends BaseDataTable {
    protected $table = DB::JOURNALS;
    protected $searchFields = ['journal_no', 'reference_no', 'notes'];
    protected $sortableColumns = [0 => 'journal_date', 1 => 'journal_no', 2 => 'reference_no', 3 => 'journal_status', 4 => 'notes', 5 => 'grand_total', 6 => 'created_by', 7 => 'reporting_method', 8 => 'id'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        $userIds = array_values(array_filter(array_unique(array_map(function ($row) {
            return (int)($row['created_by'] ?? 0);
        }, $rows))));

        $this->relatedDataCache['users'] = [];
        if (!$userIds) {
            return;
        }

        $result = $this->mysqli->query("SELECT id, full_name FROM `" . DB::USERS . "` WHERE id IN (" . implode(',', $userIds) . ")");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['users'][(int)$row['id']] = (string)($row['full_name'] ?? '');
            }
            $result->free();
        }
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $status = (string)($row['journal_status'] ?? '');
        $statusBadge = $status !== '' ? BadgeHelper::info(htmlspecialchars($status)) : '';
        $createdBy = (int)($row['created_by'] ?? 0);

        return [
            htmlspecialchars((string)($row['journal_date'] ?? '')),
            htmlspecialchars((string)($row['journal_no'] ?? '')),
            htmlspecialchars((string)($row['reference_no'] ?? '')),
            $statusBadge,
            htmlspecialchars((string)($row['notes'] ?? '')),
            htmlspecialchars(number_format((float)($row['grand_total'] ?? 0), 2)),
            htmlspecialchars((string)($this->relatedDataCache['users'][$createdBy] ?? '')),
            htmlspecialchars((string)($row['reporting_method'] ?? '')),
            $this->getActionButtons($id, 'journals'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton((int)$id, 'journals.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return trim($actions);
    }
}
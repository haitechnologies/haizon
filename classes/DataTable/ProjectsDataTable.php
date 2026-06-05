<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class ProjectsDataTable extends BaseDataTable {
    protected $table = DB::PROJECTS;
    protected $searchFields = ['project_name'];
    protected $sortableColumns = [0 => 'id', 1 => 'created_at', 2 => 'id', 3 => 'project_name', 4 => 'job_id', 5 => 'customer_id', 6 => 'id'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        $customerIds = [];
        foreach ($rows as $row) {
            $customerIds[] = (int)($row['customer_id'] ?? 0);
        }
        $this->relatedDataCache['customers'] = [];
        $customerIds = array_values(array_filter(array_unique(array_map('intval', $customerIds))));
        if (!$customerIds) {
            return;
        }

        $result = $this->mysqli->query("SELECT id, display_name FROM `" . DB::CUSTOMERS . "` WHERE id IN (" . implode(',', $customerIds) . ")");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['customers'][(int)$row['id']] = (string)($row['display_name'] ?? '');
            }
            $result->free();
        }
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $customerId = (int)($row['customer_id'] ?? 0);

        return [
            $id,
            htmlspecialchars((string)($row['created_at'] ?? '')),
            $id,
            htmlspecialchars((string)($row['project_name'] ?? '')),
            htmlspecialchars((string)($row['job_id'] ?? '')),
            htmlspecialchars((string)($this->relatedDataCache['customers'][$customerId] ?? '')),
            $this->getActionButtons($id, 'projects'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton((int)$id, 'projects.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return trim($actions);
    }
}
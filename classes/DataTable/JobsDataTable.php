<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class JobsDataTable extends BaseDataTable {
    protected $table = DB::JOBS;
    protected $searchFields = ['job_no', 'job_ref_no'];
    protected $sortableColumns = [0 => 'job_date', 1 => 'job_no', 2 => 'job_ref_no', 3 => 'customer_id', 4 => 'job_status', 5 => 'estimated_invoice_amount', 6 => 'id', 7 => 'id'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        $customerIds = [];
        $statusIds = [];
        $jobIds = [];

        foreach ($rows as $row) {
            $customerIds[] = (int)($row['customer_id'] ?? 0);
            $statusIds[] = (int)($row['job_status'] ?? 0);
            $jobIds[] = (int)($row['id'] ?? 0);
        }

        $this->relatedDataCache['customers'] = $this->fetchLookupMap(DB::CUSTOMERS, $customerIds, 'display_name');
        $this->relatedDataCache['statuses'] = $this->fetchLookupMap(DB::JOB_STATUSES, $statusIds, 'job_status');
        $this->relatedDataCache['projects'] = [];

        $jobIds = array_values(array_filter(array_unique(array_map('intval', $jobIds))));
        if (!$jobIds) {
            return;
        }

        $result = $this->mysqli->query("SELECT id, job_id FROM `" . DB::PROJECTS . "` WHERE job_id IN (" . implode(',', $jobIds) . ") ORDER BY id ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $jobId = (int)($row['job_id'] ?? 0);
                if ($jobId > 0 && !isset($this->relatedDataCache['projects'][$jobId])) {
                    $this->relatedDataCache['projects'][$jobId] = (int)($row['id'] ?? 0);
                }
            }
            $result->free();
        }
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $customerId = (int)($row['customer_id'] ?? 0);
        $statusId = (int)($row['job_status'] ?? 0);
        $statusLabel = (string)($this->relatedDataCache['statuses'][$statusId] ?? '');

        return [
            htmlspecialchars((string)($row['job_date'] ?? '')),
            htmlspecialchars((string)($row['job_no'] ?? '')),
            htmlspecialchars((string)($row['job_ref_no'] ?? '')),
            htmlspecialchars((string)($this->relatedDataCache['customers'][$customerId] ?? '')),
            $statusLabel !== '' ? BadgeHelper::info(htmlspecialchars($statusLabel)) : '',
            htmlspecialchars(number_format((float)($row['estimated_invoice_amount'] ?? 0), 2)),
            htmlspecialchars((string)($this->relatedDataCache['projects'][$id] ?? '')),
            $this->getActionButtons($id, 'jobs'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton((int)$id, 'jobs.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return trim($actions);
    }

    private function fetchLookupMap(string $table, array $ids, string $valueField): array {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }

        $map = [];
        $result = $this->mysqli->query("SELECT id, {$valueField} AS value_label FROM `" . $table . "` WHERE id IN (" . implode(',', $ids) . ")");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $map[(int)$row['id']] = (string)($row['value_label'] ?? '');
            }
            $result->free();
        }
        return $map;
    }
}
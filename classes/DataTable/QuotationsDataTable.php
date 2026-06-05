<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';

class QuotationsDataTable extends BaseDataTable {
    protected $table = DB::QUOTATIONS;
    protected $searchFields = ['quotation_no', 'job_reference_no'];
    protected $sortableColumns = [0 => 'quotation_date', 1 => 'quotation_no', 2 => 'job_reference_no', 3 => 'customer_id', 4 => 'quotation_status', 5 => 'grand_total'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        $ids = array_filter(array_unique(array_map(fn($r) => (int)($r['customer_id'] ?? 0), $rows)));
        $this->relatedDataCache['customers'] = [];
        if ($ids) {
            $r = $this->mysqli->query("SELECT id, display_name FROM `" . DB::CUSTOMERS . "` WHERE id IN (" . implode(',', $ids) . ")");
            if ($r) { while ($row = $r->fetch_assoc()) $this->relatedDataCache['customers'][(int)$row['id']] = $row['display_name']; $r->free(); }
        }
    }

    protected function formatRow($row, $requestData = []) {
        $date       = (string)($row['quotation_date'] ?? '');
        $no         = (string)($row['quotation_no'] ?? '');
        $jobRef     = (string)($row['job_reference_no'] ?? '');
        $custId     = (int)($row['customer_id'] ?? 0);
        $status     = (string)($row['quotation_status'] ?? '');
        $total      = (string)($row['grand_total'] ?? '0');
        $custName   = $this->relatedDataCache['customers'][$custId] ?? '';
        $statusBadge = BadgeHelper::info(htmlspecialchars($status));
        return [
            htmlspecialchars($date),
            htmlspecialchars($no),
            htmlspecialchars($jobRef),
            htmlspecialchars($custName),
            $statusBadge,
            htmlspecialchars(number_format((float)$total, 2)),
        ];
    }
}

<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';

class PaymentsReceivedDataTable extends BaseDataTable {
    protected $table = DB::PAYMENTS_RECEIVED;
    protected $searchFields = ['reference_no'];
    protected $sortableColumns = [0 => 'payment_date', 1 => 'id', 2 => 'reference_no', 3 => 'customer_id', 4 => 'id', 5 => 'payment_method', 6 => 'total_amount_received', 7 => 'id', 8 => 'payment_status'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        $ids = array_filter(array_unique(array_map(fn($r) => (int)($r['customer_id'] ?? 0), $rows)));
        $this->relatedDataCache['customers'] = [];
        if ($ids) {
            $r = $this->mysqli->query("SELECT id, display_name FROM `" . DB::CUSTOMERS . "` WHERE id IN (" . implode(',', $ids) . ")");
            if ($r) { while ($row = $r->fetch_assoc()) $this->relatedDataCache['customers'][(int)$row['id']] = $row['display_name']; $r->free(); }
        }
    }

    protected function formatRow($row, $requestData = []) {
        $date     = (string)($row['payment_date'] ?? '');
        $custId   = (int)($row['customer_id'] ?? 0);
        $amount   = (string)($row['total_amount_received'] ?? '0');
        $method   = (string)($row['payment_method'] ?? '');
        $ref      = (string)($row['reference_no'] ?? '');
        $status   = (string)($row['payment_status'] ?? '');
        $custName = $this->relatedDataCache['customers'][$custId] ?? '';
        $badge    = BadgeHelper::info(htmlspecialchars($status));
        return [
            htmlspecialchars($date),
            (int)($row['id'] ?? 0),
            htmlspecialchars($ref),
            htmlspecialchars($custName),
            '',
            htmlspecialchars($method),
            htmlspecialchars(number_format((float)$amount, 2)),
            '',
            $badge,
        ];
    }
}

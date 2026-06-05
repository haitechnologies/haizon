<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';

class PaymentsMadeDataTable extends BaseDataTable {
    protected $table = DB::PAYMENTS_MADE;
    protected $searchFields = ['reference_no'];
    protected $sortableColumns = [0 => 'payment_date', 1 => 'id', 2 => 'reference_no', 3 => 'vendor_id', 4 => 'id', 5 => 'payment_method', 6 => 'total_amount_paid', 7 => 'payment_status'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        $ids = array_filter(array_unique(array_map(fn($r) => (int)($r['vendor_id'] ?? 0), $rows)));
        $this->relatedDataCache['vendors'] = [];
        if ($ids) {
            $r = $this->mysqli->query("SELECT id, display_name FROM `" . DB::VENDORS . "` WHERE id IN (" . implode(',', $ids) . ")");
            if ($r) { while ($row = $r->fetch_assoc()) $this->relatedDataCache['vendors'][(int)$row['id']] = $row['display_name']; $r->free(); }
        }
    }

    protected function formatRow($row, $requestData = []) {
        $date       = (string)($row['payment_date'] ?? '');
        $vendorId   = (int)($row['vendor_id'] ?? 0);
        $amount     = (string)($row['total_amount_paid'] ?? '0');
        $method     = (string)($row['payment_method'] ?? '');
        $ref        = (string)($row['reference_no'] ?? '');
        $status     = (string)($row['payment_status'] ?? '');
        $vendorName = $this->relatedDataCache['vendors'][$vendorId] ?? '';
        $badge      = BadgeHelper::info(htmlspecialchars($status));
        return [
            htmlspecialchars($date),
            (int)($row['id'] ?? 0),
            htmlspecialchars($ref),
            htmlspecialchars($vendorName),
            '',
            htmlspecialchars($method),
            htmlspecialchars(number_format((float)$amount, 2)),
            $badge,
        ];
    }
}

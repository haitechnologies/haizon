<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';

class DebitNotesDataTable extends BaseDataTable {
    protected $table = DB::DEBIT_NOTES;
    protected $searchFields = ['debit_note_no', 'reference_no'];
    protected $sortableColumns = [0 => 'debit_note_date', 1 => 'debit_note_no', 2 => 'reference_no', 3 => 'vendor_id', 4 => 'debit_note_status', 5 => 'grand_total'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        $ids = array_filter(array_unique(array_map(fn($r) => (int)($r['vendor_id'] ?? 0), $rows)));
        $this->relatedDataCache['vendors'] = [];
        if ($ids) {
            $r = $this->mysqli->query("SELECT id, display_name FROM `" . DB::VENDORS . "` WHERE id IN (" . implode(',', $ids) . ")");
            if ($r) { while ($row = $r->fetch_assoc()) $this->relatedDataCache['vendors'][(int)$row['id']] = $row['display_name']; $r->free(); }
        }
    }

    protected function formatRow($row, $requestData = []) {
        $date       = (string)($row['debit_note_date'] ?? '');
        $no         = (string)($row['debit_note_no'] ?? '');
        $ref        = (string)($row['reference_no'] ?? '');
        $vendorId   = (int)($row['vendor_id'] ?? 0);
        $status     = (string)($row['debit_note_status'] ?? '');
        $total      = (string)($row['grand_total'] ?? '0');
        $vendorName = $this->relatedDataCache['vendors'][$vendorId] ?? '';
        $badge      = BadgeHelper::info(htmlspecialchars($status));
        return [
            htmlspecialchars($date),
            htmlspecialchars($no),
            htmlspecialchars($ref),
            htmlspecialchars($vendorName),
            $badge,
            htmlspecialchars(number_format((float)$total, 2)),
        ];
    }
}

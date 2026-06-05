<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class PurchasesDataTable extends BaseDataTable
{
    protected $table = DB::PURCHASES;
    protected $searchFields = ['purchase_no', 'reference_no'];
    protected $sortableColumns = [0 => 'purchase_date', 1 => 'purchase_no', 2 => 'reference_no', 3 => 'vendor_id', 4 => 'purchase_status', 5 => 'expiry_date', 6 => 'grand_total', 7 => 'id'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $ids = array_filter(array_unique(array_map(fn($r) => (int)($r['vendor_id'] ?? 0), $rows)));
        $this->relatedDataCache['vendors'] = [];
        if ($ids) {
            try {
                $lookupRows = $this->db->fetchAll("SELECT id, display_name FROM `" . DB::VENDORS . "` WHERE id IN (" . implode(',', $ids) . ")");
                foreach ($lookupRows as $row) {
                    $this->relatedDataCache['vendors'][(int)$row['id']] = $row['display_name'];
                }
            } catch (\Throwable $e) {
                error_log("PurchasesDataTable::prepareRelatedData error: " . $e->getMessage());
            }
        }
    }

    protected function formatRow($row, $requestData = [])
    {
        $date       = (string)($row['purchase_date'] ?? '');
        $no         = (string)($row['purchase_no'] ?? '');
        $ref        = (string)($row['reference_no'] ?? '');
        $vendorId   = (int)($row['vendor_id'] ?? 0);
        $status     = (string)($row['purchase_status'] ?? '');
        $dueDate    = (string)($row['expiry_date'] ?? '');
        $total      = (string)($row['grand_total'] ?? '0');
        $vendorName = $this->relatedDataCache['vendors'][$vendorId] ?? '';
        $badge      = BadgeHelper::info(htmlspecialchars($status));
        return [
            htmlspecialchars($date),
            htmlspecialchars($no),
            htmlspecialchars($ref),
            htmlspecialchars($vendorName),
            $badge,
            htmlspecialchars($dueDate),
            htmlspecialchars(number_format((float)$total, 2)),
            '',
        ];
    }
}

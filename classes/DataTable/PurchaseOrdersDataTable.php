<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class PurchaseOrdersDataTable extends BaseDataTable {
    protected $table = DB::PURCHASE_ORDERS;
    protected $searchFields = ['purchase_order_no', 'reference_no'];
    protected $sortableColumns = [0 => 'id', 1 => 'purchase_order_date', 2 => 'purchase_order_no', 3 => 'reference_no', 4 => 'vendor_id', 5 => 'purchase_order_status', 6 => 'grand_total', 7 => 'id'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        $ids = array_filter(array_unique(array_map(fn($r) => (int)($r['vendor_id'] ?? 0), $rows)));
        $this->relatedDataCache['vendors'] = [];
        if ($ids) {
            $r = $this->mysqli->query("SELECT id, display_name FROM `" . DB::VENDORS . "` WHERE id IN (" . implode(',', $ids) . ")");
            if ($r) { while ($row = $r->fetch_assoc()) $this->relatedDataCache['vendors'][(int)$row['id']] = $row['display_name']; $r->free(); }
        }
    }

    protected function formatRow($row, $requestData = []) {
        $id         = (int)($row['id'] ?? 0);
        $date       = (string)($row['purchase_order_date'] ?? '');
        $no         = (string)($row['purchase_order_no'] ?? '');
        $ref        = (string)($row['reference_no'] ?? '');
        $vendorId   = (int)($row['vendor_id'] ?? 0);
        $status     = (string)($row['purchase_order_status'] ?? '');
        $total      = (string)($row['grand_total'] ?? '0');
        $vendorName = $this->relatedDataCache['vendors'][$vendorId] ?? '';
        $badge      = BadgeHelper::info(htmlspecialchars($status));
        return [
            $id,
            htmlspecialchars($date),
            htmlspecialchars($no),
            htmlspecialchars($ref),
            htmlspecialchars($vendorName),
            $badge,
            htmlspecialchars(number_format((float)$total, 2)),
            $this->getActionButtons($id, 'purchase_orders'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'purchase_orders.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class ShippingAdviceItemsDataTable extends BaseDataTable
{
    protected $table = DB::SHIPPING_ADVICE_ITEMS;
    protected $searchFields = ['hs_code', 'description'];
    protected $sortableColumns = [0 => 'id', 1 => 'id', 2 => 'hs_code', 3 => 'description', 4 => 'qty', 5 => 'qty', 6 => 'origin', 7 => 'value', 8 => 'weight', 9 => 'advice_id'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $itemIds = array_filter(array_unique(array_column($rows, 'id')));
        $adviceIds = array_filter(array_unique(array_column($rows, 'advice_id')));

        if (!empty($adviceIds)) {
            $placeholders = implode(',', array_fill(0, count($adviceIds), '?'));
            $sql = "SELECT id, invoice_no FROM `" . DB::SHIPPING_ADVICES . "` WHERE id IN ($placeholders)";
            $advices = $this->db->fetchAll($sql, array_values($adviceIds));
            foreach ($advices as $advice) {
                $this->relatedDataCache['advices'][$advice['id']] = $advice['invoice_no'];
            }
        }

        if (!empty($itemIds)) {
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $sql = "SELECT shipping_advice_item_id, SUM(out_qty) as total_out 
                    FROM `" . DB::SHIPPING_STOCK_ITEMS . "` 
                    WHERE shipping_advice_item_id IN ($placeholders) 
                    GROUP BY shipping_advice_item_id";
            $outs = $this->db->fetchAll($sql, array_values($itemIds));
            foreach ($outs as $out) {
                $this->relatedDataCache['outs'][$out['shipping_advice_item_id']] = (int)$out['total_out'];
            }
        }
    }

    protected function formatRow($row, $requestData = [])
    {
        $id       = (int)($row['id'] ?? 0);
        $hsCode   = (string)($row['hs_code'] ?? '');
        $desc     = (string)($row['description'] ?? '');
        $qty      = (int)($row['qty'] ?? 0);
        $origin   = (string)($row['origin'] ?? '');
        $value    = (string)($row['value'] ?? '');
        $weight   = (string)($row['weight'] ?? '');
        $adviceId = (int)($row['advice_id'] ?? 0);

        $invoiceNo = $this->relatedDataCache['advices'][$adviceId] ?? '';
        $totalOut = $this->relatedDataCache['outs'][$id] ?? 0;
        $remainingQty = max(0, $qty - $totalOut);

        $checkbox = '<input type="checkbox" class="form-check-input item-checkbox" value="' . $id . '">';

        return [
            $checkbox,
            $id,
            htmlspecialchars($hsCode),
            htmlspecialchars($desc),
            $qty,
            (string)$remainingQty,
            htmlspecialchars($origin),
            htmlspecialchars($value),
            htmlspecialchars($weight),
            htmlspecialchars($invoiceNo),
        ];
    }
}

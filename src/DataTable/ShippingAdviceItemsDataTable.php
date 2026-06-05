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

    protected function formatRow($row, $requestData = [])
    {
        $id       = (int)($row['id'] ?? 0);
        $hsCode   = (string)($row['hs_code'] ?? '');
        $desc     = (string)($row['description'] ?? '');
        $qty      = (string)($row['qty'] ?? '');
        $origin   = (string)($row['origin'] ?? '');
        $value    = (string)($row['value'] ?? '');
        $weight   = (string)($row['weight'] ?? '');
        $adviceId = (string)($row['advice_id'] ?? '');
        return [
            '',
            $id,
            htmlspecialchars($hsCode),
            htmlspecialchars($desc),
            htmlspecialchars($qty),
            '',
            htmlspecialchars($origin),
            htmlspecialchars($value),
            htmlspecialchars($weight),
            htmlspecialchars($adviceId),
        ];
    }
}

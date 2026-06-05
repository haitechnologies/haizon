<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;

class ShippingStocksDataTable extends BaseDataTable
{
    protected $table = DB::SHIPPING_STOCKS;

    protected $searchFields = [
        'invoice_date',
    ];

    protected $sortableColumns = [
        0 => 'invoice_date',
        1 => 'consignee_id',
        2 => 'destination_port',
        3 => 'destination_country',
        4 => 'incoterm',
        5 => 'id',
    ];

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $invoiceDate = (string)($row['invoice_date'] ?? '');
        $consigneeId = (int)($row['consignee_id'] ?? 0);
        $destinationPort = (int)($row['destination_port'] ?? 0);
        $destinationCountry = (int)($row['destination_country'] ?? 0);
        $incoterm = (int)($row['incoterm'] ?? 0);

        return [
            htmlspecialchars($invoiceDate),
            htmlspecialchars('Consignee #' . $consigneeId),
            htmlspecialchars('Port #' . $destinationPort),
            htmlspecialchars('Country #' . $destinationCountry),
            htmlspecialchars('Incoterm #' . $incoterm),
            '<a href="view_shipping_stocks.php?id=' . $id . '" title="View"><span class="text-dark opacity-75"><i class="ph-eye"></i></span></a>',
        ];
    }

    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? 0);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'invoice_date';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }
}

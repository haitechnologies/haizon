<?php

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class ShippingInvoicesDataTable extends BaseDataTable
{
    protected $table = DB::SHIPPING_INVOICES;

    protected $searchFields = [
        'invoice_no',
        'reference_no',
        'master_awb_no',
    ];

    protected $sortableColumns = [
        0 => 'id',
        1 => 'invoice_no',
        2 => 'invoice_date',
        3 => 'customer_id',
        4 => 'grand_total',
        5 => 'no_of_packs',
        6 => 'gross_weight',
        7 => 'master_awb_no',
        8 => 'created_at',
        9 => 'invoice_status',
        10 => 'id',
        11 => 'id',
    ];

    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $customerIds = array_filter(array_map(static function ($row) {
            return (int)($row['customer_id'] ?? 0);
        }, $rows));

        if (empty($customerIds)) {
            return;
        }

        $idList = implode(',', array_unique($customerIds));
        $this->relatedDataCache['customers'] = [];

        $query = "SELECT id, customer_name FROM `" . DB::SHIPPING_CUSTOMERS . "` WHERE id IN ({$idList})";
        $result = $this->mysqli->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['customers'][(int)$row['id']] = (string)($row['customer_name'] ?? '');
            }
            $result->free();
        }
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $invoiceNo = (string)($row['invoice_no'] ?? '');
        $invoiceDate = (string)($row['invoice_date'] ?? '');
        $customerId = (int)($row['customer_id'] ?? 0);
        $grandTotal = (float)($row['grand_total'] ?? 0);
        $packs = (string)($row['no_of_packs'] ?? '0');
        $weight = (string)($row['gross_weight'] ?? '0');
        $awb = (string)($row['master_awb_no'] ?? '');
        $createdAt = (string)($row['created_at'] ?? '');
        $invoiceStatus = (string)($row['invoice_status'] ?? 'draft');

        $customerName = $this->relatedDataCache['customers'][$customerId] ?? ('Customer #' . $customerId);
        $statusBadge = '<span class="badge bg-light text-dark">' . htmlspecialchars(ucwords(str_replace('_', ' ', $invoiceStatus))) . '</span>';

        return [
            $id,
            '<a href="view_shipping_invoice.php?id=' . $id . '" class="text-primary">' . htmlspecialchars($invoiceNo) . '</a>',
            htmlspecialchars($invoiceDate),
            htmlspecialchars($customerName),
            BASE_CURRENCY['code'] . dec_($grandTotal),
            htmlspecialchars($packs),
            htmlspecialchars($weight),
            htmlspecialchars($awb),
            htmlspecialchars(timeAgo($createdAt)),
            $statusBadge,
            '-',
            $this->getActionButtons($id, 'shipping_invoices'),
        ];
    }

    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? 0);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }

    protected function getActionButtons($id, $module)
    {
        $actions = '';

        if (granted_('edit', $module)) {
            $actions .= '<a href="view_shipping_invoice.php?id=' . (int)$id . '" title="View"><span class="text-dark opacity-75"><i class="ph-eye"></i></span></a> ';
        }

        if (granted_('delete', $module)) {
            $actions .= ActionButtonHelper::deleteButton((int)$id, $module);
        }

        return $actions;
    }
}

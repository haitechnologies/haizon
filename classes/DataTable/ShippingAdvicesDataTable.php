<?php

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class ShippingAdvicesDataTable extends BaseDataTable
{
    protected $table = DB::SHIPPING_ADVICES;

    protected $searchFields = [
        'invoice_no',
        'awb_no',
        'license_no',
        'mirsal_II_code',
    ];

    protected $sortableColumns = [
        0 => 'id',
        1 => 'invoice_no',
        2 => 'invoice_date',
        3 => 'customer_id',
        4 => 'awb_no',
        5 => 'license_no',
        6 => 'mirsal_II_code',
        7 => 'publish',
        8 => 'id',
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
        $awbNo = (string)($row['awb_no'] ?? '');
        $licenseNo = (string)($row['license_no'] ?? '');
        $mirsalCode = (string)($row['mirsal_II_code'] ?? '');
        $publish = (int)($row['publish'] ?? 0);

        $customerName = $this->relatedDataCache['customers'][$customerId] ?? ('Customer #' . $customerId);
        $statusBadge = ($publish === 1) ? BadgeHelper::success('Published') : BadgeHelper::warning('Draft');

        return [
            $id,
            '<a href="view_shipping_advice.php?id=' . $id . '" class="text-primary">' . htmlspecialchars($invoiceNo) . '</a>',
            htmlspecialchars($invoiceDate),
            htmlspecialchars($customerName),
            htmlspecialchars($awbNo),
            htmlspecialchars($licenseNo),
            htmlspecialchars($mirsalCode),
            $statusBadge,
            $this->getActionButtons($id, 'shipping_advices'),
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
            $actions .= '<a href="view_shipping_advice.php?id=' . (int)$id . '" title="View"><span class="text-dark opacity-75"><i class="ph-eye"></i></span></a> ';
        }

        if (granted_('delete', $module)) {
            $actions .= ActionButtonHelper::deleteButton((int)$id, $module);
        }

        return $actions;
    }
}

<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class PaymentsReceivedDataTable extends BaseDataTable
{
    protected $table = DB::PAYMENTS_RECEIVED;
    protected $searchFields = ['reference_no'];
    protected $sortableColumns = [0 => 'payment_date', 1 => 'id', 2 => 'reference_no', 3 => 'customer_id', 4 => 'id', 5 => 'payment_method', 6 => 'total_amount_received', 7 => 'id', 8 => 'payment_status'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $ids = array_filter(array_unique(array_map(fn($r) => (int)($r['customer_id'] ?? 0), $rows)));
        $this->relatedDataCache['customers'] = [];
        if ($ids) {
            try {
                $lookupRows = $this->db->fetchAll("SELECT id, display_name FROM `" . DB::CUSTOMERS . "` WHERE id IN (" . implode(',', $ids) . ")");
                foreach ($lookupRows as $row) {
                    $this->relatedDataCache['customers'][(int)$row['id']] = $row['display_name'];
                }
            } catch (\Throwable $e) {
                error_log("PaymentsReceivedDataTable::prepareRelatedData error: " . $e->getMessage());
            }
        }
    }

    protected function formatRow($row, $requestData = [])
    {
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

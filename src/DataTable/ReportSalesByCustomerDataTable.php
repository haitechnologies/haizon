<?php

/**
 * ReportSalesByCustomerDataTable Handler
 *
 * Manages server-side DataTable processing for the Sales by Customer report
 */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;

class ReportSalesByCustomerDataTable extends BaseDataTable
{
    /**
     * Table name placeholder
     */
    protected $table = DB::INVOICES;

    /**
     * Search fields in subquery output
     */
    protected $searchFields = [
        'customer'
    ];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'customer',
        1 => 'total_sales',
        2 => 'order_count',
        3 => 'avg_order_value'
    ];

    /**
     * Build base query with grouping, organization check and date filtering
     */
    protected function buildBaseQuery($requestData)
    {
        $dateFrom = $requestData['date_from'] ?? '';
        $dateTo = $requestData['date_to'] ?? '';
        
        $where = "WHERE i.id > 0 AND i.invoice_status IN ('sent', 'paid', 'partially_paid', 'overdue')";
        
        if ($this->organizationId !== null) {
            $where .= " AND i.organization_id = :active_org_id";
            $this->params['active_org_id'] = (int)$this->organizationId;
        }
        
        if (!empty($dateFrom)) {
            $where .= " AND i.invoice_date >= :date_from";
            $this->params['date_from'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where .= " AND i.invoice_date <= :date_to";
            $this->params['date_to'] = $dateTo;
        }
        
        return "SELECT * FROM (
            SELECT c.display_name AS customer,
                   COALESCE(SUM(i.grand_total), 0) AS total_sales,
                   COUNT(i.id) AS order_count,
                   CASE WHEN COUNT(i.id) > 0 THEN COALESCE(SUM(i.grand_total), 0) / COUNT(i.id) ELSE 0 END AS avg_order_value
            FROM `" . DB::INVOICES . "` i
            JOIN `" . DB::CUSTOMERS . "` c ON c.id = i.customer_id
            $where
            GROUP BY i.customer_id, c.display_name
        ) AS report_table WHERE 1=1";
    }

    /**
     * Format row data
     */
    protected function formatRow($row, $requestData = [])
    {
        $currencyCode = defined('BASE_CURRENCY') ? BASE_CURRENCY['code'] : 'AED';
        
        return [
            'customer' => htmlspecialchars($row['customer']),
            'total_sales' => $currencyCode . ' ' . number_format((float)$row['total_sales'], 2),
            'order_count' => (int)$row['order_count'],
            'avg_order_value' => $currencyCode . ' ' . number_format((float)$row['avg_order_value'], 2)
        ];
    }
}

<?php

/**
 * ReportSalesSummaryDataTable Handler
 *
 * Manages server-side DataTable processing for the Sales Summary report
 */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;

class ReportSalesSummaryDataTable extends BaseDataTable
{
    /**
     * Table name placeholder
     */
    protected $table = DB::INVOICES;

    /**
     * Search fields in subquery output
     */
    protected $searchFields = [
        'period'
    ];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'period_sort',
        1 => 'revenue',
        2 => 'orders',
        3 => 'customers',
        4 => 'avg_order_value'
    ];

    /**
     * Build base query with grouping and organization check
     */
    protected function buildBaseQuery($requestData)
    {
        $where = "WHERE i.id > 0 AND i.invoice_status IN ('sent', 'paid', 'partially_paid', 'overdue')";
        
        if ($this->organizationId !== null) {
            $where .= " AND i.organization_id = :active_org_id";
            $this->params['active_org_id'] = (int)$this->organizationId;
        }
        
        return "SELECT * FROM (
            SELECT DATE_FORMAT(i.invoice_date, '%M %Y') AS period,
                   COALESCE(SUM(i.grand_total), 0) AS revenue,
                   COUNT(i.id) AS orders,
                   COUNT(DISTINCT i.customer_id) AS customers,
                   CASE WHEN COUNT(i.id) > 0 THEN COALESCE(SUM(i.grand_total), 0) / COUNT(i.id) ELSE 0 END AS avg_order_value,
                   DATE_FORMAT(i.invoice_date, '%Y-%m') AS period_sort
            FROM `" . DB::INVOICES . "` i
            $where
            GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m'), DATE_FORMAT(i.invoice_date, '%M %Y')
        ) AS report_table WHERE 1=1";
    }

    /**
     * Format row data
     */
    protected function formatRow($row, $requestData = [])
    {
        $currencyCode = defined('BASE_CURRENCY') ? BASE_CURRENCY['code'] : 'AED';
        
        return [
            'period' => htmlspecialchars($row['period']),
            'revenue' => $currencyCode . ' ' . number_format((float)$row['revenue'], 2),
            'orders' => (int)$row['orders'],
            'customers' => (int)$row['customers'],
            'avg_order_value' => $currencyCode . ' ' . number_format((float)$row['avg_order_value'], 2)
        ];
    }
}

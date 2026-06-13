<?php

/**
 * ReportSalesByItemDataTable Handler
 *
 * Manages server-side DataTable processing for the Sales by Item report
 */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;

class ReportSalesByItemDataTable extends BaseDataTable
{
    /**
     * Table name placeholder
     */
    protected $table = DB::INVOICE_ITEMS;

    /**
     * Search fields in subquery output
     */
    protected $searchFields = [
        'item_name'
    ];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'item_name',
        1 => 'quantity_sold',
        2 => 'revenue',
        3 => 'cost',
        4 => 'profit',
        5 => 'profit_margin'
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
            $where .= " AND ii.organization_id = :active_org_id";
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
            SELECT item_name,
                   SUM(qty) AS quantity_sold,
                   SUM(total_revenue) AS revenue,
                   SUM(total_cost) AS cost,
                   SUM(total_revenue) - SUM(total_cost) AS profit,
                   CASE WHEN SUM(total_revenue) > 0 THEN ((SUM(total_revenue) - SUM(total_cost)) / SUM(total_revenue)) * 100 ELSE 0 END AS profit_margin
            FROM (
                SELECT COALESCE(NULLIF(t.item_name, ''), CONCAT('Item #', ii.service)) AS item_name,
                       ii.qty,
                       ii.total AS total_revenue,
                       ii.qty * COALESCE((
                           SELECT AVG(pi.rate)
                           FROM `" . DB::getPrefix() . "purchase_items` pi
                           WHERE pi.service = ii.service
                       ), 0) AS total_cost
                FROM `" . DB::INVOICE_ITEMS . "` ii
                JOIN `" . DB::INVOICES . "` i ON i.id = ii.invoice_id
                LEFT JOIN `" . DB::ITEMS . "` t ON t.id = ii.service
                $where
            ) AS item_sales
            GROUP BY item_name
        ) AS report_table WHERE 1=1";
    }

    /**
     * Format row data
     */
    protected function formatRow($row, $requestData = [])
    {
        $currencyCode = defined('BASE_CURRENCY') ? BASE_CURRENCY['code'] : 'AED';
        
        return [
            'item_name' => htmlspecialchars($row['item_name']),
            'quantity_sold' => number_format((float)$row['quantity_sold'], 2),
            'revenue' => $currencyCode . ' ' . number_format((float)$row['revenue'], 2),
            'cost' => $currencyCode . ' ' . number_format((float)$row['cost'], 2),
            'profit' => $currencyCode . ' ' . number_format((float)$row['profit'], 2),
            'profit_margin' => number_format((float)$row['profit_margin'], 2) . '%'
        ];
    }
}

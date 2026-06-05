<?php

/**
 * InvoicesDataTable Handler
 *
 * Manages server-side DataTable processing for the Invoices module.
 */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\ActionButtonHelper;

class InvoicesDataTable extends BaseDataTable
{
    /**
     * Table name
     */
    protected $table = DB::INVOICES;

    /**
     * Search fields
     */
    protected $searchFields = [
        'invoice_no',
        'reference_no'
    ];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'invoice_date',
        1 => 'invoice_no',
        2 => 'sale_order_id',
        3 => 'customer_id',
        4 => 'invoice_status',
        5 => 'expiry_date',
        6 => 'grand_total',
        7 => 'grand_total'
    ];

    /**
     * Build base query with status filtering and organization check
     */
    protected function buildBaseQuery($requestData)
    {
        $query = "SELECT * FROM `" . $this->table . "` WHERE id > 0 AND recurring = 0" . $this->getOrgIdWhereClause();

        $customerId = isset($requestData['customer_id']) ? (int)$requestData['customer_id'] : 0;
        $invoiceStatus = $requestData['invoice_status'] ?? '';

        if ($customerId > 0) {
            $query .= " AND customer_id = :customer_id";
            $this->params['customer_id'] = $customerId;
        }

        if (!empty($invoiceStatus)) {
            $query .= " AND invoice_status = :invoice_status";
            $this->params['invoice_status'] = $invoiceStatus;
        }

        return $query;
    }

    /**
     * Build search clause by checking customer names first or searching by invoice fields
     */
    protected function buildSearchClause($requestData)
    {
        $searchValue = $requestData['search']['value'] ?? '';
        if (empty($searchValue)) {
            return '';
        }

        // First try to find customer by name in active organization
        $customerQuery = "SELECT id FROM `" . DB::CUSTOMERS . "` 
                          WHERE display_name LIKE :search_val";
        $custParams = ['search_val' => '%' . $searchValue . '%'];
        if ($this->organizationId !== null) {
            $customerQuery .= " AND organization_id = :cust_org_id";
            $custParams['cust_org_id'] = (int)$this->organizationId;
        }
        $customerQuery .= " LIMIT 1";

        try {
            $customerRow = $this->db->fetchOne($customerQuery, $custParams);
            if ($customerRow !== null) {
                $this->params['search_customer_id'] = (int)$customerRow['id'];
                return " AND customer_id = :search_customer_id";
            }
        } catch (\Throwable $e) {
            error_log("InvoicesDataTable::buildSearchClause() customer search failed: " . $e->getMessage());
        }

        // If no customer found, search by invoice number or reference
        $this->params['search_invoice_no'] = '%' . $searchValue . '%';
        $this->params['search_reference_no'] = '%' . $searchValue . '%';
        return " AND (invoice_no LIKE :search_invoice_no OR reference_no LIKE :search_reference_no)";
    }

    /**
     * Pre-fetch customer names to prevent N+1 queries
     */
    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $customerIds = array_unique(array_filter(array_map(fn($r) => (int)($r['customer_id'] ?? 0), $rows)));

        if (empty($customerIds)) {
            return;
        }

        $placeholders = [];
        $params = [];
        foreach ($customerIds as $index => $id) {
            $key = 'cust_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        $placeholdersStr = implode(',', $placeholders);

        $customerQuery = "
            SELECT id, display_name 
            FROM `" . DB::CUSTOMERS . "` 
            WHERE id IN ({$placeholdersStr})
        ";
        if ($this->organizationId !== null) {
            $customerQuery .= " AND organization_id = :cust_org_id";
            $params['cust_org_id'] = (int)$this->organizationId;
        }

        $this->relatedDataCache['customers'] = [];
        try {
            $customerRows = $this->db->fetchAll($customerQuery, $params);
            foreach ($customerRows as $cRow) {
                $this->relatedDataCache['customers'][(int)$cRow['id']] = $cRow['display_name'] ?? '-';
            }
        } catch (\Throwable $e) {
            error_log("InvoicesDataTable::prepareRelatedData() failed: " . $e->getMessage());
        }
    }

    /**
     * Format row data
     */
    protected function formatRow($row, $requestData = [])
    {
        $id = (int)$row['id'];
        $invoiceDate = $row['invoice_date'] ?? '';
        $invoiceNo = $row['invoice_no'] ?? '';
        $saleOrderNo = $row['sale_order_no'] ?? ($row['sale_order_id'] ?? '');
        $customerId = (int)$row['customer_id'];
        $invoiceStatus = $row['invoice_status'] ?? 'draft';
        $grandTotal = (float)($row['grand_total'] ?? 0.0);
        $expiryDate = $row['expiry_date'] ?? '';
        $balanceDue = $row['balance_due'] ?? $grandTotal;

        $customerName = $this->relatedDataCache['customers'][$customerId] ?? '-';

        // Build status badge
        $statusBadge = '<span class="badge text-dark">' . ucwords(str_replace('_', ' ', $invoiceStatus)) . '</span>';

        $expiryDisplay = (!empty($expiryDate) && $expiryDate !== '1970-01-01') ? (function_exists('timeAgo') ? timeAgo($expiryDate) : $expiryDate) : '-';
        $dateDisplay = !empty($invoiceDate) ? (function_exists('timeAgo') ? timeAgo($invoiceDate) : $invoiceDate) : '-';

        $currencyCode = defined('BASE_CURRENCY') ? BASE_CURRENCY['code'] : 'AED';
        $formattedGrandTotal = function_exists('dec_') ? dec_($grandTotal) : number_format($grandTotal, 2);
        $formattedBalanceDue = function_exists('dec_') ? dec_((float)$balanceDue) : number_format((float)$balanceDue, 2);

        return [
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . htmlspecialchars($dateDisplay) . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-primary"> ' . htmlspecialchars($invoiceNo) . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . htmlspecialchars((string)$saleOrderNo) . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . htmlspecialchars($customerName) . ' </a>',
            $statusBadge,
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . htmlspecialchars($expiryDisplay) . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . $currencyCode . ' ' . $formattedGrandTotal . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . $currencyCode . ' ' . $formattedBalanceDue . ' </a>'
        ];
    }

    /**
     * Get action buttons
     */
    protected function getActionButtons($id, $module)
    {
        $actions = '';

        if (function_exists('granted_') && granted_('edit', $module)) {
            $actions .= '<a href="invoice_overview.php?invoice_id=' . $id . '" title="View"><span class="text-dark opacity-50"><i class="ph-eye"></i></span></a> ';
        }

        if (function_exists('granted_') && granted_('delete', $module)) {
            $actions .= ActionButtonHelper::deleteButton($id, $module);
        }

        return $actions;
    }

    /**
     * Build order clause
     */
    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? count($this->sortableColumns) - 2);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }
}

<?php
/**
 * InvoicesDataTable Handler
 * 
 * Manages server-side DataTable processing for the Invoices module
 * Handles: search by customer or invoice number, sort, filter, pagination
 * 
 * @package DataTable
 * @subpackage Handlers
 * @version 1.0
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class InvoicesDataTable extends BaseDataTable {
    
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
        2 => 'sale_order_no',
        3 => 'customer_id',
        4 => 'invoice_status',
        5 => 'expiry_date',
        6 => 'grand_total',
        7 => 'grand_total'
    ];
    
    /**
     * OPTIMIZATION: Pre-fetch customer names to prevent N+1 queries
     */
    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $customerIds = array_filter(array_map(fn($r) => (int)($r['customer_id'] ?? 0), $rows));
        
        if (empty($customerIds)) {
            return;
        }

        $idList = implode(',', array_unique($customerIds));

        // OPTIMIZATION: Fetch all customer names in ONE query
        $customerQuery = "
            SELECT id, display_name 
            FROM " . DB::CUSTOMERS . " 
            WHERE id IN ({$idList})
        ";
        
        $this->relatedDataCache['customers'] = [];
        $result = $this->mysqli->query($customerQuery);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['customers'][(int)$row['id']] = $row['display_name'] ?? '-';
            }
        }
    }
    
    /**
     * Build base query with filters
     * 
     * @param array $requestData Request data
     * @return string Base SQL query
     */
    protected function buildBaseQuery($requestData) {
        $query = "SELECT * FROM `" . $this->table . "` WHERE id > 0 AND recurring = 0";
        
        // Filter by customer and status if provided
        $customerId = isset($requestData['customer_id']) ? (int)$requestData['customer_id'] : 0;
        $invoiceStatus = $requestData['invoice_status'] ?? '';
        
        if ($customerId > 0 && !empty($invoiceStatus)) {
            $query .= " AND customer_id = {$customerId}";
        } elseif ($customerId > 0) {
            $query .= " AND customer_id = {$customerId}";
        }
        
        return $query;
    }
    
    /**
     * Build search clause with customer name or invoice lookup
     * 
     * @param array $requestData Request data
     * @return string WHERE clause
     */
    protected function buildSearchClause($requestData) {
        global $mysqli;
        
        if (empty($requestData['search']['value'])) {
            return '';
        }
        
        $searchValue = $mysqli->real_escape_string($requestData['search']['value']);
        
        // First try to find customer by name
        $customerResult = $mysqli->query(
            "SELECT id FROM `" . DB::CUSTOMERS . "` 
             WHERE display_name LIKE '%{$searchValue}%' 
             LIMIT 1"
        );
        
        if ($customerResult && $customerResult->num_rows > 0) {
            $customerRow = $customerResult->fetch_assoc();
            $customerId = $customerRow['id'];
            return " AND customer_id = {$customerId}";
        }
        
        // If no customer found, search by invoice number
        return " AND (invoice_no LIKE '%{$searchValue}%' OR reference_no LIKE '%{$searchValue}%')";
    }
    
    /**
     * Format row data - OPTIMIZATION: Uses pre-fetched customer names
     * 
     * @param array $row Database row
     * @param array $requestData Request data
     * @return array Formatted row
     */
    protected function formatRow($row, $requestData = []) {
        global $mysqli;
        
        $id = (int)$row['id'];
        $invoiceDate = $row['invoice_date'] ?? '';
        $invoiceNo = $row['invoice_no'] ?? '';
        $saleOrderNo = $row['sale_order_no'] ?? ($row['sale_order_id'] ?? '');
        $customerId = (int)$row['customer_id'];
        $invoiceStatus = $row['invoice_status'] ?? 'draft';
        $grandTotal = (float)($row['grand_total'] ?? 0);
        $expiryDate = $row['expiry_date'] ?? '';
        $balanceDue = $row['balance_due'] ?? $grandTotal;
        
        // OPTIMIZATION: Use pre-fetched customer name instead of per-row query
        $customerName = $this->relatedDataCache['customers'][$customerId] ?? '-';
        
        // Build status badge
        $statusBadge = '<span class="badge text-dark">' . ucwords($invoiceStatus) . '</span>';

        $expiryDisplay = (!empty($expiryDate) && $expiryDate !== '1970-01-01') ? timeAgo($expiryDate) : '-';
        
        return [
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . timeAgo($invoiceDate) . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-primary"> ' . htmlspecialchars($invoiceNo) . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . htmlspecialchars($saleOrderNo) . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . htmlspecialchars($customerName) . ' </a>',
            $statusBadge,
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . $expiryDisplay . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . BASE_CURRENCY['code'] . dec_($grandTotal) . ' </a>',
            '<a href="invoice_overview.php?invoice_id=' . $id . '" class="text-black"> ' . BASE_CURRENCY['code'] . dec_($balanceDue) . ' </a>'
        ];
    }
    
    /**
     * Get action buttons
     * 
     * @param int $id Record ID
     * @param string $module Module name
     * @return string HTML action buttons
     */    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? count($this->sortableColumns) - 2);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }


    protected function getActionButtons($id, $module) {
        $actions = '';
        
        if (granted_('edit', $module)) {
            $actions .= '<a href="invoice_overview.php?invoice_id=' . $id . '" title="View"><span class="text-dark opacity-50"><i class="ph-eye"></i></span></a> ';
        }
        
        if (granted_('delete', $module)) {
            $actions .= ActionButtonHelper::deleteButton($id, $module);
        }
        
        return $actions;
    }
}



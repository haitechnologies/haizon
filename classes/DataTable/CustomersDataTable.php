<?php
/**
 * CustomersDataTable Handler
 * 
 * Manages server-side DataTable processing for the Customers module
 * Handles: search, sort, filter by status, pagination, and complex calculations
 * 
 * @package DataTable
 * @subpackage Handlers
 * @version 1.0
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class CustomersDataTable extends BaseDataTable {
    
    /**
     * Table name (uses DB constants)
     */
    protected $table = DB::CUSTOMERS;
    
    /**
     * Search fields - columns that can be searched
     */
    protected $searchFields = [
        'first_name',
        'last_name',
        'display_name'
    ];
    
    /**
     * Sortable columns with safe mapping
     */
    protected $sortableColumns = [
        0 => 'id',
        1 => 'display_name',
        2 => 'email',
        3 => 'phone',
        4 => 'customer_owner',
        5 => 'created_at',
        6 => 'approved',
        7 => 'is_active',
        8 => 'id'
    ];
    
    /**
     * Build base query - override for custom WHERE conditions
     * 
     * @param array $requestData Request data from DataTable
     * @return string Base SQL query
     */
    protected function buildBaseQuery($requestData) {
        global $mysqli;
        
        $query = "SELECT * FROM `" . $this->table . "` WHERE id > 0";
        
        // Filter by customer status if provided
        $customerStatus = isset($requestData['customer_status']) ? (int)$requestData['customer_status'] : 0;
        if ($customerStatus > 0) {
            $query .= " AND customer_status = " . $customerStatus;
        }
        
        return $query;
    }
    
    /**
     * Build search clause with multiple field search
     * 
     * @param array $requestData Request data
     * @return string WHERE clause for search
     */
    protected function buildSearchClause($requestData) {
        global $mysqli;
        
        if (empty($requestData['search']['value'])) {
            return '';
        }
        
        $search = $mysqli->real_escape_string($requestData['search']['value']);
        
        $conditions = [];
        foreach ($this->searchFields as $field) {
            $conditions[] = "`{$field}` LIKE '%{$search}%'";
        }
        
        return 'AND (' . implode(' OR ', $conditions) . ')';
    }

    /**
     * Pre-fetch attachment counts and invoice receivables for all displayed customers
     * OPTIMIZATION: Prevents N+1 queries by fetching all related data in bulk
     * 
     * @param array $rows Customer rows
     * @param array $requestData Request parameters
     */
    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        // Get all customer IDs from current page
        $customerIds = array_filter(array_map(fn($r) => (int)($r['id'] ?? 0), $rows));
        
        if (empty($customerIds)) {
            return;
        }

        $idList = implode(',', $customerIds);

        // Attachment count — erp_customer_attachments decommissioned; always 0
        $this->relatedDataCache['attachments'] = [];

        // OPTIMIZATION 2: Fetch all receivables in ONE query
        $invoiceQuery = "
            SELECT customer_id, COALESCE(SUM(grand_total), 0) as total 
            FROM `" . DB::INVOICES . "` 
            WHERE customer_id IN ({$idList})
            AND invoice_status IN ('sent', 'partially_paid', 'overdue')
            GROUP BY customer_id
        ";
        
        $this->relatedDataCache['receivables'] = [];
        $result = $this->mysqli->query($invoiceQuery);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['receivables'][(int)$row['customer_id']] = max(0, (float)$row['total']);
            }
        }
    }
    
    /**
     * Format row data for JSON response
     * OPTIMIZATION: Uses pre-fetched data instead of per-row queries
     * 
     * @param array $row Database row
     * @param array $requestData Request data
     * @return array Formatted row data
     */
    protected function formatRow($row, $requestData = []) {
        global $mysqli;
        
        $id = (int)$row['id'];
        $displayName = $row['display_name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $email = $row['email'] ?? '';
        $phone = $row['phone'] ?? '';
        $created_at = $row['created_at'] ?? '';
        $approved = (int)($row['approved'] ?? 0);
        $publish = (int)($row['is_active'] ?? 1);
        
        // OPTIMIZATION: Use pre-fetched attachment count instead of querying
        $totalAttachments = $this->relatedDataCache['attachments'][$id] ?? 0;
        $paperclip = $totalAttachments > 0 ? ' <i class="ph-paperclip"></i>' : '';
        
        // OPTIMIZATION: Use pre-fetched receivables instead of querying
        $customerReceivables = $this->relatedDataCache['receivables'][$id] ?? 0;
        
        // Build approval status badge
        $approvalBadge = match($approved) {
            0 => BadgeHelper::warning('Approval Requested'),
            1 => BadgeHelper::success('Approved'),
            2 => BadgeHelper::danger('Not Approved'),
            default => BadgeHelper::secondary('Unknown')
        };
        
        // Build publish status badge
        $publishBadge = $publish == 0 
            ? BadgeHelper::danger('Inactive')
            : BadgeHelper::success('Active');
        
        return [
            '<a href="customer_overview.php?customer_id=' . $id . '" class="text-primary"> ' . htmlspecialchars($displayName) . $paperclip . ' </a>',
            '<a href="customer_overview.php?customer_id=' . $id . '" class="text-black"> ' . htmlspecialchars($email) . ' </a>',
            '<a href="customer_overview.php?customer_id=' . $id . '" class="text-black"> ' . htmlspecialchars($phone) . ' </a>',
            '<a href="customer_overview.php?customer_id=' . $id . '" class="text-black"> ' . BASE_CURRENCY['code'] . dec_($customerReceivables) . '</a>',
            $publishBadge,
            $approvalBadge,
            $this->getActionButtons($id, 'customers')
        ];
    }
    
    /**
     * Get action buttons for row
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
            $actions .= '<a href="customer_overview.php?customer_id=' . $id . '" title="View"><span class="text-dark opacity-50"><i class="ph-eye"></i></span></a> ';
        }
        
        if (granted_('delete', $module)) {
            $actions .= ActionButtonHelper::deleteButton($id, $module);
        }
        
        return $actions;
    }
}



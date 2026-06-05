<?php
/**
 * CustomerLogsDataTable Handler
 *
 * Manages server-side DataTable processing for customer activity logs
 *
 * @package DataTable
 * @subpackage Handlers
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class CustomerLogsDataTable extends BaseDataTable
{
    /**
     * Table name
     */
    protected $table = DB::ENTITY_LOGS;

    /**
     * Searchable fields
     */
    protected $searchFields = [
        'cl.module',
        'cl.action',
        'c.display_name',
        'c.email'
    ];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'cl.id',
        1 => 'c.display_name',
        2 => 'cl.module',
        3 => 'cl.action',
        4 => 'cl.created_at',
        5 => 'cl.id'
    ];

    /**
     * Build base query with customer join
     *
     * @param array $requestData Request data
     * @return string Base SQL query
     */
    protected function buildBaseQuery($requestData)
    {
        return "SELECT cl.*, c.display_name, c.email "
            . "FROM `" . DB::ENTITY_LOGS . "` cl "
            . "LEFT JOIN `" . DB::CUSTOMERS . "` c ON c.id = cl.entity_id "
            . "WHERE cl.entity_type = 'customer'";
    }

    /**
     * Build search clause
     *
     * @param array $requestData Request data
     * @return string Search clause
     */
    protected function buildSearchClause($requestData)
    {
        $searchValue = $requestData['search']['value'] ?? '';
        if (empty($searchValue)) {
            return '';
        }

        $searchValue = $this->mysqli->real_escape_string($searchValue);
        $conditions = [];

        foreach ($this->searchFields as $field) {
            $conditions[] = "{$field} LIKE '%{$searchValue}%'";
        }

        return 'AND (' . implode(' OR ', $conditions) . ')';
    }

    /**
     * Format row data
     *
     * @param array $row Database row
     * @param array $requestData Request data
     * @return array Formatted row
     */
    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $customerId = (int)($row['entity_id'] ?? 0);
        $displayName = s__($row['display_name'] ?? '') ?: 'Unknown';
        $module = s__($row['module'] ?? '') ?: '-';
        $action = s__($row['action'] ?? '') ?: '-';
        $createdAt = $row['created_at'] ?? '';

        $createdDisplay = !empty($createdAt) ? dd_($createdAt, 'd M Y g:ia') : '-';

        return [
            $id,
            '<a href="customer_overview.php?customer_id=' . $customerId . '" class="text-primary">' . htmlspecialchars($displayName) . '</a>',
            htmlspecialchars(ucwords(str_replace('_', ' ', $module))),
            htmlspecialchars(ucwords($action)),
            $createdDisplay,
            $this->getActionButtons($id, 'customer_logs', $customerId)
        ];
    }

    /**
     * Build action buttons
     *
     * @param int $id Record ID
     * @param string $module Module name
     * @param int $customerId Customer ID
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


    protected function getActionButtons($id, $module, $customerId)
    {
        $actions = '';

        if (granted_('edit', 'customers')) {
            $actions .= '<a href="customer_logs.php?customer_id=' . $customerId . '" title="View"><span class="text-dark opacity-50"><i class="ph-eye"></i></span></a> ';
        }

        if (granted_('delete', $module)) {
            $actions .= ActionButtonHelper::deleteButton($id, $module);
        }

        return $actions;
    }
}



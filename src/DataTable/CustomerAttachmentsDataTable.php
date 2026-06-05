<?php

/**
 * CustomerAttachmentsDataTable Handler
 *
 * Manages server-side DataTable processing for customer attachments
 *
 * @package DataTable
 * @subpackage Handlers
 */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class CustomerAttachmentsDataTable extends BaseDataTable
{
    /**
     * Table name
     */
    protected $table = 'erp_customer_attachments'; // table decommissioned

    /**
     * Searchable fields
     */
    protected $searchFields = [
        'ca.customer_attachment',
        'ca.filename',
        'c.display_name',
        'c.email'
    ];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'ca.id',
        1 => 'c.display_name',
        2 => 'ca.customer_attachment',
        3 => 'ca.filename',
        4 => 'ca.created_at',
        5 => 'ca.id'
    ];

    /**
     * Build base query with customer join
     *
     * @param array $requestData Request data
     * @return string Base SQL query
     */
    protected function buildBaseQuery($requestData)
    {
        return "SELECT 1 WHERE 0 = 1"; // erp_customer_attachments decommissioned
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
        $customerId = (int)($row['customer_id'] ?? 0);
        $displayName = s__($row['display_name'] ?? '') ?: 'Unknown';
        $attachment = s__($row['customer_attachment'] ?? '') ?: '-';
        $filename = s__($row['filename'] ?? '') ?: '';
        $createdAt = $row['created_at'] ?? '';

        $fileLink = '-';
        if (!empty($filename)) {
            $fileLink = '<a href="../uploads/customer_attachments/' . rawurlencode($filename) . '" target="_blank">' . htmlspecialchars($filename) . '</a>';
        }

        $createdDisplay = !empty($createdAt) ? dd_($createdAt, 'd M Y') : '-';

        return [
            $id,
            '<a href="customer_overview.php?customer_id=' . $customerId . '" class="text-primary">' . htmlspecialchars($displayName) . '</a>',
            htmlspecialchars($attachment),
            $fileLink,
            $createdDisplay,
            $this->getActionButtons($id, 'customer_attachments', $customerId)
        ];
    }

    /**
     * Build order clause
     *
     * @param array $requestData Request data
     * @return string ORDER BY clause
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

    /**
     * Build action buttons
     *
     * @param int $id Record ID
     * @param string $module Module name
     * @param int $customerId Customer ID
     * @return string HTML action buttons
     */
    protected function getActionButtons($id, $module, $customerId)
    {
        $buttons = [];

        if (granted_('view', 'customers')) {
            $buttons[] = ActionButtonHelper::viewButton($id, $module);
        }

        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }

        return implode(' ', array_filter($buttons));
    }
}

<?php
/**
 * CustomerAddressesDataTable Handler
 *
 * Manages server-side DataTable processing for customer addresses
 *
 * @package DataTable
 * @subpackage Handlers
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class CustomerAddressesDataTable extends BaseDataTable
{
    /**
     * Table name
     */
    protected $table = DB::CUSTOMER_ADDRESSES;

    /**
     * Searchable fields
     */
    protected $searchFields = [
        'ca.attention',
        'ca.address_line1',
        'ca.address_line2',
        'ca.city',
        'ca.state',
        'ca.phone',
        'c.display_name',
        'c.email'
    ];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'ca.id',
        1 => 'c.display_name',
        2 => 'ca.type',
        3 => 'ca.attention',
        4 => 'ca.city',
        5 => 'ca.state',
        6 => 'ca.country',
        7 => 'ca.phone',
        8 => 'ca.created_at',
        9 => 'ca.id'
    ];

    /**
     * Build base query with customer join
     *
     * @param array $requestData Request data
     * @return string Base SQL query
     */
    protected function buildBaseQuery($requestData)
    {
        return "SELECT ca.*, c.display_name, c.email "
            . "FROM `" . DB::CUSTOMER_ADDRESSES . "` ca "
            . "LEFT JOIN `" . DB::CUSTOMERS . "` c ON c.id = ca.customer_id "
            . "WHERE ca.id > 0";
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
        $type = s__($row['type'] ?? '');
        $attention = s__($row['attention'] ?? '') ?: '-';
        $city = s__($row['city'] ?? '') ?: '-';
        $state = s__($row['state'] ?? '') ?: '-';
        $countryId = (int)($row['country'] ?? 0);
        $phone = s__($row['phone'] ?? '') ?: '-';
        $createdAt = $row['created_at'] ?? '';

        $typeBadge = $type === 'billing'
            ? BadgeHelper::info('Billing')
            : ($type === 'shipping' ? BadgeHelper::primary('Shipping') : BadgeHelper::secondary('Other'));

        $countryName = $countryId > 0
            ? getTableAttr('country_name', DB::GEO_COUNTRIES, $countryId)
            : '-';

        $createdDisplay = !empty($createdAt) ? dd_($createdAt, 'd M Y') : '-';

        return [
            $id,
            '<a href="customer_overview.php?customer_id=' . $customerId . '" class="text-primary">' . htmlspecialchars($displayName) . '</a>',
            $typeBadge,
            htmlspecialchars($attention),
            htmlspecialchars($city),
            htmlspecialchars($state),
            htmlspecialchars($countryName ?? '-'),
            htmlspecialchars($phone),
            $createdDisplay,
            $this->getActionButtons($id, 'customer_addresses', $customerId)
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
            $actions .= '<a href="customer_overview.php?customer_id=' . $customerId . '" title="View"><span class="text-dark opacity-50"><i class="ph-eye"></i></span></a> ';
        }

        if (granted_('delete', $module)) {
            $actions .= ActionButtonHelper::deleteButton($id, $module);
        }

        return $actions;
    }
}



<?php
/**
 * FrontendUserSearchesDataTable Handler
 *
 * Manages server-side DataTable processing for frontend user searches
 *
 * @package DataTable
 * @subpackage Handlers
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class FrontendUserSearchesDataTable extends BaseDataTable
{
    /**
     * Table name
     */
    protected $table = DB::SEARCHES;

    protected $searchFields = ['search_query', 'keyword'];

    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 's.id',
        1 => 'fu.full_name',
        2 => 'fu.email',
        3 => 's.search_query',
        4 => 's.result_count',
        5 => 's.created_at',
        6 => 's.id'
    ];

    /**
     * Build base query with optional joins
     *
     * @param array $requestData Request data
     * @return string Base SQL query
     */
    protected function buildBaseQuery($requestData)
    {
        return "SELECT s.*, fu.full_name, fu.email "
            . "FROM `" . DB::SEARCHES . "` s "
            . "LEFT JOIN `" . DB::FRONTEND_USERS . "` fu ON fu.id = s.user_id "
            . "WHERE s.id > 0 AND s.search_type = 'saved'";
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

        return "AND (fu.full_name LIKE '%{$searchValue}%' OR fu.email LIKE '%{$searchValue}%' OR s.search_query LIKE '%{$searchValue}%' OR s.keyword LIKE '%{$searchValue}%')";
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
        $fullName = s__($row['full_name'] ?? '') ?: 'Unknown';
        $email = s__($row['email'] ?? '') ?: '-';
        $query = s__($row['search_query'] ?? ($row['keyword'] ?? ($row['query'] ?? ''))) ?: '-';
        $results = $row['results_count'] ?? ($row['total_results'] ?? ($row['results'] ?? null));
        if ($results === null && isset($row['result_count'])) {
            $results = $row['result_count'];
        }
        $resultsDisplay = $results !== null ? (int)$results : '-';
        $createdAt = $row['created_at'] ?? '';

        $createdDisplay = !empty($createdAt) ? dd_($createdAt, 'd M Y g:ia') : '-';

        return [
            $id,
            htmlspecialchars($fullName),
            htmlspecialchars($email),
            htmlspecialchars($query),
            $resultsDisplay,
            $createdDisplay,
            $this->getActionButtons($id, 'frontend_user_searches')
        ];
    }

    /**
     * Build action buttons
     *
     * @param int $id Record ID
     * @param string $module Module name
     * @return string HTML action buttons
     */
    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? 0);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 's.id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }

    /**
     * Build action buttons
     *
     * @param int $id Record ID
     * @param string $module Module name
     * @return string HTML action buttons
     */
    protected function getActionButtons($id, $module)
    {
        $buttons = [];

        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }

        return implode(' ', array_filter($buttons));
    }
}



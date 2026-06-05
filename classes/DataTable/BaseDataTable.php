<?php
/**
 * Abstract Base Class for DataTable Handlers
 *
 * Provides common functionality for all DataTable modules:
 * - Query building
 * - Search and filtering
 * - Sorting and pagination
 * - Row formatting
 * - Error handling
 */

abstract class BaseDataTable
{
    /** @var mysqli */
    protected $mysqli;

    /** @var int|null */
    protected $userId = null;

    /** @var int|null */
    protected $roleId = null;

    /** @var int|null */
    protected $organizationId = null;

    /** @var string */
    protected $table;

    /** @var array */
    protected $searchFields = [];

    /** @var array */
    protected $sortableColumns = [];

    /** @var int */
    protected $totalRecords = 0;

    /** @var int */
    protected $filteredRecords = 0;

    /** @var array */
    protected $data = [];

    /** @var array Cache for related data to prevent N+1 queries */
    protected $relatedDataCache = [];

    public function __construct($mysqli, $userId = null, $roleId = null, $organizationId = null)
    {
        $this->mysqli = $mysqli;
        $this->userId = $userId;
        $this->roleId = $roleId;
        $this->organizationId = $organizationId;
    }

    /**
     * Main DataTable request processor
     */
    public function process(array $requestData): array
    {
        try {
            $draw = (int)($requestData['draw'] ?? 1);
            $start = (int)($requestData['start'] ?? 0);
            $length = (int)($requestData['length'] ?? 10);

            $baseQuery = $this->buildBaseQuery($requestData);
            $this->totalRecords = $this->countRecords($baseQuery);

            $searchClause = $this->buildSearchClause($requestData);
            if ($searchClause !== '') {
                $baseQuery .= ' ' . $searchClause;
            }

            $this->filteredRecords = $this->countRecords($baseQuery);

            $orderClause = $this->buildOrderClause($requestData);
            if ($orderClause !== '') {
                $baseQuery .= ' ' . $orderClause;
            }

            $limitClause = $this->buildLimitClause($requestData, $start, $length);
            if ($limitClause !== '') {
                $baseQuery .= ' ' . $limitClause;
            }

            $result = $this->mysqli->query($baseQuery);
            if (!$result) {
                error_log('DataTable SQL (' . static::class . '): ' . $baseQuery);
                throw new Exception('Database query failed: ' . $this->mysqli->error);
            }

            // Fetch all rows first
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            // Pre-fetch related data for all rows (prevents N+1 queries)
            if (!empty($rows)) {
                $this->prepareRelatedData($rows, $requestData);
            }

            // Format rows using pre-fetched data
            $this->data = [];
            foreach ($rows as $row) {
                $this->data[] = $this->formatRow($row, $requestData);
            }

            return [
                'draw' => $draw,
                'recordsTotal' => $this->totalRecords,
                'recordsFiltered' => $this->filteredRecords,
                'data' => $this->data
            ];
        } catch (\Throwable $e) {
            error_log('DataTable Error (' . static::class . ') [' . get_class($e) . ']: ' . $e->getMessage());
            return $this->errorResponse('Error processing request: ' . $e->getMessage());
        }
    }

    /**
     * Hook for subclasses to pre-fetch related data for all rows
     * Override this method to fetch data in bulk instead of per-row
     * Store data in $this->relatedDataCache
     * 
     * @param array $rows All rows about to be formatted
     * @param array $requestData Request parameters
     */
    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        // Default: no related data needed. Override in subclass if needed.
    }

    /**
     * Build base query for this module
     */
    protected function buildBaseQuery($requestData)
    {
        return "SELECT * FROM `" . $this->table . "` WHERE id > 0" . $this->getOrgIdWhereClause();
    }

    /**
     * Get WHERE clause fragment for organization_id filtering
     * Returns empty string if table doesn't have organization_id or org_id not set
     * Returns " AND organization_id = {id}" if both conditions met
     */
    protected function getOrgIdWhereClause(): string
    {
        if ($this->organizationId === null) {
            return '';
        }

        // Check if table has organization_id column
        $result = $this->mysqli->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '" . $this->mysqli->real_escape_string($this->table) . "' 
            AND COLUMN_NAME = 'organization_id' 
            LIMIT 1"
        );

        if (!$result || $result->num_rows === 0) {
            return ''; // Table doesn't have org_id column
        }

        return " AND `organization_id` = " . (int)$this->organizationId;
    }

    /**
     * Format single row of data
     */
    abstract protected function formatRow($row, $requestData = []);

    /**
     * Build search WHERE clause
     */
    protected function buildSearchClause($requestData)
    {
        $searchValue = $requestData['search']['value'] ?? '';
        if (empty($searchValue) || empty($this->searchFields)) {
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
     * Build ORDER BY clause
     */
    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? 0);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'ASC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'ASC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }

    /**
     * Build LIMIT clause
     */
    protected function buildLimitClause($requestData, int $start, int $length): string
    {
        return "LIMIT {$length} OFFSET {$start}";
    }

    /**
     * Count total records for a query
     */
    protected function countRecords(string $query): int
    {
        $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as count_table";
        $result = $this->mysqli->query($countQuery);

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Build error response
     */
    protected function errorResponse(string $message): array
    {
        return [
            'draw' => 1,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $message
        ];
    }

    /**
     * Default action buttons for a row.
     * Handlers that need custom buttons should define their own private/protected version.
     * Requires ActionButtonHelper to be loaded.
     *
     * @param int    $id     Record ID
     * @param string $module Module name (e.g. 'geo_cities')
     * @param mixed  ...$extra Extra arguments (ignored by default)
     * @return string HTML string of action buttons
     */
    private function getActionButtons(int $id, string $module, ...$extra): string
    {
        if (!class_exists('ActionButtonHelper')) {
            return '';
        }
        $buttons = [];
        if (method_exists('ActionButtonHelper', 'editButton')) {
            $buttons[] = ActionButtonHelper::editButton($id, $module . '.php', $module, 'Edit', false);
        }
        if (method_exists('ActionButtonHelper', 'deleteButton')) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', $buttons);
    }

    /**
     * Truncate text to a specified length with ellipsis.
     *
     * @param string $text   Input text
     * @param int    $length Maximum length
     * @return string Truncated text with HTML escaping applied
     */
    protected function truncateText(string $text, int $length = 100): string
    {
        $text = strip_tags($text);
        if (mb_strlen($text) <= $length) {
            return htmlspecialchars($text);
        }
        return htmlspecialchars(mb_substr($text, 0, $length)) . '&hellip;';
    }
}
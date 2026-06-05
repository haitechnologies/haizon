<?php
require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';


class Search {
    private $mysqli;
    private $companiesTable = DB::COMPANIES;
    private $categoriesTable = DB::CATEGORIES;
    private $searchesTable = DB::SEARCHES; // Unified table
    private $engagementTable = DB::COMPANY_ENGAGEMENT;

    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }

    private function normalizeTextValue($value): string {
        return function_exists('display_text') ? display_text($value) : html_entity_decode((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function normalizeCompanyRows(array $rows): array {
        foreach ($rows as $index => $row) {
            foreach (['company_name', 'display_name', 'category_name', 'city', 'state', 'location', 'company_profile', 'services', 'meta_keywords'] as $field) {
                if (array_key_exists($field, $row) && is_scalar($row[$field])) {
                    $rows[$index][$field] = $this->normalizeTextValue($row[$field]);
                }
            }
        }

        return $rows;
    }

    private function hasColumn($table, $column) {
        $sql = "SELECT COUNT(*) AS cnt
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ((int)($row['cnt'] ?? 0)) > 0;
    }

    public function advancedSearch(array $filters = [], $limit = 12, $offset = 0) {
        $hasEngagement = true;
        $hasAvgRating = $this->hasColumn($this->engagementTable, 'avg_rating');
        $hasReviewCount = $this->hasColumn($this->engagementTable, 'review_count');
        if (!$hasAvgRating && !$hasReviewCount) {
            $hasEngagement = false;
        }

        $query = "SELECT c.id, c.company_name, c.slug, c.city, c.state, c.location, c.telephone AS phone,
                         c.email, c.website, c.verified, c.primary_category_id,
                         c.company_profile, c.services, c.meta_keywords,
                 IFNULL(cat.name, 'Business') AS category_name";

        if ($hasEngagement) {
            $query .= ", " . ($hasAvgRating ? "COALESCE(ce.avg_rating,0)" : "0") . " AS avg_rating,
                       " . ($hasReviewCount ? "COALESCE(ce.review_count,0)" : "0") . " AS review_count";
        } else {
            $query .= ", 0 AS avg_rating, 0 AS review_count";
        }

        $query .= " FROM `{$this->companiesTable}` c
                    LEFT JOIN `{$this->categoriesTable}` cat ON cat.id = c.primary_category_id";

        if ($hasEngagement) {
            $query .= " LEFT JOIN `{$this->engagementTable}` ce ON ce.company_id = c.id";
        }

        $where = ["c.publish = 1"];
        $params = [];
        $types = '';

        $keyword = trim((string)($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $where[] = "(c.company_name LIKE ? OR c.company_profile LIKE ? OR c.services LIKE ? OR c.meta_keywords LIKE ? OR c.website LIKE ?)";
            $searchTerm = '%' . $keyword . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'sssss';
        }

        $categoryId = (int)($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where[] = "c.primary_category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        $emirate = trim((string)($filters['emirate'] ?? ''));
        if ($emirate !== '') {
            $where[] = "(LOWER(c.state) = LOWER(?) OR LOWER(c.city) LIKE LOWER(?) OR LOWER(c.location) LIKE LOWER(?))";
            $params[] = $emirate;
            $params[] = '%' . $emirate . '%';
            $params[] = '%' . $emirate . '%';
            $types .= 'sss';
        }

        $verifiedOnly = !empty($filters['verified_only']);
        if ($verifiedOnly) {
            $where[] = "c.verified = 1";
        }

        $minRating = (float)($filters['min_rating'] ?? 0);
        if ($hasEngagement && $hasAvgRating && $minRating > 0) {
            $where[] = "ce.avg_rating >= ?";
            $params[] = $minRating;
            $types .= 'd';
        }

        $query .= " WHERE " . implode(' AND ', $where);

        $sortBy = (string)($filters['sort_by'] ?? 'recommended');
        if ($sortBy === 'name') {
            $query .= " ORDER BY c.company_name ASC";
        } elseif ($sortBy === 'newest') {
            $query .= " ORDER BY c.id DESC";
        } elseif ($sortBy === 'rating' && $hasEngagement && $hasAvgRating) {
            $query .= " ORDER BY ce.avg_rating DESC, c.verified DESC, c.id DESC";
        } else {
            $query .= " ORDER BY c.verified DESC, c.id DESC";
        }

        $query .= " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        $types .= 'ii';

        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) {
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $this->normalizeCompanyRows($rows);
    }

    public function countAdvancedSearch(array $filters = []) {
        $query = "SELECT COUNT(*) AS total FROM `{$this->companiesTable}` c WHERE c.publish = 1";
        $params = [];
        $types = '';

        $keyword = trim((string)($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query .= " AND (c.company_name LIKE ? OR c.company_profile LIKE ? OR c.services LIKE ? OR c.meta_keywords LIKE ? OR c.website LIKE ?)";
            $searchTerm = '%' . $keyword . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'sssss';
        }

        $categoryId = (int)($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $query .= " AND c.primary_category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        $emirate = trim((string)($filters['emirate'] ?? ''));
        if ($emirate !== '') {
            $query .= " AND (LOWER(c.state) = LOWER(?) OR LOWER(c.city) LIKE LOWER(?) OR LOWER(c.location) LIKE LOWER(?))";
            $params[] = $emirate;
            $params[] = '%' . $emirate . '%';
            $params[] = '%' . $emirate . '%';
            $types .= 'sss';
        }

        if (!empty($filters['verified_only'])) {
            $query .= " AND c.verified = 1";
        }

        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) {
            return 0;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['total'] ?? 0);
    }


    public function saveSearch($userId, array $filters = [], $alertEnabled = 0) {
        $normalizedFilters = [
            'keyword' => trim((string)($filters['keyword'] ?? '')),
            'category_id' => (int)($filters['category_id'] ?? 0),
            'emirate' => trim((string)($filters['emirate'] ?? '')),
            'min_rating' => (float)($filters['min_rating'] ?? 0),
            'verified_only' => !empty($filters['verified_only']) ? 1 : 0,
            'sort_by' => (string)($filters['sort_by'] ?? 'recommended')
        ];

        $searchQuery = trim((string)($filters['search_query'] ?? ''));
        if ($searchQuery === '') {
            $searchQuery = $this->buildSearchQueryLabel($normalizedFilters);
        }

        $filtersJson = json_encode($normalizedFilters);
        if ($filtersJson === false) {
            return false;
        }

        // Check for existing saved search
        $checkSql = "SELECT id FROM `{$this->searchesTable}` WHERE user_id = ? AND search_query = ? AND search_filters = ? AND search_type = 'saved' LIMIT 1";
        $checkStmt = $this->mysqli->prepare($checkSql);
        if ($checkStmt) {
            $checkStmt->bind_param('iss', $userId, $searchQuery, $filtersJson);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            if ($existing) {
                return true;
            }
        }

        $sql = "INSERT INTO `{$this->searchesTable}` (user_id, search_query, search_filters, alert_enabled, search_type, created_at)
                VALUES (?, ?, ?, ?, 'saved', NOW())";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('issi', $userId, $searchQuery, $filtersJson, $alertEnabled);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }


    public function logSearchHistory($userId, array $filters = [], $resultCount = 0) {
        $normalizedFilters = [
            'keyword' => trim((string)($filters['keyword'] ?? '')),
            'category_id' => (int)($filters['category_id'] ?? 0),
            'emirate' => trim((string)($filters['emirate'] ?? '')),
            'min_rating' => (float)($filters['min_rating'] ?? 0),
            'verified_only' => !empty($filters['verified_only']) ? 1 : 0,
            'sort_by' => (string)($filters['sort_by'] ?? 'recommended')
        ];

        $searchQuery = trim((string)($filters['search_query'] ?? ''));
        if ($searchQuery === '') {
            $searchQuery = $this->buildSearchQueryLabel($normalizedFilters);
        }

        $filtersJson = json_encode($normalizedFilters);
        if ($filtersJson === false) {
            return false;
        }

        $sql = "INSERT INTO `{$this->searchesTable}` (user_id, search_query, search_filters, result_count, search_type, created_at)
                VALUES (?, ?, ?, ?, 'manual', NOW())";

        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $resultCount = max(0, (int)$resultCount);
        $stmt->bind_param('issi', $userId, $searchQuery, $filtersJson, $resultCount);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    private function buildSearchQueryLabel(array $filters = []) {
        $keyword = trim((string)($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            return $keyword;
        }

        $emirate = trim((string)($filters['emirate'] ?? ''));
        if ($emirate !== '') {
            return 'Businesses in ' . ucfirst($emirate);
        }

        $categoryId = (int)($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            return 'Category #' . $categoryId;
        }

        return 'All Businesses';
    }
}

<?php
/**
 * Companies Data Access Class
 * 
 * Handles all database operations for company listings on the frontend.
 * Supports filtering by category, search, pagination, and view tracking.
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class Companies {
    
    private $mysqli;
    private $table = DB::COMPANIES;
    private $archiveTable = 'erp_companies_archive_2026_02_unpublished';
    private $hasDetailsTable = null;
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }

    private function hasCompaniesDetailsTable(): bool {
        if ($this->hasDetailsTable !== null) {
            return $this->hasDetailsTable;
        }

        $tableName = DB::COMPANIES_DETAILS;
        $check = $this->mysqli->query("SHOW TABLES LIKE '" . $this->mysqli->real_escape_string($tableName) . "'");
        $this->hasDetailsTable = (bool)($check && $check->num_rows > 0);

        return $this->hasDetailsTable;
    }

    private function normalizeTextValue($value): string {
        $text = (string)($value ?? '');
        if ($text === '') {
            return '';
        }

        if (function_exists('display_text')) {
            return display_text($text);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $text) {
                break;
            }
            $text = $decoded;
        }

        return $text;
    }

    private function normalizeCompanyRecord(?array $company): ?array {
        if (!is_array($company)) {
            return $company;
        }

        foreach (['company_name', 'name', 'display_name', 'description', 'company_profile', 'services', 'meta_description', 'category_name', 'city', 'state', 'emirate', 'address', 'location'] as $field) {
            if (array_key_exists($field, $company) && is_scalar($company[$field])) {
                $company[$field] = $this->normalizeTextValue($company[$field]);
            }
        }

        return $company;
    }

    private function normalizeCompanyRows(array $companies): array {
        foreach ($companies as $index => $company) {
            $companies[$index] = $this->normalizeCompanyRecord($company) ?? $company;
        }

        return $companies;
    }
    
    /**
     * Get all published companies with optional filters
     * 
     * @param array $options Filter options (search, limit, offset, order_by)
     * @return array Array of company records
     */
    public function getAll($options = []) {
        $where = ["publish = 1"];
        $params = [];
        $types = "";
        
        // Search in company name, location, services
        if (!empty($options['search'])) {
            $searchTerm = '%' . $options['search'] . '%';
            $where[] = "(company_name LIKE ? OR location LIKE ? OR city LIKE ? OR services LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= "ssss";
        }
        
        // Filter by verified status
        if (isset($options['verified'])) {
            $where[] = "verified = ?";
            $params[] = $options['verified'];
            $types .= "i";
        }
        
        // Build WHERE clause
        $whereClause = implode(' AND ', $where);
        
        // Order by
        $orderBy = $options['order_by'] ?? 'created_at DESC';
        
        // Pagination
        $limit = $options['limit'] ?? 20;
        $offset = $options['offset'] ?? 0;
        
        $detailsSelect = $this->hasCompaniesDetailsTable()
            ? "hcd.company_profile, hcd.services, hcd.meta_keywords, hcd.meta_description"
            : "NULL AS company_profile, NULL AS services, NULL AS meta_keywords, NULL AS meta_description";
        $detailsJoin = $this->hasCompaniesDetailsTable()
            ? "LEFT JOIN `" . DB::COMPANIES_DETAILS . "` hcd ON hc.id = hcd.company_id"
            : "";

        $sql = "SELECT hc.*, {$detailsSelect}
                FROM `{$this->table}` hc
                {$detailsJoin}
                WHERE {$whereClause} 
                ORDER BY {$orderBy} 
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getAll - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->normalizeCompanyRows($companies);
    }
    
    /**
     * Get total count of published companies with optional filters
     * 
     * @param array $options Filter options (search, verified)
     * @return int Total count
     */
    public function getCount($options = []) {
        $where = ["publish = 1"];
        $params = [];
        $types = "";
        
        // Search filter
        if (!empty($options['search'])) {
            $searchTerm = '%' . $options['search'] . '%';
            $where[] = "(company_name LIKE ? OR location LIKE ? OR city LIKE ? OR services LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= "ssss";
        }

        // Filter by verified status
        if (isset($options['verified'])) {
            $where[] = "verified = ?";
            $params[] = $options['verified'];
            $types .= "i";
        }
        
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` WHERE {$whereClause}";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getCount - Prepare failed: " . $this->mysqli->error);
            return 0;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total'] ?? 0);
    }
    
    /**
     * Get a single company by slug
     * 
     * @param string $slug Company slug
     * @param bool $incrementViews Whether to increment view count
     * @return array|null Company record or null if not found
     */
    public function getBySlug($slug, $incrementViews = true) {
        $detailsSelect = $this->hasCompaniesDetailsTable()
            ? "hcd.company_profile, hcd.services, hcd.meta_keywords, hcd.meta_description"
            : "NULL AS company_profile, NULL AS services, NULL AS meta_keywords, NULL AS meta_description";
        $detailsJoin = $this->hasCompaniesDetailsTable()
            ? "LEFT JOIN `" . DB::COMPANIES_DETAILS . "` hcd ON hc.id = hcd.company_id"
            : "";

        $sql = "SELECT hc.*, {$detailsSelect}
            FROM `{$this->table}` hc
            {$detailsJoin}
            WHERE hc.slug = ? AND hc.is_active = 1 AND (hc.publish = 1 OR hc.verified = 1)
            LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getBySlug - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();
        $stmt->close();
        
        // Note: View count tracking removed in Phase 6 optimization
        // The $incrementViews parameter is deprecated and no longer used
        
        return $this->normalizeCompanyRecord($company);
    }
    
    /**
     * Get a single company by ID
     * 
     * @param int $id Company ID
     * @return array|null Company record or null if not found
     */
    public function getById($id) {
        $detailsSelect = $this->hasCompaniesDetailsTable()
            ? "hcd.company_profile, hcd.services, hcd.meta_keywords, hcd.meta_description"
            : "NULL AS company_profile, NULL AS services, NULL AS meta_keywords, NULL AS meta_description";
        $detailsJoin = $this->hasCompaniesDetailsTable()
            ? "LEFT JOIN `" . DB::COMPANIES_DETAILS . "` hcd ON hc.id = hcd.company_id"
            : "";

        $sql = "SELECT hc.*, {$detailsSelect}
            FROM `{$this->table}` hc
            {$detailsJoin}
            WHERE hc.id = ? AND hc.is_active = 1 AND (hc.publish = 1 OR hc.verified = 1)
            LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getById - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();
        $stmt->close();
        
        return $this->normalizeCompanyRecord($company);
    }
    
    /**
     * NOTE: incrementViews method removed in Phase 6 optimization
     * View analytics should be tracked in a separate table if needed.
     * Reasons for removal:
     * - views column was rarely used and caused unnecessary write operations
     * - last_visit was redundant with timestamp columns
     * - Analytics belongs in a normalized separate table for better performance
     */
    // DEPRECATED - Use separate hai_company_engagement table for view tracking if analytics needed

    /**
     * Record a company view in the engagement table
     *
     * @param int $id Company ID
     * @return bool Success status
     */
    public function recordView($id) {
        // Increment views in hai_companies table
        $sql = "UPDATE `" . DB::COMPANIES . "` SET views = views + 1 WHERE id = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::recordView - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Get featured/pinned companies
     * 
     * @param int $limit Maximum number of companies to return
     * @return array Array of company records
     */
    public function getFeatured($limit = 6) {
        // After Phase 6 optimization: pin column removed
        // Returns verified and published companies with featured-first ordering
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 AND verified = 1 ORDER BY partner_verified DESC, created_at DESC LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getFeatured - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->normalizeCompanyRows($companies);
    }

    /**
     * Get featured companies with only homepage-required columns.
     * Reduces payload and improves homepage render time.
     *
     * @param int $limit Maximum number of companies to return
     * @return array Array of company summary records
     */
    public function getFeaturedSummary($limit = 6) {
        $sql = "SELECT id, slug, company_name, city,
                   LEFT(COALESCE(company_profile, services, ''), 220) AS description,
                   partner_verified AS featured,
                   verified
                FROM `{$this->table}`
                WHERE publish = 1 AND verified = 1
                ORDER BY partner_verified DESC, created_at DESC
                LIMIT ?";

        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getFeaturedSummary - Prepare failed: " . $this->mysqli->error);
            return [];
        }

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $this->normalizeCompanyRows($companies);
    }
    
    /**
     * Get recently added companies
     * 
     * @param int $limit Maximum number of companies to return
     * @return array Array of company records
     */
    public function getRecent($limit = 6) {
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getRecent - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->normalizeCompanyRows($companies);
    }
    
    /**
     * Get popular companies (by recency after views removal)
     * 
     * @param int $limit Maximum number of companies to return
     * @return array Array of company records
     */
    public function getMostViewed($limit = 6) {
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getMostViewed - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->normalizeCompanyRows($companies);
    }
    
    /**
     * Search companies by keyword
     * 
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array Array of company records
     */
    public function search($keyword, $limit = 20, $offset = 0) {
        $searchTerm = '%' . $keyword . '%';
        
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE publish = 1 
                AND (company_name LIKE ? OR location LIKE ? OR city LIKE ? OR services LIKE ? OR company_profile LIKE ?)
                ORDER BY 
                    CASE 
                        WHEN company_name LIKE ? THEN 1
                        WHEN location LIKE ? THEN 2
                        ELSE 3
                    END,
                    created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::search - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("sssssssii", 
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm,
            $limit, $offset
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->normalizeCompanyRows($companies);
    }
    
    /**
     * Get total count of search results
     * 
     * @param string $keyword Search keyword
     * @return int Total count
     */
    public function searchCount($keyword) {
        $searchTerm = '%' . $keyword . '%';
        
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` 
                WHERE publish = 1 
                AND (company_name LIKE ? OR location LIKE ? OR city LIKE ? OR services LIKE ? OR company_profile LIKE ?)";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::searchCount - Prepare failed: " . $this->mysqli->error);
            return 0;
        }
        
        $stmt->bind_param("sssss", 
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total'] ?? 0);
    }
    
    /**
     * Get related companies (same category, excluding current company)
     * 
     * @param string $categorySlug Category slug (deprecated - kept for backward compatibility)
     * @param int $excludeId Company ID to exclude
     * @param int $limit Maximum number of companies
     * @return array Array of company records
     */
    public function getRelated($categorySlug, $excludeId, $limit = 4) {
        // Note: categorySlug parameter kept for backward compatibility but no longer used
        // Uses primary_category_id instead
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE publish = 1 
                AND id != ?
                ORDER BY RAND()
                LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getRelated - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("ii", $excludeId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->normalizeCompanyRows($companies);
    }

    /**
     * Get companies in a subcategory
     * 
     * @param array $options Filter options
     * @return array Array of company records
     */
    public function getBySubcategory($options = []) {
        $subcategoryId = $options['subcategory_id'] ?? 0;
        $page = $options['page'] ?? 1;
        $perPage = $options['per_page'] ?? 18;
        $offset = ($page - 1) * $perPage;
        
        $where = ["hc.publish = 1", "hc.primary_subcategory_id = ?"];
        $params = [$subcategoryId];
        $types = "i";

        if (!empty($options['emirate'])) {
            $where[] = "hc.city = ?";
            $params[] = $options['emirate'];
            $types .= "s";
        }
        if (!empty($options['verified'])) {
            $where[] = "hc.verified = 1";
        }

        $whereClause = implode(" AND ", $where);
        
        $orderBy = "hc.created_at DESC";
        if (!empty($options['sort_by'])) {
            switch ($options['sort_by']) {
                case 'name':
                    $orderBy = "hc.company_name ASC";
                    break;
                case 'rating':
                    $orderBy = "hc.verified DESC, hc.company_name ASC";
                    break;
            }
        }

        $detailsSelect = $this->hasCompaniesDetailsTable()
            ? "hcd.company_profile as description, hcd.services"
            : "NULL as description, NULL as services";
        $detailsJoin = $this->hasCompaniesDetailsTable()
            ? "LEFT JOIN `" . DB::COMPANIES_DETAILS . "` hcd ON hc.id = hcd.company_id"
            : "";

        $sql = "SELECT hc.*, {$detailsSelect}
                FROM `{$this->table}` hc
                {$detailsJoin}
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getBySubcategory - Prepare failed: " . $this->mysqli->error);
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $this->normalizeCompanyRows($companies);
    }

    /**
     * Get statistics for a subcategory
     * 
     * @param int $subcategoryId Subcategory ID
     * @return array Statistics
     */
    public function getSubcategoryStats($subcategoryId) {
        $sql = "SELECT COUNT(*) as total 
                FROM `{$this->table}` 
                WHERE primary_subcategory_id = ? AND publish = 1";
                
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Companies::getSubcategoryStats - Prepare failed: " . $this->mysqli->error);
            return ['total' => 0];
        }

        $stmt->bind_param("i", $subcategoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();

        return $stats ?: ['total' => 0];
    }
}

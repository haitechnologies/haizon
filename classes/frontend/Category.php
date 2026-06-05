<?php
/**
 * Category Page Data Access Class
 * 
 * Handles all database operations for category landing pages.
 * Provides companies in category, category info, and statistics.
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class Category {
    
    private $mysqli;
    private $categoriesTable = DB::CATEGORIES;
    private $companiesTable = DB::COMPANIES;
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }

    private function normalizeTextValue($value): string {
        return function_exists('display_text') ? display_text($value) : html_entity_decode((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function normalizeCompanyRows(array $companies): array {
        foreach ($companies as $index => $company) {
            foreach (['company_name', 'description', 'email', 'website', 'emirate', 'city'] as $field) {
                if (array_key_exists($field, $company) && is_scalar($company[$field])) {
                    $companies[$index][$field] = $this->normalizeTextValue($company[$field]);
                }
            }
        }

        return $companies;
    }
    
    /**
     * Get category by slug
     * 
     * @param string $slug Category slug
     * @return array|null Category record or null if not found
     */
    public function getCategoryBySlug($slug) {
        $sql = "SELECT * FROM `{$this->categoriesTable}` 
                WHERE slug = ? AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Category::getCategoryBySlug - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        $stmt->close();
        
        return $category;
    }
    
    /**
     * Get category by ID
     * 
     * @param int|string $id Category ID
     * @return array|null Category record or null if not found
     */
    public function getCategoryById($id) {
        $sql = "SELECT * FROM `{$this->categoriesTable}` 
                WHERE id = ? AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Category::getCategoryById - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        $stmt->close();
        
        return $category;
    }
    
    /**
     * Get companies in category with pagination and filters
     * 
     * @param int $categoryId Category ID
     * @param int $page Page number (starts at 1)
     * @param int $perPage Items per page (default 18)
     * @param array $filters Filter options (emirate, min_rating, verified, featured, sort_by)
     * @return array Array of company records
     */
    public function getCompaniesInCategory($categoryId, $page = 1, $perPage = 18, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE conditions
        $conditions = ["c.primary_category_id = ?", "c.publish = 1"];
        $params = [$categoryId];
        $paramTypes = "i";
        
        // Apply filters
        if (!empty($filters['emirate'])) {
            $conditions[] = "c.city = ?";
            $params[] = $filters['emirate'];
            $paramTypes .= "s";
        }
        
        if (!empty($filters['verified'])) {
            $conditions[] = "c.verified = 1";
        }
        
        // Note: featured and min_rating filters would need additional table columns
        // For now, we'll prepare the SQL structure
        
        $whereClause = implode(" AND ", $conditions);
        
        // Build ORDER BY clause
        $orderBy = "c.verified DESC, c.company_name ASC"; // Default
        
        if (!empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'name':
                    $orderBy = "c.company_name ASC";
                    break;
                case 'newest':
                    $orderBy = "c.id DESC";
                    break;
                case 'rating':
                    // Would need rating data from reviews table
                    $orderBy = "c.verified DESC, c.company_name ASC";
                    break;
                default: // relevance
                    $orderBy = "c.verified DESC, c.company_name ASC";
                    break;
            }
        }
        
        $sql = "SELECT 
                    c.id, c.company_name, c.slug, c.email, c.telephone AS phone, c.website,
                    c.verified, 0 AS featured, c.city AS emirate, c.company_profile AS description,
                    0 AS avg_rating, 0 AS review_count, c.views AS profile_views
                FROM `{$this->companiesTable}` c
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Category::getCompaniesInCategory - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        // Add limit and offset to params
        $params[] = $perPage;
        $params[] = $offset;
        $paramTypes .= "ii";
        
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $companies = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->normalizeCompanyRows($companies);
    }
    
    /**
     * Get category statistics with verified count
     * 
     * @param int $categoryId Category ID
     * @return array Statistics (total_companies, avg_rating, verified_count)
     */
    public function getCategoryStats($categoryId) {
        $sql = "SELECT 
                    COUNT(DISTINCT c.id) as total_companies,
                    SUM(CASE WHEN c.verified = 1 THEN 1 ELSE 0 END) as verified_count,
                    0 as avg_rating
                FROM `{$this->companiesTable}` c
                WHERE c.primary_category_id = ? AND c.publish = 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Category::getCategoryStats - Prepare failed: " . $this->mysqli->error);
            return ['total_companies' => 0, 'avg_rating' => 0, 'verified_count' => 0];
        }
        
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats ?: ['total_companies' => 0, 'avg_rating' => 0, 'verified_count' => 0];
    }
    
    /**
     * Get total pages for category with filters
     * 
     * @param int $categoryId Category ID
     * @param int $perPage Items per page (default 18)
     * @param array $filters Filter options
     * @return int Total number of pages
     */
    public function getTotalPages($categoryId, $perPage = 18, $filters = []) {
        // Build WHERE conditions
        $conditions = ["primary_category_id = ?", "publish = 1"];
        $params = [$categoryId];
        $paramTypes = "i";
        
        // Apply filters
        if (!empty($filters['emirate'])) {
            $conditions[] = "city = ?";
            $params[] = $filters['emirate'];
            $paramTypes .= "s";
        }
        
        if (!empty($filters['verified'])) {
            $conditions[] = "verified = 1";
        }
        
        $whereClause = implode(" AND ", $conditions);
        
        $sql = "SELECT COUNT(*) as total FROM `{$this->companiesTable}`
                WHERE {$whereClause}";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Category::getTotalPages - Prepare failed: " . $this->mysqli->error);
            return 1;
        }
        
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $total = $row['total'] ?? 0;
        return max(1, ceil($total / $perPage));
    }
}
?>

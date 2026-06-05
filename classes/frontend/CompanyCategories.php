<?php
/**
 * Company Categories Data Access Class (UPDATED FOR NEW SYSTEM)
 * 
 * Handles all database operations for company categories on the frontend.
 * NOW USES the new hai_categories system instead of legacy tables.
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class CompanyCategories {
    
    private $mysqli;
    private $table = DB::CATEGORIES;  // Use new table instead of legacy
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }
    
    /**
     * Get all published categories
     * 
     * @param array $options Filter options (order_by)
     * @return array Array of category records
     */
    public function getAll($options = []) {
        $where = ["publish = 1"];
        $params = [];
        $types = "";
        
        $whereClause = implode(' AND ', $where);
        $orderBy = $options['order_by'] ?? 'name ASC';
        
        $sql = "SELECT * FROM `{$this->table}` WHERE {$whereClause} ORDER BY {$orderBy}";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("CompanyCategories::getAll - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $categories;
    }
    
    /**
     * Get a single category by slug
     * 
     * @param string $slug Category slug
     * @return array|null Category record or null if not found
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM `{$this->table}` WHERE slug = ? AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("CompanyCategories::getBySlug - Prepare failed: " . $this->mysqli->error);
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
     * Get a single category by ID
     * 
     * @param int $id Category ID
     * @return array|null Category record or null if not found
     */
    public function getById($id) {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = ? AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("CompanyCategories::getById - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        $stmt->close();
        
        return $category;
    }
    
    /**
     * Get categories with company counts
     * 
     * @param int $limit Maximum number of categories
     * @return array Array of category records
     */
    public function getPopular($limit = 10) {
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 AND total_companies > 0 ORDER BY total_companies DESC LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("CompanyCategories::getPopular - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $categories;
    }

    /**
     * Get main categories for homepage display
     *
     * @param int $limit Maximum number of categories
     * @return array Array of category records
     */
    public function getMainCategories($limit = 8) {
        return $this->getPopular($limit);
    }
}

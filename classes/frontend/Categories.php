<?php
/**
 * Categories Model - Level 1 (Main Categories)
 * 
 * Handles operations for the main category level (33 total categories).
 * Part of the 3-level hierarchical category system.
 * 
 * Hierarchy:
 * Categories (33) → Subcategories (111) → Category Items (3,344)
 * 
 * @package Classes\Frontend
 * @version 1.0.0
 */

class Categories {
    
    private $conn;
    
    /**
     * Initialize with database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all main categories
     * 
     * @param array $options {
     *     @type bool   $published_only  Only return published categories (default: true)
     *     @type bool   $with_counts     Include company counts (default: true)
     *     @type bool   $with_subcats    Include subcategory count (default: false)
     *     @type string $order_by        SQL ORDER BY clause (default: 'sort_order ASC')
     *     @type int    $limit           Maximum results (default: 100)
     *     @type int    $offset          Offset for pagination (default: 0)
     * }
     * @return array Array of category records
     */
    public function getAll($options = []) {
        $publishedOnly = $options['published_only'] ?? true;
        $withCounts = $options['with_counts'] ?? true;
        $withSubcats = $options['with_subcats'] ?? false;
        $orderBy = $options['order_by'] ?? 'sort_order ASC';
        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;
        
        $query = "SELECT * FROM " . DB::CATEGORIES;
        
        $where = [];
        if ($publishedOnly) {
            $where[] = "publish = 1";
        }
        
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        $query .= " ORDER BY {$orderBy} LIMIT {$limit} OFFSET {$offset}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
    
    /**
     * Get single category by ID
     * 
     * @param string $categoryId Category ID (e.g., 'CAT001')
     * @param bool $withSubcategories Include subcategories
     * @return array|null Category record or null if not found
     */
    public function getById($categoryId) {
        $categoryId = intval($categoryId);
        
        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE id = {$categoryId} 
                  AND publish = 1 
                  LIMIT 1";
        
        $result = $this->conn->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Get category by slug (for SEO-friendly URLs)
     * 
     * @param string $slug Category slug (e.g., 'automotive-vehicles')
     * @return array|null Category record or null if not found
     */
    public function getBySlug($slug) {
        $slug = $this->conn->real_escape_string($slug);
        
        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE slug = '{$slug}' 
                  AND publish = 1 
                  LIMIT 1";
        
        $result = $this->conn->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Get category with all subcategories
     * 
     * @param string $categoryId Category ID
     * @return array|null Category with 'subcategories' array
     */
    public function getWithSubcategories($categoryId) {
        $category = $this->getById($categoryId);
        
        if (!$category) {
            return null;
        }
        
        // Get subcategories
        $subcatQuery = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                        WHERE category_id = '{$categoryId}' 
                        AND publish = 1 
                        ORDER BY sort_order ASC";
        
        $result = $this->conn->query($subcatQuery);
        $subcategories = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $subcategories[] = $row;
            }
        }
        
        $category['subcategories'] = $subcategories;
        return $category;
    }
    
    /**
     * Get category with full hierarchy (subcats + items)
     * 
     * @param string $categoryId Category ID
     * @return array|null Category with full nested structure
     */
    public function getWithFullHierarchy($categoryId) {
        $category = $this->getById($categoryId);
        
        if (!$category) {
            return null;
        }
        
        // Get subcategories
        $subcatQuery = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                        WHERE category_id = '{$categoryId}' 
                        AND publish = 1 
                        ORDER BY sort_order ASC";
        
        $result = $this->conn->query($subcatQuery);
        $subcategories = [];
        
        if ($result) {
            while ($subcat = $result->fetch_assoc()) {
                // Get items for this subcategory
                $itemsQuery = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                               WHERE subcategory_id = '{$subcat['subcategory_id']}' 
                               AND publish = 1 
                               ORDER BY sort_order ASC";
                
                $itemsResult = $this->conn->query($itemsQuery);
                $items = [];
                
                if ($itemsResult) {
                    while ($item = $itemsResult->fetch_assoc()) {
                        $items[] = $item;
                    }
                }
                
                $subcat['items'] = $items;
                $subcategories[] = $subcat;
            }
        }
        
        $category['subcategories'] = $subcategories;
        return $category;
    }
    
    /**
     * Get categories with most companies (popular categories)
     * 
     * @param int $limit Maximum results
     * @return array Top categories by company count
     */
    public function getPopular($limit = 10) {
        $limit = (int)$limit;
        
        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE publish = 1 
                  AND total_companies > 0 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
    
    /**
     * Get featured categories
     * 
     * @param int $limit Maximum results
     * @return array Featured categories (if featured column exists)
     */
    public function getFeatured($limit = 6) {
        $limit = (int)$limit;
        
        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE publish = 1 
                  AND featured = 1 
                  ORDER BY sort_order ASC 
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
    
    /**
     * Get companies in a category
     * 
     * @param string $categoryId Category ID
     * @param array $options {
     *     @type int $limit  Maximum results
     *     @type int $offset Offset for pagination
     * }
     * @return array Array of company records
     */
    public function getCompanies($categoryId, $options = []) {
        $categoryId = (int)$categoryId;
        $limit = (int)($options['limit'] ?? 12);
        $offset = (int)($options['offset'] ?? 0);
        
        $query = "SELECT comp.* 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_category_id = {$categoryId} 
                  AND comp.publish = 1 
                  ORDER BY comp.created_at DESC 
                  LIMIT {$limit} OFFSET {$offset}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $companies = [];
        while ($row = $result->fetch_assoc()) {
            $companies[] = $row;
        }
        
        return $companies;
    }
    
    /**
     * Get total company count in category
     * 
     * @param string $categoryId Category ID
     * @return int Company count
     */
    public function getCompanyCount($categoryId) {
        $categoryId = (int)$categoryId;
        
        $query = "SELECT COUNT(*) as total 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_category_id = {$categoryId} 
                  AND comp.publish = 1";
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    /**
     * Get total category count
     * 
     * @param bool $publishedOnly Only count published
     * @return int Total categories
     */
    public function getCount($publishedOnly = true) {
        $query = "SELECT COUNT(*) as total FROM " . DB::CATEGORIES;
        
        if ($publishedOnly) {
            $query .= " WHERE publish = 1";
        }
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    /**
     * Search categories by name
     * 
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @return array Matching categories
     */
    public function search($keyword, $limit = 20) {
        $keyword = $this->conn->real_escape_string($keyword);
        $limit = (int)$limit;
        
        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE publish = 1 
                  AND (name LIKE '%{$keyword}%' OR name_ar LIKE '%{$keyword}%') 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
    
    /**
     * Get breadcrumb trail for category
     * 
     * @param string $categoryId Category ID
     * @return array Breadcrumb array
     */
    public function getBreadcrumb($categoryId) {
        $category = $this->getById($categoryId);
        
        if (!$category) {
            return [];
        }
        
        return [
            [
                'name' => 'Home',
                'url' => '/',
                'current' => false
            ],
            [
                'name' => 'Categories',
                'url' => '/categories',
                'current' => false
            ],
            [
                'name' => $category['name'],
                'url' => '/category/' . $category['slug'],
                'current' => true
            ]
        ];
    }
}

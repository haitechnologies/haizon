<?php
/**
 * Subcategories Model - Level 2 (Subcategories under main categories)
 * 
 * Handles operations for subcategories (111 total).
 * Part of the 3-level hierarchical category system.
 * 
 * Hierarchy:
 * Categories (33) → **Subcategories (111)** → Category Items (3,344)
 * 
 * @package Classes\Frontend
 * @version 1.0.0
 */

class Subcategories {
    
    private $conn;
    
    /**
     * Initialize with database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function normalizeTextValue($value): string {
        return function_exists('display_text') ? display_text($value) : html_entity_decode((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function normalizeCompanyRows(array $companies): array {
        foreach ($companies as $index => $company) {
            foreach (['company_name', 'description', 'emirate', 'city', 'website', 'email'] as $field) {
                if (array_key_exists($field, $company) && is_scalar($company[$field])) {
                    $companies[$index][$field] = $this->normalizeTextValue($company[$field]);
                }
            }
        }

        return $companies;
    }
    
    /**
     * Get all subcategories
     * 
     * @param array $options {
     *     @type string $category_id    Filter by parent category ID
     *     @type bool   $published_only Only return published (default: true)
     *     @type string $order_by       SQL ORDER BY (default: 'sort_order ASC')
     *     @type int    $limit          Maximum results (default: 200)
     *     @type int    $offset         Offset for pagination (default: 0)
     * }
     * @return array Array of subcategory records
     */
    public function getAll($options = []) {
        $categoryId = $options['category_id'] ?? null;
        $publishedOnly = $options['published_only'] ?? true;
        $orderBy = $options['order_by'] ?? 'sort_order ASC';
        $limit = $options['limit'] ?? 200;
        $offset = $options['offset'] ?? 0;
        
        $query = "SELECT * FROM " . DB::SUBCATEGORIES;
        
        $where = [];
        if ($categoryId) {
            $categoryId = $this->conn->real_escape_string($categoryId);
            $where[] = "category_id = '{$categoryId}'";
        }
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
        
        $subcategories = [];
        while ($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
        
        return $subcategories;
    }
    
    /**
     * Get subcategories by parent category ID
     * 
     * @param string $categoryId Parent category ID
     * @return array Subcategories in this category
     */
    public function getByCategory($categoryId) {
        $categoryId = $this->conn->real_escape_string($categoryId);
        
        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE category_id = '{$categoryId}' 
                  AND publish = 1 
                  ORDER BY sort_order ASC";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $subcategories = [];
        while ($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
        
        return $subcategories;
    }
    
    /**
     * Get single subcategory by ID
     * 
     * @param string $subcategoryId Subcategory ID (e.g., 'CAT001-001')
     * @return array|null Subcategory record or null if not found
     */
    public function getById($subcategoryId) {
        $subcategoryId = $this->conn->real_escape_string($subcategoryId);
        
        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE subcategory_id = '{$subcategoryId}' 
                  AND publish = 1 
                  LIMIT 1";
        
        $result = $this->conn->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Get subcategory by slug
     * 
     * @param string $slug Subcategory slug
     * @return array|null Subcategory record or null if not found
     */
    public function getBySlug($slug) {
        $slug = $this->conn->real_escape_string($slug);
        
        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE slug = '{$slug}' 
                  AND publish = 1 
                  LIMIT 1";
        
        $result = $this->conn->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Get subcategory with all items
     * 
     * @param string $subcategoryId Subcategory ID
     * @return array|null Subcategory with 'items' array
     */
    public function getWithItems($subcategoryId) {
        $subcategory = $this->getById($subcategoryId);
        
        if (!$subcategory) {
            return null;
        }
        
        // Get items
        $itemsQuery = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                       WHERE subcategory_id = '{$subcategoryId}' 
                       AND publish = 1 
                       ORDER BY sort_order ASC";
        
        $result = $this->conn->query($itemsQuery);
        $items = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        $subcategory['items'] = $items;
        return $subcategory;
    }
    
    /**
     * Get companies in a subcategory
     * 
     * @param string $subcategoryId Subcategory ID
     * @param array $options Options array
     * @return array Array of company records
     */
    public function getCompanies($subcategoryId, $options = []) {
        $subcategoryId = (int)$subcategoryId;
        $limit = (int)($options['limit'] ?? 12);
        $offset = (int)($options['offset'] ?? 0);
        
        $query = "SELECT comp.* 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_subcategory_id = {$subcategoryId} 
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
        
        return $this->normalizeCompanyRows($companies);
    }
    
    /**
     * Get total company count in subcategory
     * 
     * @param string $subcategoryId Subcategory ID
     * @return int Company count
     */
    public function getCompanyCount($subcategoryId) {
        $subcategoryId = (int)$subcategoryId;
        
        $query = "SELECT COUNT(*) as total 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_subcategory_id = {$subcategoryId} 
                  AND comp.publish = 1";
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    /**
     * Get popular subcategories (most companies)
     * 
     * @param int $limit Maximum results
     * @return array Popular subcategories
     */
    public function getPopular($limit = 10) {
        $limit = (int)$limit;
        
        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE publish = 1 
                  AND total_companies > 0 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $subcategories = [];
        while ($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
        
        return $subcategories;
    }
    
    /**
     * Search subcategories by name
     * 
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @return array Matching subcategories
     */
    public function search($keyword, $limit = 20) {
        $keyword = $this->conn->real_escape_string($keyword);
        $limit = (int)$limit;
        
        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE publish = 1 
                  AND (name LIKE '%{$keyword}%' OR name_ar LIKE '%{$keyword}%') 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $subcategories = [];
        while ($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
        
        return $subcategories;
    }
    
    /**
     * Get breadcrumb trail for subcategory
     * 
     * @param string $subcategoryId Subcategory ID
     * @return array Breadcrumb array
     */
    public function getBreadcrumb($subcategoryId) {
        $subcat = $this->getById($subcategoryId);
        
        if (!$subcat) {
            return [];
        }
        
        // Get parent category
        $catQuery = "SELECT * FROM " . DB::CATEGORIES . " 
                     WHERE category_id = '{$subcat['category_id']}' 
                     LIMIT 1";
        $catResult = $this->conn->query($catQuery);
        $category = $catResult ? $catResult->fetch_assoc() : null;
        
        $breadcrumb = [
            ['name' => 'Home', 'url' => '/', 'current' => false],
            ['name' => 'Categories', 'url' => '/categories', 'current' => false]
        ];
        
        if ($category) {
            $breadcrumb[] = [
                'name' => $category['name'],
                'url' => '/category/' . $category['slug'],
                'current' => false
            ];
        }
        
        $breadcrumb[] = [
            'name' => $subcat['name'],
            'url' => '/subcategory/' . $subcat['slug'],
            'current' => true
        ];
        
        return $breadcrumb;
    }
}

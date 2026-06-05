<?php
/**
 * Category Items Model - Level 3 (Specific category items)
 * 
 * Handles operations for category items (3,344 total).
 * The most specific level in the hierarchical category system.
 * 
 * Hierarchy:
 * Categories (33) → Subcategories (111) → **Category Items (3,344)**
 * 
 * @package Classes\Frontend
 * @version 1.0.0
 */

class CategoryItems {
    
    private $conn;
    
    /**
     * Initialize with database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all category items
     * 
     * @param array $options {
     *     @type string $category_id     Filter by category ID
     *     @type string $subcategory_id  Filter by subcategory ID
     *     @type bool   $published_only  Only return published (default: true)
     *     @type string $order_by        SQL ORDER BY (default: 'sort_order ASC')
     *     @type int    $limit           Maximum results (default: 500)
     *     @type int    $offset          Offset for pagination (default: 0)
     * }
     * @return array Array of item records
     */
    public function getAll($options = []) {
        $categoryId = $options['category_id'] ?? null;
        $subcategoryId = $options['subcategory_id'] ?? null;
        $publishedOnly = $options['published_only'] ?? true;
        $orderBy = $options['order_by'] ?? 'sort_order ASC';
        $limit = $options['limit'] ?? 500;
        $offset = $options['offset'] ?? 0;
        
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS;
        
        $where = [];
        if ($categoryId) {
            $categoryId = $this->conn->real_escape_string($categoryId);
            $where[] = "category_id = '{$categoryId}'";
        }
        if ($subcategoryId) {
            $subcategoryId = $this->conn->real_escape_string($subcategoryId);
            $where[] = "subcategory_id = '{$subcategoryId}'";
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
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    /**
     * Get items by subcategory ID
     * 
     * @param string $subcategoryId Subcategory ID
     * @return array Items in this subcategory
     */
    public function getBySubcategory($subcategoryId) {
        $subcategoryId = $this->conn->real_escape_string($subcategoryId);
        
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE subcategory_id = '{$subcategoryId}' 
                  AND publish = 1 
                  ORDER BY sort_order ASC";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    /**
     * Get items by category ID (all items in category)
     * 
     * @param string $categoryId Category ID
     * @return array All items in this category (across all subcategories)
     */
    public function getByCategory($categoryId) {
        $categoryId = $this->conn->real_escape_string($categoryId);
        
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE category_id = '{$categoryId}' 
                  AND publish = 1 
                  ORDER BY sort_order ASC";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    /**
     * Get single item by ID
     * 
     * @param string $itemId Item ID (e.g., 'CAT001-001-001')
     * @return array|null Item record or null if not found
     */
    public function getById($itemId) {
        $itemId = intval($itemId);
        
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE id = {$itemId} 
                  AND publish = 1 
                  LIMIT 1";
        
        $result = $this->conn->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Get item by slug
     * 
     * @param string $slug Item slug
     * @return array|null Item record or null if not found
     */
    public function getBySlug($slug) {
        $slug = $this->conn->real_escape_string($slug);
        
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE slug = '{$slug}' 
                  AND publish = 1
                  ORDER BY total_companies DESC, id DESC
                  LIMIT 1";
        
        $result = $this->conn->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Get item with full parent hierarchy
     * 
     * @param string $itemId Item ID
     * @return array|null Item with 'subcategory' and 'category' data
     */
    public function getWithHierarchy($itemId) {
        $item = $this->getById($itemId);
        
        if (!$item) {
            return null;
        }
        
        // Get subcategory
        $subcatQuery = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                        WHERE id = " . intval($item['subcategory_id']) . " 
                        LIMIT 1";
        $subcatResult = $this->conn->query($subcatQuery);
        $item['subcategory'] = $subcatResult ? $subcatResult->fetch_assoc() : null;
        
        // Get category
        $catQuery = "SELECT * FROM " . DB::CATEGORIES . " 
                     WHERE id = " . intval($item['category_id']) . " 
                     LIMIT 1";
        $catResult = $this->conn->query($catQuery);
        $item['category'] = $catResult ? $catResult->fetch_assoc() : null;
        
        return $item;
    }
    
    /**
     * Get companies in a category item
     * 
     * @param string $itemId Item ID
     * @param array $options Options array
     * @return array Array of company records
     */
    public function getCompanies($itemId, $options = []) {
        $itemId = (int)$itemId;
        $perPage = (int)($options['per_page'] ?? ($options['limit'] ?? 12));
        $perPage = $perPage > 0 ? $perPage : 12;

        $page = (int)($options['page'] ?? 1);
        $page = $page > 0 ? $page : 1;

        $offset = array_key_exists('offset', $options)
            ? (int)$options['offset']
            : (($page - 1) * $perPage);

        $publishedOnly = array_key_exists('published', $options) ? (bool)$options['published'] : true;
        $verifiedOnly = !empty($options['verified']);

        $where = ["comp.primary_item_id = {$itemId}"];
        if ($publishedOnly) {
            $where[] = "comp.publish = 1";
        }
        if ($verifiedOnly) {
            $where[] = "comp.verified = 1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT comp.* 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE {$whereClause} 
                  ORDER BY comp.created_at DESC 
                  LIMIT {$perPage} OFFSET {$offset}";
        
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
     * Get total company count in item
     * 
     * @param string $itemId Item ID
     * @return int Company count
     */
    public function getCompanyCount($itemId) {
        $itemId = (int)$itemId;
        
        $query = "SELECT COUNT(*) as total 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_item_id = {$itemId} 
                  AND comp.publish = 1";
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    /**
     * Get popular items (most companies)
     * 
     * @param int $limit Maximum results
     * @return array Popular items
     */
    public function getPopular($limit = 20) {
        $limit = (int)$limit;
        
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE publish = 1 
                  AND total_companies > 0 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    /**
     * Search items by name
     * 
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @return array Matching items
     */
    public function search($keyword, $limit = 50) {
        $keyword = $this->conn->real_escape_string($keyword);
        $limit = (int)$limit;
        
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE publish = 1 
                  AND (name LIKE '%{$keyword}%' OR name_ar LIKE '%{$keyword}%') 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    /**
     * Get autocomplete suggestions
     * 
     * @param string $keyword Partial keyword
     * @param int $limit Maximum suggestions
     * @return array Item suggestions
     */
    public function getAutocomplete($keyword, $limit = 10) {
        $keyword = $this->conn->real_escape_string($keyword);
        $limit = (int)$limit;
        
        $query = "SELECT item_id, name, total_companies 
                  FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE publish = 1 
                  AND name LIKE '{$keyword}%' 
                  ORDER BY total_companies DESC, name ASC 
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        if (!$result) {
            return [];
        }
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => $row['item_id'],
                'label' => $row['name'] . ' (' . number_format($row['total_companies']) . ')',
                'value' => $row['name'],
                'count' => $row['total_companies']
            ];
        }
        
        return $items;
    }
    
    /**
     * Get breadcrumb trail for item
     * 
     * @param string $itemId Item ID
     * @return array Breadcrumb array
     */
    public function getBreadcrumb($itemId) {
        $item = $this->getWithHierarchy($itemId);
        
        if (!$item) {
            return [];
        }
        
        $breadcrumb = [
            ['name' => 'Home', 'url' => '/', 'current' => false],
            ['name' => 'Categories', 'url' => '/categories', 'current' => false]
        ];
        
        if (isset($item['category'])) {
            $breadcrumb[] = [
                'name' => $item['category']['name'],
                'url' => '/category/' . $item['category']['slug'],
                'current' => false
            ];
        }
        
        if (isset($item['subcategory'])) {
            $breadcrumb[] = [
                'name' => $item['subcategory']['name'],
                'url' => '/subcategory/' . $item['subcategory']['slug'],
                'current' => false
            ];
        }
        
        $breadcrumb[] = [
            'name' => $item['name'],
            'url' => '/item/' . $item['slug'],
            'current' => true
        ];
        
        return $breadcrumb;
    }
}

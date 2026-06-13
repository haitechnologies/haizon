<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

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
class CategoryItems
{
    private Database $conn;

    /**
     * Initialize with database connection
     */
    public function __construct(mixed $conn = null)
    {
        if ($conn instanceof Database) {
            $this->conn = $conn;
        } else {
            try {
                $container = Container::getInstance();
                if ($container->has(Database::class)) {
                    $this->conn = $container->get(Database::class);
                } else {
                    $this->conn = new Database();
                }
            } catch (Throwable $e) {
                $this->conn = new Database();
            }
        }
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
    public function getAll($options = [])
    {
        $categoryId = $options['category_id'] ?? null;
        $subcategoryId = $options['subcategory_id'] ?? null;
        $publishedOnly = $options['published_only'] ?? true;
        $orderBy = $options['order_by'] ?? 'sort_order ASC';
        $limit = (int)($options['limit'] ?? 500);
        $offset = (int)($options['offset'] ?? 0);

        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS;
        $where = [];
        $params = [];

        if ($categoryId) {
            $where[] = "category_id = ?";
            $params[] = $categoryId;
        }
        if ($subcategoryId) {
            $where[] = "subcategory_id = ?";
            $params[] = $subcategoryId;
        }
        if ($publishedOnly) {
            $where[] = "is_active = 1";
        }

        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        $query .= " ORDER BY {$orderBy} LIMIT {$limit} OFFSET {$offset}";

        try {
            return $this->conn->fetchAll($query, $params);
        } catch (Throwable $e) {
            error_log("CategoryItems::getAll failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get items by subcategory ID
     *
     * @param string $subcategoryId Subcategory ID
     * @return array Items in this subcategory
     */
    public function getBySubcategory($subcategoryId)
    {
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE subcategory_id = ? 
                  AND is_active = 1 
                  ORDER BY sort_order ASC";

        try {
            return $this->conn->fetchAll($query, [$subcategoryId]);
        } catch (Throwable $e) {
            error_log("CategoryItems::getBySubcategory failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get items by category ID (all items in category)
     *
     * @param string $categoryId Category ID
     * @return array All items in this category (across all subcategories)
     */
    public function getByCategory($categoryId)
    {
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE category_id = ? 
                  AND is_active = 1 
                  ORDER BY sort_order ASC";

        try {
            return $this->conn->fetchAll($query, [$categoryId]);
        } catch (Throwable $e) {
            error_log("CategoryItems::getByCategory failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single item by ID
     *
     * @param string $itemId Item ID (e.g., 'CAT001-001-001')
     * @return array|null Item record or null if not found
     */
    public function getById($itemId)
    {
        $itemId = (int)$itemId;

        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE id = ? 
                  AND is_active = 1 
                  LIMIT 1";

        try {
            return $this->conn->fetchOne($query, [$itemId]);
        } catch (Throwable $e) {
            error_log("CategoryItems::getById failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get item by slug
     *
     * @param string $slug Item slug
     * @return array|null Item record or null if not found
     */
    public function getBySlug($slug)
    {
        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE slug = ? 
                  AND is_active = 1
                  ORDER BY total_companies DESC, id DESC
                  LIMIT 1";

        try {
            return $this->conn->fetchOne($query, [$slug]);
        } catch (Throwable $e) {
            error_log("CategoryItems::getBySlug failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get item with full parent hierarchy
     *
     * @param string $itemId Item ID
     * @return array|null Item with 'subcategory' and 'category' data
     */
    public function getWithHierarchy($itemId)
    {
        $item = $this->getById($itemId);
        if (!$item) {
            return null;
        }

        // Get subcategory
        $subcatQuery = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                        WHERE id = ? 
                        LIMIT 1";
        try {
            $item['subcategory'] = $this->conn->fetchOne($subcatQuery, [(int)$item['subcategory_id']]);
        } catch (Throwable $e) {
            $item['subcategory'] = null;
        }

        // Get category
        $catQuery = "SELECT * FROM " . DB::CATEGORIES . " 
                     WHERE id = ? 
                     LIMIT 1";
        try {
            $item['category'] = $this->conn->fetchOne($catQuery, [(int)$item['category_id']]);
        } catch (Throwable $e) {
            $item['category'] = null;
        }

        return $item;
    }

    /**
     * Get companies in a category item
     *
     * @param string $itemId Item ID
     * @param array $options Options array
     * @return array Array of company records
     */
    public function getCompanies($itemId, $options = [])
    {
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

        $where = ["comp.primary_item_id = ?"];
        $params = [$itemId];

        if ($publishedOnly) {
            $where[] = "comp.is_active = 1";
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

        try {
            return $this->conn->fetchAll($query, $params);
        } catch (Throwable $e) {
            error_log("CategoryItems::getCompanies failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total company count in item
     *
     * @param string $itemId Item ID
     * @return int Company count
     */
    public function getCompanyCount($itemId)
    {
        $itemId = (int)$itemId;

        $query = "SELECT COUNT(*) as total 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_item_id = ? 
                  AND comp.is_active = 1";

        try {
            $row = $this->conn->fetchOne($query, [$itemId]);
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("CategoryItems::getCompanyCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get popular items (most companies)
     *
     * @param int $limit Maximum results
     * @return array Popular items
     */
    public function getPopular($limit = 20)
    {
        $limit = (int)$limit;

        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE is_active = 1 
                  AND total_companies > 0 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";

        try {
            return $this->conn->fetchAll($query);
        } catch (Throwable $e) {
            error_log("CategoryItems::getPopular failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search items by name
     *
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @return array Matching items
     */
    public function search($keyword, $limit = 50)
    {
        $limit = (int)$limit;

        $query = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE is_active = 1 
                  AND (name LIKE ? OR name_ar LIKE ?) 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";

        try {
            $likeWord = '%' . $keyword . '%';
            return $this->conn->fetchAll($query, [$likeWord, $likeWord]);
        } catch (Throwable $e) {
            error_log("CategoryItems::search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get autocomplete suggestions
     *
     * @param string $keyword Partial keyword
     * @param int $limit Maximum suggestions
     * @return array Item suggestions
     */
    public function getAutocomplete($keyword, $limit = 10)
    {
        $limit = (int)$limit;

        $query = "SELECT item_id, name, total_companies 
                  FROM " . DB::CATEGORY_ITEMS . " 
                  WHERE is_active = 1 
                  AND name LIKE ? 
                  ORDER BY total_companies DESC, name ASC 
                  LIMIT {$limit}";

        try {
            $likeWord = $keyword . '%';
            $rows = $this->conn->fetchAll($query, [$likeWord]);
            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'id' => $row['item_id'],
                    'label' => $row['name'] . ' (' . number_format((float)$row['total_companies']) . ')',
                    'value' => $row['name'],
                    'count' => $row['total_companies']
                ];
            }
            return $items;
        } catch (Throwable $e) {
            error_log("CategoryItems::getAutocomplete failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get breadcrumb trail for item
     *
     * @param string $itemId Item ID
     * @return array Breadcrumb array
     */
    public function getBreadcrumb($itemId)
    {
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

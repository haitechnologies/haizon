<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

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
class Categories
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
    public function getAll($options = [])
    {
        $publishedOnly = $options['published_only'] ?? true;
        $orderBy = $options['order_by'] ?? 'sort_order ASC';
        $limit = (int)($options['limit'] ?? 100);
        $offset = (int)($options['offset'] ?? 0);

        $query = "SELECT * FROM " . DB::CATEGORIES;
        $where = [];
        $params = [];

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
            error_log("Categories::getAll failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single category by ID
     *
     * @param string $categoryId Category ID (e.g., 'CAT001')
     * @return array|null Category record or null if not found
     */
    public function getById($categoryId)
    {
        $categoryId = (int)$categoryId;

        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE id = ? 
                  AND is_active = 1 
                  LIMIT 1";

        try {
            return $this->conn->fetchOne($query, [$categoryId]);
        } catch (Throwable $e) {
            error_log("Categories::getById failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get category by slug (for SEO-friendly URLs)
     *
     * @param string $slug Category slug (e.g., 'automotive-vehicles')
     * @return array|null Category record or null if not found
     */
    public function getBySlug($slug)
    {
        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE slug = ? 
                  AND is_active = 1 
                  LIMIT 1";

        try {
            return $this->conn->fetchOne($query, [$slug]);
        } catch (Throwable $e) {
            error_log("Categories::getBySlug failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get category with all subcategories
     *
     * @param string $categoryId Category ID
     * @return array|null Category with 'subcategories' array
     */
    public function getWithSubcategories($categoryId)
    {
        $category = $this->getById($categoryId);
        if (!$category) {
            return null;
        }

        // Get subcategories
        $subcatQuery = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                        WHERE category_id = ? 
                        AND is_active = 1 
                        ORDER BY sort_order ASC";

        try {
            $subcategories = $this->conn->fetchAll($subcatQuery, [$categoryId]);
        } catch (Throwable $e) {
            error_log("Categories::getWithSubcategories failed to load subcategories: " . $e->getMessage());
            $subcategories = [];
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
    public function getWithFullHierarchy($categoryId)
    {
        $category = $this->getById($categoryId);
        if (!$category) {
            return null;
        }

        // Get subcategories
        $subcatQuery = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                        WHERE category_id = ? 
                        AND is_active = 1 
                        ORDER BY sort_order ASC";

        try {
            $subcategories = $this->conn->fetchAll($subcatQuery, [$categoryId]);
            foreach ($subcategories as $idx => $subcat) {
                // Get items for this subcategory
                $itemsQuery = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                               WHERE subcategory_id = ? 
                               AND is_active = 1 
                               ORDER BY sort_order ASC";
                $subcategories[$idx]['items'] = $this->conn->fetchAll($itemsQuery, [$subcat['subcategory_id']]);
            }
        } catch (Throwable $e) {
            error_log("Categories::getWithFullHierarchy failed: " . $e->getMessage());
            $subcategories = [];
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
    public function getPopular($limit = 10)
    {
        $limit = (int)$limit;

        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE is_active = 1 
                  AND total_companies > 0 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";

        try {
            return $this->conn->fetchAll($query);
        } catch (Throwable $e) {
            error_log("Categories::getPopular failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get featured categories
     *
     * @param int $limit Maximum results
     * @return array Featured categories (if featured column exists)
     */
    public function getFeatured($limit = 6)
    {
        $limit = (int)$limit;

        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE is_active = 1 
                  AND featured = 1 
                  ORDER BY sort_order ASC 
                  LIMIT {$limit}";

        try {
            return $this->conn->fetchAll($query);
        } catch (Throwable $e) {
            error_log("Categories::getFeatured failed: " . $e->getMessage());
            return [];
        }
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
    public function getCompanies($categoryId, $options = [])
    {
        $categoryId = (int)$categoryId;
        $limit = (int)($options['limit'] ?? 12);
        $offset = (int)($options['offset'] ?? 0);

        $query = "SELECT comp.* 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_category_id = ? 
                  AND comp.is_active = 1 
                  ORDER BY comp.created_at DESC 
                  LIMIT {$limit} OFFSET {$offset}";

        try {
            return $this->conn->fetchAll($query, [$categoryId]);
        } catch (Throwable $e) {
            error_log("Categories::getCompanies failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total company count in category
     *
     * @param string $categoryId Category ID
     * @return int Company count
     */
    public function getCompanyCount($categoryId)
    {
        $categoryId = (int)$categoryId;

        $query = "SELECT COUNT(*) as total 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_category_id = ? 
                  AND comp.is_active = 1";

        try {
            $row = $this->conn->fetchOne($query, [$categoryId]);
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("Categories::getCompanyCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total category count
     *
     * @param bool $publishedOnly Only count published
     * @return int Total categories
     */
    public function getCount($publishedOnly = true)
    {
        $query = "SELECT COUNT(*) as total FROM " . DB::CATEGORIES;
        $where = [];
        $params = [];

        if ($publishedOnly) {
            $where[] = "is_active = 1";
        }

        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        try {
            $row = $this->conn->fetchOne($query, $params);
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("Categories::getCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Search categories by name
     *
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @return array Matching categories
     */
    public function search($keyword, $limit = 20)
    {
        $limit = (int)$limit;

        $query = "SELECT * FROM " . DB::CATEGORIES . " 
                  WHERE is_active = 1 
                  AND (name LIKE ? OR name_ar LIKE ?) 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";

        try {
            $likeWord = '%' . $keyword . '%';
            return $this->conn->fetchAll($query, [$likeWord, $likeWord]);
        } catch (Throwable $e) {
            error_log("Categories::search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get breadcrumb trail for category
     *
     * @param string $categoryId Category ID
     * @return array Breadcrumb array
     */
    public function getBreadcrumb($categoryId)
    {
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

<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

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
class Subcategories
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

    private function normalizeTextValue($value): string
    {
        return function_exists('display_text') ? display_text($value) : html_entity_decode((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function normalizeCompanyRows(array $companies): array
    {
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
    public function getAll($options = [])
    {
        $categoryId = $options['category_id'] ?? null;
        $publishedOnly = $options['published_only'] ?? true;
        $orderBy = $options['order_by'] ?? 'sort_order ASC';
        $limit = (int)($options['limit'] ?? 200);
        $offset = (int)($options['offset'] ?? 0);

        $query = "SELECT * FROM " . DB::SUBCATEGORIES;
        $where = [];
        $params = [];

        if ($categoryId) {
            $where[] = "category_id = ?";
            $params[] = $categoryId;
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
            error_log("Subcategories::getAll failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get subcategories by parent category ID
     *
     * @param string $categoryId Parent category ID
     * @return array Subcategories in this category
     */
    public function getByCategory($categoryId)
    {
        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE category_id = ? 
                  AND is_active = 1 
                  ORDER BY sort_order ASC";

        try {
            return $this->conn->fetchAll($query, [$categoryId]);
        } catch (Throwable $e) {
            error_log("Subcategories::getByCategory failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single subcategory by ID
     *
     * @param string $subcategoryId Subcategory ID (e.g., 'CAT001-001')
     * @return array|null Subcategory record or null if not found
     */
    public function getById($subcategoryId)
    {
        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE subcategory_id = ? 
                  AND is_active = 1 
                  LIMIT 1";

        try {
            return $this->conn->fetchOne($query, [$subcategoryId]);
        } catch (Throwable $e) {
            error_log("Subcategories::getById failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get subcategory by slug
     *
     * @param string $slug Subcategory slug
     * @return array|null Subcategory record or null if not found
     */
    public function getBySlug($slug)
    {
        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE slug = ? 
                  AND is_active = 1 
                  LIMIT 1";

        try {
            return $this->conn->fetchOne($query, [$slug]);
        } catch (Throwable $e) {
            error_log("Subcategories::getBySlug failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get subcategory with all items
     *
     * @param string $subcategoryId Subcategory ID
     * @return array|null Subcategory with 'items' array
     */
    public function getWithItems($subcategoryId)
    {
        $subcategory = $this->getById($subcategoryId);
        if (!$subcategory) {
            return null;
        }

        // Get items
        $itemsQuery = "SELECT * FROM " . DB::CATEGORY_ITEMS . " 
                       WHERE subcategory_id = ? 
                       AND is_active = 1 
                       ORDER BY sort_order ASC";

        try {
            $items = $this->conn->fetchAll($itemsQuery, [$subcategoryId]);
        } catch (Throwable $e) {
            $items = [];
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
    public function getCompanies($subcategoryId, $options = [])
    {
        $subcategoryId = (int)$subcategoryId;
        $limit = (int)($options['limit'] ?? 12);
        $offset = (int)($options['offset'] ?? 0);

        $query = "SELECT comp.* 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_subcategory_id = ? 
                  AND comp.is_active = 1 
                  ORDER BY comp.created_at DESC 
                  LIMIT {$limit} OFFSET {$offset}";

        try {
            $companies = $this->conn->fetchAll($query, [$subcategoryId]);
            return $this->normalizeCompanyRows($companies);
        } catch (Throwable $e) {
            error_log("Subcategories::getCompanies failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total company count in subcategory
     *
     * @param string $subcategoryId Subcategory ID
     * @return int Company count
     */
    public function getCompanyCount($subcategoryId)
    {
        $subcategoryId = (int)$subcategoryId;

        $query = "SELECT COUNT(*) as total 
                  FROM " . DB::COMPANIES . " comp 
                  WHERE comp.primary_subcategory_id = ? 
                  AND comp.is_active = 1";

        try {
            $row = $this->conn->fetchOne($query, [$subcategoryId]);
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("Subcategories::getCompanyCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get popular subcategories (most companies)
     *
     * @param int $limit Maximum results
     * @return array Popular subcategories
     */
    public function getPopular($limit = 10)
    {
        $limit = (int)$limit;

        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE is_active = 1 
                  AND total_companies > 0 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";

        try {
            return $this->conn->fetchAll($query);
        } catch (Throwable $e) {
            error_log("Subcategories::getPopular failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search subcategories by name
     *
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @return array Matching subcategories
     */
    public function search($keyword, $limit = 20)
    {
        $limit = (int)$limit;

        $query = "SELECT * FROM " . DB::SUBCATEGORIES . " 
                  WHERE is_active = 1 
                  AND (name LIKE ? OR name_ar LIKE ?) 
                  ORDER BY total_companies DESC 
                  LIMIT {$limit}";

        try {
            $likeWord = '%' . $keyword . '%';
            return $this->conn->fetchAll($query, [$likeWord, $likeWord]);
        } catch (Throwable $e) {
            error_log("Subcategories::search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get breadcrumb trail for subcategory
     *
     * @param string $subcategoryId Subcategory ID
     * @return array Breadcrumb array
     */
    public function getBreadcrumb($subcategoryId)
    {
        $subcat = $this->getById($subcategoryId);
        if (!$subcat) {
            return [];
        }

        // Get parent category
        $catQuery = "SELECT * FROM " . DB::CATEGORIES . " 
                     WHERE category_id = ? 
                     LIMIT 1";
        try {
            $category = $this->conn->fetchOne($catQuery, [$subcat['category_id']]);
        } catch (Throwable $e) {
            $category = null;
        }

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

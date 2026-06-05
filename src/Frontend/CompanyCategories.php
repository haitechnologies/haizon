<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

/**
 * Company Categories Data Access Class (UPDATED FOR NEW SYSTEM)
 *
 * Handles all database operations for company categories on the frontend.
 * NOW USES the new hai_categories system instead of legacy tables.
 *
 * @package Classes\Frontend
 */
class CompanyCategories
{
    /** @var Database */
    protected Database $conn;

    private string $table = DB::CATEGORIES;

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
     * Get all published categories
     *
     * @param array $options Filter options (order_by)
     * @return array Array of category records
     */
    public function getAll($options = [])
    {
        $orderBy = $options['order_by'] ?? 'name ASC';

        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 ORDER BY {$orderBy}";

        try {
            return $this->conn->fetchAll($sql);
        } catch (Throwable $e) {
            error_log("CompanyCategories::getAll failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single category by slug
     *
     * @param string $slug Category slug
     * @return array|null Category record or null if not found
     */
    public function getBySlug($slug)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE slug = ? AND publish = 1 LIMIT 1";

        try {
            return $this->conn->fetchOne($sql, [$slug]);
        } catch (Throwable $e) {
            error_log("CompanyCategories::getBySlug failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a single category by ID
     *
     * @param int $id Category ID
     * @return array|null Category record or null if not found
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = ? AND publish = 1 LIMIT 1";

        try {
            return $this->conn->fetchOne($sql, [$id]);
        } catch (Throwable $e) {
            error_log("CompanyCategories::getById failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get categories with company counts
     *
     * @param int $limit Maximum number of categories
     * @return array Array of category records
     */
    public function getPopular($limit = 10)
    {
        $limit = (int)$limit;
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 AND total_companies > 0 ORDER BY total_companies DESC LIMIT {$limit}";

        try {
            return $this->conn->fetchAll($sql);
        } catch (Throwable $e) {
            error_log("CompanyCategories::getPopular failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get main categories for homepage display
     *
     * @param int $limit Maximum number of categories
     * @return array Array of category records
     */
    public function getMainCategories($limit = 8)
    {
        return $this->getPopular($limit);
    }
}

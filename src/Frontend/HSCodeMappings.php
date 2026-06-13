<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

/**
 * HS Code Mappings Manager
 *
 * Handles linking and retrieval of HS codes for categories and subcategories.
 * Provides hierarchical support with subcategory codes overriding category codes.
 *
 * @package Classes/Frontend
 * @author  UAE HS Codes Team
 * @version 1.0.0
 */
class HSCodeMappings
{
    private Database $conn;

    /**
     * Constructor
     *
     * @param mixed $conn Database connection object
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
     * Get HS codes for a company via its category hierarchy
     * Prioritizes subcategory codes over category codes
     *
     * @param int $company_id Company ID
     * @param string $lang Language code (en, ar) - default: en
     * @return array Array of HS code records with descriptions
     */
    public function getForCompany($company_id, $lang = 'en')
    {
        $query = "SELECT primary_category_id FROM " . DB::COMPANIES . " WHERE id = ?";
        try {
            $row = $this->conn->fetchOne($query, [(int)$company_id]);
        } catch (Throwable $e) {
            return [];
        }

        if (!$row) {
            return [];
        }

        $category_id = $row['primary_category_id'];
        if (empty($category_id)) {
            return [];
        }

        // Get subcategory HS codes first (highest priority)
        $subcategory_codes = $this->getForSubcategoryByCategory($category_id, $lang);
        if (!empty($subcategory_codes)) {
            return $subcategory_codes;
        }

        // Fall back to category HS codes
        return $this->getForCategory($category_id, $lang);
    }

    /**
     * Get HS codes for all subcategories within a category
     *
     * @param int $category_id Category ID
     * @param string $lang Language code - default: en
     * @return array Array of HS code records
     */
    public function getForSubcategoryByCategory($category_id, $lang = 'en')
    {
        $category_id = (int)$category_id;

        $query = "SELECT DISTINCT h.*, t.long_desc, t.short_desc, shc.relevance
                  FROM " . DB::HS_CODES . " h
                  LEFT JOIN " . DB::HS_CODE_TEXTS . " t 
                    ON h.id = t.hs_code_id AND t.lang = ?
                  INNER JOIN " . DB::SUBCATEGORY_HS_CODES . " shc 
                    ON h.id = shc.hs_code_id AND shc.mappable_type = 'Subcategory'
                  WHERE shc.mappable_id IN (
                      SELECT id FROM " . DB::SUBCATEGORIES . " 
                      WHERE category_id = ?
                  )
                  ORDER BY shc.relevance ASC, h.code ASC";

        try {
            return $this->conn->fetchAll($query, [$lang, $category_id]);
        } catch (Throwable $e) {
            error_log("HSCodeMappings::getForSubcategoryByCategory failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get HS codes for a specific category
     *
     * @param int $category_id Category ID
     * @param string $lang Language code - default: en
     * @return array Array of HS code records
     */
    public function getForCategory($category_id, $lang = 'en')
    {
        $category_id = (int)$category_id;

        $query = "SELECT h.*, t.long_desc, t.short_desc, chc.relevance
                  FROM " . DB::HS_CODES . " h
                  LEFT JOIN " . DB::HS_CODE_TEXTS . " t 
                    ON h.id = t.hs_code_id AND t.lang = ?
                  INNER JOIN " . DB::CATEGORY_HS_CODES . " chc 
                    ON h.id = chc.hs_code_id AND chc.mappable_type = 'Category'
                  WHERE chc.mappable_id = ?
                  ORDER BY chc.relevance ASC, h.code ASC";

        try {
            return $this->conn->fetchAll($query, [$lang, $category_id]);
        } catch (Throwable $e) {
            error_log("HSCodeMappings::getForCategory failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get HS codes for a specific subcategory
     *
     * @param int $subcategory_id Subcategory ID
     * @param string $lang Language code - default: en
     * @return array Array of HS code records
     */
    public function getForSubcategory($subcategory_id, $lang = 'en')
    {
        $subcategory_id = (int)$subcategory_id;

        $query = "SELECT h.*, t.long_desc, t.short_desc, shc.relevance
                  FROM " . DB::HS_CODES . " h
                  LEFT JOIN " . DB::HS_CODE_TEXTS . " t 
                    ON h.id = t.hs_code_id AND t.lang = ?
                  INNER JOIN " . DB::SUBCATEGORY_HS_CODES . " shc 
                    ON h.id = shc.hs_code_id AND shc.mappable_type = 'Subcategory'
                  WHERE shc.mappable_id = ?
                  ORDER BY shc.relevance ASC, h.code ASC";

        try {
            return $this->conn->fetchAll($query, [$lang, $subcategory_id]);
        } catch (Throwable $e) {
            error_log("HSCodeMappings::getForSubcategory failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Link an HS code to a category
     *
     * @param int $category_id Category ID
     * @param int $hs_code_id HS Code ID
     * @param int $relevance Relevance level (1=Primary, 2=Secondary, 3=Related)
     * @param string $notes Optional notes
     * @return bool Success status
     */
    public function linkToCategory($category_id, $hs_code_id, $relevance = 1, $notes = '')
    {
        $category_id = (int)$category_id;
        $hs_code_id = (int)$hs_code_id;
        $relevance = (int)$relevance;

        $query = "INSERT INTO " . DB::CATEGORY_HS_CODES . " 
                  (mappable_type, mappable_id, hs_code_id, relevance, notes) 
                  VALUES ('Category', ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                    relevance = VALUES(relevance),
                    notes = VALUES(notes),
                    updated_at = NOW()";

        try {
            $this->conn->execute($query, [$category_id, $hs_code_id, $relevance, $notes]);
            return true;
        } catch (Throwable $e) {
            error_log("HSCodeMappings::linkToCategory failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Link an HS code to a subcategory
     *
     * @param int $subcategory_id Subcategory ID
     * @param int $hs_code_id HS Code ID
     * @param int $relevance Relevance level (1=Primary, 2=Secondary, 3=Related)
     * @param string $notes Optional notes
     * @return bool Success status
     */
    public function linkToSubcategory($subcategory_id, $hs_code_id, $relevance = 1, $notes = '')
    {
        $subcategory_id = (int)$subcategory_id;
        $hs_code_id = (int)$hs_code_id;
        $relevance = (int)$relevance;

        $query = "INSERT INTO " . DB::SUBCATEGORY_HS_CODES . " 
                  (mappable_type, mappable_id, hs_code_id, relevance, notes) 
                  VALUES ('Subcategory', ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                    relevance = VALUES(relevance),
                    notes = VALUES(notes),
                    updated_at = NOW()";

        try {
            $this->conn->execute($query, [$subcategory_id, $hs_code_id, $relevance, $notes]);
            return true;
        } catch (Throwable $e) {
            error_log("HSCodeMappings::linkToSubcategory failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unlink an HS code from a category
     *
     * @param int $category_id Category ID
     * @param int $hs_code_id HS Code ID
     * @return bool Success status
     */
    public function unlinkFromCategory($category_id, $hs_code_id)
    {
        $category_id = (int)$category_id;
        $hs_code_id = (int)$hs_code_id;

        $query = "DELETE FROM " . DB::CATEGORY_HS_CODES . " 
                  WHERE mappable_type = 'Category' AND mappable_id = ? AND hs_code_id = ?";

        try {
            $this->conn->execute($query, [$category_id, $hs_code_id]);
            return true;
        } catch (Throwable $e) {
            error_log("HSCodeMappings::unlinkFromCategory failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unlink an HS code from a subcategory
     *
     * @param int $subcategory_id Subcategory ID
     * @param int $hs_code_id HS Code ID
     * @return bool Success status
     */
    public function unlinkFromSubcategory($subcategory_id, $hs_code_id)
    {
        $subcategory_id = (int)$subcategory_id;
        $hs_code_id = (int)$hs_code_id;

        $query = "DELETE FROM " . DB::SUBCATEGORY_HS_CODES . " 
                  WHERE mappable_type = 'Subcategory' AND mappable_id = ? AND hs_code_id = ?";

        try {
            $this->conn->execute($query, [$subcategory_id, $hs_code_id]);
            return true;
        } catch (Throwable $e) {
            error_log("HSCodeMappings::unlinkFromSubcategory failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get count of HS codes linked to a category
     *
     * @param int $category_id Category ID
     * @return int Count of linked HS codes
     */
    public function countForCategory($category_id)
    {
        $category_id = (int)$category_id;

        $query = "SELECT COUNT(*) as total FROM " . DB::CATEGORY_HS_CODES . " 
                  WHERE mappable_type = 'Category' AND mappable_id = ?";

        try {
            $row = $this->conn->fetchOne($query, [$category_id]);
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("HSCodeMappings::countForCategory failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get count of HS codes linked to a subcategory
     *
     * @param int $subcategory_id Subcategory ID
     * @return int Count of linked HS codes
     */
    public function countForSubcategory($subcategory_id)
    {
        $subcategory_id = (int)$subcategory_id;

        $query = "SELECT COUNT(*) as total FROM " . DB::SUBCATEGORY_HS_CODES . " 
                  WHERE mappable_type = 'Subcategory' AND mappable_id = ?";

        try {
            $row = $this->conn->fetchOne($query, [$subcategory_id]);
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("HSCodeMappings::countForSubcategory failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Batch link HS codes to a category
     *
     * @param int $category_id Category ID
     * @param array $hs_codes Array of HS code IDs
     * @param int $relevance Default relevance level
     * @return array Results with count and errors
     */
    public function batchLinkToCategory($category_id, array $hs_codes, $relevance = 1)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (empty($hs_codes)) {
            $results['errors'][] = 'No HS codes provided';
            return $results;
        }

        foreach ($hs_codes as $hs_code_id) {
            if ($this->linkToCategory($category_id, $hs_code_id, $relevance)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to link HS code {$hs_code_id}";
            }
        }

        return $results;
    }

    /**
     * Batch link HS codes to a subcategory
     *
     * @param int $subcategory_id Subcategory ID
     * @param array $hs_codes Array of HS code IDs
     * @param int $relevance Default relevance level
     * @return array Results with count and errors
     */
    public function batchLinkToSubcategory($subcategory_id, array $hs_codes, $relevance = 1)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (empty($hs_codes)) {
            $results['errors'][] = 'No HS codes provided';
            return $results;
        }

        foreach ($hs_codes as $hs_code_id) {
            if ($this->linkToSubcategory($subcategory_id, $hs_code_id, $relevance)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to link HS code {$hs_code_id}";
            }
        }

        return $results;
    }

    /**
     * Get categories that have this HS code linked
     *
     * @param int $hs_code_id HS Code ID
     * @return array Array of category records
     */
    public function getCategoriesForHSCode($hs_code_id)
    {
        $hs_code_id = (int)$hs_code_id;

        $query = "SELECT c.*, chc.relevance
                  FROM " . DB::CATEGORIES . " c
                  INNER JOIN " . DB::CATEGORY_HS_CODES . " chc 
                    ON c.id = chc.mappable_id AND chc.mappable_type = 'Category'
                  WHERE chc.hs_code_id = ?
                  ORDER BY chc.relevance ASC, c.name ASC";

        try {
            return $this->conn->fetchAll($query, [$hs_code_id]);
        } catch (Throwable $e) {
            error_log("HSCodeMappings::getCategoriesForHSCode failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get subcategories that have this HS code linked
     *
     * @param int $hs_code_id HS Code ID
     * @return array Array of subcategory records
     */
    public function getSubcategoriesForHSCode($hs_code_id)
    {
        $hs_code_id = (int)$hs_code_id;

        $query = "SELECT s.*, shc.relevance
                  FROM " . DB::SUBCATEGORIES . " s
                  INNER JOIN " . DB::SUBCATEGORY_HS_CODES . " shc 
                    ON s.id = shc.mappable_id AND shc.mappable_type = 'Subcategory'
                  WHERE shc.hs_code_id = ?
                  ORDER BY shc.relevance ASC, s.name ASC";

        try {
            return $this->conn->fetchAll($query, [$hs_code_id]);
        } catch (Throwable $e) {
            error_log("HSCodeMappings::getSubcategoriesForHSCode failed: " . $e->getMessage());
            return [];
        }
    }
}

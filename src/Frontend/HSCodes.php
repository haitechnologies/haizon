<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

/**
 * HS Codes Frontend Model
 *
 * Handles retrieval and display of HS (Harmonized System) trade codes.
 * Supports bilingual content (English/Arabic) and hierarchical code structure.
 *
 * @package Classes/Frontend
 * @version 1.0.0
 */

class HSCodes
{
    private $conn;

    /**
     * Constructor
     *
     * @param Database|null $conn Database connection object
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
     * Get all HS codes with optional filters
     *
     * @param array $options Query options:
     *   - lang: Language code (en, ar) - default: en
     *   - level: Code level (2, 4, 6, 8, 10 digits)
     *   - parent_id: Parent code ID for hierarchical browsing
     *   - search: Search term for code or description
    *   - codes: Exact HS codes array for single/multiple direct lookup
     *   - limit: Number of records to return - default: 50
     *   - offset: Starting record - default: 0
     * @return array Array of HS code records with translations
     */
    public function getAll($options = [])
    {
        $lang = $options['lang'] ?? 'en';
        $level = isset($options['level']) ? (int)$options['level'] : null;
        $parent_id = isset($options['parent_id']) ? (int)$options['parent_id'] : null;
        $search = $options['search'] ?? '';
        $codes = isset($options['codes']) && is_array($options['codes']) ? $options['codes'] : [];
        $limit = isset($options['limit']) ? (int)$options['limit'] : 50;
        $offset = isset($options['offset']) ? (int)$options['offset'] : 0;

        // Public-facing HS listings must never expose more than 50 records per query.
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);

        $lang = $this->conn->real_escape_string($lang);
        $search = $this->conn->real_escape_string($search);
        $codes = array_values(array_unique(array_filter(array_map(function ($code) {
            $code = trim((string)$code);
            return preg_match('/^[0-9]{2,14}(?:\.[0-9]{2})*$/', $code) ? $code : '';
        }, $codes))));

        $where = [];

        // When searching by keyword or exact codes, do not restrict by level.
        if ($level !== null && empty($search) && empty($codes)) {
            $where[] = "h.level = {$level}";
        }

        if ($parent_id !== null) {
            $where[] = "h.parent_id = {$parent_id}";
        }

        if (!empty($codes)) {
            $escapedCodes = array_map([$this->conn, 'real_escape_string'], $codes);
            $quotedCodes = array_map(function ($code) {
                return "'" . $code . "'";
            }, $escapedCodes);
            // Match either code or old_code
            $where[] = "(h.code IN (" . implode(',', $quotedCodes) . ") OR h.old_code IN (" . implode(',', $quotedCodes) . "))";
        }

        if (!empty($search)) {
            // Join both EN and AR text tables for searching
            $where[] = "(h.code LIKE '%{$search}%'"
                . " OR t.short_desc LIKE '%{$search}%'"
                . " OR t.long_desc LIKE '%{$search}%'"
                . " OR t_ar.short_desc LIKE '%{$search}%'"
                . " OR t_ar.long_desc LIKE '%{$search}%')";
        }

        $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        $orderClause = "ORDER BY h.code ASC";
        if (!empty($codes)) {
            $escapedCodes = array_map([$this->conn, 'real_escape_string'], $codes);
            $quotedCodes = array_map(function ($code) {
                return "'" . $code . "'";
            }, $escapedCodes);
            $orderClause = "ORDER BY FIELD(h.code, " . implode(',', $quotedCodes) . "), h.code ASC";
        }

        $query = "
            SELECT 
                h.id, h.code, h.old_code, h.parent_id, h.level, h.duty_rate, h.vgn_mat,
                MAX(t.long_desc) AS long_desc,
                MAX(t.short_desc) AS short_desc,
                '{$lang}' AS lang,
                parent.code as parent_code
            FROM " . DB::HS_CODES . " h
            LEFT JOIN " . DB::HS_CODE_TEXTS . " t ON h.id = t.hs_code_id AND t.lang = '{$lang}'
            LEFT JOIN " . DB::HS_CODE_TEXTS . " t_ar ON h.id = t_ar.hs_code_id AND t_ar.lang = 'ar'
            LEFT JOIN " . DB::HS_CODES . " parent ON h.parent_id = parent.id
            {$whereClause}
            GROUP BY h.id, h.code, h.old_code, h.parent_id, h.level, h.duty_rate, h.vgn_mat, parent.code
            {$orderClause}
            LIMIT {$limit} OFFSET {$offset}
        ";

        $result = $this->conn->query($query);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        foreach ($rows as &$row) {
            $row = $this->normalizeHsRow($row);
        }
        unset($row);
        return $rows;
    }

    /**
     * Get single HS code by code number
     *
     * @param string $code HS code (e.g., "8703.23.11")
     * @param string $lang Language code (en, ar) - default: en
     * @return array|null HS code record with translations
     */
    public function getByCode($code, $lang = 'en')
    {
        $code = $this->conn->real_escape_string($code);
        $lang = $this->conn->real_escape_string($lang);

        $query = "
            SELECT 
                h.id, h.code, h.old_code, h.parent_id, h.level, h.duty_rate, h.vgn_mat,
                t_en.long_desc as desc_en, t_en.short_desc as short_en,
                t_ar.long_desc as desc_ar, t_ar.short_desc as short_ar,
                parent.code as parent_code,
                parent.id as parent_id_val
            FROM " . DB::HS_CODES . " h
            LEFT JOIN " . DB::HS_CODE_TEXTS . " t_en ON h.id = t_en.hs_code_id AND t_en.lang = 'en'
            LEFT JOIN " . DB::HS_CODE_TEXTS . " t_ar ON h.id = t_ar.hs_code_id AND t_ar.lang = 'ar'
            LEFT JOIN " . DB::HS_CODES . " parent ON h.parent_id = parent.id
            WHERE h.code = '{$code}'
            LIMIT 1
        ";

        $result = $this->conn->query($query);
        if (!$result || $result->num_rows === 0) {
            return null;
        }

        return $this->normalizeHsRow($result->fetch_assoc());
    }

    /**
     * Get child codes for a specific HS code (more specific codes)
     *
     * @param int $parent_id Parent HS code ID
     * @param string $lang Language code - default: en
     * @param int $limit Maximum records to return - default: 50
     * @return array Array of child HS code records
     */
    public function getChildren($parent_id, $lang = 'en', $limit = 50)
    {
        $parent_id = (int)$parent_id;
        $lang = $this->conn->real_escape_string($lang);
        $limit = (int)$limit;

        $query = "
            SELECT 
                h.id, h.code, h.level, h.duty_rate,
                t.short_desc, t.long_desc
            FROM " . DB::HS_CODES . " h
            LEFT JOIN " . DB::HS_CODE_TEXTS . " t ON h.id = t.hs_code_id AND t.lang = '{$lang}'
            WHERE h.parent_id = {$parent_id}
            ORDER BY h.code ASC
            LIMIT {$limit}
        ";

        $result = $this->conn->query($query);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        foreach ($rows as &$row) {
            $row = $this->normalizeHsRow($row);
        }
        unset($row);
        return $rows;
    }

    /**
     * Get categories related to an HS code
     *
     * @param int $hs_code_id HS code ID
     * @param int $limit Maximum records - default: 10
     * @return array Array of category records with relevance scores
     */
    public function getRelatedCategories($hs_code_id, $limit = 10)
    {
        $hs_code_id = (int)$hs_code_id;
        $limit = (int)$limit;

        $query = "
            SELECT 
                c.id, c.name, c.slug, c.icon,
                chc.relevance
            FROM " . DB::CATEGORIES . " c
            INNER JOIN " . DB::CATEGORY_HS_CODES . " chc ON c.id = chc.category_id
            WHERE chc.hs_code_id = {$hs_code_id}
            ORDER BY chc.relevance DESC
            LIMIT {$limit}
        ";

        $result = $this->conn->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get similar HS codes based on shared mapped categories.
     *
     * @param int $hs_code_id HS code ID
     * @param string $lang Language for descriptions
     * @param int $limit Maximum records - default: 10
     * @return array Array of similar HS code records
     */
    public function getSimilarCodesByCategories($hs_code_id, $lang = 'en', $limit = 10)
    {
        $hs_code_id = (int)$hs_code_id;
        $limit = (int)$limit;
        $lang = $this->conn->real_escape_string($lang ?: 'en');

        $query = "
            SELECT
                h.id,
                h.code,
                h.level,
                h.duty_rate,
                t.short_desc,
                t.long_desc,
                COUNT(DISTINCT chc.category_id) AS shared_categories,
                GROUP_CONCAT(DISTINCT c.name ORDER BY chc.relevance DESC, c.name ASC SEPARATOR ', ') AS matched_categories
            FROM " . DB::CATEGORY_HS_CODES . " base_chc
            INNER JOIN " . DB::CATEGORY_HS_CODES . " chc
                ON base_chc.category_id = chc.category_id
               AND chc.hs_code_id != base_chc.hs_code_id
            INNER JOIN " . DB::HS_CODES . " h ON h.id = chc.hs_code_id
            LEFT JOIN " . DB::HS_CODE_TEXTS . " t ON t.hs_code_id = h.id AND t.lang = '{$lang}'
            LEFT JOIN " . DB::CATEGORIES . " c ON c.id = chc.category_id
            WHERE base_chc.hs_code_id = {$hs_code_id}
            GROUP BY h.id, h.code, h.level, h.duty_rate, t.short_desc, t.long_desc
            ORDER BY shared_categories DESC, MAX(chc.relevance) DESC, h.level DESC, h.code ASC
            LIMIT {$limit}
        ";

        $result = $this->conn->query($query);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        if (count($rows) >= $limit) {
            foreach ($rows as &$row) {
                $row = $this->normalizeHsRow($row);
            }
            unset($row);
            return $rows;
        }

        $current = $this->conn->query(
            "SELECT id, code, level FROM " . DB::HS_CODES . " WHERE id = {$hs_code_id} LIMIT 1"
        );
        $currentRow = $current ? $current->fetch_assoc() : null;
        if (!$currentRow || empty($currentRow['code'])) {
            foreach ($rows as &$row) {
                $row = $this->normalizeHsRow($row);
            }
            unset($row);
            return $rows;
        }

        $existingIds = [];
        foreach ($rows as $row) {
            $existingIds[] = (int)($row['id'] ?? 0);
        }
        $existingIds[] = $hs_code_id;
        $existingIds = array_values(array_unique(array_filter($existingIds)));

        $code = (string)$currentRow['code'];
        $codeLevel = (int)($currentRow['level'] ?? 0);
        $prefixLengths = [];
        if (strlen($code) >= 10) {
            $prefixLengths[] = 10;
        }
        if (strlen($code) >= 8) {
            $prefixLengths[] = 8;
        }
        if (strlen($code) >= 6) {
            $prefixLengths[] = 6;
        }
        if (strlen($code) >= 4) {
            $prefixLengths[] = 4;
        }
        if (strlen($code) >= 2) {
            $prefixLengths[] = 2;
        }

        foreach ($prefixLengths as $prefixLength) {
            if (count($rows) >= $limit) {
                break;
            }

            $prefix = $this->conn->real_escape_string(substr($code, 0, $prefixLength));
            $remaining = $limit - count($rows);
            $excludeIds = implode(',', $existingIds);

            $fallbackQuery = "
                SELECT
                    h.id,
                    h.code,
                    h.level,
                    h.duty_rate,
                    t.short_desc,
                    t.long_desc,
                    0 AS shared_categories,
                    'Same HS code family' AS matched_categories
                FROM " . DB::HS_CODES . " h
                LEFT JOIN " . DB::HS_CODE_TEXTS . " t ON t.hs_code_id = h.id AND t.lang = '{$lang}'
                WHERE h.code LIKE '{$prefix}%'
                  AND h.level = {$codeLevel}
                  AND h.id NOT IN ({$excludeIds})
                ORDER BY h.code ASC
                LIMIT {$remaining}
            ";

            $fallbackResult = $this->conn->query($fallbackQuery);
            if (!$fallbackResult) {
                continue;
            }

            while ($fallbackRow = $fallbackResult->fetch_assoc()) {
                $rows[] = $fallbackRow;
                $existingIds[] = (int)$fallbackRow['id'];
                if (count($rows) >= $limit) {
                    break;
                }
            }
        }

        foreach ($rows as &$row) {
            $row = $this->normalizeHsRow($row);
        }
        unset($row);
        return $rows;
    }

    /**
     * Normalize HS text fields so legacy mojibake Arabic content renders correctly.
     */
    private function normalizeHsRow(array $row): array
    {
        $textFields = [
            'short_desc', 'long_desc',
            'desc_en', 'short_en',
            'desc_ar', 'short_ar',
            'matched_categories'
        ];

        foreach ($textFields as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = $this->cleanUtf8Text((string)($row[$field] ?? ''));
            }
        }

        return $row;
    }

    /**
     * Clean UTF-8 issues and repair common legacy Arabic mojibake seen on live hosts.
     */
    private function cleanUtf8Text(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;

        if ($this->looksLikeArabicMojibake($text)) {
            $repaired = $this->repairCp437Mojibake($text);
            if ($repaired !== '' && $this->containsArabic($repaired)) {
                $text = $repaired;
            }
        }

        if (function_exists('mb_convert_encoding')) {
            $detected = @mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($detected && $detected !== 'UTF-8') {
                $converted = @mb_convert_encoding($text, 'UTF-8', $detected);
                if (is_string($converted) && $converted !== '') {
                    $text = $converted;
                }
            }
        }

        if (function_exists('iconv')) {
            $iconvText = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
            if (is_string($iconvText) && $iconvText !== '') {
                $text = $iconvText;
            }
        }

        return trim($text);
    }

    private function looksLikeArabicMojibake(string $text): bool
    {
        if ($this->containsArabic($text)) {
            return false;
        }

        return (bool)preg_match('/[╪╫┘┤┐└├]/u', $text);
    }

    private function containsArabic(string $text): bool
    {
        return (bool)preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $text);
    }

    private function repairCp437Mojibake(string $text): string
    {
        if (!function_exists('iconv')) {
            return '';
        }

        $cp437Bytes = @iconv('UTF-8', 'CP437//IGNORE', $text);
        if (!is_string($cp437Bytes) || $cp437Bytes === '') {
            return '';
        }

        $repaired = @iconv('CP437', 'UTF-8//IGNORE', $cp437Bytes);
        return is_string($repaired) ? trim($repaired) : '';
    }

    /**
     * Get companies dealing with this HS code
    * Note: Requires erp_company_hs_codes table (Phase 2 feature)
     *
     * @param int $hs_code_id HS code ID
     * @param int $limit Maximum records - default: 20
     * @return array Array of company records
     */
    public function getRelatedCompanies($hs_code_id, $limit = 20)
    {
        $hs_code_id = (int)$hs_code_id;
        $limit = (int)$limit;

        // Check if company_hs_codes table exists
        $companyHsCodesTable = 'erp_company_hs_codes';
        $tableCheck = $this->conn->query("SHOW TABLES LIKE '" . $this->conn->real_escape_string($companyHsCodesTable) . "'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return []; // Table doesn't exist yet (Phase 2 feature)
        }

        $query = "
            SELECT 
                c.id,
                c.company_name,
                c.company_name AS name,
                c.slug,
                c.city,
                c.og_image AS logo_url,
                c.profile_views_month AS views,
                c.verified,
                chc.relationship_type
            FROM " . DB::COMPANIES . " c
            INNER JOIN `{$companyHsCodesTable}` chc ON c.id = chc.company_id
            WHERE chc.hs_code_id = {$hs_code_id} 
              AND c.is_active = 1 
              AND c.publish = 1
                        ORDER BY c.verified DESC, c.profile_views_month DESC
            LIMIT {$limit}
        ";

        $result = $this->conn->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get total count of HS codes with optional filters
     *
     * @param array $options Filter options (same as getAll)
     * @return int Total count
     */
    public function getCount($options = [])
    {
        $level = isset($options['level']) ? (int)$options['level'] : null;
        $parent_id = isset($options['parent_id']) ? (int)$options['parent_id'] : null;
        $search = $options['search'] ?? '';
        $codes = isset($options['codes']) && is_array($options['codes']) ? $options['codes'] : [];
        $lang = $options['lang'] ?? 'en';

        $lang = $this->conn->real_escape_string($lang);
        $search = $this->conn->real_escape_string($search);
        $codes = array_values(array_unique(array_filter(array_map(function ($code) {
            $code = trim((string)$code);
            return preg_match('/^[0-9]{2,14}(?:\.[0-9]{2})*$/', $code) ? $code : '';
        }, $codes))));

        $where = [];

        // Keep count query behavior aligned with getAll() search behavior.
        if ($level !== null && empty($search) && empty($codes)) {
            $where[] = "h.level = {$level}";
        }

        if ($parent_id !== null) {
            $where[] = "h.parent_id = {$parent_id}";
        }

        if (!empty($codes)) {
            $escapedCodes = array_map([$this->conn, 'real_escape_string'], $codes);
            $quotedCodes = array_map(function ($code) {
                return "'" . $code . "'";
            }, $escapedCodes);
            $where[] = "h.code IN (" . implode(',', $quotedCodes) . ")";
        }

        if (!empty($search)) {
            $where[] = "(h.code LIKE '%{$search}%' OR t.short_desc LIKE '%{$search}%' OR t.long_desc LIKE '%{$search}%')";
        }

        $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        $query = "
            SELECT COUNT(DISTINCT h.id) as total
            FROM " . DB::HS_CODES . " h
            LEFT JOIN " . DB::HS_CODE_TEXTS . " t ON h.id = t.hs_code_id AND t.lang = '{$lang}'
            {$whereClause}
        ";

        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }

    /**
     * Get top-level HS codes (2-digit codes)
     *
     * @param string $lang Language code - default: en
     * @return array Array of top-level HS code records
     */
    public function getTopLevel($lang = 'en')
    {
        return $this->getAll([
            'lang' => $lang,
            'level' => 2,
            'limit' => 100
        ]);
    }

    /**
     * Search HS codes by keyword
     *
     * @param string $keyword Search keyword
     * @param string $lang Language code - default: en
     * @param int $limit Maximum results - default: 20
     * @return array Array of matching HS code records
     */
    public function search($keyword, $lang = 'en', $limit = 20)
    {
        return $this->getAll([
            'search' => $keyword,
            'lang' => $lang,
            'limit' => $limit
        ]);
    }
}

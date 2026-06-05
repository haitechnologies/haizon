<?php
/**
 * Searches Model
 * Handles retrieval and management of search analytics data
 */

class Searches {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get trending searches with optional filters
     * @param array $opts Options: limit, min_count, offset
     * @return array Array of trending searches
     */
    public function getTrending($opts = []) {
        $limit = $opts['limit'] ?? 20;
        $min_count = $opts['min_count'] ?? 1;
        $offset = $opts['offset'] ?? 0;

        $query = "SELECT 
                    search_query,
                    COUNT(*) as search_count
                  FROM `" . DB::SEARCHES . "`
                  WHERE search_query IS NOT NULL 
                    AND search_query != ''
                    AND search_query NOT REGEXP '^[[:space:]]*$'
                  GROUP BY search_query
                  HAVING search_count >= {$min_count}
                  ORDER BY search_count DESC
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare error: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("ii", $limit, $offset);
        if (!$stmt->execute()) {
            error_log("Execute error: " . $stmt->error);
            return [];
        }

        $result = $stmt->get_result();
        $searches = [];

        while ($row = $result->fetch_assoc()) {
            $searches[] = $row;
        }

        $stmt->close();
        return $searches;
    }

    /**
     * Get trending searches by category (if category data exists)
     * @param string $category Category slug
     * @param array $opts Options: limit
     * @return array Array of trending searches for category
     */
    public function getTrendingByCategory($category, $opts = []) {
        $limit = $opts['limit'] ?? 15;
        $category = $this->conn->real_escape_string($category);

        // For now, return trending searches as category data isn't stored in hai_searches
        // This method can be enhanced if category-based search tracking is added
        return $this->getTrending(['limit' => $limit]);
    }

    /**
     * Get total search volume
     * @return int Total number of searches recorded
     */
    public function getTotalSearchCount() {
        $query = "SELECT COUNT(*) as total FROM `" . DB::SEARCHES . "`";
        $result = $this->conn->query($query);

        if (!$result) {
            error_log("Query error: " . $this->conn->error);
            return 0;
        }

        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Get unique search count
     * @return int Number of unique search queries
     */
    public function getUniqueSearchCount() {
        $query = "SELECT COUNT(DISTINCT search_query) as unique_count FROM `" . DB::SEARCHES . "`";
        $result = $this->conn->query($query);

        if (!$result) {
            error_log("Query error: " . $this->conn->error);
            return 0;
        }

        $row = $result->fetch_assoc();
        return $row['unique_count'] ?? 0;
    }

    /**
     * Search for specific query statistics
     * @param string $query Search query to find
     * @return array Statistics for the search query
     */
    public function getSearchStats($query) {
        $query = $this->conn->real_escape_string($query);
        
        $sql = "SELECT 
                    search_query,
                    COUNT(*) as search_count
                FROM `" . DB::SEARCHES . "`
                WHERE search_query = '{$query}'
                GROUP BY search_query";

        $result = $this->conn->query($sql);

        if (!$result) {
            error_log("Query error: " . $this->conn->error);
            return [];
        }

        $row = $result->fetch_assoc();
        return $row ? [$row] : [];
    }

    /**
     * Record a search query with full context
     * @param string $search_query The search query to record
     * @param string $ip_address User's IP address (optional)
     * @param int|null $user_id User ID (optional)
     * @param int|null $result_count Number of results (optional)
     * @param string $search_type Type of search (manual, guest, etc.)
     * @return bool Success status
     */
    public function recordSearch($search_query, $ip_address = null, $user_id = null, $result_count = null, $search_type = 'manual') {
        if (empty($search_query)) {
            return false;
        }

        $search_query = $this->conn->real_escape_string(trim($search_query));
        $ip_address = $ip_address ? $this->conn->real_escape_string($ip_address) : null;
        $timestamp = date('Y-m-d H:i:s');
        $user_id_sql = $user_id !== null ? (int)$user_id : 'NULL';
        $result_count_sql = $result_count !== null ? (int)$result_count : 'NULL';
        $search_type_sql = $this->conn->real_escape_string($search_type);

        $sql = "INSERT INTO `" . DB::SEARCHES . "` 
                (search_query, ip_address, user_id, result_count, search_type, created_at) 
                VALUES ('{$search_query}', " . 
                ($ip_address ? "'{$ip_address}'" : "NULL") . 
                ", {$user_id_sql}, {$result_count_sql}, '{$search_type_sql}', '{$timestamp}')";

        if (!$this->conn->query($sql)) {
            error_log("Insert error: " . $this->conn->error);
            return false;
        }

        return true;
    }

    /**
     * Get trending searches with related company/service matches
     * @param array $opts Options: limit
     * @return array Trending searches with match counts
     */
    public function getTrendingWithMatches($opts = []) {
        $limit = $opts['limit'] ?? 15;

        $query = "SELECT 
                    s.search_query,
                    COUNT(*) as search_count,
                    (SELECT COUNT(*) FROM `" . DB::COMPANIES . "` 
                     WHERE company_name LIKE CONCAT('%', TRIM(s.search_query), '%')
                        OR services LIKE CONCAT('%', TRIM(s.search_query), '%')
                        OR company_profile LIKE CONCAT('%', TRIM(s.search_query), '%')) as company_matches,
                    (SELECT COUNT(*) FROM `" . DB::BLOGS . "` 
                     WHERE title LIKE CONCAT('%', TRIM(s.search_query), '%')
                        AND publish = 1) as blog_matches
                  FROM `" . DB::SEARCHES . "` s
                  WHERE s.search_query IS NOT NULL 
                    AND s.search_query != ''
                    AND s.search_query NOT REGEXP '^[[:space:]]*$'
                  GROUP BY s.search_query
                  ORDER BY search_count DESC
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare error: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("i", $limit);
        if (!$stmt->execute()) {
            error_log("Execute error: " . $stmt->error);
            return [];
        }

        $result = $stmt->get_result();
        $searches = [];

        while ($row = $result->fetch_assoc()) {
            $searches[] = $row;
        }

        $stmt->close();
        return $searches;
    }
}

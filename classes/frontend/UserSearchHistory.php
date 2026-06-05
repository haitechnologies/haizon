<?php
/**
 * UserSearchHistory Model
 * Tracks all searches performed by users for history and analytics
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class UserSearchHistory {
    
    private $mysqli;
    private $table = DB::SEARCHES;
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }
    
    /**
     * Record a search
     * 
     * @param int $userId User ID
     * @param string $query Search query
     * @param int $resultCount Number of results found
     * @return bool Success status
     */
    public function recordSearch($userId, $query, $resultCount = 0) {
        $query = trim((string)$query);
        
        if (empty($query)) {
            return false;
        }
        
        $sql = "INSERT INTO `{$this->table}` 
                (user_id, search_query, result_count, created_at)
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("UserSearchHistory::recordSearch - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param('isi', $userId, $query, $resultCount);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get user's search history
     * 
     * @param int $userId User ID
     * @param array $opts Options (limit, offset, days)
     * @return array Array of search history records
     */
    public function getUserHistory($userId, $opts = []) {
        $limit = (int)($opts['limit'] ?? 100);
        $offset = (int)($opts['offset'] ?? 0);
        $days = (int)($opts['days'] ?? 90); // Last 90 days by default
        
        $sql = "SELECT 
                    id,
                    user_id,
                    search_query,
                    result_count,
                    created_at
                FROM `{$this->table}`
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("UserSearchHistory::getUserHistory - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param('iiii', $userId, $days, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        $stmt->close();
        return $history;
    }
    
    /**
     * Get user's most frequent searches
     * 
     * @param int $userId User ID
     * @param array $opts Options (limit, days)
     * @return array Array of frequent searches with counts
     */
    public function getFrequentSearches($userId, $opts = []) {
        $limit = (int)($opts['limit'] ?? 10);
        $days = (int)($opts['days'] ?? 90);
        
        $sql = "SELECT 
                    search_query,
                    COUNT(*) as search_count,
                    MAX(created_at) as last_searched,
                    SUM(result_count) as total_results
                FROM `{$this->table}`
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY search_query
                ORDER BY search_count DESC
                LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param('iii', $userId, $days, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $searches = [];
        
        while ($row = $result->fetch_assoc()) {
            $searches[] = $row;
        }
        
        $stmt->close();
        return $searches;
    }
    
    /**
     * Get total search count for user
     * 
     * @param int $userId User ID
     * @param int $days Number of days to look back
     * @return int Total searches
     */
    public function getTotalSearchCount($userId, $days = 90) {
        $sql = "SELECT COUNT(*) as cnt 
                FROM `{$this->table}`
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param('ii', $userId, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['cnt'] ?? 0);
    }
    
    /**
     * Clear search history for user
     * 
     * @param int $userId User ID
     * @param int $beforeDays Only delete searches older than this many days
     * @return bool Success status
     */
    public function clearHistory($userId, $beforeDays = null) {
        if ($beforeDays) {
            $sql = "DELETE FROM `{$this->table}` 
                    WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->mysqli->prepare($sql);
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param('ii', $userId, $beforeDays);
        } else {
            // Clear all history for user
            $sql = "DELETE FROM `{$this->table}` WHERE user_id = ?";
            
            $stmt = $this->mysqli->prepare($sql);
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param('i', $userId);
        }
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Delete specific search from history
     * 
     * @param int $id History record ID
     * @param int $userId User ID (for security)
     * @return bool Success status
     */
    public function deleteRecord($id, $userId) {
        $sql = "DELETE FROM `{$this->table}` WHERE id = ? AND user_id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('ii', $id, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
}

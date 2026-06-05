<?php
/**
 * SavedSearches Model
 * Manages user saved searches and email alert preferences
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class SavedSearches {
    private $mysqli;
    private $table = DB::SEARCHES; // Unified table
    private $usersTable = DB::FRONTEND_USERS;
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }

    private function hasColumn($table, $column) {
        $sql = "SELECT COUNT(*) AS cnt
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ((int)($row['cnt'] ?? 0)) > 0;
    }
    
    /**
     * Get all saved searches for a user
     * 
     * @param int $userId User ID
     * @param array $opts Options (limit, offset, order)
     * @return array Array of saved searches
     */
    public function getUserSearches($userId, $opts = []) {
        $limit = (int)($opts['limit'] ?? 50);
        $offset = (int)($opts['offset'] ?? 0);
        $order = ($opts['order'] ?? 'DESC');
        $orderByColumn = 'created_at';
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }
        $sql = "SELECT id, user_id, search_query, search_name, alert_enabled, alert_frequency, last_executed_at, created_at, updated_at
                FROM `{$this->table}`
                WHERE user_id = ? AND search_type = 'saved' AND is_active = 1
                ORDER BY {$orderByColumn} {$order}
                LIMIT ? OFFSET ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("SavedSearches::getUserSearches - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        $stmt->bind_param('iii', $userId, $limit, $offset);
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
     * Get count of user's saved searches
     * 
     * @param int $userId User ID
     * @return int Number of saved searches
     */
    public function getUserSearchCount($userId) {
        $sql = "SELECT COUNT(*) as cnt FROM `{$this->table}` WHERE user_id = ? AND search_type = 'saved' AND is_active = 1";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['cnt'] ?? 0);
    }
    
    /**
     * Save a new search for a user
     * 
     * @param int $userId User ID
     * @param string $query Search query
     * @param string $name Friendly name for search
     * @param array $opts Options (email_alerts, alert_frequency)
     * @return int|false Search ID on success, false on failure
     */
    public function saveSearch($userId, $query, $name, $opts = []) {
        $query = trim((string)$query);
        $name = trim((string)$name);
        if (empty($query)) {
            error_log("SavedSearches::saveSearch - Empty search query");
            return false;
        }
        if (empty($name)) {
            $name = (strlen($query) > 50) ? substr($query, 0, 47) . '...' : $query;
        }
        $alertEnabled = $opts['alert_enabled'] ?? ($opts['email_alerts'] ?? false ? 1 : 0);
        $alertFrequency = $opts['alert_frequency'] ?? 'daily';
        if (!in_array($alertFrequency, ['daily', 'weekly', 'instant', 'monthly'])) {
            $alertFrequency = 'daily';
        }
        $sql = "INSERT INTO `{$this->table}` 
                (user_id, search_query, search_name, alert_enabled, alert_frequency, search_type, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'saved', 1, NOW(), NOW())";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("SavedSearches::saveSearch - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        $stmt->bind_param('issis', $userId, $query, $name, $alertEnabled, $alertFrequency);
        $success = $stmt->execute();
        $searchId = $success ? $stmt->insert_id : false;
        $stmt->close();
        return $searchId;
    }
    
    /**
     * Update saved search preferences
     * 
     * @param int $searchId Search ID
     * @param int $userId User ID (for security)
     * @param array $data Fields to update (search_name, email_alerts, alert_frequency)
     * @return bool Success status
     */
    public function updateSearch($searchId, $userId, $data) {
        $searchName = trim($data['search_name'] ?? '');
        $alertEnabled = $data['alert_enabled'] ?? ($data['email_alerts'] ?? false ? 1 : 0);
        $alertFrequency = $data['alert_frequency'] ?? 'daily';
        if (!in_array($alertFrequency, ['daily', 'weekly', 'instant', 'monthly'])) {
            $alertFrequency = 'daily';
        }
        $sql = "UPDATE `{$this->table}` 
                SET search_name = ?, alert_enabled = ?, alert_frequency = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ? AND search_type = 'saved'";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sisii', $searchName, $alertEnabled, $alertFrequency, $searchId, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Delete a saved search
     * 
     * @param int $searchId Search ID to delete
     * @param int $userId User ID (for security)
     * @return bool Success status
     */
    public function deleteSearch($searchId, $userId) {
        // Soft delete: set is_active = 0
        $sql = "UPDATE `{$this->table}` SET is_active = 0 WHERE id = ? AND user_id = ? AND search_type = 'saved'";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $searchId, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Get searches with alert emails enabled
     * 
     * @param string $frequency Filter by frequency (daily, weekly, or null for all)
     * @return array Array of searches with email alerts enabled
     */
    public function getSearchesWithAlerts($frequency = null) {
        $sql = "SELECT 
                    s.id,
                    s.user_id,
                    s.search_query,
                    s.search_name,
                    s.alert_frequency,
                    u.email,
                    u.full_name,
                    u.email_verified
                FROM `{$this->table}` s
                INNER JOIN `{$this->usersTable}` u ON u.id = s.user_id
                WHERE s.alert_enabled = 1 AND s.search_type = 'saved' AND s.is_active = 1 AND u.email_verified = 1";
        if ($frequency && in_array($frequency, ['daily', 'weekly', 'instant', 'monthly'])) {
            $sql .= " AND s.alert_frequency = ?";
        }
        $sql .= " ORDER BY s.created_at DESC";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($frequency && in_array($frequency, ['daily', 'weekly', 'instant', 'monthly'])) {
            $stmt->bind_param('s', $frequency);
        }
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
     * Record last search time
     * 
     * @param int $searchId Search ID
     * @return bool Success status
     */
    public function recordSearchTime($searchId) {
        $sql = "UPDATE `{$this->table}` 
                SET last_executed_at = NOW()
                WHERE id = ? AND search_type = 'saved'";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $searchId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}

<?php
/**
 * IP-Based Rate Limiting for Public Pages
 * Prevents automated data scraping by limiting requests per IP.
 *
 * Usage:
 * IpRateLimiter::check('search', 30, 60);  // Max 30 requests per 60 seconds
 *
 * @package Security
 * @author Development Team
 * @date March 5, 2026
 */

class IpRateLimiter {
    private static $db;

    /**
     * Resolve table name for public rate limiting.
     *
     * @return string
     */
    private static function tableName() {
        return (class_exists('DB') && defined('DB::RATE_LIMIT_PUBLIC'))
            ? constant('DB::RATE_LIMIT_PUBLIC')
            : 'erp_rate_limit_public';
    }

    /**
     * Initialize with database connection.
     */
    public static function init($mysqli) {
        self::$db = $mysqli;
    }

    /**
     * Check if IP is within rate limit.
     *
     * @param string $action Action type (search, listings, api_call, etc.)
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_in' => int]
     */
    public static function check($action, $maxRequests = 100, $windowSeconds = 60) {
        if (!self::$db) {
            return ['allowed' => true]; // Disabled if DB not initialized
        }

        $ip = self::getClientIP();
        $now = time();
        $windowStart = $now - $windowSeconds;

        // Ensure table exists before querying.
        self::ensureTable();

        $stmt = self::$db->prepare(
            "SELECT COUNT(*) as count, MIN(timestamp) as oldest 
             FROM `" . self::tableName() . "`
             WHERE ip_address = ? AND action = ? AND timestamp > ?"
        );

        if (!$stmt) {
            error_log("Rate limiter query failed: " . self::$db->error);
            return ['allowed' => true]; // Fail open on DB error
        }

        $stmt->bind_param("ssi", $ip, $action, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $count = (int)($result['count'] ?? 0);
        $stmt->close();

        // Log this request.
        $insertStmt = self::$db->prepare(
            "INSERT INTO `" . self::tableName() . "` (ip_address, action, timestamp)
             VALUES (?, ?, ?)"
        );
        if ($insertStmt) {
            $insertStmt->bind_param("ssi", $ip, $action, $now);
            $insertStmt->execute();
            $insertStmt->close();
        }

        // Check if exceeded.
        $allowed = $count < $maxRequests;

        return [
            'allowed' => $allowed,
            'remaining' => max(0, $maxRequests - $count),
            'reset_in' => $allowed ? 0 : max(0, $maxRequests - $count)
        ];
    }

    /**
     * Get client IP address (handles proxies).
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Extract first IP if multiple.
        if (strpos($ip, ',') !== false) {
            $ips = array_map('trim', explode(',', $ip));
            $ip = $ips[0];
        }

        return $ip;
    }

    /**
     * Ensure rate limit table exists.
     */
    private static function ensureTable() {
        if (!self::$db) {
            return;
        }

        $tableName = self::tableName();
        $result = self::$db->query("SHOW TABLES LIKE '" . self::$db->real_escape_string($tableName) . "'");
        if ($result && $result->num_rows > 0) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ip_address` VARCHAR(45) NOT NULL,
            `action` VARCHAR(50) NOT NULL,
            `timestamp` INT NOT NULL,
            KEY `idx_ip_action` (`ip_address`, `action`, `timestamp`),
            KEY `idx_timestamp` (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!self::$db->query($sql)) {
            error_log("Failed to create rate limit table: " . self::$db->error);
        }

        $cutoff = time() - 86400;
        self::$db->query("DELETE FROM `" . $tableName . "` WHERE timestamp < " . $cutoff);
    }

    /**
     * Get stats for an IP.
     */
    public static function getStats($action) {
        if (!self::$db) {
            return [];
        }

        $ip = self::getClientIP();
        $stmt = self::$db->prepare(
            "SELECT COUNT(*) as total, MAX(timestamp) as last_request
             FROM `" . self::tableName() . "`
             WHERE ip_address = ? AND action = ?"
        );

        if ($stmt) {
            $stmt->bind_param("ss", $ip, $action);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $data;
        }

        return [];
    }
}

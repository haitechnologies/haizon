<?php
/**
 * Rate Limiter Class
 * 
 * Prevents brute-force attacks by limiting the number of attempts
 * for specific actions (login, password reset, etc.) within a time window.
 * 
 * Features:
 * - IP-based rate limiting
 * - Configurable attempt limits and time windows
 * - Automatic cleanup of expired records
 * - Database-backed persistence
 * - Temporary bans for repeated violations
 * 
 * @package HAI\Security
 * @version 1.0
 * @date February 27, 2026
 */

class RateLimiter
{
    /**
     * Database connection
     * @var mysqli
     */
    private static $conn = null;
    
    /**
     * Default configuration
     */
    const DEFAULT_MAX_ATTEMPTS = 5;
    const DEFAULT_TIME_WINDOW = 900; // 15 minutes
    const DEFAULT_BAN_DURATION = 1800; // 30 minutes

    /**
     * Resolve attempts table name from DB constants when available.
     *
     * @return string
     */
    private static function tableName()
    {
        return class_exists('DB') ? DB::RATE_LIMIT_ATTEMPTS : 'erp_rate_limit_attempts';
    }
    
    /**
     * Initialize the rate limiter with database connection
     * 
     * @param mysqli $conn Database connection
     * @return void
     */
    public static function init($conn)
    {
        self::$conn = $conn;
        self::createTableIfNotExists();
    }
    
    /**
     * Create rate_limit_attempts table if it doesn't exist
     * 
     * @return void
     */
    private static function createTableIfNotExists()
    {
        if (!self::$conn) {
            return;
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS `" . self::tableName() . "` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `identifier` VARCHAR(255) NOT NULL COMMENT 'IP address or user identifier',
            `action` VARCHAR(100) NOT NULL COMMENT 'Action type (login, password_reset, etc.)',
            `attempts` INT(11) NOT NULL DEFAULT 1,
            `first_attempt_at` DATETIME NOT NULL,
            `last_attempt_at` DATETIME NOT NULL,
            `banned_until` DATETIME DEFAULT NULL COMMENT 'NULL if not banned',
            PRIMARY KEY (`id`),
            UNIQUE KEY `identifier_action` (`identifier`, `action`),
            KEY `banned_until` (`banned_until`),
            KEY `last_attempt_at` (`last_attempt_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        self::$conn->query($sql);
    }
    
    /**
     * Check if an action is allowed for a specific identifier
     * 
     * @param string $identifier Unique identifier (typically IP address)
     * @param string $action Action type (login, password_reset, etc.)
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $timeWindow Time window in seconds
     * @return array ['allowed' => bool, 'remainingAttempts' => int, 'retryAfter' => int|null, 'reason' => string]
     */
    public static function check($identifier, $action = 'login', $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, $timeWindow = self::DEFAULT_TIME_WINDOW)
    {
        if (!self::$conn) {
            // If no connection, allow the action (fail open)
            return [
                'allowed' => true,
                'remainingAttempts' => $maxAttempts,
                'retryAfter' => null,
                'reason' => ''
            ];
        }
        
        // Clean up expired records
        self::cleanup($timeWindow);
        
        // Check if currently banned
        $stmt = self::$conn->prepare("SELECT banned_until, attempts FROM `" . self::tableName() . "` 
                                       WHERE identifier = ? AND action = ?");
        $stmt->bind_param("ss", $identifier, $action);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $bannedUntil = $row['banned_until'];
            $attempts = (int)$row['attempts'];
            
            // Check if banned
            if ($bannedUntil && strtotime($bannedUntil) > time()) {
                $retryAfter = strtotime($bannedUntil) - time();
                $stmt->close();
                
                return [
                    'allowed' => false,
                    'remainingAttempts' => 0,
                    'retryAfter' => $retryAfter,
                    'reason' => 'Too many failed attempts. Please try again in ' . self::formatDuration($retryAfter) . '.'
                ];
            }
            
            // Check if exceeded attempts within time window
            if ($attempts >= $maxAttempts) {
                $stmt->close();
                
                return [
                    'allowed' => false,
                    'remainingAttempts' => 0,
                    'retryAfter' => $timeWindow,
                    'reason' => 'Too many attempts. Please try again in ' . self::formatDuration($timeWindow) . '.'
                ];
            }
            
            $stmt->close();
            
            return [
                'allowed' => true,
                'remainingAttempts' => $maxAttempts - $attempts,
                'retryAfter' => null,
                'reason' => ''
            ];
        }
        
        $stmt->close();
        
        // No record found, allowed
        return [
            'allowed' => true,
            'remainingAttempts' => $maxAttempts,
            'retryAfter' => null,
            'reason' => ''
        ];
    }
    
    /**
     * Record an attempt for a specific identifier and action
     * 
     * @param string $identifier Unique identifier
     * @param string $action Action type
     * @param int $maxAttempts Maximum allowed attempts (used for ban calculation)
     * @param int $banDuration Duration of ban in seconds if max attempts exceeded
     * @return void
     */
    public static function recordAttempt($identifier, $action = 'login', $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, $banDuration = self::DEFAULT_BAN_DURATION)
    {
        if (!self::$conn) {
            return;
        }
        
        $now = date('Y-m-d H:i:s');
        
        // Check if record exists
        $stmt = self::$conn->prepare("SELECT id, attempts FROM `" . self::tableName() . "` 
                                       WHERE identifier = ? AND action = ?");
        $stmt->bind_param("ss", $identifier, $action);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Update existing record
            $id = $row['id'];
            $attempts = (int)$row['attempts'] + 1;
            $stmt->close();
            
            // Check if should be banned
            $bannedUntil = null;
            if ($attempts >= $maxAttempts) {
                $bannedUntil = date('Y-m-d H:i:s', time() + $banDuration);
            }
            
            $stmt = self::$conn->prepare("UPDATE `" . self::tableName() . "` 
                                           SET attempts = ?, last_attempt_at = ?, banned_until = ? 
                                           WHERE id = ?");
            $stmt->bind_param("issi", $attempts, $now, $bannedUntil, $id);
            $stmt->execute();
            $stmt->close();
            
        } else {
            // Insert new record
            $stmt->close();
            
            $stmt = self::$conn->prepare("INSERT INTO `" . self::tableName() . "` 
                                           (identifier, action, attempts, first_attempt_at, last_attempt_at) 
                                           VALUES (?, ?, 1, ?, ?)");
            $stmt->bind_param("ssss", $identifier, $action, $now, $now);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Reset attempts for a specific identifier and action (e.g., after successful login)
     * 
     * @param string $identifier Unique identifier
     * @param string $action Action type
     * @return void
     */
    public static function reset($identifier, $action = 'login')
    {
        if (!self::$conn) {
            return;
        }
        
        $stmt = self::$conn->prepare("DELETE FROM `" . self::tableName() . "` 
                                       WHERE identifier = ? AND action = ?");
        $stmt->bind_param("ss", $identifier, $action);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Clean up expired records beyond the time window
     * 
     * @param int $timeWindow Time window in seconds
     * @return void
     */
    private static function cleanup($timeWindow = self::DEFAULT_TIME_WINDOW)
    {
        if (!self::$conn) {
            return;
        }
        
        $expiredTime = date('Y-m-d H:i:s', time() - $timeWindow);
        
        // Delete records older than time window and not currently banned
        $stmt = self::$conn->prepare("DELETE FROM `" . self::tableName() . "` 
                                       WHERE last_attempt_at < ? 
                                       AND (banned_until IS NULL OR banned_until < NOW())");
        $stmt->bind_param("s", $expiredTime);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get rate limit status for a specific identifier
     * 
     * @param string $identifier Unique identifier
     * @param string $action Action type
     * @return array|null Array with status info or null if no record
     */
    public static function getStatus($identifier, $action = 'login')
    {
        if (!self::$conn) {
            return null;
        }
        
        $stmt = self::$conn->prepare("SELECT * FROM `" . self::tableName() . "` 
                                       WHERE identifier = ? AND action = ?");
        $stmt->bind_param("ss", $identifier, $action);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    }
    
    /**
     * Ban a specific identifier for a duration
     * 
     * @param string $identifier Unique identifier
     * @param string $action Action type
     * @param int $duration Ban duration in seconds
     * @return void
     */
    public static function ban($identifier, $action = 'login', $duration = self::DEFAULT_BAN_DURATION)
    {
        if (!self::$conn) {
            return;
        }
        
        $now = date('Y-m-d H:i:s');
        $bannedUntil = date('Y-m-d H:i:s', time() + $duration);
        
        // Check if record exists
        $stmt = self::$conn->prepare("SELECT id FROM `" . self::tableName() . "` 
                                       WHERE identifier = ? AND action = ?");
        $stmt->bind_param("ss", $identifier, $action);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Update existing record
            $id = $row['id'];
            $stmt->close();
            
            $stmt = self::$conn->prepare("UPDATE `" . self::tableName() . "` 
                                           SET banned_until = ?, last_attempt_at = ? 
                                           WHERE id = ?");
            $stmt->bind_param("ssi", $bannedUntil, $now, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new record with ban
            $stmt->close();
            
            $stmt = self::$conn->prepare("INSERT INTO `" . self::tableName() . "` 
                                           (identifier, action, attempts, first_attempt_at, last_attempt_at, banned_until) 
                                           VALUES (?, ?, 99, ?, ?, ?)");
            $stmt->bind_param("sssss", $identifier, $action, $now, $now, $bannedUntil);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Unban a specific identifier
     * 
     * @param string $identifier Unique identifier
     * @param string $action Action type
     * @return void
     */
    public static function unban($identifier, $action = 'login')
    {
        if (!self::$conn) {
            return;
        }
        
        $stmt = self::$conn->prepare("UPDATE `" . self::tableName() . "` 
                                       SET banned_until = NULL 
                                       WHERE identifier = ? AND action = ?");
        $stmt->bind_param("ss", $identifier, $action);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get client IP address (handles proxies)
     * 
     * @return string Client IP address
     */
    public static function getClientIP()
    {
        $ipAddress = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs (take the first one)
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($ipList[0]);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP address
        if (filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return $ipAddress;
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Format duration in human-readable format
     * 
     * @param int $seconds Duration in seconds
     * @return string Formatted duration
     */
    private static function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' second' . ($seconds != 1 ? 's' : '');
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '');
        } else {
            $hours = floor($seconds / 3600);
            return $hours . ' hour' . ($hours != 1 ? 's' : '');
        }
    }
    
    /**
     * Get all banned identifiers
     * 
     * @return array Array of banned records
     */
    public static function getBannedList()
    {
        if (!self::$conn) {
            return [];
        }
        
        $stmt = self::$conn->prepare("SELECT * FROM `" . self::tableName() . "` 
                                       WHERE banned_until IS NOT NULL 
                                       AND banned_until > NOW() 
                                       ORDER BY banned_until DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $banned = [];
        while ($row = $result->fetch_assoc()) {
            $banned[] = $row;
        }
        
        $stmt->close();
        
        return $banned;
    }
    
    /**
     * Get statistics for rate limiting
     * 
     * @return array Statistics
     */
    public static function getStatistics()
    {
        if (!self::$conn) {
            return [
                'total_records' => 0,
                'currently_banned' => 0,
                'total_attempts_today' => 0
            ];
        }
        
        $stats = [];
        
        // Total records
        $result = self::$conn->query("SELECT COUNT(*) as count FROM `" . self::tableName() . "`");
        $row = $result->fetch_assoc();
        $stats['total_records'] = (int)$row['count'];
        
        // Currently banned
        $result = self::$conn->query("SELECT COUNT(*) as count FROM `" . self::tableName() . "` 
                                      WHERE banned_until IS NOT NULL AND banned_until > NOW()");
        $row = $result->fetch_assoc();
        $stats['currently_banned'] = (int)$row['count'];
        
        // Total attempts today
        $result = self::$conn->query("SELECT SUM(attempts) as total FROM `" . self::tableName() . "` 
                                      WHERE DATE(last_attempt_at) = CURDATE()");
        $row = $result->fetch_assoc();
        $stats['total_attempts_today'] = (int)($row['total'] ?? 0);
        
        return $stats;
    }
    
    /**
     * Check if a specific identifier is currently blocked/banned
     * 
     * @param string $identifier Unique identifier (typically IP address)
     * @param string $action Action type (optional, checks if blocked for any action if not specified)
     * @return bool True if blocked, false otherwise
     */
    public static function isBlocked($identifier, $action = null)
    {
        if (!self::$conn) {
            return false;
        }
        
        if ($action === null) {
            // Check if blocked for ANY action
            $stmt = self::$conn->prepare("SELECT id FROM `" . self::tableName() . "` 
                                           WHERE identifier = ? 
                                           AND banned_until IS NOT NULL 
                                           AND banned_until > NOW() 
                                           LIMIT 1");
            $stmt->bind_param("s", $identifier);
        } else {
            // Check if blocked for specific action
            $stmt = self::$conn->prepare("SELECT id FROM `" . self::tableName() . "` 
                                           WHERE identifier = ? 
                                           AND action = ? 
                                           AND banned_until IS NOT NULL 
                                           AND banned_until > NOW() 
                                           LIMIT 1");
            $stmt->bind_param("ss", $identifier, $action);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $isBlocked = $result->num_rows > 0;
        $stmt->close();
        
        return $isBlocked;
    }
}

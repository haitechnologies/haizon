<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Database;
use App\Core\DB;

/**
 * IP-Based Rate Limiting for Public Pages
 * Prevents automated data scraping by limiting requests per IP.
 */
class IpRateLimiter
{
    private static ?Database $db = null;

    /**
     * Resolve table name for public rate limiting.
     */
    private static function tableName(): string
    {
        return (class_exists('DB') && defined('DB::RATE_LIMIT_PUBLIC'))
            ? DB::RATE_LIMIT_PUBLIC
            : 'erp_rate_limit_public';
    }

    /**
     * Initialize with database connection.
     */
    public static function init($db): void
    {
        if ($db instanceof Database) {
            self::$db = $db;
        } elseif ($db instanceof \mysqli || $db instanceof \PDO) {
            self::$db = new Database();
        } else {
            self::$db = new Database();
        }
    }

    /**
     * Check if IP is within rate limit.
     *
     * @param string $action Action type (search, listings, api_call, etc.)
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_in' => int]
     */
    public static function check(string $action, int $maxRequests = 100, int $windowSeconds = 60): array
    {
        if (!self::$db) {
            return ['allowed' => true]; // Disabled if DB not initialized
        }

        $ip = self::getClientIP();
        $now = time();
        $windowStart = $now - $windowSeconds;

        // Ensure table exists before querying.
        self::ensureTable();

        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM `" . self::tableName() . "`
                    WHERE ip_address = :ip AND action = :action AND timestamp > :start";

            $result = self::$db->fetchOne($sql, [
                'ip' => $ip,
                'action' => $action,
                'start' => $windowStart
            ]);

            $count = (int)($result['count'] ?? 0);
        } catch (\Throwable $e) {
            error_log("Rate limiter query failed: " . $e->getMessage());
            return ['allowed' => true]; // Fail open on DB error
        }

        // Log this request.
        try {
            $insertSql = "INSERT INTO `" . self::tableName() . "` (ip_address, action, timestamp)
                          VALUES (:ip, :action, :ts)";
            self::$db->execute($insertSql, [
                'ip' => $ip,
                'action' => $action,
                'ts' => $now
            ]);
        } catch (\Throwable $e) {
            error_log("Rate limiter insert failed: " . $e->getMessage());
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
    private static function getClientIP(): string
    {
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
    private static function ensureTable(): void
    {
        if (!self::$db) {
            return;
        }

        $tableName = self::tableName();

        // Check if table exists using PDO
        $tableExists = false;
        try {
            $checkSql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = :table_name 
                         LIMIT 1";
            $res = self::$db->fetchOne($checkSql, ['table_name' => $tableName]);
            if ($res !== null) {
                $tableExists = true;
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        if ($tableExists) {
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

        try {
            self::$db->execute($sql);
        } catch (\Throwable $e) {
            error_log("Failed to create rate limit table: " . $e->getMessage());
        }

        // Clean up old records (older than 24 hours)
        $cutoff = time() - 86400;
        try {
            self::$db->execute("DELETE FROM `" . $tableName . "` WHERE timestamp < :cutoff", ['cutoff' => $cutoff]);
        } catch (\Throwable $e) {
            // Safe to ignore on cleanup
        }
    }

    /**
     * Get stats for an IP.
     */
    public static function getStats(string $action): array
    {
        if (!self::$db) {
            return [];
        }

        $ip = self::getClientIP();
        try {
            $sql = "SELECT COUNT(*) as total, MAX(timestamp) as last_request
                    FROM `" . self::tableName() . "`
                    WHERE ip_address = :ip AND action = :action";
            return self::$db->fetchOne($sql, [
                'ip' => $ip,
                'action' => $action
            ]) ?? [];
        } catch (\Throwable $e) {
            error_log("Rate limiter getStats failed: " . $e->getMessage());
            return [];
        }
    }
}

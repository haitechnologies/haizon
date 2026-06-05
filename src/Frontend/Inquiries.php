<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

/**
 * Inquiries Data Access Class
 *
 * Handles all database operations for contact form inquiries on the frontend.
 * Supports spam prevention, IP tracking, and user agent logging.
 *
 * @package Classes\Frontend
 */
class Inquiries
{
    /** @var Database */
    protected Database $conn;

    private string $table = DB::INQUIRIES;

    // Status constants
    public const STATUS_PENDING = 0;
    public const STATUS_READ = 1;
    public const STATUS_REPLIED = 2;
    public const STATUS_SPAM = 3;
    public const STATUS_ARCHIVED = 4;

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
     * Create a new inquiry from contact form submission
     *
     * @param array $data Inquiry data (subject, full_name, email, mobile, message)
     * @return int|false Inserted inquiry ID or false on failure
     */
    public function create($data)
    {
        // Validate required fields
        $required = ['subject', 'full_name', 'email', 'message'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                error_log("Inquiries::create - Missing required field: {$field}");
                return false;
            }
        }

        // Get IP address and user agent
        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Check for spam (same IP, same message within last hour)
        if ($this->isDuplicateInquiry($ipAddress, $data['message'])) {
            error_log("Inquiries::create - Duplicate inquiry detected from IP: {$ipAddress}");
            return false;
        }

        $sql = "INSERT INTO `{$this->table}` 
                (subject, full_name, email, mobile, message, ip_address, user_agent, status, publish, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";

        $mobile = $data['mobile'] ?? null;
        $status = self::STATUS_PENDING;

        try {
            $insertId = $this->conn->insert($sql, [
                $data['subject'],
                $data['full_name'],
                $data['email'],
                $mobile,
                $data['message'],
                $ipAddress,
                $userAgent,
                $status
            ]);
            return $insertId ? (int)$insertId : false;
        } catch (Throwable $e) {
            error_log("Inquiries::create failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a single inquiry by ID
     *
     * @param int $id Inquiry ID
     * @return array|null Inquiry record or null if not found
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = ? LIMIT 1";

        try {
            return $this->conn->fetchOne($sql, [$id]);
        } catch (Throwable $e) {
            error_log("Inquiries::getById failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all inquiries with optional filters (for admin dashboard)
     *
     * @param array $options Filter options (status, limit, offset, order_by)
     * @return array Array of inquiry records
     */
    public function getAll($options = [])
    {
        $where = [];
        $params = [];

        // Filter by status
        if (isset($options['status'])) {
            $where[] = "status = ?";
            $params[] = $options['status'];
        }

        // Filter by publish
        if (isset($options['publish'])) {
            $where[] = "publish = ?";
            $params[] = $options['publish'];
        }

        // Search in name, email, subject, message
        if (!empty($options['search'])) {
            $searchTerm = '%' . $options['search'] . '%';
            $where[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        // Build WHERE clause
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Order by
        $orderBy = $options['order_by'] ?? 'created_at DESC';

        // Pagination
        $limit = (int)($options['limit'] ?? 50);
        $offset = (int)($options['offset'] ?? 0);

        $sql = "SELECT * FROM `{$this->table}` {$whereClause} ORDER BY {$orderBy} LIMIT {$limit} OFFSET {$offset}";

        try {
            return $this->conn->fetchAll($sql, $params);
        } catch (Throwable $e) {
            error_log("Inquiries::getAll failed: " . $e->getMessage() . " | SQL: " . $sql);
            return [];
        }
    }

    /**
     * Get count of inquiries with optional filters
     *
     * @param array $options Filter options (status, publish, search)
     * @return int Total count
     */
    public function getCount($options = [])
    {
        $where = [];
        $params = [];

        // Filter by status
        if (isset($options['status'])) {
            $where[] = "status = ?";
            $params[] = $options['status'];
        }

        // Filter by publish
        if (isset($options['publish'])) {
            $where[] = "publish = ?";
            $params[] = $options['publish'];
        }

        // Search filter
        if (!empty($options['search'])) {
            $searchTerm = '%' . $options['search'] . '%';
            $where[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        // Build WHERE clause
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` {$whereClause}";

        try {
            $row = $this->conn->fetchOne($sql, $params);
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("Inquiries::getCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update inquiry status
     *
     * @param int $id Inquiry ID
     * @param int $status New status
     * @param int|null $userId User ID who updated
     * @return bool Success status
     */
    public function updateStatus($id, $status, $userId = null)
    {
        $sql = "UPDATE `{$this->table}` SET status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";

        try {
            $this->conn->execute($sql, [$status, $userId, $id]);
            return true;
        } catch (Throwable $e) {
            error_log("Inquiries::updateStatus failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark inquiry as replied
     *
     * @param int $id Inquiry ID
     * @param int|null $userId User ID who replied
     * @return bool Success status
     */
    public function markAsReplied($id, $userId = null)
    {
        $sql = "UPDATE `{$this->table}` SET status = ?, replied_at = NOW(), replied_by = ?, updated_at = NOW() WHERE id = ?";
        $status = self::STATUS_REPLIED;

        try {
            $this->conn->execute($sql, [$status, $userId, $id]);
            return true;
        } catch (Throwable $e) {
            error_log("Inquiries::markAsReplied failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add notes to an inquiry
     *
     * @param int $id Inquiry ID
     * @param string $notes Notes text
     * @param int|null $userId User ID who added notes
     * @return bool Success status
     */
    public function addNotes($id, $notes, $userId = null)
    {
        $sql = "UPDATE `{$this->table}` SET notes = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";

        try {
            $this->conn->execute($sql, [$notes, $userId, $id]);
            return true;
        } catch (Throwable $e) {
            error_log("Inquiries::addNotes failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if inquiry is duplicate (same IP + similar message within 1 hour)
     *
     * @param string $ipAddress IP address
     * @param string $message Message text
     * @return bool True if duplicate found
     */
    private function isDuplicateInquiry($ipAddress, $message)
    {
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}` 
                WHERE ip_address = ? 
                AND message = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";

        try {
            $row = $this->conn->fetchOne($sql, [$ipAddress, $message]);
            return ($row['count'] ?? 0) > 0;
        } catch (Throwable $e) {
            error_log("Inquiries::isDuplicateInquiry failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get client IP address (handles proxies and forwarded IPs)
     *
     * @return string Client IP address
     */
    private function getClientIp()
    {
        $ipAddress = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipAddress = 'UNKNOWN';
        }

        return $ipAddress;
    }
}

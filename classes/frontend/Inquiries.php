<?php
/**
 * Inquiries Data Access Class
 * 
 * Handles all database operations for contact form inquiries on the frontend.
 * Supports spam prevention, IP tracking, and user agent logging.
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class Inquiries {
    
    private $mysqli;
    private $table = DB::INQUIRIES;
    
    // Status constants
    const STATUS_PENDING = 0;
    const STATUS_READ = 1;
    const STATUS_REPLIED = 2;
    const STATUS_SPAM = 3;
    const STATUS_ARCHIVED = 4;
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }
    
    /**
     * Create a new inquiry from contact form submission
     * 
     * @param array $data Inquiry data (subject, full_name, email, mobile, message)
     * @return int|false Inserted inquiry ID or false on failure
     */
    public function create($data) {
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
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Inquiries::create - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $mobile = $data['mobile'] ?? null;
        $status = self::STATUS_PENDING;
        
        $stmt->bind_param("sssssssi", 
            $data['subject'],
            $data['full_name'],
            $data['email'],
            $mobile,
            $data['message'],
            $ipAddress,
            $userAgent,
            $status
        );
        
        $success = $stmt->execute();
        $insertId = $success ? $stmt->insert_id : false;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Get a single inquiry by ID
     * 
     * @param int $id Inquiry ID
     * @return array|null Inquiry record or null if not found
     */
    public function getById($id) {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = ? LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Inquiries::getById - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $inquiry = $result->fetch_assoc();
        $stmt->close();
        
        return $inquiry;
    }
    
    /**
     * Get all inquiries with optional filters (for admin dashboard)
     * 
     * @param array $options Filter options (status, limit, offset, order_by)
     * @return array Array of inquiry records
     */
    public function getAll($options = []) {
        $where = [];
        $params = [];
        $types = "";
        
        // Filter by status
        if (isset($options['status'])) {
            $where[] = "status = ?";
            $params[] = $options['status'];
            $types .= "i";
        }
        
        // Filter by publish
        if (isset($options['publish'])) {
            $where[] = "publish = ?";
            $params[] = $options['publish'];
            $types .= "i";
        }
        
        // Search in name, email, subject, message
        if (!empty($options['search'])) {
            $searchTerm = '%' . $options['search'] . '%';
            $where[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= "ssss";
        }
        
        // Build WHERE clause
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Order by
        $orderBy = $options['order_by'] ?? 'created_at DESC';
        
        // Pagination
        $limit = $options['limit'] ?? 50;
        $offset = $options['offset'] ?? 0;
        
        $sql = "SELECT * FROM `{$this->table}` {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Inquiries::getAll - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $inquiries = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $inquiries;
    }
    
    /**
     * Get count of inquiries with optional filters
     * 
     * @param array $options Filter options (status, publish, search)
     * @return int Total count
     */
    public function getCount($options = []) {
        $where = [];
        $params = [];
        $types = "";
        
        // Filter by status
        if (isset($options['status'])) {
            $where[] = "status = ?";
            $params[] = $options['status'];
            $types .= "i";
        }
        
        // Filter by publish
        if (isset($options['publish'])) {
            $where[] = "publish = ?";
            $params[] = $options['publish'];
            $types .= "i";
        }
        
        // Search filter
        if (!empty($options['search'])) {
            $searchTerm = '%' . $options['search'] . '%';
            $where[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= "ssss";
        }
        
        // Build WHERE clause
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` {$whereClause}";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Inquiries::getCount - Prepare failed: " . $this->mysqli->error);
            return 0;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total'] ?? 0);
    }
    
    /**
     * Update inquiry status
     * 
     * @param int $id Inquiry ID
     * @param int $status New status
     * @param int|null $userId User ID who updated
     * @return bool Success status
     */
    public function updateStatus($id, $status, $userId = null) {
        $sql = "UPDATE `{$this->table}` SET status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Inquiries::updateStatus - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("iii", $status, $userId, $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Mark inquiry as replied
     * 
     * @param int $id Inquiry ID
     * @param int|null $userId User ID who replied
     * @return bool Success status
     */
    public function markAsReplied($id, $userId = null) {
        $sql = "UPDATE `{$this->table}` SET status = ?, replied_at = NOW(), replied_by = ?, updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Inquiries::markAsReplied - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $status = self::STATUS_REPLIED;
        $stmt->bind_param("iii", $status, $userId, $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Add notes to an inquiry
     * 
     * @param int $id Inquiry ID
     * @param string $notes Notes text
     * @param int|null $userId User ID who added notes
     * @return bool Success status
     */
    public function addNotes($id, $notes, $userId = null) {
        $sql = "UPDATE `{$this->table}` SET notes = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Inquiries::addNotes - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("sii", $notes, $userId, $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Check if inquiry is duplicate (same IP + similar message within 1 hour)
     * 
     * @param string $ipAddress IP address
     * @param string $message Message text
     * @return bool True if duplicate found
     */
    private function isDuplicateInquiry($ipAddress, $message) {
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}` 
                WHERE ip_address = ? 
                AND message = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ss", $ipAddress, $message);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return ($row['count'] ?? 0) > 0;
    }
    
    /**
     * Get client IP address (handles proxies and forwarded IPs)
     * 
     * @return string Client IP address
     */
    private function getClientIp() {
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

<?php
/**
 * Email Queue Helper - Wrapper for EmailQueue with fallbacks
 * Handles database errors gracefully and ensures tables exist
 * 
 * @package HAI\Email
 * @version 1.1 (Robust Version)
 */

class EmailQueueHelper {
    private $conn;
    private $tableName;
    private $ready = false;
    private $errors = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->tableName = class_exists('DB') ? DB::EMAIL_QUEUE : 'erp_email_queue';
        $this->validateTables();
    }
    
    /**
     * Validate that email queue table exists with correct schema
     * If table doesn't exist or schema is wrong, attempt to fix it
     */
    private function validateTables() {
        try {
            // Check if table exists
            $tableCheck = $this->conn->query("SHOW TABLES LIKE '" . $this->conn->real_escape_string($this->tableName) . "'");
            if (!$tableCheck || $tableCheck->num_rows === 0) {
                // Table doesn't exist, create it
                $this->createEmailQueueTable();
                $this->ready = true;
                return;
            }
            
            // Table exists, verify schema
            $describeResult = $this->conn->query("DESCRIBE `" . $this->tableName . "`");
            if (!$describeResult) {
                $this->errors[] = "Failed to describe {$this->tableName} table";
                $this->ready = false;
                return;
            }
            
            // Get all column names
            $columns = [];
            while ($col = $describeResult->fetch_assoc()) {
                $columns[$col['Field']] = $col['Type'];
            }
            
            // Verify required columns exist
            $requiredColumns = [
                'id' => 'int',
                'recipient' => 'varchar',
                'subject' => 'varchar',
                'body' => 'longtext',
                'headers' => 'json',
                'priority' => 'int',
                'status' => 'enum',
                'attempts' => 'int',
                'created_at' => 'timestamp'
            ];
            
            $missingColumns = [];
            foreach ($requiredColumns as $col => $type) {
                if (!isset($columns[$col])) {
                    $missingColumns[] = $col;
                }
            }
            
            if (!empty($missingColumns)) {
                // Try to add missing columns
                $this->addMissingColumns($missingColumns, $columns);
            }
            
            $this->ready = true;
            
        } catch (Exception $e) {
            $this->errors[] = "Table validation error: " . $e->getMessage();
            $this->ready = false;
        }
    }
    
    /**
     * Create email queue table from scratch
     */
    private function createEmailQueueTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . $this->tableName . "` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `recipient` VARCHAR(255) NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `body` LONGTEXT NOT NULL,
            `headers` JSON DEFAULT NULL,
            `priority` INT(11) DEFAULT 2,
            `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            `attempts` INT(11) DEFAULT 0,
            `max_retries` INT(11) DEFAULT 3,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `sent_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_status` (`status`),
            KEY `idx_recipient` (`recipient`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$this->conn->query($sql)) {
            $this->errors[] = "Failed to create {$this->tableName} table: " . $this->conn->error;
            $this->ready = false;
        }
    }
    
    /**
     * Add missing columns to the email queue table
     */
    private function addMissingColumns($missing, $existing) {
        $columnDefinitions = [
            'recipient' => '`recipient` VARCHAR(255) NOT NULL',
            'subject' => '`subject` VARCHAR(255) NOT NULL',
            'body' => '`body` LONGTEXT NOT NULL',
            'headers' => '`headers` JSON DEFAULT NULL',
            'priority' => '`priority` INT(11) DEFAULT 2',
            'status' => '`status` ENUM("pending", "sent", "failed") DEFAULT "pending"',
            'attempts' => '`attempts` INT(11) DEFAULT 0',
            'max_retries' => '`max_retries` INT(11) DEFAULT 3',
            'created_at' => '`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'sent_at' => '`sent_at` TIMESTAMP NULL DEFAULT NULL'
        ];
        
        foreach ($missing as $col) {
            if (isset($columnDefinitions[$col])) {
                $alter = "ALTER TABLE `" . $this->tableName . "` ADD COLUMN " . $columnDefinitions[$col];
                if (!$this->conn->query($alter)) {
                    $this->errors[] = "Failed to add column $col: " . $this->conn->error;
                }
            }
        }
    }
    
    /**
     * Queue an email with fallback to direct send if queue fails
     */
    public function queueEmail($to, $subject, $body, $headers = [], $priority = 2) {
        if (!$this->ready) {
            // Table not available, return failure but don't break
            error_log("Email queue not ready. Email to $to would have been queued. Subject: $subject");
            return false;
        }
        
        try {
            // Try to insert into queue
            $headersJson = json_encode($headers ?: []);
            
            $stmt = $this->conn->prepare(
                "INSERT INTO `" . $this->tableName . "` 
                (recipient, subject, body, headers, priority, status, attempts) 
                VALUES (?, ?, ?, ?, ?, 'pending', 0)"
            );
            
            if (!$stmt) {
                error_log("Failed to prepare email queue insert: " . $this->conn->error);
                return false;
            }
            
            $status = $stmt->bind_param("ssssi", $to, $subject, $body, $headersJson, $priority);
            if (!$status) {
                error_log("Failed to bind params: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            if ($stmt->execute()) {
                $queueId = $this->conn->insert_id;
                $stmt->close();
                return $queueId;
            } else {
                error_log("Failed to insert into email queue: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email queue exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the status of the email queue system
     */
    public function isReady() {
        return $this->ready;
    }
    
    /**
     * Get any errors that occurred during initialization
     */
    public function getErrors() {
        return $this->errors;
    }
}
?>

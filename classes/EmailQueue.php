<?php
/**
 * Email Queue Class
 * Manages email delivery with retry logic and persistence
 * 
 * @package HAI\Email
 * @version 1.0
 * @date March 5, 2026
 */

class EmailQueue {
    private $conn;
    private $tableName;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->tableName = class_exists('DB') ? DB::EMAIL_QUEUE : 'erp_email_queue';
    }
    
    /**
     * Add email to queue
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $headers
     * @param int $priority (1-high, 2-medium, 3-low)
     * @return int Queue ID or false
     */
    public function enqueue($to, $subject, $body, $headers = [], $priority = 2) {
        $headersJson = json_encode($headers);
        $maxRetries = 3;
        $status = 'pending';
        
        $stmt = $this->conn->prepare(
            "INSERT INTO `" . $this->tableName . "` 
            (recipient_email, recipient, subject, body, headers, priority, status, max_retries, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        
        if (!$stmt) {
            error_log("Failed to prepare email queue insert: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("sssssisi", $to, $to, $subject, $body, $headersJson, $priority, $status, $maxRetries);
        
        if ($stmt->execute()) {
            $queueId = $this->conn->insert_id;
            $stmt->close();
            return $queueId;
        } else {
            error_log("Failed to insert into email queue: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    /**
     * Process pending emails in queue
     * @param int $limit Maximum emails to process
     * @return int Number of emails sent
     */
    public function processPending($limit = 10) {
        $status = 'pending';
        
        $stmt = $this->conn->prepare(
            "SELECT id, recipient, subject, body, headers, retries 
             FROM `" . $this->tableName . "` 
             WHERE status = ? 
             ORDER BY priority ASC, created_at ASC 
             LIMIT ?"
        );
        
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param("si", $status, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $sent = 0;
        
        while ($row = $result->fetch_assoc()) {
            $headers = json_decode($row['headers'], true) ?: [];
            
            if ($this->sendEmail($row['recipient'], $row['subject'], $row['body'], $headers)) {
                $this->markAsSent($row['id']);
                $sent++;
            } else {
                $this->incrementRetry($row['id']);
            }
        }
        
        $stmt->close();
        return $sent;
    }
    
    /**
     * Send email using SMTP (via SMTPMailer class)
     * Falls back to mail() function if SMTPMailer not available
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $headers
     * @return bool
     */
    private function sendEmail($to, $subject, $body, $headers = []) {
        try {
            // Use SMTPMailer if available
            if (class_exists('SMTPMailer')) {
                $mailer = new SMTPMailer();
                return $mailer->send($to, $subject, $body, $headers);
            }
            // Fallback to mail() is DISABLED. Log error and return false.
            error_log("[EmailQueue] SMTPMailer not found, mail() fallback is disabled. Email not sent.");
            return false;
        } catch (Exception $e) {
            error_log("[EmailQueue] Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark email as successfully sent
     * @param int $queueId
     * @return bool
     */
    private function markAsSent($queueId) {
        $status = 'sent';
        
        $stmt = $this->conn->prepare(
            "UPDATE `" . $this->tableName . "` 
             SET status = ?, sent_at = NOW() 
             WHERE id = ?"
        );
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("si", $status, $queueId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Increment retry count for failed email
     * @param int $queueId
     * @return bool
     */
    private function incrementRetry($queueId) {
        $stmt = $this->conn->prepare(
            "UPDATE `" . $this->tableName . "` 
             SET retries = retries + 1, 
                 updated_at = NOW(),
                 status = IF(retries >= max_retries - 1, 'failed', 'pending')
             WHERE id = ?"
        );
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $queueId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get queue statistics
     * @return array
     */
    public function getStats() {
        $stmt = $this->conn->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(IF(status = 'pending', 1, 0)) as pending,
                SUM(IF(status = 'sent', 1, 0)) as sent,
                SUM(IF(status = 'failed', 1, 0)) as failed
             FROM `" . $this->tableName . "`"
        );
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats;
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use mysqli;
use PDO;
use PDOException;
use App\Core\Container;
use App\Core\Database;
use App\Core\DB;
use Throwable;

/**
 * Email Queue Class
 * Manages email delivery with retry logic and persistence
 */
class EmailQueue
{
    private mixed $conn;
    private ?Database $db = null;
    private bool $isMysqli = false;
    private string $tableName;

    /**
     * Constructor
     *
     * @param mixed $conn Database connection (mysqli, PDO, or Database wrapper)
     */
    public function __construct(mixed $conn = null)
    {
        if ($conn instanceof mysqli) {
            $this->conn = $conn;
            $this->isMysqli = true;
        } else {
            if ($conn instanceof Database) {
                $this->db = $conn;
            } else {
                $this->db = self::resolveDatabase($conn);
            }
            $this->conn = $this->db->getConnection();
            $this->isMysqli = false;
        }

        $this->tableName = class_exists('DB') && defined('DB::EMAIL_QUEUE') ? (string)constant('DB::EMAIL_QUEUE') : 'erp_email_queue';
    }

    /**
     * Resolve database instance from DI container or fallback.
     *
     * @param mixed $conn
     * @return Database
     */
    private static function resolveDatabase(mixed $conn = null): Database
    {
        if ($conn instanceof Database) {
            return $conn;
        }
        try {
            $container = Container::getInstance();
            if ($container->has(Database::class)) {
                $resolved = $container->get(Database::class);
                if ($resolved instanceof Database) {
                    return $resolved;
                }
            }
        } catch (Throwable $e) {
            // Ignore container errors
        }
        return new Database();
    }

    /**
     * Add email to queue
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $headers
     * @param int $priority (1-high, 2-medium, 3-low)
     * @return int|bool Queue ID or false
     */
    public function enqueue(string $to, string $subject, string $body, array $headers = [], int $priority = 2): int|bool
    {
        $headersJson = json_encode($headers);
        $maxRetries = 3;
        $status = 'pending';

        $sql = "INSERT INTO `" . $this->tableName . "` 
                (recipient_email, recipient, subject, body, headers, priority, status, max_retries, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        if ($this->isMysqli) {
            $stmt = $this->conn->prepare($sql);
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
        } else {
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$to, $to, $subject, $body, $headersJson, $priority, $status, $maxRetries]);
                $lastId = $this->conn->lastInsertId();
                return $lastId ? (int)$lastId : true;
            } catch (PDOException $e) {
                error_log("Failed to insert into email queue: " . $e->getMessage());
                return false;
            }
        }
    }

    /**
     * Process pending emails in queue
     *
     * @param int $limit Maximum emails to process
     * @return int Number of emails sent
     */
    public function processPending(int $limit = 10): int
    {
        $status = 'pending';

        $sql = "SELECT id, recipient, subject, body, headers, retries 
                FROM `" . $this->tableName . "` 
                WHERE status = ? 
                ORDER BY priority ASC, created_at ASC 
                LIMIT ?";

        $sent = 0;

        if ($this->isMysqli) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param("si", $status, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $headers = json_decode($row['headers'], true) ?: [];
                if ($this->sendEmail($row['recipient'], $row['subject'], $row['body'], $headers)) {
                    $this->markAsSent((int)$row['id']);
                    $sent++;
                } else {
                    $this->incrementRetry((int)$row['id']);
                }
            }
            $stmt->close();
        } else {
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$status, $limit]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $headers = json_decode($row['headers'], true) ?: [];
                    if ($this->sendEmail($row['recipient'], $row['subject'], $row['body'], $headers)) {
                        $this->markAsSent((int)$row['id']);
                        $sent++;
                    } else {
                        $this->incrementRetry((int)$row['id']);
                    }
                }
            } catch (PDOException $e) {
                error_log("Failed processing pending email queue: " . $e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Send email using SMTP (via SMTPMailer class)
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $headers
     * @return bool
     */
    private function sendEmail(string $to, string $subject, string $body, array $headers = []): bool
    {
        try {
            if (class_exists(SMTPMailer::class)) {
                $mailer = new SMTPMailer();
                return $mailer->send($to, $subject, $body, $headers);
            }
            if (class_exists('SMTPMailer')) {
                $class = 'SMTPMailer';
                $mailer = new $class();
                return $mailer->send($to, $subject, $body, $headers);
            }
            error_log("[EmailQueue] SMTPMailer not found, fallback is disabled. Email not sent.");
            return false;
        } catch (Throwable $e) {
            error_log("[EmailQueue] Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark email as successfully sent
     *
     * @param int $queueId
     * @return bool
     */
    private function markAsSent(int $queueId): bool
    {
        $status = 'sent';

        $sql = "UPDATE `" . $this->tableName . "` 
                SET status = ?, sent_at = NOW() 
                WHERE id = ?";

        if ($this->isMysqli) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("si", $status, $queueId);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } else {
            try {
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([$status, $queueId]);
            } catch (PDOException $e) {
                error_log("Failed to mark email as sent: " . $e->getMessage());
                return false;
            }
        }
    }

    /**
     * Increment retry count for failed email
     *
     * @param int $queueId
     * @return bool
     */
    private function incrementRetry(int $queueId): bool
    {
        $sql = "UPDATE `" . $this->tableName . "` 
                SET retries = retries + 1, 
                    updated_at = NOW(),
                    status = IF(retries >= max_retries - 1, 'failed', 'pending')
                WHERE id = ?";

        if ($this->isMysqli) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("i", $queueId);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } else {
            try {
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([$queueId]);
            } catch (PDOException $e) {
                error_log("Failed to increment retry: " . $e->getMessage());
                return false;
            }
        }
    }

    /**
     * Get queue statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(IF(status = 'pending', 1, 0)) as pending,
                    SUM(IF(status = 'sent', 1, 0)) as sent,
                    SUM(IF(status = 'failed', 1, 0)) as failed
                 FROM `" . $this->tableName . "`";

        if ($this->isMysqli) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result ? ($result->fetch_assoc() ?: []) : [];
            $stmt->close();
            return $stats;
        } else {
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (PDOException $e) {
                error_log("Failed to get email queue stats: " . $e->getMessage());
                return [];
            }
        }
    }
}

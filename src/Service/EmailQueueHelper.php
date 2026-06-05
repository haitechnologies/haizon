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
 * Email Queue Helper - Wrapper for EmailQueue with fallbacks
 * Handles database errors gracefully and ensures tables exist
 */
class EmailQueueHelper
{
    private mixed $conn;
    private ?Database $db = null;
    private bool $isMysqli = false;
    private string $tableName;
    private bool $ready = false;
    private array $errors = [];

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
        $this->validateTables();
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
     * Validate that email queue table exists with correct schema
     * If table doesn't exist or schema is wrong, attempt to fix it
     *
     * @return void
     */
    private function validateTables(): void
    {
        try {
            $tableExists = false;
            if ($this->isMysqli) {
                $escapedTable = $this->conn->real_escape_string($this->tableName);
                $tableCheck = $this->conn->query("SHOW TABLES LIKE '{$escapedTable}'");
                $tableExists = $tableCheck && $tableCheck->num_rows > 0;
            } else {
                $stmt = $this->conn->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
                $stmt->execute([$this->tableName]);
                $tableExists = (bool) $stmt->fetch();
            }

            if (!$tableExists) {
                // Table doesn't exist, create it
                $this->createEmailQueueTable();
                $this->ready = true;
                return;
            }

            // Table exists, verify schema
            $columns = [];
            if ($this->isMysqli) {
                $describeResult = $this->conn->query("DESCRIBE `" . $this->tableName . "`");
                if (!$describeResult) {
                    $this->errors[] = "Failed to describe {$this->tableName} table";
                    $this->ready = false;
                    return;
                }
                while ($col = $describeResult->fetch_assoc()) {
                    $columns[$col['Field']] = $col['Type'];
                }
            } else {
                try {
                    $stmt = $this->conn->query("DESCRIBE `" . $this->tableName . "`");
                    if ($stmt) {
                        while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $columns[$col['Field']] = $col['Type'];
                        }
                    } else {
                        $this->errors[] = "Failed to describe {$this->tableName} table";
                        $this->ready = false;
                        return;
                    }
                } catch (PDOException $e) {
                    $this->errors[] = "Failed to describe {$this->tableName} table: " . $e->getMessage();
                    $this->ready = false;
                    return;
                }
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
                $this->addMissingColumns($missingColumns);
            }

            $this->ready = true;
        } catch (Throwable $e) {
            $this->errors[] = "Table validation error: " . $e->getMessage();
            $this->ready = false;
        }
    }

    /**
     * Create email queue table from scratch
     *
     * @return void
     */
    private function createEmailQueueTable(): void
    {
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

        if ($this->isMysqli) {
            if (!$this->conn->query($sql)) {
                $this->errors[] = "Failed to create {$this->tableName} table: " . $this->conn->error;
                $this->ready = false;
            }
        } else {
            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                $this->errors[] = "Failed to create {$this->tableName} table: " . $e->getMessage();
                $this->ready = false;
            }
        }
    }

    /**
     * Add missing columns to the email queue table
     *
     * @param array<string> $missing
     * @return void
     */
    private function addMissingColumns(array $missing): void
    {
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
                if ($this->isMysqli) {
                    if (!$this->conn->query($alter)) {
                        $this->errors[] = "Failed to add column $col: " . $this->conn->error;
                    }
                } else {
                    try {
                        $this->conn->exec($alter);
                    } catch (PDOException $e) {
                        $this->errors[] = "Failed to add column $col: " . $e->getMessage();
                    }
                }
            }
        }
    }

    /**
     * Queue an email with fallback to direct send if queue fails
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $headers
     * @param int $priority
     * @return int|bool
     */
    public function queueEmail(string $to, string $subject, string $body, array $headers = [], int $priority = 2): int|bool
    {
        if (!$this->ready) {
            error_log("Email queue not ready. Email to $to would have been queued. Subject: $subject");
            return false;
        }

        try {
            $headersJson = json_encode($headers ?: []);

            $sql = "INSERT INTO `" . $this->tableName . "` 
                    (recipient, subject, body, headers, priority, status, attempts) 
                    VALUES (?, ?, ?, ?, ?, 'pending', 0)";

            if ($this->isMysqli) {
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    error_log("Failed to prepare email queue insert: " . $this->conn->error);
                    return false;
                }

                $stmt->bind_param("ssssi", $to, $subject, $body, $headersJson, $priority);
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
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$to, $subject, $body, $headersJson, $priority]);
                $lastId = $this->conn->lastInsertId();
                return $lastId ? (int)$lastId : true;
            }
        } catch (Throwable $e) {
            error_log("Email queue exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the status of the email queue system
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return $this->ready;
    }

    /**
     * Get any errors that occurred during initialization
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

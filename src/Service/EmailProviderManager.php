<?php

declare(strict_types=1);

namespace App\Service;

use mysqli;
use PDO;
use PDOException;
use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Exception;
use Throwable;

/**
 * Email Provider Manager - Database-Driven Email Configuration
 * Loads SMTP credentials from erp_email_providers table with encryption support
 */
class EmailProviderManager
{
    private mixed $conn;
    private ?Database $db = null;
    private bool $isMysqli = false;
    private string $tableName;
    private string $emailHistoryTable;
    private array $cache = [];
    private static ?string $encryption_key = null;
    private static string $cipher = 'AES-256-CBC';

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

        $this->tableName = class_exists('DB') && defined('DB::EMAIL_PROVIDERS') ? (string)constant('DB::EMAIL_PROVIDERS') : 'erp_email_providers';
        $this->emailHistoryTable = class_exists('DB') && defined('DB::EMAIL_HISTORY') ? (string)constant('DB::EMAIL_HISTORY') : 'erp_email_history';

        // Load encryption key from environment if not already set
        if (!self::$encryption_key) {
            self::$encryption_key = getenv('EMAIL_ENCRYPTION_KEY') ?: null;
        }
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
     * Get email provider by email address
     *
     * @param string $email
     * @return array|null Provider configuration with decrypted password
     */
    public function getByEmail(string $email): ?array
    {
        // Check cache first
        if (isset($this->cache[$email])) {
            return $this->cache[$email];
        }

        $sql = "SELECT * FROM `" . $this->tableName . "` 
                WHERE email = ? AND is_active = 1 
                LIMIT 1";

        $provider = null;
        if ($this->isMysqli) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("[EmailProviderManager] Prepare failed: " . $this->conn->error);
                return null;
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $provider = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        } else {
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$email]);
                $provider = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (PDOException $e) {
                error_log("[EmailProviderManager] PDO query failed: " . $e->getMessage());
                return null;
            }
        }

        if ($provider) {
            $provider = $this->decryptProvider($provider);
            $this->cache[$email] = $provider;
        }

        return $provider;
    }

    /**
     * Get email provider by ID
     *
     * @param int $id
     * @return array|null Provider configuration with decrypted password
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $cacheKey = 'id:' . $id;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $sql = "SELECT * FROM `" . $this->tableName . "`
                WHERE id = ? AND is_active = 1
                LIMIT 1";

        $provider = null;
        if ($this->isMysqli) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("[EmailProviderManager] Prepare failed in getById: " . $this->conn->error);
                return null;
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $provider = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        } else {
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$id]);
                $provider = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (PDOException $e) {
                error_log("[EmailProviderManager] PDO query failed in getById: " . $e->getMessage());
                return null;
            }
        }

        if ($provider) {
            $provider = $this->decryptProvider($provider);
            $this->cache[$cacheKey] = $provider;
        }

        return $provider;
    }

    /**
     * Decrypt provider password if encrypted
     *
     * @param array $provider
     * @return array Provider with decrypted password
     */
    private function decryptProvider(array $provider): array
    {
        if (!empty($provider['smtp_password_encrypted']) && !empty($provider['encryption_iv']) && self::$encryption_key) {
            try {
                $decrypted = openssl_decrypt(
                    (string)$provider['smtp_password_encrypted'],
                    self::$cipher,
                    (string)hex2bin(self::$encryption_key),
                    0,
                    (string)hex2bin((string)$provider['encryption_iv'])
                );

                if ($decrypted !== false) {
                    $provider['smtp_password_decrypted'] = $decrypted;
                    $provider['smtp_password'] = $decrypted; // Override plaintext with decrypted
                } else {
                    error_log("Failed to decrypt password for: " . $provider['email']);
                    $provider['smtp_password_decrypted'] = $provider['smtp_password'];
                }
            } catch (Throwable $e) {
                error_log("Decryption error for {$provider['email']}: " . $e->getMessage());
                $provider['smtp_password_decrypted'] = $provider['smtp_password'];
            }
        } else {
            $provider['smtp_password_decrypted'] = $provider['smtp_password'];
        }

        return $provider;
    }

    /**
     * Get email provider by purpose/context
     *
     * @param string $purpose The purpose/context
     * @return array|null Provider configuration
     */
    public function getForPurpose(string $purpose = 'system'): ?array
    {
        $purpose = strtolower($purpose);

        $purpose_map = [
            'system' => defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@haizon.com',
            'support' => defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : 'support@haizon.com',
            'sales' => defined('SALES_EMAIL') ? SALES_EMAIL : 'sales@haizon.com',
            'notifications' => defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@haizon.com',
            'register' => defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@haizon.com',
            'verify' => defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@haizon.com',
            'reset' => defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@haizon.com',
            'contact' => defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : 'support@haizon.com',
            'ticket' => defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : 'support@haizon.com',
            'inquiry' => defined('SALES_EMAIL') ? SALES_EMAIL : 'sales@haizon.com'
        ];

        $email = $purpose_map[$purpose] ?? (defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@haizon.com');
        return $this->getByEmail($email);
    }

    /**
     * Get default (primary) email provider
     *
     * @return array|null
     */
    public function getDefault(): ?array
    {
        if (isset($this->cache['default'])) {
            return $this->cache['default'];
        }

        $sql = "SELECT * FROM `" . $this->tableName . "` 
                WHERE is_primary = 1 AND is_active = 1 
                ORDER BY created_at ASC LIMIT 1";

        $provider = null;
        if ($this->isMysqli) {
            $result = $this->conn->query($sql);
            if (!$result) {
                error_log("[EmailProviderManager] Query failed: " . $this->conn->error);
                return null;
            }
            $provider = $result->fetch_assoc();
        } else {
            try {
                $stmt = $this->conn->query($sql);
                $provider = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: null) : null;
            } catch (PDOException $e) {
                error_log("[EmailProviderManager] PDO query failed: " . $e->getMessage());
                return null;
            }
        }

        if ($provider) {
            $provider = $this->decryptProvider($provider);
            $this->cache['default'] = $provider;
        }

        return $provider;
    }

    /**
     * Get all active email providers
     *
     * @return array
     */
    public function getAllActive(): array
    {
        $sql = "SELECT * FROM `" . $this->tableName . "` 
                WHERE is_active = 1 
                ORDER BY is_primary DESC, created_at ASC";

        $providers = [];
        if ($this->isMysqli) {
            $result = $this->conn->query($sql);
            if (!$result) {
                error_log("[EmailProviderManager] getAllActive failed: " . $this->conn->error);
                return [];
            }
            while ($row = $result->fetch_assoc()) {
                $providers[] = $this->decryptProvider($row);
            }
        } else {
            try {
                $stmt = $this->conn->query($sql);
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $providers[] = $this->decryptProvider($row);
                    }
                }
            } catch (PDOException $e) {
                error_log("[EmailProviderManager] PDO query failed: " . $e->getMessage());
            }
        }

        return $providers;
    }

    /**
     * Get an active provider that still has daily quota remaining today.
     * Prefers primary providers; orders by most remaining quota.
     *
     * @param array<int> $excludeIds Provider IDs to skip
     * @return array|null Provider config with decrypted password, or null if all are exhausted
     */
    public function getAvailableWithQuota(array $excludeIds = []): ?array
    {
        $excludeClause = '';
        if (!empty($excludeIds)) {
            $safeIds = implode(',', array_map('intval', $excludeIds));
            $excludeClause = " AND p.id NOT IN ($safeIds)";
        }

        $sql = "
            SELECT p.*,
                   COALESCE(h.sent_today, 0) AS sent_today,
                   (CASE WHEN p.daily_limit > 0 THEN p.daily_limit ELSE 100 END) AS effective_limit,
                   (CASE WHEN p.daily_limit > 0 THEN p.daily_limit ELSE 100 END)
                   - COALESCE(h.sent_today, 0) AS quota_remaining
            FROM `{$this->tableName}` p
            LEFT JOIN (
                SELECT provider_id, COUNT(*) AS sent_today
                FROM `{$this->emailHistoryTable}`
                WHERE status = 'sent'
                  AND DATE(COALESCE(sent_at, created_at)) = CURDATE()
                GROUP BY provider_id
            ) h ON h.provider_id = p.id
            WHERE p.is_active = 1
              AND COALESCE(h.sent_today, 0) < (CASE WHEN p.daily_limit > 0 THEN p.daily_limit ELSE 100 END)
              {$excludeClause}
            ORDER BY p.is_primary DESC, quota_remaining DESC
            LIMIT 1
        ";

        $provider = null;
        if ($this->isMysqli) {
            $result = $this->conn->query($sql);
            if (!$result) {
                error_log('[EmailProviderManager] getAvailableWithQuota failed: ' . $this->conn->error);
                return null;
            }
            $provider = $result->fetch_assoc();
        } else {
            try {
                $stmt = $this->conn->query($sql);
                $provider = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: null) : null;
            } catch (PDOException $e) {
                error_log('[EmailProviderManager] PDO query failed: ' . $e->getMessage());
                return null;
            }
        }

        if (!$provider) {
            return null; // All providers exhausted for today
        }

        return $this->decryptProvider($provider);
    }

    /**
     * Get statistics about email providers
     *
     * @return array|null Stats (total, active, primary)
     */
    public function getStats(): ?array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_primary=1 THEN 1 ELSE 0 END) as primary_count
                 FROM `" . $this->tableName . "`";

        if ($this->isMysqli) {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_assoc() : null;
        } else {
            try {
                $stmt = $this->conn->query($sql);
                return $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: null) : null;
            } catch (PDOException $e) {
                error_log('[EmailProviderManager] PDO query failed: ' . $e->getMessage());
                return null;
            }
        }
    }

    /**
     * Test SMTP connection for a provider
     *
     * @param string $email
     * @return array Result array with 'success' and 'message'
     */
    public function testConnection(string $email): array
    {
        $provider = $this->getByEmail($email);

        if (!$provider) {
            return ['success' => false, 'message' => 'Provider not found'];
        }

        if (!class_exists('SMTPMailer')) {
            return ['success' => false, 'message' => 'SMTPMailer class not available'];
        }

        try {
            $mailer = new SMTPMailer();
            return $mailer->testConnection(
                (string)$provider['smtp_host'],
                (int)$provider['smtp_port'],
                (string)$provider['email'],
                (string)$provider['smtp_password_decrypted'],
                (string)$provider['email_encryption']
            );
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

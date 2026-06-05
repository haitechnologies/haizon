<?php
/**
 * Email Provider Manager - Database-Driven Email Configuration
 * Loads SMTP credentials from erp_email_providers table with encryption support
 * 
 * Passwords are encrypted with AES-256-CBC and decrypted on-the-fly
 * Uses unique IVs (Initialization Vectors) per encrypted password
 * 
 * @package HAI\Email
 * @version 2.0
 * @date March 6, 2026
 */

class EmailProviderManager {
    private $conn;
    private $tableName;
    private $emailHistoryTable;
    private $cache = [];
    private static $encryption_key = null;
    private static $cipher = 'AES-256-CBC';
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->tableName = class_exists('DB') ? DB::EMAIL_PROVIDERS : 'erp_email_providers';
        $this->emailHistoryTable = class_exists('DB') ? DB::EMAIL_HISTORY : 'erp_email_history';
        // Load encryption key from environment if not already set
        if (!self::$encryption_key) {
            self::$encryption_key = getenv('EMAIL_ENCRYPTION_KEY');
        }
    }
    
    /**
     * Get email provider by email address
     * @param string $email
     * @return array|null Provider configuration with decrypted password
     */
    public function getByEmail($email) {
        // Check cache first
        if (isset($this->cache[$email])) {
            return $this->cache[$email];
        }
        
        $stmt = $this->conn->prepare(
            "SELECT * FROM `" . $this->tableName . "` 
             WHERE email = ? AND is_active = 1 
             LIMIT 1"
        );
        
        if (!$stmt) {
            error_log("[EmailProviderManager] Prepare failed: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $provider = $result->fetch_assoc();
        $stmt->close();
        
        if ($provider) {
            $provider = $this->decryptProvider($provider);
            $this->cache[$email] = $provider;
        }
        
        return $provider;
    }

    /**
     * Get email provider by ID
     * @param int $id
     * @return array|null Provider configuration with decrypted password
     */
    public function getById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        $cacheKey = 'id:' . $id;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $stmt = $this->conn->prepare(
            "SELECT * FROM `" . $this->tableName . "`
             WHERE id = ? AND is_active = 1
             LIMIT 1"
        );

        if (!$stmt) {
            error_log("[EmailProviderManager] Prepare failed in getById: " . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $provider = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($provider) {
            $provider = $this->decryptProvider($provider);
            $this->cache[$cacheKey] = $provider;
        }

        return $provider;
    }
    
    /**
     * Decrypt provider password if encrypted
     * @param array $provider
     * @return array Provider with decrypted password
     */
    private function decryptProvider($provider) {
        // If encrypted password exists, decrypt it
        if (!empty($provider['smtp_password_encrypted']) && !empty($provider['encryption_iv']) && self::$encryption_key) {
            try {
                $decrypted = openssl_decrypt(
                    $provider['smtp_password_encrypted'],
                    self::$cipher,
                    hex2bin(self::$encryption_key),
                    0,
                    hex2bin($provider['encryption_iv'])
                );
                
                if ($decrypted !== false) {
                    // Use decrypted password
                    $provider['smtp_password_decrypted'] = $decrypted;
                    $provider['smtp_password'] = $decrypted; // Override plaintext with decrypted
                } else {
                    error_log("Failed to decrypt password for: " . $provider['email']);
                    // Fallback to plaintext
                    $provider['smtp_password_decrypted'] = $provider['smtp_password'];
                }
            } catch (Exception $e) {
                error_log("Decryption error for {$provider['email']}: " . $e->getMessage());
                // Fallback to plaintext
                $provider['smtp_password_decrypted'] = $provider['smtp_password'];
            }
        } else {
            // No encryption, use plaintext
            $provider['smtp_password_decrypted'] = $provider['smtp_password'];
        }
        
        return $provider;
    }
    
    /**
     * Get email provider by purpose/context
     * 
     * Routes to appropriate email based on context:
    * - 'system' → noreply@haipulse.com (System notifications, registration)
    * - 'support' → support@haipulse.com (Support, contact form, tickets)
    * - 'sales' → sales@haipulse.com (Sales inquiries)
     * 
     * @param string $purpose The purpose/context
     * @return array|null Provider configuration
     */
    public function getForPurpose($purpose = 'system') {
        $purpose = strtolower($purpose);
        
        // Purpose to email mapping
        $purpose_map = [
            'system' => 'noreply@haipulse.com',
            'support' => 'support@haipulse.com',
            'sales' => 'sales@haipulse.com',
            'notifications' => 'noreply@haipulse.com',
            'register' => 'noreply@haipulse.com',
            'verify' => 'noreply@haipulse.com',
            'reset' => 'noreply@haipulse.com',
            'contact' => 'support@haipulse.com',
            'ticket' => 'support@haipulse.com',
            'inquiry' => 'sales@haipulse.com'
        ];
        
        $email = isset($purpose_map[$purpose]) ? $purpose_map[$purpose] : 'noreply@haipulse.com';
        return $this->getByEmail($email);
    }
    
    /**
     * Get default (primary) email provider
     * @return array|null
     */
    public function getDefault() {
        // Check cache
        if (isset($this->cache['default'])) {
            return $this->cache['default'];
        }
        
        // Query for primary provider
        $result = $this->conn->query(
            "SELECT * FROM `" . $this->tableName . "` 
             WHERE is_primary = 1 AND is_active = 1 
             ORDER BY created_at ASC LIMIT 1"
        );
        
        if (!$result) {
            error_log("[EmailProviderManager] Query failed: " . $this->conn->error);
            return null;
        }
        
        $provider = $result->fetch_assoc();
        if ($provider) {
            $provider = $this->decryptProvider($provider);
            $this->cache['default'] = $provider;
        }
        
        return $provider;
    }
    
    /**
     * Get all active email providers
     * @return array
     */
    public function getAllActive() {
        $result = $this->conn->query(
            "SELECT * FROM `" . $this->tableName . "` 
             WHERE is_active = 1 
             ORDER BY is_primary DESC, created_at ASC"
        );
        
        if (!$result) {
            error_log("[EmailProviderManager] getAllActive failed: " . $this->conn->error);
            return [];
        }
        
        $providers = [];
        while ($row = $result->fetch_assoc()) {
            $providers[] = $this->decryptProvider($row);
        }
        
        return $providers;
    }
    
    /**
     * Get an active provider that still has daily quota remaining today.
     * Prefers primary providers; orders by most remaining quota.
     * Optionally excludes specific provider IDs (e.g. already-tried ones).
     *
     * @param array $excludeIds Provider IDs to skip
     * @return array|null Provider config with decrypted password, or null if all are exhausted
     */
    public function getAvailableWithQuota(array $excludeIds = []) {
        $excludeClause = '';
        if (!empty($excludeIds)) {
            $safeIds = implode(',', array_map('intval', $excludeIds));
            $excludeClause = " AND p.id NOT IN ($safeIds)";
        }

        // Join with sent-today counts; only return providers whose sent_today < daily_limit
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

        $result = $this->conn->query($sql);
        if (!$result) {
            error_log('[EmailProviderManager] getAvailableWithQuota failed: ' . $this->conn->error);
            return null;
        }

        $provider = $result->fetch_assoc();
        if (!$provider) {
            return null; // All providers exhausted for today
        }

        return $this->decryptProvider($provider);
    }

    /**
     * Get statistics about email providers
     * @return array Stats (total, active, primary)
     */
    public function getStats() {
        $result = $this->conn->query(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_primary=1 THEN 1 ELSE 0 END) as primary_count
             FROM `" . $this->tableName . "`"
        );
        
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Test SMTP connection for a provider
     * @param string $email
     * @return array Result array with 'success' and 'message'
     */
    public function testConnection($email) {
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
                $provider['smtp_host'],
                $provider['smtp_port'],
                $provider['email'],
                $provider['smtp_password_decrypted'],
                $provider['email_encryption']
            );
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

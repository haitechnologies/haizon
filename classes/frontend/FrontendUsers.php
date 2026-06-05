<?php
/**
 * Frontend Users Data Access Class
 * 
 * Handles all database operations for public-facing user accounts.
 * Supports registration, authentication, favorites, and user profiles.
 * 
 * NOTE: This class requires the hai_frontend_users table to exist.
 * See FRONTEND_USERS_DECISION.md for the SQL schema.
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../SMTPMailer.php';

class FrontendUsers {
    
    private $mysqli;
    private $table = DB::FRONTEND_USERS;
    private $favoritesTable = DB::FRONTEND_USER_FAVORITES;
    private $searchesTable = DB::SEARCHES;
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }
    
    /**
     * Register a new user
     * 
     * @param array $data User data (email, password, full_name, mobile)
     * @return int|false User ID on success, false on failure
     */
    public function register($data) {
        // Validate required fields
        $required = ['email', 'password', 'full_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                error_log("FrontendUsers::register - Missing required field: {$field}");
                return false;
            }
        }
        
        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            error_log("FrontendUsers::register - Email already exists: {$data['email']}");
            return false;
        }
        
        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Generate email verification token
        $verificationToken = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO `{$this->table}` 
                (email, password, full_name, mobile, email_verification_token, is_active, publish, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, 1, NOW())";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::register - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $mobile = $data['mobile'] ?? null;
        $stmt->bind_param("sssss", 
            $data['email'],
            $passwordHash,
            $data['full_name'],
            $mobile,
            $verificationToken
        );
        
        $success = $stmt->execute();
        $userId = $success ? $stmt->insert_id : false;
        $stmt->close();
        
        // Send verification email
        if ($userId) {
            $this->sendVerificationEmail($data['email'], $data['full_name'], $verificationToken);
        }
        
        return $userId;
    }
    
    /**
     * Authenticate user login
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @return array|false User data on success, false on failure
     */
    public function login($email, $password) {
        $sql = "SELECT * FROM `{$this->table}` WHERE email = ? AND is_active = 1 AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::login - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Verify password
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Remove password from returned data
        unset($user['password']);
        
        return $user;
    }
    
    /**
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function getById($id) {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = ? LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::getById - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Remove password
        if ($user) {
            unset($user['password']);
        }
        
        return $user;
    }
    
    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM `{$this->table}` WHERE email = ? LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::getByEmail - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Remove password
        if ($user) {
            unset($user['password']);
        }
        
        return $user;
    }
    
    /**
     * Check if email exists
     * 
     * @param string $email Email to check
     * @return bool True if exists
     */
    public function emailExists($email) {
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE email = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return ($row['count'] ?? 0) > 0;
    }
    
    /**
     * Update user profile
     * 
     * @param int $id User ID
     * @param array $data Fields to update
     * @return bool Success status
     */
    public function updateProfile($id, $data) {
        $allowedFields = ['full_name', 'mobile', 'profile_photo'];
        $sets = [];
        $params = [];
        $types = "";
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $sets[] = "`{$field}` = ?";
                $params[] = $data[$field];
                $types .= "s";
            }
        }
        
        if (empty($sets)) {
            return false;
        }
        
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::updateProfile - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Update password
     * 
     * @param int $id User ID
     * @param string $newPassword New plain text password
     * @return bool Success status
     */
    public function updatePassword($id, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE `{$this->table}` SET password = ?, updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::updatePassword - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("si", $passwordHash, $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Verify email with token
     * 
     * @param string $token Verification token
     * @return bool Success status
     */
    public function verifyEmail($token) {
        $sql = "UPDATE `{$this->table}` 
                SET email_verified = 1, email_verification_token = NULL, updated_at = NOW() 
                WHERE email_verification_token = ? AND is_active = 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::verifyEmail - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("s", $token);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $success && $affected > 0;
    }
    
    /**
     * Generate password reset token
     * 
     * @param string $email User email
     * @return string|false Reset token on success, false on failure
     */
    public function generatePasswordResetToken($email) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "UPDATE `{$this->table}` 
                SET password_reset_token = ?, password_reset_expires = ?, updated_at = NOW() 
                WHERE email = ? AND is_active = 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::generatePasswordResetToken - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("sss", $token, $expires, $email);
        $success = $stmt->execute();
        $stmt->close();
        
        // Send password reset email
        if ($success) {
            $user = $this->getByEmail($email);
            if ($user) {
                $this->sendPasswordResetEmail($email, $user['full_name'], $token);
            }
        }
        
        return $success ? $token : false;
    }
    
    /**
     * Reset password with token
     * 
     * @param string $token Reset token
     * @param string $newPassword New plain text password
     * @return bool Success status
     */
    public function resetPassword($token, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');
        
        $sql = "UPDATE `{$this->table}` 
                SET password = ?, password_reset_token = NULL, password_reset_expires = NULL, updated_at = NOW() 
                WHERE password_reset_token = ? AND password_reset_expires > ? AND is_active = 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::resetPassword - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("sss", $passwordHash, $token, $now);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $success && $affected > 0;
    }
    
    /**
     * Update last login timestamp
     * 
     * @param int $id User ID
     * @return bool Success status
     */
    private function updateLastLogin($id) {
        $sql = "UPDATE `{$this->table}` SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Add company to user favorites
     * 
     * @param int $userId User ID
     * @param int $companyId Company ID
     * @return bool Success status
     */
    public function addFavorite($userId, $companyId) {
        $sql = "INSERT IGNORE INTO `{$this->favoritesTable}` (user_id, company_id, created_at) VALUES (?, ?, NOW())";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::addFavorite - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("ii", $userId, $companyId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Remove company from user favorites
     * 
     * @param int $userId User ID
     * @param int $companyId Company ID
     * @return bool Success status
     */
    public function removeFavorite($userId, $companyId) {
        $sql = "DELETE FROM `{$this->favoritesTable}` WHERE user_id = ? AND company_id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::removeFavorite - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("ii", $userId, $companyId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get user's favorite companies
     * 
     * @param int $userId User ID
     * @return array Array of company IDs
     */
    public function getFavorites($userId) {
        $sql = "SELECT company_id FROM `{$this->favoritesTable}` WHERE user_id = ? ORDER BY created_at DESC";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("FrontendUsers::getFavorites - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $favorites = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return array_column($favorites, 'company_id');
    }
    
    /**
     * Check if company is in user's favorites
     * 
     * @param int $userId User ID
     * @param int $companyId Company ID
     * @return bool True if favorited
     */
    public function isFavorite($userId, $companyId) {
        $sql = "SELECT COUNT(*) as count FROM `{$this->favoritesTable}` WHERE user_id = ? AND company_id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ii", $userId, $companyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return ($row['count'] ?? 0) > 0;
    }
    
    /**
     * Send email using PHPMailer
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body HTML email body
     * @return bool Success status
     */
    private function sendEmail($to, $subject, $body) {
        try {
            $mailer = new SMTPMailer();
            $sent = $mailer->send($to, $subject, $body, [
                'from_name' => 'UAE Business Directory',
            ]);
            if (!$sent) {
                error_log("FrontendUsers::sendEmail - Error: " . $mailer->getLastError());
            }
            return $sent;

        } catch (\Exception $e) {
            error_log("FrontendUsers::sendEmail - Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send verification email to new user
     * 
     * @param string $email User email
     * @param string $fullName User full name
     * @param string $token Verification token
     * @return bool Success status
     */
    private function sendVerificationEmail($email, $fullName, $token) {
        $verificationUrl = "http://" . $_SERVER['HTTP_HOST'] . "/verify-email?token=" . $token;
        
        $subject = "Verify Your Email - UAE Business Directory";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #0066cc; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to UAE Business Directory</h1>
                </div>
                <div class='content'>
                    <p>Dear {$fullName},</p>
                    
                    <p>Thank you for registering with UAE Business Directory! To complete your registration and start exploring thousands of UAE businesses, please verify your email address.</p>
                    
                    <p style='text-align: center;'>
                        <a href='{$verificationUrl}' class='button'>Verify Email Address</a>
                    </p>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #0066cc;'>{$verificationUrl}</p>
                    
                    <p>This verification link will expire in 24 hours.</p>
                    
                    <p>If you did not create this account, please ignore this email.</p>
                    
                    <p>Best regards,<br>UAE Business Directory Team</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " UAE Business Directory. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($email, $subject, $body);
    }
    
    /**
     * Send password reset email
     * 
     * @param string $email User email
     * @param string $fullName User full name
     * @param string $token Reset token
     * @return bool Success status
     */
    private function sendPasswordResetEmail($email, $fullName, $token) {
        $resetUrl = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=" . $token;
        
        $subject = "Reset Your Password - UAE Business Directory";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #0066cc; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                </div>
                <div class='content'>
                    <p>Dear {$fullName},</p>
                    
                    <p>We received a request to reset the password for your UAE Business Directory account.</p>
                    
                    <p style='text-align: center;'>
                        <a href='{$resetUrl}' class='button'>Reset Password</a>
                    </p>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #0066cc;'>{$resetUrl}</p>
                    
                    <div class='warning'>
                        <strong>Important:</strong> This password reset link will expire in 1 hour for security reasons.
                    </div>
                    
                    <p>If you did not request a password reset, please ignore this email or contact us if you have concerns about your account security.</p>
                    
                    <p>Best regards,<br>UAE Business Directory Team</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " UAE Business Directory. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($email, $subject, $body);
    }
}

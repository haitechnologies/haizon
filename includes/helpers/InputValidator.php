<?php
/**
 * FrontendInputValidator - Centralized input validation utility
 * Consolidates validation functions to eliminate code duplication
 * 
 * Replaces scattered validation logic across:
 *   listings.php, search.php, add-business.php, register.php, contact.php
 * 
 * Usage:
 *   FrontendInputValidator::isMeaningfulSearchTerm($keyword)
 *   FrontendInputValidator::isValidEmail($email)
 *   FrontendInputValidator::isValidPhone($phone)
 */

class FrontendInputValidator {
    
    /**
     * Check if search term is meaningful (not empty/too short)
     * Used in: listings.php, search.php, add-business.php
     * 
     * @param string $term Search term to validate
     * @param int $minLength Minimum character length (default: 2)
     * @return bool True if term is meaningful
     */
    public static function isMeaningfulSearchTerm($term, $minLength = 2) {
        // Normalize whitespace
        $normalized = trim((string)$term);
        
        // Empty check
        if ($normalized === '') {
            return false;
        }
        
        // Remove non-alphanumeric to get actual content length
        $alnumOnly = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalized);
        
        // Get string length (UTF-8 safe)
        $length = function_exists('mb_strlen') 
            ? mb_strlen($alnumOnly, 'UTF-8') 
            : strlen($alnumOnly);
        
        return $length >= $minLength;
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email to validate
     * @return bool True if email format is valid
     */
    public static function isValidEmail($email) {
        $email = trim((string)$email);
        
        // Use PHP's filter_var for basic validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Optional: Additional checks for domain
        list($local, $domain) = explode('@', $email);
        
        // Check domain has at least one dot
        if (strpos($domain, '.') === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone number format (flexible, accepts various formats)
     * Accepts: +971501234567, 050-123-4567, (050) 123-4567, etc.
     * 
     * @param string $phone Phone number to validate
     * @return bool True if phone format is valid
     */
    public static function isValidPhone($phone) {
        $phone = trim((string)$phone);
        
        if (empty($phone)) {
            return false;
        }
        
        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-\(\)\.]/u', '', $phone);
        
        // Should contain at least 7 digits after cleanup
        $digits = preg_replace('/[^\d]/u', '', $cleaned);
        
        return strlen($digits) >= 7;
    }
    
    /**
     * Validate business/company name
     * 
     * @param string $name Business name to validate
     * @param int $minLength Minimum length (default: 2)
     * @param int $maxLength Maximum length (default: 100)
     * @return bool True if name is valid
     */
    public static function isValidBusinessName($name, $minLength = 2, $maxLength = 100) {
        $name = trim((string)$name);
        $length = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        
        return $length >= $minLength && $length <= $maxLength;
    }
    
    /**
     * Validate URL format (optional: basic or strict)
     * 
     * @param string $url URL to validate
     * @param bool $strict Use strict validation (default: false)
     * @return bool True if URL is valid
     */
    public static function isValidUrl($url, $strict = false) {
        $url = trim((string)$url);
        
        if (empty($url)) {
            return false;
        }
        
        if ($strict) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        } else {
            // Allow URLs without protocol
            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'http://' . $url;
            }
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }
    }
    
    /**
     * Validate password strength
     * Requirements: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
     * 
     * @param string $password Password to validate
     * @return array ['valid' => bool, 'errors' => [string]]
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        $password = (string)$password;
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Sanitize string input (remove HTML tags, trim whitespace)
     * 
     * @param string $input Input to sanitize
     * @return string Sanitized input
     */
    public static function sanitizeString($input) {
        $input = (string)$input;
        $input = strip_tags($input);
        $input = trim($input);
        return $input;
    }
    
    /**
     * Sanitize HTML input (allow safe tags like <b>, <i>, <strong>)
     * 
     * @param string $input HTML input to sanitize
     * @return string Sanitized HTML
     */
    public static function sanitizeHtml($input) {
        $input = (string)$input;
        
        // Define allowed tags
        $allowedTags = '<p><br><strong><b><em><i><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';
        
        $cleaned = strip_tags($input, $allowedTags);
        
        // Remove dangerous attributes
        $cleaned = preg_replace('/ on\w+="[^"]*"/i', '', $cleaned);
        
        return trim($cleaned);
    }
    
    /**
     * Validate text length
     * 
     * @param string $text Text to validate
     * @param int $minLength Minimum length (0 = no minimum)
     * @param int $maxLength Maximum length (0 = no maximum)
     * @return bool True if length is within range
     */
    public static function isValidLength($text, $minLength = 0, $maxLength = 0) {
        $text = (string)$text;
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        
        if ($minLength > 0 && $length < $minLength) {
            return false;
        }
        
        if ($maxLength > 0 && $length > $maxLength) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if input contains only alphanumeric characters
     * 
     * @param string $input Input to validate
     * @param bool $allowSpaces Allow spaces (default: false)
     * @return bool
     */
    public static function isAlphanumeric($input, $allowSpaces = false) {
        $input = (string)$input;
        
        if ($allowSpaces) {
            return preg_match('/^[\p{L}\p{N}\s]+$/u', $input) === 1;
        } else {
            return preg_match('/^[\p{L}\p{N}]+$/u', $input) === 1;
        }
    }
    
    /**
     * Validate UAE phone number specifically
     * Accepts: +971501234567, 050-123-4567, 0501234567
     * 
     * @param string $phone Phone number to validate
     * @return bool True if UAE phone format is valid
     */
    public static function isValidUAEPhone($phone) {
        $phone = trim((string)$phone);
        
        // Accept formats: +971..., 00971..., 0...
        // Must be 10-12 digits total
        
        // Remove common formatting
        $cleaned = preg_replace('/[\s\-\(\)\.]/u', '', $phone);
        
        // Accept +971 format
        if (preg_match('/^\+971\d{9}$/', $cleaned)) {
            return true;
        }
        
        // Accept 00971 format
        if (preg_match('/^00971\d{9}$/', $cleaned)) {
            return true;
        }
        
        // Accept 0 prefix format (leading 0 + 9 digits)
        if (preg_match('/^0\d{9}$/', $cleaned)) {
            return true;
        }
        
        return false;
    }
    
}

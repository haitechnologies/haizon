<?php
/**
 * Input Validation & Sanitization Class
 * 
 * Provides comprehensive validation for all user inputs to prevent:
 * - SQL Injection
 * - XSS attacks
 * - Buffer overflow
 * - Invalid data types
 * - Business logic violations
 * 
 * @package HAI\Security
 * @version 1.0
 * @date February 27, 2026
 */

class InputValidator
{
    /**
     * Validate string input
     * 
     * @param mixed $value Input value
     * @param int $maxLength Maximum string length
     * @param int $minLength Minimum string length
     * @param bool $allowEmpty Allow empty string
     * @return array ['valid' => bool, 'error' => string, 'value' => string]
     */
    public static function string($value, $maxLength = 255, $minLength = 0, $allowEmpty = true)
    {
        if (!$allowEmpty && empty($value)) {
            return [
                'valid' => false,
                'error' => 'This field is required',
                'value' => null
            ];
        }
        
        if ($allowEmpty && empty($value)) {
            return [
                'valid' => true,
                'error' => null,
                'value' => ''
            ];
        }
        
        if (!is_string($value)) {
            return [
                'valid' => false,
                'error' => 'Value must be a string',
                'value' => null
            ];
        }
        
        $length = mb_strlen($value, 'UTF-8');
        
        if ($length < $minLength) {
            return [
                'valid' => false,
                'error' => "Value must be at least $minLength characters",
                'value' => null
            ];
        }
        
        if ($length > $maxLength) {
            return [
                'valid' => false,
                'error' => "Value cannot exceed $maxLength characters",
                'value' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $value
        ];
    }
    
    /**
     * Validate integer input
     * 
     * @param mixed $value Input value
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @param bool $allowNull Allow null value
     * @return array ['valid' => bool, 'error' => string, 'value' => int|null]
     */
    public static function integer($value, $min = PHP_INT_MIN, $max = PHP_INT_MAX, $allowNull = false)
    {
        if ($allowNull && ($value === null || $value === '')) {
            return [
                'valid' => true,
                'error' => null,
                'value' => null
            ];
        }
        
        if (!is_numeric($value)) {
            return [
                'valid' => false,
                'error' => 'Value must be a number',
                'value' => null
            ];
        }
        
        $intValue = (int)$value;
        
        if ((string)$intValue !== (string)$value) {
            return [
                'valid' => false,
                'error' => 'Value must be an integer',
                'value' => null
            ];
        }
        
        if ($intValue < $min) {
            return [
                'valid' => false,
                'error' => "Value must be at least $min",
                'value' => null
            ];
        }
        
        if ($intValue > $max) {
            return [
                'valid' => false,
                'error' => "Value cannot exceed $max",
                'value' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $intValue
        ];
    }
    
    /**
     * Validate float/decimal input
     * 
     * @param mixed $value Input value
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @param int $decimals Maximum decimal places
     * @return array ['valid' => bool, 'error' => string, 'value' => float|null]
     */
    public static function float($value, $min = PHP_FLOAT_MIN, $max = PHP_FLOAT_MAX, $decimals = 2)
    {
        if (!is_numeric($value)) {
            return [
                'valid' => false,
                'error' => 'Value must be a number',
                'value' => null
            ];
        }
        
        $floatValue = (float)$value;
        
        if ($floatValue < $min) {
            return [
                'valid' => false,
                'error' => "Value must be at least $min",
                'value' => null
            ];
        }
        
        if ($floatValue > $max) {
            return [
                'valid' => false,
                'error' => "Value cannot exceed $max",
                'value' => null
            ];
        }
        
        // Round to specified decimal places
        $floatValue = round($floatValue, $decimals);
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $floatValue
        ];
    }
    
    /**
     * Validate email address
     * 
     * @param string $value Email address
     * @param bool $checkDNS Verify domain has MX records
     * @return array ['valid' => bool, 'error' => string, 'value' => string]
     */
    public static function email($value, $checkDNS = false)
    {
        if (empty($value)) {
            return [
                'valid' => false,
                'error' => 'Email address is required',
                'value' => null
            ];
        }
        
        // Remove whitespace
        $value = trim($value);
        
        // Validate email format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => 'Invalid email address format',
                'value' => null
            ];
        }
        
        // Check DNS records (optional)
        if ($checkDNS) {
            $domain = substr(strrchr($value, "@"), 1);
            if (!checkdnsrr($domain, "MX")) {
                return [
                    'valid' => false,
                    'error' => 'Email domain does not exist',
                    'value' => null
                ];
            }
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => strtolower($value)
        ];
    }
    
    /**
     * Validate URL
     * 
     * @param string $value URL
     * @param array $allowedSchemes Allowed URL schemes
     * @return array ['valid' => bool, 'error' => string, 'value' => string]
     */
    public static function url($value, $allowedSchemes = ['http', 'https'])
    {
        if (empty($value)) {
            return [
                'valid' => false,
                'error' => 'URL is required',
                'value' => null
            ];
        }
        
        $value = trim($value);
        
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'error' => 'Invalid URL format',
                'value' => null
            ];
        }
        
        // Check scheme
        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (!in_array($scheme, $allowedSchemes)) {
            return [
                'valid' => false,
                'error' => 'URL scheme not allowed. Allowed: ' . implode(', ', $allowedSchemes),
                'value' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $value
        ];
    }
    
    /**
     * Validate phone number
     * 
     * @param string $value Phone number
     * @param string $format Format pattern (e.g., '/^\+971[0-9]{9}$/' for UAE)
     * @return array ['valid' => bool, 'error' => string, 'value' => string]
     */
    public static function phone($value, $format = null)
    {
        if (empty($value)) {
            return [
                'valid' => false,
                'error' => 'Phone number is required',
                'value' => null
            ];
        }
        
        $value = trim($value);
        
        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $value);
        
        // If format specified, validate against it
        if ($format && !preg_match($format, $cleaned)) {
            return [
                'valid' => false,
                'error' => 'Invalid phone number format',
                'value' => null
            ];
        }
        
        // Basic validation: must be numeric and reasonable length
        if (!preg_match('/^[\+]?[0-9]{7,15}$/', $cleaned)) {
            return [
                'valid' => false,
                'error' => 'Invalid phone number',
                'value' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $cleaned
        ];
    }
    
    /**
     * Validate date
     * 
     * @param string $value Date string
     * @param string $format Expected date format (default Y-m-d)
     * @return array ['valid' => bool, 'error' => string, 'value' => string]
     */
    public static function date($value, $format = 'Y-m-d')
    {
        if (empty($value)) {
            return [
                'valid' => false,
                'error' => 'Date is required',
                'value' => null
            ];
        }
        
        $dateObj = DateTime::createFromFormat($format, $value);
        
        if (!$dateObj || $dateObj->format($format) !== $value) {
            return [
                'valid' => false,
                'error' => "Invalid date format. Expected: $format",
                'value' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $value
        ];
    }
    
    /**
     * Validate filename (no directory traversal)
     * 
     * @param string $value Filename
     * @return array ['valid' => bool, 'error' => string, 'value' => string]
     */
    public static function filename($value)
    {
        if (empty($value)) {
            return [
                'valid' => false,
                'error' => 'Filename is required',
                'value' => null
            ];
        }
        
        // Check for directory traversal attempts
        if (preg_match('/\.\./', $value) || preg_match('/[\/\\\\]/', $value)) {
            return [
                'valid' => false,
                'error' => 'Invalid filename. Directory traversal not allowed',
                'value' => null
            ];
        }
        
        // Check for valid characters
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $value)) {
            return [
                'valid' => false,
                'error' => 'Filename contains invalid characters',
                'value' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $value
        ];
    }
    
    /**
     * Validate enum/choice (value must be in allowed list)
     * 
     * @param mixed $value Value to validate
     * @param array $allowedValues List of allowed values
     * @return array ['valid' => bool, 'error' => string, 'value' => mixed]
     */
    public static function enum($value, $allowedValues)
    {
        if (!in_array($value, $allowedValues, true)) {
            return [
                'valid' => false,
                'error' => 'Invalid value. Allowed values: ' . implode(', ', $allowedValues),
                'value' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $value
        ];
    }
    
    /**
     * Sanitize HTML (strip all tags except allowed)
     * 
     * @param string $value HTML content
     * @param array $allowedTags Allowed HTML tags
     * @return array ['valid' => bool, 'error' => string, 'value' => string]
     */
    public static function html($value, $allowedTags = [])
    {
        if (!is_string($value)) {
            return [
                'valid' => false,
                'error' => 'Value must be a string',
                'value' => null
            ];
        }
        
        if (empty($allowedTags)) {
            // Strip all HTML tags
            $sanitized = strip_tags($value);
        } else {
            // Allow only specified tags
            $sanitized = strip_tags($value, $allowedTags);
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $sanitized
        ];
    }
    
    /**
     * Validate boolean
     * 
     * @param mixed $value Value to validate
     * @return array ['valid' => bool, 'error' => string, 'value' => bool]
     */
    public static function boolean($value)
    {
        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        
        if ($booleanValue === null) {
            return [
                'valid' => false,
                'error' => 'Value must be a boolean',
                'value' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'value' => $booleanValue
        ];
    }
    
    /**
     * Sanitize string (remove special characters)
     * 
     * @param string $value Input string
     * @param string $pattern Regex pattern to keep (default: alphanumeric, underscore, hyphen)
     * @return string Sanitized string
     */
    public static function sanitize($value, $pattern = '/[^a-zA-Z0-9_\-]/')
    {
        return preg_replace($pattern, '', $value);
    }
    
    /**
     * Escape output for HTML (prevent XSS)
     * Alias for htmlspecialchars with secure defaults
     * 
     * @param string $value Value to escape
     * @return string Escaped value
     */
    public static function escape($value)
    {
        if ($value === null) {
            return '';
        }
        
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validate multiple fields at once
     * 
     * @param array $data Associative array of field => value
     * @param array $rules Associative array of field => validation rules
     * @return array ['valid' => bool, 'errors' => array, 'values' => array]
     * 
     * Example:
     * $result = InputValidator::validateMultiple($_POST, [
     *     'email' => ['type' => 'email'],
     *     'age' => ['type' => 'integer', 'min' => 18, 'max' => 120],
     *     'name' => ['type' => 'string', 'maxLength' => 100]
     * ]);
     */
    public static function validateMultiple($data, $rules)
    {
        $errors = [];
        $values = [];
        $allValid = true;
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $type = $rule['type'] ?? 'string';
            
            switch ($type) {
                case 'string':
                    $result = self::string(
                        $value,
                        $rule['maxLength'] ?? 255,
                        $rule['minLength'] ?? 0,
                        $rule['allowEmpty'] ?? true
                    );
                    break;
                    
                case 'integer':
                    $result = self::integer(
                        $value,
                        $rule['min'] ?? PHP_INT_MIN,
                        $rule['max'] ?? PHP_INT_MAX,
                        $rule['allowNull'] ?? false
                    );
                    break;
                    
                case 'float':
                    $result = self::float(
                        $value,
                        $rule['min'] ?? PHP_FLOAT_MIN,
                        $rule['max'] ?? PHP_FLOAT_MAX,
                        $rule['decimals'] ?? 2
                    );
                    break;
                    
                case 'email':
                    $result = self::email($value, $rule['checkDNS'] ?? false);
                    break;
                    
                case 'url':
                    $result = self::url($value, $rule['allowedSchemes'] ?? ['http', 'https']);
                    break;
                    
                case 'phone':
                    $result = self::phone($value, $rule['format'] ?? null);
                    break;
                    
                case 'date':
                    $result = self::date($value, $rule['format'] ?? 'Y-m-d');
                    break;
                    
                case 'enum':
                    $result = self::enum($value, $rule['allowedValues'] ?? []);
                    break;
                    
                case 'boolean':
                    $result = self::boolean($value);
                    break;
                    
                default:
                    $result = [
                        'valid' => false,
                        'error' => "Unknown validation type: $type",
                        'value' => null
                    ];
            }
            
            if (!$result['valid']) {
                $errors[$field] = $result['error'];
                $allValid = false;
            } else {
                $values[$field] = $result['value'];
            }
        }
        
        return [
            'valid' => $allValid,
            'errors' => $errors,
            'values' => $values
        ];
    }
}

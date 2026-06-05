<?php
/**
 * File Upload Security Validator
 * 
 * Provides comprehensive validation for file uploads to prevent:
 * - Arbitrary file upload exploitation
 * - Remote Code Execution (RCE)
 * - Path traversal attacks
 * - MIME type spoofing
 * - File size abuse
 * 
 * @package HAI\Security
 * @version 1.0
 * @date February 27, 2026
 */

class FileUploadValidator
{
    /**
     * Allowed file extensions (whitelist)
     * @var array
     */
    private static $allowedExtensions = [
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'rtf', 'odt', 'ods', 'odp',
        
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
        
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz',
        
        // Other
        'csv', 'xml', 'json'
    ];
    
    /**
     * Allowed MIME types (whitelist)
     * @var array
     */
    private static $allowedMimeTypes = [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/rtf',
        'application/rtf',
        
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        
        // Archives
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
        
        // Other
        'text/csv',
        'application/xml',
        'text/xml',
        'application/json'
    ];
    
    /**
     * Dangerous file extensions (blacklist - double-check)
     * @var array
     */
    private static $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'pht',
        'exe', 'bat', 'cmd', 'com', 'pif',
        'sh', 'bash', 'csh', 'ksh',
        'js', 'vbs', 'wsf', 'wsh',
        'asp', 'aspx', 'jsp', 'jspx',
        'cgi', 'pl', 'py', 'rb',
        'htm', 'html', 'shtml'
    ];
    
    /**
     * Maximum file size in bytes (default 10MB)
     * @var int
     */
    private static $maxFileSize = 10485760; // 10MB
    
    /**
     * Validate uploaded file
     * 
     * @param array $file $_FILES array element
     * @param array $options Additional options (maxSize, allowedTypes, etc.)
     * @return array ['valid' => bool, 'error' => string, 'sanitizedName' => string]
     */
    public static function validate($file, $options = [])
    {
        // Check if file was actually uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return [
                'valid' => false,
                'error' => 'Invalid file upload. File was not uploaded via HTTP POST.',
                'sanitizedName' => null
            ];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => self::getUploadErrorMessage($file['error']),
                'sanitizedName' => null
            ];
        }
        
        // Get original filename
        $originalName = basename($file['name']);
        
        // Get file extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // 1. Check if extension is in dangerous blacklist
        if (in_array($extension, self::$dangerousExtensions)) {
            return [
                'valid' => false,
                'error' => "File type '$extension' is not allowed for security reasons.",
                'sanitizedName' => null
            ];
        }
        
        // 2. Check if extension is in allowed whitelist
        $allowedExts = $options['allowedExtensions'] ?? self::$allowedExtensions;
        if (!in_array($extension, $allowedExts)) {
            return [
                'valid' => false,
                'error' => "File extension '$extension' is not allowed. Allowed types: " . implode(', ', $allowedExts),
                'sanitizedName' => null
            ];
        }
        
        // 3. Check file size
        $maxSize = $options['maxSize'] ?? self::$maxFileSize;
        if ($file['size'] > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . self::formatBytes($maxSize),
                'sanitizedName' => null
            ];
        }
        
        // 4. Validate MIME type
        $mimeValid = self::validateMimeType($file['tmp_name'], $extension);
        if (!$mimeValid['valid']) {
            return [
                'valid' => false,
                'error' => $mimeValid['error'],
                'sanitizedName' => null
            ];
        }
        
        // 5. Check for embedded PHP code in images
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
            if (self::containsPhpCode($file['tmp_name'])) {
                return [
                    'valid' => false,
                    'error' => 'Image file contains suspicious content.',
                    'sanitizedName' => null
                ];
            }
        }
        
        // 6. Generate sanitized filename
        $sanitizedName = self::generateSafeFilename($originalName, $extension);
        
        return [
            'valid' => true,
            'error' => null,
            'sanitizedName' => $sanitizedName,
            'originalName' => $originalName,
            'extension' => $extension,
            'size' => $file['size'],
            'mimeType' => $mimeValid['mimeType']
        ];
    }
    
    /**
     * Validate MIME type of uploaded file
     * 
     * @param string $filePath Path to uploaded file
     * @param string $extension File extension
     * @return array ['valid' => bool, 'error' => string, 'mimeType' => string]
     */
    private static function validateMimeType($filePath, $extension)
    {
        // Get MIME type using finfo
        if (!function_exists('finfo_open')) {
            // Fallback: Skip MIME validation if finfo not available
            return [
                'valid' => true,
                'error' => null,
                'mimeType' => 'unknown'
            ];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        if (!$mimeType) {
            return [
                'valid' => false,
                'error' => 'Unable to determine file MIME type.',
                'mimeType' => null
            ];
        }
        
        // Check if MIME type is in allowed list
        if (!in_array($mimeType, self::$allowedMimeTypes)) {
            return [
                'valid' => false,
                'error' => "MIME type '$mimeType' is not allowed.",
                'mimeType' => $mimeType
            ];
        }
        
        // Verify MIME type matches extension (prevent spoofing)
        $expectedMimes = self::getExpectedMimeTypes($extension);
        if (!empty($expectedMimes) && !in_array($mimeType, $expectedMimes)) {
            return [
                'valid' => false,
                'error' => "File extension '$extension' does not match MIME type '$mimeType'. Possible file type spoofing.",
                'mimeType' => $mimeType
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'mimeType' => $mimeType
        ];
    }
    
    /**
     * Get expected MIME types for a given extension
     * 
     * @param string $extension File extension
     * @return array Expected MIME types
     */
    private static function getExpectedMimeTypes($extension)
    {
        $mimeMap = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'bmp' => ['image/bmp'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'csv' => ['text/csv', 'text/plain'],
            'txt' => ['text/plain'],
            'json' => ['application/json'],
            'xml' => ['application/xml', 'text/xml']
        ];
        
        return $mimeMap[$extension] ?? [];
    }
    
    /**
     * Check if file contains PHP code (for images)
     * 
     * @param string $filePath Path to file
     * @return bool True if PHP code detected
     */
    private static function containsPhpCode($filePath)
    {
        $content = file_get_contents($filePath);
        
        // Check for PHP tags
        $phpPatterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<\?/i',
            '/<script\s+language\s*=\s*["\']?php["\']?/i'
        ];
        
        foreach ($phpPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate safe filename
     * Uses hash of original name + timestamp to prevent collisions
     * 
     * @param string $originalName Original filename
     * @param string $extension File extension
     * @return string Safe filename
     */
    private static function generateSafeFilename($originalName, $extension)
    {
        // Remove extension from original name
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize base name (remove special characters)
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $baseName = substr($baseName, 0, 50); // Limit length
        
        // Generate unique hash
        $hash = substr(hash('sha256', $originalName . microtime(true)), 0, 16);
        
        // Combine: sanitizedName_hash.extension
        return $baseName . '_' . $hash . '.' . $extension;
    }
    
    /**
     * Get upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private static function getUploadErrorMessage($errorCode)
    {
        $phpFileUploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
        ];
        
        return $phpFileUploadErrors[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted size
     */
    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Move validated file to destination
     * 
     * @param array $file $_FILES array element
     * @param string $destination Destination directory path
     * @param string $sanitizedName Sanitized filename from validate()
     * @return array ['success' => bool, 'error' => string, 'path' => string]
     */
    public static function moveUploadedFile($file, $destination, $sanitizedName)
    {
        // Ensure destination directory exists
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                return [
                    'success' => false,
                    'error' => 'Failed to create destination directory',
                    'path' => null
                ];
            }
        }
        
        // Ensure directory is writable
        if (!is_writable($destination)) {
            return [
                'success' => false,
                'error' => 'Destination directory is not writable',
                'path' => null
            ];
        }
        
        // Full destination path
        $fullPath = rtrim($destination, '/') . '/' . $sanitizedName;
        
        // Check if file already exists
        if (file_exists($fullPath)) {
            return [
                'success' => false,
                'error' => 'File already exists at destination',
                'path' => null
            ];
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Set proper permissions
            chmod($fullPath, 0644);
            
            return [
                'success' => true,
                'error' => null,
                'path' => $fullPath
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to move uploaded file',
            'path' => null
        ];
    }
    
    /**
     * Set custom allowed extensions
     * 
     * @param array $extensions Array of allowed extensions
     */
    public static function setAllowedExtensions($extensions)
    {
        self::$allowedExtensions = array_map('strtolower', $extensions);
    }
    
    /**
     * Set maximum file size
     * 
     * @param int $bytes Maximum size in bytes
     */
    public static function setMaxFileSize($bytes)
    {
        self::$maxFileSize = (int)$bytes;
    }
}

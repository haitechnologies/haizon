<?php
/**
 * Centralized Image & Document Upload Handler
 * 
 * Provides unified, secure file upload handling with:
 * - MIME type validation (finfo)
 * - Size limits
 * - Safe filename generation
 * - Automatic thumbnail creation
 * - Directory management
 * - Error logging
 * 
 * @package HAIPULSE
 * @version 2.0
 * @date 2026-02-24
 */

class ImageUploadHandler
{
    /**
     * Default MIME type allowlists
     */
    const MIME_IMAGES_COMMON = ['image/webp', 'image/jpeg', 'image/png', 'image/gif'];
    const MIME_IMAGES_WITH_ICO = ['image/webp', 'image/jpeg', 'image/png', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'];
    const MIME_DOCUMENTS = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    
    /**
     * Error codes
     */
    const ERROR_NO_FILE = 'no_file';
    const ERROR_UPLOAD_ERROR = 'upload_error';
    const ERROR_SIZE_EXCEEDED = 'size_exceeded';
    const ERROR_INVALID_MIME = 'invalid_mime';
    const ERROR_INVALID_IMAGE = 'invalid_image';
    const ERROR_WRITE_FAILED = 'write_failed';
    const ERROR_EXTENSION_DENIED = 'extension_denied';

    /**
     * Upload configuration
     */
    protected $uploadPath;
    protected $maxSize;
    protected $allowedMimes;
    protected $allowedExtensions;
    protected $createThumbnails;
    protected $thumbWidth;
    protected $thumbHeight;
    protected $thumbQuality;
    protected $mysqli;
    protected $errors = [];
    protected $lastUploadedFile;

    /**
     * Constructor
     * 
     * @param string $uploadPath Directory path for uploads (will be created if missing)
     * @param int $maxSizeMB Maximum file size in MB (default: 5)
     * @param array $allowedMimes MIME types whitelist (default: MIME_IMAGES_COMMON)
     * @param bool $createThumbnails Generate thumbnails (default: true)
     * @param mysqli $mysqli Database connection for logging (optional)
     */
    public function __construct(
        $uploadPath,
        $maxSizeMB = 5,
        $allowedMimes = null,
        $createThumbnails = true,
        $mysqli = null
    ) {
        $this->uploadPath = rtrim($uploadPath, '/\\') . '/';
        $this->maxSize = $maxSizeMB * 1024 * 1024; // Convert to bytes
        $this->allowedMimes = $allowedMimes ?? self::MIME_IMAGES_COMMON;
        $this->createThumbnails = $createThumbnails;
        $this->thumbWidth = 150;
        $this->thumbHeight = 150;
        $this->thumbQuality = 85;
        $this->mysqli = $mysqli;
        
        // Derive allowed extensions from MIME types
        $this->allowedExtensions = $this->getExtensionsFromMimes($this->allowedMimes);
        
        // Ensure directories exist
        $this->ensureDirectories();
    }

    /**
     * Set thumbnail dimensions
     */
    public function setThumbnailDimensions($width, $height, $quality = 85)
    {
        $this->thumbWidth = (int)$width;
        $this->thumbHeight = (int)$height;
        $this->thumbQuality = max(1, min(100, (int)$quality));
    }

    /**
     * Validate and upload a file from $_FILES
     * 
     * @param string $fieldName Name of file input field
     * @param string $label Display name for error messages
     * @param string $prefix Prefix for uploaded filename (default: timestamp)
     * @return array ['success' => bool, 'file' => string, 'error' => string, 'code' => string]
     */
    public function upload($fieldName, $label, $prefix = null)
    {
        $this->errors = [];
        $this->lastUploadedFile = null;

        // Check if file exists in submission
        if (empty($_FILES[$fieldName]['name'])) {
            return $this->formatResponse(false, self::ERROR_NO_FILE, "{$label} was not uploaded.");
        }

        $file = $_FILES[$fieldName];

        // Validate upload error
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->formatResponse(false, self::ERROR_UPLOAD_ERROR, "{$label} upload failed. Error code: {$file['error']}");
        }

        // Check if tmp_name is a valid upload
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->formatResponse(false, self::ERROR_UPLOAD_ERROR, "{$label} upload validation failed.");
        }

        // Validate size
        if ($file['size'] > $this->maxSize) {
            $maxMB = round($this->maxSize / 1024 / 1024, 1);
            return $this->formatResponse(false, self::ERROR_SIZE_EXCEEDED, "{$label} exceeds maximum size of {$maxMB}MB.");
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $actualMime = @$finfo->file($file['tmp_name']) ?: '';

        if (!in_array($actualMime, $this->allowedMimes, true)) {
            $mimeStr = implode(', ', array_map(fn($m) => str_replace('image/', '', $m), $this->allowedMimes));
            return $this->formatResponse(false, self::ERROR_INVALID_MIME, "{$label} must be: {$mimeStr}.");
        }

        // For non-ICO images, validate image structure
        if (!in_array($actualMime, ['image/x-icon', 'image/vnd.microsoft.icon'], true)) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return $this->formatResponse(false, self::ERROR_INVALID_IMAGE, "{$label} must be a valid image file.");
            }
        }

        // Generate safe filename
        $extension = $this->resolveExtension($actualMime, $file['name']);
        if (!$extension) {
            return $this->formatResponse(false, self::ERROR_EXTENSION_DENIED, "{$label} has an unsupported extension.");
        }

        $prefix = $prefix ?? date('Y_m_d_H_i_s');
        $safeFilename = $this->generateSafeFilename($prefix, $extension);

        // Move uploaded file
        $targetPath = $this->uploadPath . $safeFilename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $this->formatResponse(false, self::ERROR_WRITE_FAILED, "Failed to save {$label} to storage.");
        }

        // Create thumbnail if needed
        if ($this->createThumbnails && !in_array($actualMime, ['image/x-icon', 'image/vnd.microsoft.icon'], true)) {
            $thumbPath = $this->uploadPath . 'thumbs/' . $safeFilename;
            $this->createThumbnail($targetPath, $thumbPath);
        }

        $this->lastUploadedFile = $safeFilename;
        return $this->formatResponse(true, null, null, $safeFilename);
    }

    /**
     * Delete a file and its thumbnail
     * 
     * @param string $filename Filename to delete
     * @param bool $includeThumbnail Also delete thumbnail (default: true)
     * @return bool Success status
     */
    public function delete($filename, $includeThumbnail = true)
    {
        $filename = basename($filename); // Prevent directory traversal
        $filePath = $this->uploadPath . $filename;

        if (!file_exists($filePath)) {
            return false;
        }

        $success = @unlink($filePath);

        if ($includeThumbnail) {
            $thumbPath = $this->uploadPath . 'thumbs/' . $filename;
            if (file_exists($thumbPath)) {
                @unlink($thumbPath);
            }
        }

        return $success;
    }

    /**
     * Get currently uploaded file
     */
    public function getLastUploadedFile()
    {
        return $this->lastUploadedFile;
    }

    /**
     * Get upload directory path
     */
    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    /**
     * Check if file exists
     */
    public function fileExists($filename)
    {
        return file_exists($this->uploadPath . basename($filename));
    }

    /**
     * Get file size in bytes
     */
    public function getFileSize($filename)
    {
        $path = $this->uploadPath . basename($filename);
        return file_exists($path) ? filesize($path) : 0;
    }

    /**
     * Get file modification timestamp
     */
    public function getFileModified($filename)
    {
        $path = $this->uploadPath . basename($filename);
        return file_exists($path) ? filemtime($path) : null;
    }

    /**
     * Get all uploaded files
     */
    public function listFiles($includeThembnails = false)
    {
        $files = [];
        $dir = new DirectoryIterator($this->uploadPath);
        
        foreach ($dir as $file) {
            if ($file->isDot() || $file->isDir()) continue;
            if (!$includeThembnails && strpos($file->getPathname(), '/thumbs/') !== false) continue;
            
            $files[] = $file->getFilename();
        }

        return $files;
    }

    /**
     * ===== PROTECTED METHODS =====
     */

    /**
     * Ensure upload directories exist
     */
    protected function ensureDirectories()
    {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }

        $thumbPath = $this->uploadPath . 'thumbs/';
        if (!is_dir($thumbPath)) {
            mkdir($thumbPath, 0755, true);
        }
    }

    /**
     * Create thumbnail from image file
     */
    protected function createThumbnail($sourceFile, $thumbFile)
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false; // GD library not available
        }

        $imageInfo = @getimagesize($sourceFile);
        if ($imageInfo === false) {
            return false;
        }

        $mime = $imageInfo['mime'] ?? '';
        
        // Create source image resource
        $source = null;
        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourceFile);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourceFile);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($sourceFile);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source = imagecreatefromwebp($sourceFile);
                }
                break;
        }

        if (!$source) {
            return false;
        }

        // Calculate thumbnail dimensions (fit within bounds, maintain aspect)
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $ratio = min($this->thumbWidth / $srcWidth, $this->thumbHeight / $srcHeight);
        $newWidth = (int)($srcWidth * $ratio);
        $newHeight = (int)($srcHeight * $ratio);

        // Create thumbnail image
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mime === 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

        // Save thumbnail
        $saved = false;
        switch ($mime) {
            case 'image/jpeg':
                $saved = imagejpeg($thumb, $thumbFile, $this->thumbQuality);
                break;
            case 'image/png':
                $saved = imagepng($thumb, $thumbFile, 8);
                break;
            case 'image/gif':
                $saved = imagegif($thumb, $thumbFile);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $saved = imagewebp($thumb, $thumbFile, $this->thumbQuality);
                }
                break;
        }

        imagedestroy($source);
        imagedestroy($thumb);

        return $saved !== false;
    }

    /**
     * Generate cryptographically safe filename
     */
    protected function generateSafeFilename($prefix, $extension)
    {
        $random = bin2hex(random_bytes(8));
        $timestamp = time();
        $filename = $prefix . '_' . $timestamp . '_' . $random . '.' . $extension;
        
        // Remove unsafe characters
        $filename = preg_replace('/[^a-z0-9._-]/i', '', $filename);
        
        return $filename;
    }

    /**
     * Resolve file extension from MIME type
     */
    protected function resolveExtension($mimeType, $originalName)
    {
        $mimeToExt = [
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        ];

        if (isset($mimeToExt[$mimeType])) {
            $ext = $mimeToExt[$mimeType];
        } else {
            // Fall back to original extension
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        }

        // Verify extension is in allowlist
        if (!in_array($ext, $this->allowedExtensions, true)) {
            return null;
        }

        return preg_replace('/[^a-z0-9]+/', '', $ext);
    }

    /**
     * Map MIME types to file extensions
     */
    protected function getExtensionsFromMimes($mimes)
    {
        $extMap = [
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        ];

        $extensions = [];
        foreach ($mimes as $mime) {
            if (isset($extMap[$mime])) {
                $extensions[] = $extMap[$mime];
            }
        }

        return array_values(array_unique($extensions));
    }

    /**
     * Format response array
     */
    protected function formatResponse($success, $errorCode = null, $errorMsg = null, $file = null)
    {
        return [
            'success' => (bool)$success,
            'file' => $file,
            'error' => $errorMsg,
            'code' => $errorCode,
        ];
    }
}


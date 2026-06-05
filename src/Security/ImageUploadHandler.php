<?php

declare(strict_types=1);

namespace App\Security;

use finfo;
use DirectoryIterator;

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
 */
class ImageUploadHandler
{
    /**
     * Default MIME type allowlists
     */
    public const MIME_IMAGES_COMMON = ['image/webp', 'image/jpeg', 'image/png', 'image/gif'];
    public const MIME_IMAGES_WITH_ICO = ['image/webp', 'image/jpeg', 'image/png', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'];
    public const MIME_DOCUMENTS = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

    /**
     * Error codes
     */
    public const ERROR_NO_FILE = 'no_file';
    public const ERROR_UPLOAD_ERROR = 'upload_error';
    public const ERROR_SIZE_EXCEEDED = 'size_exceeded';
    public const ERROR_INVALID_MIME = 'invalid_mime';
    public const ERROR_INVALID_IMAGE = 'invalid_image';
    public const ERROR_WRITE_FAILED = 'write_failed';
    public const ERROR_EXTENSION_DENIED = 'extension_denied';

    protected string $uploadPath;
    protected int $maxSize;
    protected array $allowedMimes;
    protected array $allowedExtensions;
    protected bool $createThumbnails;
    protected int $thumbWidth;
    protected int $thumbHeight;
    protected int $thumbQuality;
    protected mixed $mysqli;
    protected array $errors = [];
    protected ?string $lastUploadedFile = null;

    /**
     * Constructor
     *
     * @param string $uploadPath Directory path for uploads (will be created if missing)
     * @param int $maxSizeMB Maximum file size in MB (default: 5)
     * @param array|null $allowedMimes MIME types whitelist (default: MIME_IMAGES_COMMON)
     * @param bool $createThumbnails Generate thumbnails (default: true)
     * @param mixed $mysqli Database connection for logging (optional, unused)
     */
    public function __construct(
        string $uploadPath,
        int $maxSizeMB = 5,
        ?array $allowedMimes = null,
        bool $createThumbnails = true,
        mixed $mysqli = null
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
     *
     * @param int|string $width
     * @param int|string $height
     * @param int|string $quality
     * @return void
     */
    public function setThumbnailDimensions($width, $height, $quality = 85): void
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
     * @param string|null $prefix Prefix for uploaded filename (default: timestamp)
     * @return array ['success' => bool, 'file' => ?string, 'error' => ?string, 'code' => ?string]
     */
    public function upload(string $fieldName, string $label, ?string $prefix = null): array
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
    public function delete(string $filename, bool $includeThumbnail = true): bool
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
     *
     * @return string|null
     */
    public function getLastUploadedFile(): ?string
    {
        return $this->lastUploadedFile;
    }

    /**
     * Get upload directory path
     *
     * @return string
     */
    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    /**
     * Check if file exists
     *
     * @param string $filename
     * @return bool
     */
    public function fileExists(string $filename): bool
    {
        return file_exists($this->uploadPath . basename($filename));
    }

    /**
     * Get file size in bytes
     *
     * @param string $filename
     * @return int
     */
    public function getFileSize(string $filename): int
    {
        $path = $this->uploadPath . basename($filename);
        return file_exists($path) ? (int) filesize($path) : 0;
    }

    /**
     * Get file modification timestamp
     *
     * @param string $filename
     * @return int|null
     */
    public function getFileModified(string $filename): ?int
    {
        $path = $this->uploadPath . basename($filename);
        return file_exists($path) ? (int) filemtime($path) : null;
    }

    /**
     * Get all uploaded files
     *
     * @param bool $includeThumbnails
     * @return array<string>
     */
    public function listFiles(bool $includeThumbnails = false): array
    {
        $files = [];
        $dir = new DirectoryIterator($this->uploadPath);

        foreach ($dir as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }
            if (!$includeThumbnails && strpos($file->getPathname(), '/thumbs/') !== false) {
                continue;
            }

            $files[] = $file->getFilename();
        }

        return $files;
    }

    /**
     * Ensure upload directories exist
     *
     * @return void
     */
    protected function ensureDirectories(): void
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
     *
     * @param string $sourceFile
     * @param string $thumbFile
     * @return bool
     */
    protected function createThumbnail(string $sourceFile, string $thumbFile): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false; // GD library not available
        }

        $imageInfo = @getimagesize($sourceFile);
        if ($imageInfo === false) {
            return false;
        }

        $mime = $imageInfo['mime'];

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
        if ($thumb === false) {
            imagedestroy($source);
            return false;
        }

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
     *
     * @param string $prefix
     * @param string $extension
     * @return string
     */
    protected function generateSafeFilename(string $prefix, string $extension): string
    {
        $random = bin2hex(random_bytes(8));
        $timestamp = time();
        $filename = $prefix . '_' . $timestamp . '_' . $random . '.' . $extension;

        // Remove unsafe characters
        $filename = preg_replace('/[^a-z0-9._-]/i', '', $filename) ?? 'file.' . $extension;

        return $filename;
    }

    /**
     * Resolve file extension from MIME type
     *
     * @param string $mimeType
     * @param string $originalName
     * @return string|null
     */
    protected function resolveExtension(string $mimeType, string $originalName): ?string
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
     *
     * @param array<string> $mimes
     * @return array<string>
     */
    protected function getExtensionsFromMimes(array $mimes): array
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
     *
     * @param bool $success
     * @param string|null $errorCode
     * @param string|null $errorMsg
     * @param string|null $file
     * @return array
     */
    protected function formatResponse(bool $success, ?string $errorCode = null, ?string $errorMsg = null, ?string $file = null): array
    {
        return [
            'success' => $success,
            'file' => $file,
            'error' => $errorMsg,
            'code' => $errorCode,
        ];
    }
}

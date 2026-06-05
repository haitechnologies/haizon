<?php
/**
 * ImageHelper - Responsive Image & Lazy Loading Utility
 * Handles image optimization: lazy loading, srcset generation, responsive sizing
 * 
 * Usage:
 *   ImageHelper::lazyImg($path, $alt, $sizes)
 *   ImageHelper::responsiveImg($path, $alt)
 *   ImageHelper::getOptimizedUrl($path, $width, $format)
 */

class ImageHelper {
    
    // Supported image formats
    const WEBP = 'webp';
    const PNG = 'png';
    const JPG = 'jpg';
    
    // Default responsive sizes (mobile-first)
    const DEFAULT_SIZES = [
        'small' => 320,    // Mobile
        'medium' => 768,   // Tablet
        'large' => 1200    // Desktop
    ];
    
    /**
     * Generate lazy-loaded image HTML with responsive srcset
     * Best for: Company logos, product images, thumbnails
     * 
     * @param string $path Image path (e.g., 'uploads/companies/logo.jpg')
     * @param string $alt Alt text for accessibility
     * @param array $sizes Custom size breakpoints [small=>320, medium=>768, large=>1200]
     * @param string $class CSS classes to apply
     * @return string HTML img tag
     */
    public static function lazyImg($path, $alt = '', $sizes = [], $class = '') {
        if (empty($path) || $path === null) {
            return self::getPlaceholder($alt, $class);
        }
        
        $sizes = !empty($sizes) ? $sizes : self::DEFAULT_SIZES;
        
        // Build srcset with multiple resolutions
        $srcset = self::buildSrcset($path, $sizes);
        
        // Determine sizes attribute for responsive loading
        $sizesAttr = "
            (max-width: 320px) 100vw,
            (max-width: 768px) 80vw,
            (max-width: 1200px) 60vw,
            100vw
        ";
        
        return sprintf(
            '<img src="%s" srcset="%s" sizes="%s" alt="%s" loading="lazy" decoding="async" class="img-responsive %s" onerror="this.onerror=null;this.src=\'%s\';">',
            self::escapeUrl(self::getSmallVersion($path)),
            $srcset,
            $sizesAttr,
            htmlspecialchars($alt, ENT_QUOTES),
            $class,
            self::escapeUrl(self::getPlaceholderPath())
        );
    }
    
    /**
     * Generate eager-loaded image (above fold, hero images)
     * Use only for critical images in viewport
     * 
     * @param string $path Image path
     * @param string $alt Alt text
     * @param array $sizes Custom breakpoints
     * @param string $class CSS classes
     * @return string HTML img tag with loading="eager"
     */
    public static function eagerImg($path, $alt = '', $sizes = [], $class = '') {
        if (empty($path) || $path === null) {
            return self::getPlaceholder($alt, $class);
        }
        
        $sizes = !empty($sizes) ? $sizes : self::DEFAULT_SIZES;
        $srcset = self::buildSrcset($path, $sizes);
        
        return sprintf(
            '<img src="%s" srcset="%s" alt="%s" loading="eager" decoding="sync" class="img-responsive %s" onerror="this.onerror=null;this.src=\'%s\';">',
            self::escapeUrl(self::getLargeVersion($path)),
            $srcset,
            htmlspecialchars($alt, ENT_QUOTES),
            $class,
            self::escapeUrl(self::getPlaceholderPath())
        );
    }
    
    /**
     * Generate picture element with WebP fallback (modern approach)
     * Best for: Hero images, featured thumbnails
     * 
     * @param string $jpgPath Path to JPG version
     * @param string $alt Alt text
     * @param string $class CSS classes
     * @param bool $eager Load eagerly (true) or lazy (false)
     * @return string HTML picture element
     */
    public static function pictureWithWebP($jpgPath, $alt = '', $class = '', $eager = false) {
        if (empty($jpgPath)) {
            return self::getPlaceholder($alt, $class);
        }
        
        $webpPath = self::toWebP($jpgPath);
        $loadAttr = $eager ? 'eager' : 'lazy';
        
        return sprintf(
            '<picture>
                <source srcset="%s" type="image/webp">
                <source srcset="%s" type="image/jpeg">
                <img src="%s" alt="%s" loading="%s" decoding="async" class="img-responsive %s" onerror="this.onerror=null;this.src=\'%s\';">
            </picture>',
            self::escapeUrl($webpPath),
            self::escapeUrl($jpgPath),
            self::escapeUrl($jpgPath),
            htmlspecialchars($alt, ENT_QUOTES),
            $loadAttr,
            $class,
            self::escapeUrl(self::getPlaceholderPath())
        );
    }
    
    /**
     * Get responsive srcset attribute
     * Generates responsive images for small, medium, large viewports
     * 
     * @param string $path Image path
     * @param array $sizes Custom breakpoints
     * @return string srcset attribute value
     */
    private static function buildSrcset($path, $sizes = []) {
        $sizes = !empty($sizes) ? $sizes : self::DEFAULT_SIZES;
        $srcset = [];
        
        // Generate small, medium, large versions
        if (isset($sizes['small'])) {
            $srcset[] = self::escapeUrl(self::resize($path, $sizes['small'])) . ' ' . $sizes['small'] . 'w';
        }
        if (isset($sizes['medium'])) {
            $srcset[] = self::escapeUrl(self::resize($path, $sizes['medium'])) . ' ' . $sizes['medium'] . 'w';
        }
        if (isset($sizes['large'])) {
            $srcset[] = self::escapeUrl(self::resize($path, $sizes['large'])) . ' ' . $sizes['large'] . 'w';
        }
        
        // Add 2x resolution for retina displays
        $srcset[] = self::escapeUrl(self::getLargeVersion($path)) . ' 2x';
        
        return implode(', ', $srcset);
    }
    
    /**
     * Get small (mobile-optimized) version of image
     * Used as default src attribute
     * 
     * @param string $path Original image path
     * @return string Path to 320px wide version
     */
    private static function getSmallVersion($path) {
        return self::resize($path, 320);
    }
    
    /**
     * Get large (desktop) version of image
     * Used for 2x retina displays
     * 
     * @param string $path Original image path
     * @return string Path to 1200px wide version
     */
    private static function getLargeVersion($path) {
        return self::resize($path, 1200);
    }
    
    /**
     * Resize image to specific width (generates cache-busted URL)
     * NOTE: Requires image processing service or CDN
     * For now, returns original path (can be enhanced with Tinify, Cloudinary, etc.)
     * 
     * @param string $path Image path
     * @param int $width Target width in pixels
     * @return string URL to resized image
     */
    private static function resize($path, $width) {
        // TODO: Implement image resizing via CDN or local service
        // Example with Cloudinary:
        // return sprintf('https://res.cloudinary.com/YOUR-CLOUD/image/fetch/w_%d,c_scale,q_auto:best/%s', $width, $path);
        
        // For now, return original path
        // In production, integrate with image optimization service
        return $path;
    }
    
    /**
     * Convert JPG path to WebP
     * 
     * @param string $jpgPath Path to JPG file
     * @return string Path to corresponding WebP file
     */
    private static function toWebP($jpgPath) {
        return preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $jpgPath);
    }
    
    /**
     * Get placeholder image path
     * Returns a default placeholder image
     * 
     * @return string Path to placeholder image
     */
    private static function getPlaceholderPath() {
        return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22320%22 height=%22240%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22320%22 height=%22240%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 fill=%22%23999%22 font-size=%2216px%22%3EImage not available%3C/text%3E%3C/svg%3E';
    }
    
    /**
     * Get HTML placeholder element
     * Used when image path is empty/null
     * 
     * @param string $alt Alt text
     * @param string $class CSS classes
     * @return string HTML img tag with placeholder
     */
    private static function getPlaceholder($alt = '', $class = '') {
        return sprintf(
            '<div class="img-placeholder %s" role="img" aria-label="%s" style="background: #f0f0f0; aspect-ratio: 1; display: flex; align-items: center; justify-content: center;"><span class="text-muted">No image</span></div>',
            $class,
            htmlspecialchars($alt, ENT_QUOTES)
        );
    }
    
    /**
     * Escape URL for safe HTML output
     * 
     * @param string $url URL to escape
     * @return string Escaped URL
     */
    private static function escapeUrl($url) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get optimized image URL with query string parameters
     * Can be used with image CDN or processing service
     * 
     * @param string $path Image path
     * @param int $width Target width
     * @param string $format Output format (webp, jpg, png)
     * @param int $quality JPEG quality (1-100)
     * @return string Optimized image URL
     */
    public static function getOptimizedUrl($path, $width = 400, $format = 'auto', $quality = 85) {
        // TODO: Integrate with image optimization service
        // Example with Squoosh API or similar:
        // return sprintf('/api/optimize-image?path=%s&width=%d&format=%s&quality=%d', 
        //     urlencode($path), $width, $format, $quality);
        
        return $path;
    }
    
}

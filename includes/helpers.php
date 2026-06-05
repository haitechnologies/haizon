<?php
/**
 * Frontend Helper Functions
 * 
 * Common utility functions for rendering UI components across the frontend.
 * 
 * @package Helpers
 */

// Auto-load optimization helpers
require_once __DIR__ . '/../config/session.php';

if (!class_exists('ImageHelper')) {
    require_once __DIR__ . '/helpers/ImageHelper.php';
}
if (!class_exists('Pagination')) {
    require_once __DIR__ . '/helpers/Pagination.php';
}
if (!class_exists('FrontendInputValidator')) {
    require_once __DIR__ . '/helpers/InputValidator.php';
}

/**
 * Get base path for navigation links
 * Works with both local (/haipulse/) and remote (/) deployments
 * 
 * @return string Base path without trailing slash
 */
function getBasePath() {
    $basePath = $GLOBALS['basePath'] ?? '';
    if (empty($basePath) && isset($_SERVER['SCRIPT_NAME'])) {
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
    }
    return $basePath;
}

/**
 * Get full URL for navigation links using base path
 * 
 * @param string $path Path (e.g., '/', '/register', '/company/slug')
 * @return string Full URL with base path
 */
function url($path = '/') {
    $base = getBasePath();
    // Ensure path starts with /
    if ($path !== '/' && strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    return $base . $path;
}

/**
 * Start frontend session safely when needed.
 *
 * @return void
 */
function ensureFrontendSessionStarted() {
    startFrontendSession();
}

/**
 * Get current frontend user ID from session.
 * Supports both current and legacy session shapes.
 *
 * @return int
 */
function getFrontendUserId() {
    ensureFrontendSessionStarted();

    if (!empty($_SESSION['frontend_user_id'])) {
        return (int) $_SESSION['frontend_user_id'];
    }

    if (!empty($_SESSION['project_pre']['FRONTEND']['user_id'])) {
        return (int) $_SESSION['project_pre']['FRONTEND']['user_id'];
    }

    return 0;
}

/**
 * Check whether a frontend user is authenticated.
 *
 * @return bool
 */
function isFrontendUserLoggedIn() {
    return getFrontendUserId() > 0;
}

/**
 * Get absolute URL with protocol and host (for emails, Schema.org, etc.)
 * 
 * @param string $path Relative path (e.g., '/company/slug', '/blog/post')
 * @return string Absolute URL (e.g., 'https://example.com/haipulse/company/slug')
 */
function getFullUrl($path = '/') {
    // Determine protocol
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    
    // Get host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Get base path and append the requested path
    $fullPath = url($path);
    
    return $scheme . '://' . $host . $fullPath;
}

/**
 * Truncate text to a specified length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to append (default: '...')
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get full URL for a company detail page
 * 
 * @param string $slug Company slug
 * @return string Full URL
 */
function companyUrl($slug) {
    global $basePath;
    $base = $basePath === '' ? '' : $basePath;
    return $base . '/company/' . $slug;
}

/**
 * Get full URL for a category page
 * 
 * @param string $slug Category slug
 * @return string Full URL
 */
function categoryUrl($slug) {
    global $basePath;
    $base = $basePath === '' ? '' : $basePath;
    return $base . '/category/' . $slug;
}

/**
 * Get full URL for a blog detail page
 * 
 * @param string $slug Blog slug
 * @return string Full URL
 */
function blogUrl($slug) {
    global $basePath;
    $base = $basePath === '' ? '' : $basePath;
    return $base . '/blog/' . $slug;
}

/**
 * Get default company image if none exists
 * 
 * @param string|null $image Company image path
 * @return string Image path or default
 */
function companyImage($image) {
    if (empty($image)) {
        return 'assets/images/products/products/placeholder.jpg';
    }
    return $image;
}

/**
 * Get category icon SVG class (placeholder - can be expanded)
 * 
 * @param string $categoryName Category name
 * @return string Icon class or default
 */
function getCategoryIcon($categoryName) {
    // This is a placeholder - you can expand with actual icon mapping
    $icons = [
        'real estate' => 'real-estate-icon',
        'restaurant' => 'restaurant-icon',
        'hotel' => 'hotel-icon',
        'automotive' => 'automotive-icon',
        'clothing' => 'clothing-icon',
    ];
    
    $key = strtolower($categoryName);
    return $icons[$key] ?? 'default-icon';
}

/**
 * Format phone number
 * 
 * @param string|null $phone Phone number
 * @return string Formatted phone or empty
 */
function formatPhone($phone) {
    if (empty($phone)) {
        return '';
    }
    // Basic formatting - can be enhanced
    return $phone;
}

/**
 * Check if company is currently open (placeholder)
 * 
 * @param array $company Company data
 * @return bool True if open
 */
function isCompanyOpen($company) {
    // Placeholder - implement business hours logic later
    return true;
}

/**
 * Get opening status badge HTML
 * 
 * @param array $company Company data
 * @return string HTML for status badge
 */
function getOpenStatusBadge($company) {
    if (isCompanyOpen($company)) {
        return '<span class="badge badge-success ms-2 fs-13">Open Now</span>';
    }
    return '<span class="badge badge-danger ms-2 fs-13">Closed</span>';
}
/**
 * Generate Organization JSON-LD structured data for Company
 * 
 * @param array $company Company data from database
 * @return string HTML script tag with JSON-LD markup
 */
function generateOrganizationSchema($company) {
    $organizationData = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $company['company_name'] ?? '',
        'url' => getFullUrl('/company/' . ($company['slug'] ?? '')),
        'telephone' => $company['telephone'] ?? '',
        'email' => $company['email'] ?? '',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $company['address'] ?? '',
            'addressLocality' => $company['city'] ?? '',
            'addressRegion' => $company['state'] ?? 'UAE',
            'postalCode' => '',
            'addressCountry' => 'AE'
        ]
    ];

    // Add logo if available
    if (!empty($company['logo'])) {
        $organizationData['logo'] = getFullUrl('/' . $company['logo']);
    }

    // Add description
    if (!empty($company['company_profile'])) {
        $organizationData['description'] = strip_tags($company['company_profile']);
    }

    return '<script type="application/ld+json">' . json_encode($organizationData) . '</script>';
}

/**
 * Generate LocalBusiness JSON-LD structured data for Company
 * 
 * @param array $company Company data from database
 * @return string HTML script tag with JSON-LD markup
 */
function generateLocalBusinessSchema($company) {
    $businessData = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $company['company_name'] ?? '',
        'url' => getFullUrl('/company/' . ($company['slug'] ?? '')),
        'telephone' => $company['telephone'] ?? '',
        'email' => $company['email'] ?? '',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $company['address'] ?? '',
            'addressLocality' => $company['city'] ?? '',
            'addressRegion' => $company['state'] ?? 'UAE',
            'addressCountry' => 'AE'
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $company['lat'] ?? '',
            'longitude' => $company['lng'] ?? ''
        ]
    ];

    // Add logo
    if (!empty($company['logo'])) {
        $businessData['image'] = getFullUrl('/' . $company['logo']);
    }

    // Add description
    if (!empty($company['company_profile'])) {
        $businessData['description'] = substr(strip_tags($company['company_profile']), 0, 500);
    }

    return '<script type="application/ld+json">' . json_encode($businessData) . '</script>';
}

/**
 * Generate BlogPosting JSON-LD structured data
 * 
 * @param array $blog Blog post data from database
 * @return string HTML script tag with JSON-LD markup
 */
function generateBlogPostSchema($blog) {
    $blogData = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $blog['title'] ?? '',
        'description' => $blog['meta_description'] ?? (isset($blog['excerpt']) ? $blog['excerpt'] : ''),
        'url' => getFullUrl('/blog/' . ($blog['slug'] ?? '')),
        'datePublished' => $blog['created_at'] ?? date('Y-m-d'),
        'dateModified' => $blog['updated_at'] ?? $blog['created_at'] ?? date('Y-m-d'),
        'author' => [
            '@type' => 'Organization',
            'name' => 'UAE Business Directory'
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'UAE Business Directory',
            'url' => getFullUrl('/')
        ]
    ];

    // Add featured image
    if (!empty($blog['featured_image'])) {
        $blogData['image'] = getFullUrl('/' . $blog['featured_image']);
    }

    // Add article body
    if (!empty($blog['content'])) {
        $blogData['articleBody'] = strip_tags($blog['content']);
    }

    return '<script type="application/ld+json">' . json_encode($blogData) . '</script>';
}

/**
 * Generate BreadcrumbList JSON-LD structured data
 * 
 * @param array $breadcrumbs Array of breadcrumb items [['name' => 'Label', 'url' => 'https://...'], ...]
 * @return string HTML script tag with JSON-LD markup
 */
function generateBreadcrumbSchema($breadcrumbs) {
    $itemListElement = [];
    foreach ($breadcrumbs as $index => $item) {
        $itemListElement[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url']
        ];
    }

    $breadcrumbData = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $itemListElement
    ];

    return '<script type="application/ld+json">' . json_encode($breadcrumbData) . '</script>';
}

/**
 * Generate WebSite JSON-LD structured data with SearchAction
 * 
 * @param string $siteName Site name
 * @param string $siteUrl Site URL
 * @return string HTML script tag with JSON-LD markup
 */
function generateWebSiteSchema($siteName = '', $siteUrl = '') {
    if (empty($siteName)) {
        $siteName = getSystemSetting('software_name', 'UAE Business Directory');
    }
    
    if (empty($siteUrl)) {
        $siteUrl = getFullUrl('/');
    }
    
    $websiteData = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => $siteUrl,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $siteUrl . '/listings?keyword={search_term_string}'
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($websiteData, JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * Generate ItemList JSON-LD structured data for listing pages
 * 
 * @param array $items Array of items to display in list
 * @param string $listName Name of the list
 * @param string $description Description of the list
 * @return string HTML script tag with JSON-LD markup
 */
function generateItemListSchema($items, $listName = '', $description = '') {
    $itemListElement = [];
    
    foreach ($items as $index => $item) {
        $listItem = [
            '@type' => 'ListItem',
            'position' => $index + 1
        ];
        
        // For companies
        if (isset($item['company_name'])) {
            $listItem['item'] = [
                '@type' => 'LocalBusiness',
                'name' => $item['company_name'],
                'url' => getFullUrl('/company/' . ($item['slug'] ?? ''))
            ];
            
            if (!empty($item['description']) || !empty($item['company_profile'])) {
                $listItem['item']['description'] = substr(strip_tags($item['description'] ?? $item['company_profile']), 0, 200);
            }
        }
        // For blogs
        elseif (isset($item['title'])) {
            $listItem['item'] = [
                '@type' => 'BlogPosting',
                'name' => $item['title'],
                'url' => getFullUrl('/blog/' . ($item['slug'] ?? ''))
            ];
            
            if (!empty($item['excerpt']) || !empty($item['meta_description'])) {
                $listItem['item']['description'] = substr(strip_tags($item['excerpt'] ?? $item['meta_description']), 0, 200);
            }
        }
        
        $itemListElement[] = $listItem;
    }
    
    $itemListData = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => $itemListElement
    ];
    
    if (!empty($listName)) {
        $itemListData['name'] = $listName;
    }
    
    if (!empty($description)) {
        $itemListData['description'] = $description;
    }
    
    return '<script type="application/ld+json">' . json_encode($itemListData, JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * Generate FAQPage JSON-LD structured data
 * 
 * @param array $faqs Array of FAQ items [['question' => '...', 'answer' => '...'], ...]
 * @return string HTML script tag with JSON-LD markup
 */
function generateFAQSchema($faqs) {
    $mainEntity = [];
    
    foreach ($faqs as $faq) {
        if (!empty($faq['question']) && !empty($faq['answer'])) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
    }
    
    if (empty($mainEntity)) {
        return '';
    }
    
    $faqData = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $mainEntity
    ];
    
    return '<script type="application/ld+json">' . json_encode($faqData, JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * ============================================================================
 * OPTIMIZATION HELPER FUNCTIONS - Wrappers for new optimization classes
 * ============================================================================
 */

/**
 * Render lazy-loaded image (mobile-optimized)
 * Shortcut for ImageHelper::lazyImg()
 * 
 * @param string $path Image path
 * @param string $alt Alt text
 * @param array $sizes Custom sizes
 * @param string $class CSS classes
 * @return string HTML img tag
 */
function lazyImg($path, $alt = '', $sizes = [], $class = '') {
    return ImageHelper::lazyImg($path, $alt, $sizes, $class);
}

/**
 * Render eager-loaded image (above-fold hero images)
 * Shortcut for ImageHelper::eagerImg()
 * 
 * @param string $path Image path
 * @param string $alt Alt text
 * @param array $sizes Custom sizes
 * @param string $class CSS classes
 * @return string HTML img tag
 */
function eagerImg($path, $alt = '', $sizes = [], $class = '') {
    return ImageHelper::eagerImg($path, $alt, $sizes, $class);
}

/**
 * Render picture element with WebP fallback
 * Use for modern browser support
 * 
 * @param string $jpgPath JPG image path
 * @param string $alt Alt text
 * @param string $class CSS classes
 * @param bool $eager Load eagerly (true) or lazy (false)
 * @return string HTML picture element
 */
function pictureWithWebP($jpgPath, $alt = '', $class = '', $eager = false) {
    return ImageHelper::pictureWithWebP($jpgPath, $alt, $class, $eager);
}

/**
 * Create pagination object for handling page logic
 * 
 * Usage:
 *   $pagination = makePagination($_GET['page'] ?? 1, $totalResults, 12);
 *   if (!$pagination->isValid()) { redirect to page 1 }
 *   $results = $query->execute();
 *   $nextUrl = $pagination->getNextPageUrl('/listings', ['category' => 'tech']);
 * 
 * @param int|string $currentPage Current page number
 * @param int $totalItems Total number of items
 * @param int $perPage Items per page
 * @return Pagination Pagination object
 */
function makePagination($currentPage = 1, $totalItems = 0, $perPage = 12) {
    return new Pagination($currentPage, $totalItems, $perPage);
}

/**
 * Validate search term is meaningful
 * Replaces scattered validation logic across pages
 * 
 * @param string $term Search term
 * @param int $minLength Minimum length
 * @return bool True if valid search term
 */
function isMeaningfulSearchTerm($term, $minLength = 2) {
    return FrontendInputValidator::isMeaningfulSearchTerm($term, $minLength);
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid email format
 */
function isValidEmail($email) {
    return FrontendInputValidator::isValidEmail($email);
}

/**
 * Validate phone number
 * 
 * @param string $phone Phone to validate
 * @return bool True if valid phone format
 */
function isValidPhone($phone) {
    return FrontendInputValidator::isValidPhone($phone);
}

/**
 * Validate UAE-specific phone number
 * 
 * @param string $phone UAE phone to validate
 * @return bool True if valid UAE phone
 */
function isValidUAEPhone($phone) {
    return FrontendInputValidator::isValidUAEPhone($phone);
}

/**
 * Validate business name
 * 
 * @param string $name Business name
 * @param int $minLength Min length
 * @param int $maxLength Max length
 * @return bool True if valid name
 */
function isValidBusinessName($name, $minLength = 2, $maxLength = 100) {
    return FrontendInputValidator::isValidBusinessName($name, $minLength, $maxLength);
}

/**
 * Validate text length is within range
 * 
 * @param string $text Text to check
 * @param int $minLength Min length (0 = no minimum)
 * @param int $maxLength Max length (0 = no maximum)
 * @return bool True if within range
 */
function isValidLength($text, $minLength = 0, $maxLength = 0) {
    return FrontendInputValidator::isValidLength($text, $minLength, $maxLength);
}

/**
 * Sanitize string input (safe for display)
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeString($input) {
    return FrontendInputValidator::sanitizeString($input);
}

/**
 * Sanitize HTML input (allow safe tags)
 * 
 * @param string $input HTML to sanitize
 * @return string Sanitized HTML
 */
function sanitizeHtml($input) {
    return FrontendInputValidator::sanitizeHtml($input);
}



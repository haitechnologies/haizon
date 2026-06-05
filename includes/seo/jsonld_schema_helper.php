<?php
/**
 * JSON-LD Schema Helper
 * 
 * Purpose: Automatically inject appropriate schema markup based on page type
 * Usage: Include in header.php after page context is known
 * 
 * This helper detects the current page and generates appropriate JSON-LD schema:
 * - Homepage: Organization + Website schema
 * - Company pages: LocalBusiness schema
 * - Product pages: Product schema
 * - Blog posts: Article schema
 * - All pages: BreadcrumbList schema
 */

// Prevent redeclaration if header is included multiple times
if (defined('JSONLD_SCHEMA_LOADED')) {
    return;
}
define('JSONLD_SCHEMA_LOADED', true);

// Get system settings for organization data
$org_name = $GLOBALS['SYS_NAME']['site_name'] ?? 'HAIPULSE';
$org_url = $base_url ?? 'https://haipulse.com';
$org_logo = ($base_url ?? '') . '/assets/images/logo.png';
$org_description = $GLOBALS['SYS_NAME']['strapline'] ?? 'Business Directory & Trade Platform in UAE';

// Detect current page context
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

/**
 * Generate and output JSON-LD schema
 */
if (!function_exists('output_jsonld_schema')) {
    function output_jsonld_schema($schema_array) {
        if (empty($schema_array)) return;
        
        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($schema_array, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n" . '</script>' . "\n";
    }
}

// ============================================
// 1. ORGANIZATION SCHEMA (All Pages)
// ============================================
$organization_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $org_name,
    'url' => $org_url,
    'logo' => [
        '@type' => 'ImageObject',
        'url' => $org_logo
    ],
    'description' => $org_description,
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'contactType' => 'Customer Service',
        'email' => 'info@haipulse.com',
        'telephone' => '+971-4-xxx-xxxx',
        'availableLanguage' => ['English', 'Arabic']
    ],
    'address' => [
        '@type' => 'PostalAddress',
        'addressCountry' => 'AE',
        'addressLocality' => 'Dubai'
    ],
    'sameAs' => [
        'https://www.facebook.com/HaiPulsecom/',
        'https://x.com/haipulse',
        'https://www.instagram.com/haipulse',
        'https://www.pinterest.com/haipulsedotcom/',
    ]
];

// ============================================
// 2. WEBSITE SCHEMA (Homepage Only)
// ============================================
if ($current_page === 'index' && strpos($request_uri, '/company/') === false && strpos($request_uri, '/blog/') === false) {
    $website_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $org_name,
        'url' => $org_url,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $org_url . '/listings?keyword={search_term_string}'
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];
    
    output_jsonld_schema($website_schema);
}

// ============================================
// 3. LOCAL BUSINESS SCHEMA (Company Pages)
// ============================================
if (strpos($request_uri, '/company/') !== false && isset($company)) {
    $business_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $company['company_name'] ?? '',
        'description' => strip_tags($company['description'] ?? ''),
        'url' => $org_url . '/company/' . ($company['slug'] ?? ''),
        'telephone' => $company['phone'] ?? '',
        'email' => $company['email'] ?? '',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $company['address'] ?? '',
            'addressLocality' => $company['city'] ?? 'Dubai',
            'addressRegion' => $company['state'] ?? 'Dubai',
            'addressCountry' => 'AE'
        ]
    ];
    
    // Add image if available
    if (!empty($company['logo'])) {
        $business_schema['image'] = $org_url . '/uploads/companies/' . $company['logo'];
    }
    
    // Add opening hours if available
    if (!empty($company['opening_hours'])) {
        $business_schema['openingHours'] = $company['opening_hours'];
    }
    
    output_jsonld_schema($business_schema);
}

// ============================================
// 4. ARTICLE SCHEMA (Blog Posts)
// ============================================
if (strpos($request_uri, '/blog/') !== false && isset($blog)) {
    $article_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $blog['blog_title'] ?? '',
        'description' => strip_tags($blog['short_description'] ?? ''),
        'author' => [
            '@type' => 'Organization',
            'name' => $org_name
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $org_name,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $org_logo
            ]
        ],
        'datePublished' => $blog['created_at'] ?? date('Y-m-d'),
        'dateModified' => $blog['updated_at'] ?? date('Y-m-d'),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $org_url . '/blog/' . ($blog['slug'] ?? '')
        ]
    ];
    
    // Add image if available
    if (!empty($blog['image'])) {
        $article_schema['image'] = [
            '@type' => 'ImageObject',
            'url' => $org_url . '/uploads/blogs/' . $blog['image']
        ];
    }
    
    output_jsonld_schema($article_schema);
}

// ============================================
// 5. BREADCRUMB LIST SCHEMA (All Pages)
// ============================================
// Build breadcrumb based on URL path
$breadcrumb_items = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => $org_url
    ]
];

$path_parts = explode('/', trim($request_uri, '/'));
$base_path = '';
$position = 2;

foreach ($path_parts as $part) {
    if (empty($part) || $part === 'haipulse') continue;
    
    $base_path .= '/' . $part;
    $breadcrumb_items[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => ucfirst(str_replace(['-', '_'], ' ', $part)),
        'item' => $org_url . $base_path
    ];
}

// Only output breadcrumb if there's more than just home
if (count($breadcrumb_items) > 1) {
    $breadcrumb_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $breadcrumb_items
    ];
    
    output_jsonld_schema($breadcrumb_schema);
}

// ============================================
// 6. OUTPUT ORGANIZATION SCHEMA (All Pages)
// ============================================
output_jsonld_schema($organization_schema);



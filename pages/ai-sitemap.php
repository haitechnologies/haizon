<?php
/**
 * AI Search Engine Sitemap (Hidden)
 * 
 * Special sitemaps for AI search engines (Perplexity, Claude, ChatGPT, etc.)
 * Not indexed by public search engines, provides specific data AI engines need
 * URL: /sitemaps/ai-sitemap.xml (hidden from robots.txt)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';

$sitemapEnabled = (int)getSystemSetting('sitemap_enabled', 1);
$aiSitemapEnabled = (int)getSystemSetting('ai_sitemap_enabled', 1);
$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));

if ($sitemapEnabled !== 1 || $aiSitemapEnabled !== 1) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Determine base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . ($GLOBALS['basePath'] ?? '');
$baseUrl = rtrim($baseUrl, '/');
if (!empty($sitemapRoot)) {
    $baseUrl = rtrim($sitemapRoot, '/');
} elseif (!empty($seoCanonicalUrl)) {
    $baseUrl = rtrim($seoCanonicalUrl, '/');
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<!-- AI Search Engine Sitemap - For Perplexity, Claude, ChatGPT, Copilot, etc. -->' . "\n";
echo '<!-- This sitemap is not indexed by public search engines -->' . "\n";
echo '<!-- Updated: ' . date('Y-m-d H:i:s') . ' -->' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xhtml="http://www.w3.org/1999/xhtml"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// AI-focused static pages (high-value pages for AI indexing)
$aiPages = [
    ['path' => '', 'title' => 'Home', 'priority' => 1.0, 'changefreq' => 'daily'],
    ['path' => 'companies', 'title' => 'Companies Directory', 'priority' => 1.0, 'changefreq' => 'daily'],
    ['path' => 'categories', 'title' => 'Business Categories', 'priority' => 0.9, 'changefreq' => 'weekly'],
    ['path' => 'blog', 'title' => 'Business Blog & Insights', 'priority' => 0.9, 'changefreq' => 'daily'],
    ['path' => 'search', 'title' => 'Advanced Search', 'priority' => 0.8, 'changefreq' => 'weekly'],
    ['path' => 'about', 'title' => 'About UAE Business Directory', 'priority' => 0.7, 'changefreq' => 'monthly'],
    ['path' => 'contact', 'title' => 'Contact & Support', 'priority' => 0.7, 'changefreq' => 'weekly'],
    ['path' => 'faq', 'title' => 'Frequently Asked Questions', 'priority' => 0.8, 'changefreq' => 'weekly'],
    ['path' => 'pricing', 'title' => 'Pricing Plans', 'priority' => 0.7, 'changefreq' => 'monthly'],
    ['path' => 'privacy-policy', 'title' => 'Privacy Policy', 'priority' => 0.6, 'changefreq' => 'yearly'],
    ['path' => 'terms-of-use', 'title' => 'Terms of Service', 'priority' => 0.6, 'changefreq' => 'yearly'],
];

// Static pages
foreach ($aiPages as $page) {
    $url = $baseUrl . ($page['path'] ? '/' . $page['path'] : '/');
    $priority = $page['priority'];
    $changefreq = $page['changefreq'];
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

// Get all categories with count
$categoriesQuery = "SELECT id, slug, created_at, updated_at 
                    FROM `" . DB::CATEGORIES . "` 
                    WHERE publish = 1 AND slug IS NOT NULL AND slug <> '' 
                    ORDER BY id ASC 
                    LIMIT 500";
$categoriesResult = $mysqli->query($categoriesQuery);
$categories = $categoriesResult ? $categoriesResult->fetch_all(MYSQLI_ASSOC) : [];

foreach ($categories as $category) {
    $url = $baseUrl . '/category/' . rawurlencode($category['slug']);
    $lastmod = $category['updated_at'] ?: $category['created_at'];
    $priority = 0.8;
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>" . date('Y-m-d', strtotime($lastmod)) . "</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

// Get top companies (AI should prioritize important businesses)
$companiesQuery = "SELECT slug, updated_at, modified_at, created_at 
                   FROM `" . DB::COMPANIES . "` 
                   WHERE publish = 1 AND slug IS NOT NULL AND slug <> '' 
                   ORDER BY updated_at DESC
                   LIMIT 2000";
$companiesResult = $mysqli->query($companiesQuery);
$companies = $companiesResult ? $companiesResult->fetch_all(MYSQLI_ASSOC) : [];

foreach ($companies as $company) {
    $url = $baseUrl . '/company/' . rawurlencode($company['slug']);
    $lastmodSource = $company['updated_at'] ?: ($company['modified_at'] ?: $company['created_at']);
    $lastmod = $lastmodSource ? date('Y-m-d', strtotime($lastmodSource)) : date('Y-m-d');
    $priority = 0.6;
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

// Get all blog posts (AI loves content)
$blogsQuery = "SELECT slug, updated_at, created_at 
               FROM `" . DB::BLOGS . "` 
               WHERE publish = 1 AND slug IS NOT NULL AND slug <> '' 
               ORDER BY updated_at DESC 
               LIMIT 500";
$blogsResult = $mysqli->query($blogsQuery);
$blogs = $blogsResult ? $blogsResult->fetch_all(MYSQLI_ASSOC) : [];

foreach ($blogs as $blog) {
    $url = $baseUrl . '/blog/' . rawurlencode($blog['slug']);
    $lastmodSource = $blog['updated_at'] ?: $blog['created_at'];
    $lastmod = $lastmodSource ? date('Y-m-d', strtotime($lastmodSource)) : date('Y-m-d');
    $priority = 0.7;
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
?>

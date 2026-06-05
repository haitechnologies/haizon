<?php
/**
 * Static Pages Sitemap
 * Route: /sitemap-static.xml
 *
 * Focused sitemap for key static routes.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

$sitemapEnabled = (int)getSystemSetting('sitemap_enabled', 1);
$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));

if ($sitemapEnabled !== 1) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($GLOBALS['basePath'] ?? '');
$baseUrl = rtrim($baseUrl, '/');
if ($sitemapRoot !== '') {
    $baseUrl = rtrim($sitemapRoot, '/');
} elseif ($seoCanonicalUrl !== '') {
    $baseUrl = rtrim($seoCanonicalUrl, '/');
}

$staticPages = [
    ['path' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
    ['path' => '/listings', 'changefreq' => 'daily', 'priority' => '0.9'],
    ['path' => '/categories', 'changefreq' => 'weekly', 'priority' => '0.9'],
    ['path' => '/blog', 'changefreq' => 'daily', 'priority' => '0.8'],
    ['path' => '/search', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['path' => '/partners', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['path' => '/services', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['path' => '/about', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['path' => '/contact', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['path' => '/pricing', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['path' => '/privacy-policy', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['path' => '/terms-of-use', 'changefreq' => 'yearly', 'priority' => '0.4'],
];

header('Content-Type: application/xml; charset=utf-8');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
$today = date('Y-m-d');
foreach ($staticPages as $page) {
    $loc = $baseUrl . $page['path'];
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>{$page['changefreq']}</changefreq>\n";
    echo "    <priority>{$page['priority']}</priority>\n";
    echo "  </url>\n";
}

$cmsPagesSql = "SELECT slug, updated_at, created_at
                FROM `" . DB::PAGES . "`
                WHERE status = 1 AND slug IS NOT NULL AND slug <> ''
                ORDER BY id ASC";
$cmsPagesResult = $mysqli->query($cmsPagesSql);
if ($cmsPagesResult) {
    while ($row = $cmsPagesResult->fetch_assoc()) {
        $loc = $baseUrl . '/page/' . rawurlencode((string)$row['slug']);
        $lastmodSource = $row['updated_at'] ?: $row['created_at'];
        $lastmod = $lastmodSource ? date('Y-m-d', strtotime((string)$lastmodSource)) : $today;

        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "    <changefreq>monthly</changefreq>\n";
        echo "    <priority>0.6</priority>\n";
        echo "  </url>\n";
    }
}

// erp_company_sources table decommissioned — partner URLs excluded from sitemap
$partnerResult = null;
if ($partnerResult) {
    while ($row = $partnerResult->fetch_assoc()) {
        $sourceNameForSlug = trim((string)($row['source'] ?? $row['source_name'] ?? $row['name'] ?? ''));
        $sourceSlug = trim((string)($row['sitemap_slug'] ?? ''));
        if ($sourceSlug === '') {
            $sourceSlug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($sourceNameForSlug));
            $sourceSlug = trim((string)$sourceSlug, '-');
        }
        if ($sourceSlug === '') {
            continue;
        }

        $loc = $baseUrl . '/partner/' . rawurlencode($sourceSlug);
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "    <lastmod>{$today}</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.6</priority>\n";
        echo "  </url>\n";
    }
}
echo "</urlset>\n";

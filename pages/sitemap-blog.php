<?php
/**
 * Blog Sitemap
 * Route: /sitemap-blog.xml
 *
 * Keeps each XML document at <= 45000 URLs.
 * Includes the blog listing page, blog category pages, and blog detail pages.
 * If total blog URLs exceed 45000, this endpoint returns a sitemap index
 * and each part is available via /sitemap-blog.xml?part=N.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

const BLOG_SITEMAP_MAX_URLS = 45000;

$sitemapEnabled = (int)getSystemSetting('sitemap_enabled', 1);
$sitemapBlogs = (int)getSystemSetting('sitemap_blogs', 1);
$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));

if ($sitemapEnabled !== 1 || $sitemapBlogs !== 1) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$requestedPart = isset($_GET['part']) ? (int)$_GET['part'] : 0;

$sitemapCacheTtl = 600;
$sitemapCacheKey = md5('sitemap-blog|' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '|' . ($GLOBALS['basePath'] ?? '') . '|part:' . $requestedPart);
$sitemapCacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_' . $sitemapCacheKey . '.xml';

if (is_file($sitemapCacheFile) && (time() - filemtime($sitemapCacheFile) < $sitemapCacheTtl)) {
    header('Content-Type: application/xml; charset=utf-8');
    $cachedXml = @file_get_contents($sitemapCacheFile);
    if ($cachedXml !== false) {
        echo $cachedXml;
        exit;
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($GLOBALS['basePath'] ?? '');
$baseUrl = rtrim($baseUrl, '/');
if ($sitemapRoot !== '') {
    $baseUrl = rtrim($sitemapRoot, '/');
} elseif ($seoCanonicalUrl !== '') {
    $baseUrl = rtrim($seoCanonicalUrl, '/');
}

$blogCategoryTotal = 0;
$blogCategoryCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::BLOG_CATEGORIES . "` WHERE status = 1 AND slug IS NOT NULL AND slug <> ''");
if ($blogCategoryCountResult) {
    $blogCategoryTotal = (int)($blogCategoryCountResult->fetch_assoc()['total'] ?? 0);
}

$blogTotal = 0;
$blogCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::BLOGS . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($blogCountResult) {
    $blogTotal = (int)($blogCountResult->fetch_assoc()['total'] ?? 0);
}

$totalEntries = 1 + $blogCategoryTotal + $blogTotal;
$totalParts = max(1, (int)ceil($totalEntries / BLOG_SITEMAP_MAX_URLS));
$part = $requestedPart;

header('Content-Type: application/xml; charset=utf-8');
ob_start();
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

if ($totalParts > 1 && $part < 1) {
    echo "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    $today = date('Y-m-d');
    for ($i = 1; $i <= $totalParts; $i++) {
        $loc = $baseUrl . '/sitemap-blog.xml?part=' . $i;
        echo "  <sitemap>\n";
        echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "    <lastmod>{$today}</lastmod>\n";
        echo "  </sitemap>\n";
    }
    echo "</sitemapindex>\n";

    $sitemapXml = ob_get_clean();
    @file_put_contents($sitemapCacheFile, $sitemapXml);
    echo $sitemapXml;
    exit;
}

if ($part < 1) {
    $part = 1;
}
if ($part > $totalParts) {
    $part = $totalParts;
}

echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

$globalOffset = ($part - 1) * BLOG_SITEMAP_MAX_URLS;
$remaining = BLOG_SITEMAP_MAX_URLS;
$today = date('Y-m-d');

if ($globalOffset === 0 && $remaining > 0) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . '/blog', ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
    $remaining--;
}

$categoryGlobalStart = 1;
if ($remaining > 0 && $globalOffset < ($categoryGlobalStart + $blogCategoryTotal)) {
    $categoryOffset = max(0, $globalOffset - $categoryGlobalStart);
    $categoryLimit = min($remaining, max(0, $blogCategoryTotal - $categoryOffset));
    $categorySql = "SELECT slug, updated_at, created_at
                    FROM `" . DB::BLOG_CATEGORIES . "`
                    WHERE status = 1 AND slug IS NOT NULL AND slug <> ''
                    ORDER BY id ASC
                    LIMIT {$categoryOffset}, {$categoryLimit}";
    $categoryResult = $mysqli->query($categorySql);

    if ($categoryResult) {
        while (($row = $categoryResult->fetch_assoc()) && $remaining > 0) {
            $loc = $baseUrl . '/blog/category/' . rawurlencode((string)$row['slug']);
            $lastmodSource = $row['updated_at'] ?: $row['created_at'];
            $lastmod = $lastmodSource ? date('Y-m-d', strtotime((string)$lastmodSource)) : $today;

            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
            $remaining--;
        }
    }
}

$blogGlobalStart = 1 + $blogCategoryTotal;
if ($remaining > 0 && $globalOffset < ($blogGlobalStart + $blogTotal)) {
    $blogOffset = max(0, $globalOffset - $blogGlobalStart);
    $blogLimit = min($remaining, max(0, $blogTotal - $blogOffset));
    $sql = "SELECT slug, updated_at, created_at
            FROM `" . DB::BLOGS . "`
            WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''
            ORDER BY updated_at DESC, id DESC
            LIMIT {$blogOffset}, {$blogLimit}";
    $result = $mysqli->query($sql);

    if ($result) {
        while (($row = $result->fetch_assoc()) && $remaining > 0) {
            $loc = $baseUrl . '/blog/' . rawurlencode((string)$row['slug']);
            $lastmodSource = $row['updated_at'] ?: $row['created_at'];
            $lastmod = $lastmodSource ? date('Y-m-d', strtotime((string)$lastmodSource)) : $today;

            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
            $remaining--;
        }
    }
}

echo "</urlset>\n";

$sitemapXml = ob_get_clean();
@file_put_contents($sitemapCacheFile, $sitemapXml);
echo $sitemapXml;


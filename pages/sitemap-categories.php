<?php
/**
 * Categories Sitemap
 * Route: /sitemap-categories.xml
 *
 * Includes category landing pages, categories, subcategories, and service pages.
 * Keeps each XML document at <= 45000 URLs.
 * If total category URLs exceed 45000, this endpoint returns a sitemap index
 * and each part is available via /sitemap-categories.xml?part=N.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

const CATEGORY_SITEMAP_MAX_URLS = 45000;

$sitemapEnabled = (int)getSystemSetting('sitemap_enabled', 1);
$sitemapCategories = (int)getSystemSetting('sitemap_categories', 1);
$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));

if ($sitemapEnabled !== 1 || $sitemapCategories !== 1) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$requestedPart = isset($_GET['part']) ? (int)$_GET['part'] : 0;

$sitemapCacheTtl = 600;
$sitemapCacheKey = md5('sitemap-categories|' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '|' . ($GLOBALS['basePath'] ?? '') . '|part:' . $requestedPart);
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

$categoryTotal = 0;
$categoryCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::CATEGORIES . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($categoryCountResult) {
    $categoryTotal = (int)($categoryCountResult->fetch_assoc()['total'] ?? 0);
}

$subcategoryTotal = 0;
$subcategoryCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::SUBCATEGORIES . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($subcategoryCountResult) {
    $subcategoryTotal = (int)($subcategoryCountResult->fetch_assoc()['total'] ?? 0);
}

$serviceTotal = 0;
// DB::CATEGORY_ITEMS table decommissioned — service items excluded from sitemap

$totalEntries = 2 + $categoryTotal + $subcategoryTotal + $serviceTotal;
$totalParts = max(1, (int)ceil($totalEntries / CATEGORY_SITEMAP_MAX_URLS));
$part = $requestedPart;

header('Content-Type: application/xml; charset=utf-8');
ob_start();
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

if ($totalParts > 1 && $part < 1) {
    echo "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    $today = date('Y-m-d');
    for ($i = 1; $i <= $totalParts; $i++) {
        $loc = $baseUrl . '/sitemap-categories.xml?part=' . $i;
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

$globalOffset = ($part - 1) * CATEGORY_SITEMAP_MAX_URLS;
$remaining = CATEGORY_SITEMAP_MAX_URLS;
$today = date('Y-m-d');

$staticRoutes = [
    ['/categories', 'weekly', '0.9'],
    ['/services', 'weekly', '0.8'],
];

$staticStart = max(0, $globalOffset);
$staticEnd = min(count($staticRoutes), $globalOffset + CATEGORY_SITEMAP_MAX_URLS);
for ($index = $staticStart; $index < $staticEnd; $index++) {
    [$path, $changefreq, $priority] = $staticRoutes[$index];
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . $path, ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
    $remaining--;
}

$categoryGlobalStart = count($staticRoutes);
if ($remaining > 0 && $globalOffset < ($categoryGlobalStart + $categoryTotal)) {
    $categoryOffset = max(0, $globalOffset - $categoryGlobalStart);
    $categoryLimit = min($remaining, max(0, $categoryTotal - $categoryOffset));
    $categoriesSql = "SELECT slug, updated_at, created_at
                      FROM `" . DB::CATEGORIES . "`
                      WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''
                      ORDER BY id ASC
                      LIMIT {$categoryOffset}, {$categoryLimit}";
    $categoriesResult = $mysqli->query($categoriesSql);

    if ($categoriesResult) {
        while (($row = $categoriesResult->fetch_assoc()) && $remaining > 0) {
        $loc = $baseUrl . '/category/' . rawurlencode((string)$row['slug']);
        $lastmodSource = $row['updated_at'] ?: $row['created_at'];
        $lastmod = $lastmodSource ? date('Y-m-d', strtotime((string)$lastmodSource)) : $today;

        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
        $remaining--;
        }
    }
}

$subcategoryGlobalStart = count($staticRoutes) + $categoryTotal;
if ($remaining > 0 && $globalOffset < ($subcategoryGlobalStart + $subcategoryTotal)) {
    $subcategoryOffset = max(0, $globalOffset - $subcategoryGlobalStart);
    $subcategoryLimit = min($remaining, max(0, $subcategoryTotal - $subcategoryOffset));
    $subcategoriesSql = "SELECT slug, updated_at, created_at
                         FROM `" . DB::SUBCATEGORIES . "`
                         WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''
                         ORDER BY id ASC
                         LIMIT {$subcategoryOffset}, {$subcategoryLimit}";
    $subcategoriesResult = $mysqli->query($subcategoriesSql);

    if ($subcategoriesResult) {
        while (($row = $subcategoriesResult->fetch_assoc()) && $remaining > 0) {
        $loc = $baseUrl . '/subcategory/' . rawurlencode((string)$row['slug']);
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

$serviceGlobalStart = count($staticRoutes) + $categoryTotal + $subcategoryTotal;
if ($remaining > 0 && $globalOffset < ($serviceGlobalStart + $serviceTotal)) {
    $serviceOffset = max(0, $globalOffset - $serviceGlobalStart);
    $serviceLimit = min($remaining, max(0, $serviceTotal - $serviceOffset));
    $serviceSql = "SELECT slug, updated_at, created_at
                   FROM `" . DB::CATEGORY_ITEMS . "`
                   WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''
                   ORDER BY total_companies DESC, id DESC
                   LIMIT {$serviceOffset}, {$serviceLimit}";
    $serviceResult = $mysqli->query($serviceSql);

    if ($serviceResult) {
        while (($row = $serviceResult->fetch_assoc()) && $remaining > 0) {
            $loc = $baseUrl . '/service/' . rawurlencode((string)$row['slug']);
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

echo "</urlset>\n";

$sitemapXml = ob_get_clean();
@file_put_contents($sitemapCacheFile, $sitemapXml);
echo $sitemapXml;


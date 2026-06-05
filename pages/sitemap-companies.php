<?php
/**
 * Companies Sitemap
 * Route: /sitemap-companies.xml
 *
 * Keeps each XML document at <= 45000 URLs.
 * If total company URLs exceed 45000, this endpoint returns a sitemap index
 * and each part is available via /sitemap-companies.xml?part=N.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

const COMPANY_SITEMAP_MAX_URLS = 45000;

$sitemapEnabled = (int)getSystemSetting('sitemap_enabled', 1);
$sitemapCompanies = (int)getSystemSetting('sitemap_companies', 1);
$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));

if ($sitemapEnabled !== 1 || $sitemapCompanies !== 1) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$requestedPart = isset($_GET['part']) ? (int)$_GET['part'] : 0;
$sitemapCacheTtl = 600;
$sitemapCacheKey = md5('sitemap-companies|' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '|' . ($GLOBALS['basePath'] ?? '') . '|part:' . $requestedPart);
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

$total = 0;
$countSql = "SELECT COUNT(*) AS total FROM `" . DB::COMPANIES . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''";
$countResult = $mysqli->query($countSql);
if ($countResult) {
    $total = (int)$countResult->fetch_assoc()['total'];
}

$totalParts = max(1, (int)ceil($total / COMPANY_SITEMAP_MAX_URLS));
$part = $requestedPart;

header('Content-Type: application/xml; charset=utf-8');
ob_start();
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

if ($totalParts > 1 && $part < 1) {
    echo "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    $today = date('Y-m-d');
    for ($i = 1; $i <= $totalParts; $i++) {
        $loc = $baseUrl . '/sitemap-companies.xml?part=' . $i;
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

$offset = ($part - 1) * COMPANY_SITEMAP_MAX_URLS;
$limit = COMPANY_SITEMAP_MAX_URLS;

$sql = "SELECT slug, updated_at, modified_at, created_at
        FROM `" . DB::COMPANIES . "`
        WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''
        ORDER BY id ASC
        LIMIT {$offset}, {$limit}";
$result = $mysqli->query($sql);

echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $loc = $baseUrl . '/company/' . rawurlencode((string)$row['slug']);
        $lastmodSource = $row['updated_at'] ?: ($row['modified_at'] ?: $row['created_at']);
        $lastmod = $lastmodSource ? date('Y-m-d', strtotime((string)$lastmodSource)) : date('Y-m-d');

        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.7</priority>\n";
        echo "  </url>\n";
    }
}
echo "</urlset>\n";

$sitemapXml = ob_get_clean();
@file_put_contents($sitemapCacheFile, $sitemapXml);
echo $sitemapXml;


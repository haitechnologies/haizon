<?php
/**
 * AMP Sitemap
 * Route: /sitemap-amp.xml
 *
 * Includes static AMP routes plus dynamic AMP content URLs.
 * Keeps each XML document at <= 45000 URLs.
 * If total AMP URLs exceed 45000, this endpoint returns a sitemap index
 * and each part is available via /sitemap-amp.xml?part=N.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

const AMP_SITEMAP_MAX_URLS = 45000;

$sitemapEnabled = (int)getSystemSetting('sitemap_enabled', 1);
$sitemapAmp = (int)getSystemSetting('sitemap_amp', 0);
$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));

if ($sitemapEnabled !== 1 || $sitemapAmp !== 1) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$requestedPart = isset($_GET['part']) ? (int)$_GET['part'] : 0;
$sitemapCacheTtl = 600;
$sitemapCacheKey = md5('sitemap-amp|' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '|' . ($GLOBALS['basePath'] ?? '') . '|part:' . $requestedPart);
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

$staticAmpRoutes = [
    '/blog/amp',
    '/listings/amp',
    '/about/amp',
    '/contact/amp',
    '/trade/hs-codes/amp',
];

$staticCount = count($staticAmpRoutes);

$companyTotal = 0;
$companyCountSql = "SELECT COUNT(*) AS total
                    FROM `" . DB::COMPANIES . "`
                    WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''";
$companyCountResult = $mysqli->query($companyCountSql);
if ($companyCountResult) {
    $companyTotal = (int)($companyCountResult->fetch_assoc()['total'] ?? 0);
}

$blogTotal = 0;
$blogCountSql = "SELECT COUNT(*) AS total
                 FROM `" . DB::BLOGS . "`
                 WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''";
$blogCountResult = $mysqli->query($blogCountSql);
if ($blogCountResult) {
    $blogTotal = (int)($blogCountResult->fetch_assoc()['total'] ?? 0);
}

$totalUrls = $staticCount + $companyTotal + $blogTotal;
$totalParts = max(1, (int)ceil($totalUrls / AMP_SITEMAP_MAX_URLS));
$part = $requestedPart;

header('Content-Type: application/xml; charset=utf-8');
ob_start();
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

if ($totalParts > 1 && $part < 1) {
    echo "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    $today = date('Y-m-d');
    for ($i = 1; $i <= $totalParts; $i++) {
        $loc = $baseUrl . '/sitemap-amp.xml?part=' . $i;
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

$globalOffset = ($part - 1) * AMP_SITEMAP_MAX_URLS;
$remaining = AMP_SITEMAP_MAX_URLS;
$emitted = 0;
$today = date('Y-m-d');

$staticStart = max(0, $globalOffset);
$staticEnd = min($staticCount, $globalOffset + AMP_SITEMAP_MAX_URLS);
for ($index = $staticStart; $index < $staticEnd; $index++) {
    $route = $staticAmpRoutes[$index];
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . $route, ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
    $emitted++;
    $remaining--;
}

$companyGlobalStart = $staticCount;
if ($remaining > 0 && $globalOffset < ($companyGlobalStart + $companyTotal)) {
    $companyOffset = max(0, $globalOffset - $companyGlobalStart);
    $companyLimit = min($remaining, max(0, $companyTotal - $companyOffset));
    $companiesSql = "SELECT slug, updated_at, modified_at, created_at
                     FROM `" . DB::COMPANIES . "`
                     WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''
                     ORDER BY id ASC
                     LIMIT {$companyOffset}, {$companyLimit}";
    $companiesResult = $mysqli->query($companiesSql);

    if ($companiesResult) {
        while (($row = $companiesResult->fetch_assoc()) && $remaining > 0) {
            $loc = $baseUrl . '/company/' . rawurlencode((string)$row['slug']) . '/amp';
            $lastmodSource = $row['updated_at'] ?: ($row['modified_at'] ?: $row['created_at']);
            $lastmod = $lastmodSource ? date('Y-m-d', strtotime((string)$lastmodSource)) : $today;

            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
            $emitted++;
            $remaining--;
        }
    }
}

$blogGlobalStart = $staticCount + $companyTotal;
if ($remaining > 0 && $globalOffset < ($blogGlobalStart + $blogTotal)) {
    $blogOffset = max(0, $globalOffset - $blogGlobalStart);
    $blogLimit = min($remaining, max(0, $blogTotal - $blogOffset));
    $blogsSql = "SELECT slug, updated_at, created_at
                 FROM `" . DB::BLOGS . "`
                 WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''
                 ORDER BY updated_at DESC, id DESC
                 LIMIT {$blogOffset}, {$blogLimit}";
    $blogsResult = $mysqli->query($blogsSql);

    if ($blogsResult) {
        while (($row = $blogsResult->fetch_assoc()) && $remaining > 0) {
            $loc = $baseUrl . '/blog/' . rawurlencode((string)$row['slug']) . '/amp';
            $lastmodSource = $row['updated_at'] ?: $row['created_at'];
            $lastmod = $lastmodSource ? date('Y-m-d', strtotime((string)$lastmodSource)) : $today;

            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
            $emitted++;
            $remaining--;
        }
    }
}

echo "</urlset>\n";

$sitemapXml = ob_get_clean();
@file_put_contents($sitemapCacheFile, $sitemapXml);
echo $sitemapXml;


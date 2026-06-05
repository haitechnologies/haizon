<?php
/**
 * Sitemap Index
 * 
 * Master sitemap that lists all available sitemaps
 * Helps search engines discover all sitemaps efficiently
 * URL: /sitemap_index.xml
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

const SITEMAP_COMPANY_MAX_URLS = 45000;
const SITEMAP_AMP_MAX_URLS = 45000;
const SITEMAP_BLOG_MAX_URLS = 45000;
const SITEMAP_CATEGORY_MAX_URLS = 45000;

$sitemapEnabled = (int)getSystemSetting('sitemap_enabled', 1);
$aiSitemapEnabled = (int)getSystemSetting('ai_sitemap_enabled', 1);
$sitemapHsCodes = (int)getSystemSetting('sitemap_hs_codes', 1);
$sitemapCompanies = (int)getSystemSetting('sitemap_companies', 1);
$sitemapBlogs = (int)getSystemSetting('sitemap_blogs', 1);
$sitemapCategories = (int)getSystemSetting('sitemap_categories', 1);
$sitemapAmp = (int)getSystemSetting('sitemap_amp', 0);
$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));
$masterSitemapFilename = trim((string)getSystemSetting('master_sitemap_filename', 'sitemap.xml'));
if (!preg_match('/^[a-z0-9][a-z0-9._-]*\.xml$/i', $masterSitemapFilename)) {
	$masterSitemapFilename = 'sitemap.xml';
}

if ($sitemapEnabled !== 1) {
	header('HTTP/1.0 404 Not Found');
	exit;
}

$sitemapCacheTtl = 600;
$sitemapCacheKey = md5('sitemap-index|' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '|' . ($GLOBALS['basePath'] ?? ''));
$sitemapCacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_' . $sitemapCacheKey . '.xml';

if (is_file($sitemapCacheFile) && (time() - filemtime($sitemapCacheFile) < $sitemapCacheTtl)) {
	header('Content-Type: application/xml; charset=utf-8');
	$cachedXml = @file_get_contents($sitemapCacheFile);
	if ($cachedXml !== false) {
		echo $cachedXml;
		exit;
	}
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
ob_start();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$today = date('Y-m-d');

$companyTotal = 0;
$companyCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::COMPANIES . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($companyCountResult) {
	$companyTotal = (int)($companyCountResult->fetch_assoc()['total'] ?? 0);
}
$companyParts = max(1, (int)ceil($companyTotal / SITEMAP_COMPANY_MAX_URLS));

$blogEntryTotal = 1;
$blogCategoryCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::BLOG_CATEGORIES . "` WHERE status = 1 AND slug IS NOT NULL AND slug <> ''");
if ($blogCategoryCountResult) {
	$blogEntryTotal += (int)($blogCategoryCountResult->fetch_assoc()['total'] ?? 0);
}
$blogCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::BLOGS . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($blogCountResult) {
	$blogEntryTotal += (int)($blogCountResult->fetch_assoc()['total'] ?? 0);
}
$blogParts = max(1, (int)ceil($blogEntryTotal / SITEMAP_BLOG_MAX_URLS));

$categoryEntryTotal = 2;
$categoryCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::CATEGORIES . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($categoryCountResult) {
	$categoryEntryTotal += (int)($categoryCountResult->fetch_assoc()['total'] ?? 0);
}
$subcategoryCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::SUBCATEGORIES . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($subcategoryCountResult) {
	$categoryEntryTotal += (int)($subcategoryCountResult->fetch_assoc()['total'] ?? 0);
}
// DB::CATEGORY_ITEMS table decommissioned — service items excluded from sitemap count
$categoryParts = max(1, (int)ceil($categoryEntryTotal / SITEMAP_CATEGORY_MAX_URLS));

$ampEntryTotal = 5;
$ampCompanyCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::COMPANIES . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($ampCompanyCountResult) {
	$ampEntryTotal += (int)($ampCompanyCountResult->fetch_assoc()['total'] ?? 0);
}
$ampBlogCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM `" . DB::BLOGS . "` WHERE publish = 1 AND slug IS NOT NULL AND slug <> ''");
if ($ampBlogCountResult) {
	$ampEntryTotal += (int)($ampBlogCountResult->fetch_assoc()['total'] ?? 0);
}
$ampParts = max(1, (int)ceil($ampEntryTotal / SITEMAP_AMP_MAX_URLS));

// Main sitemap
echo "  <sitemap>\n";
echo "    <loc>" . htmlspecialchars($baseUrl . '/' . $masterSitemapFilename) . "</loc>\n";
echo "    <lastmod>" . $today . "</lastmod>\n";
echo "  </sitemap>\n";

// Static pages sitemap
echo "  <sitemap>\n";
echo "    <loc>" . htmlspecialchars($baseUrl . '/sitemap-static.xml') . "</loc>\n";
echo "    <lastmod>" . $today . "</lastmod>\n";
echo "  </sitemap>\n";

if ($sitemapCompanies === 1) {
	for ($i = 1; $i <= $companyParts; $i++) {
		$loc = $companyParts > 1 ? ($baseUrl . '/sitemap-companies.xml?part=' . $i) : ($baseUrl . '/sitemap-companies.xml');
		echo "  <sitemap>\n";
		echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
		echo "    <lastmod>" . $today . "</lastmod>\n";
		echo "  </sitemap>\n";
	}
}

if ($sitemapBlogs === 1) {
	for ($i = 1; $i <= $blogParts; $i++) {
		$loc = $blogParts > 1 ? ($baseUrl . '/sitemap-blog.xml?part=' . $i) : ($baseUrl . '/sitemap-blog.xml');
		echo "  <sitemap>\n";
		echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
		echo "    <lastmod>" . $today . "</lastmod>\n";
		echo "  </sitemap>\n";
	}
}

if ($sitemapCategories === 1) {
	for ($i = 1; $i <= $categoryParts; $i++) {
		$loc = $categoryParts > 1 ? ($baseUrl . '/sitemap-categories.xml?part=' . $i) : ($baseUrl . '/sitemap-categories.xml');
		echo "  <sitemap>\n";
		echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
		echo "    <lastmod>" . $today . "</lastmod>\n";
		echo "  </sitemap>\n";
	}
}

// HS Codes sitemap (13,449 trade codes)
if ($sitemapHsCodes === 1) {
	echo "  <sitemap>\n";
	echo "    <loc>" . htmlspecialchars($baseUrl . '/sitemap-hs-codes.xml') . "</loc>\n";
	echo "    <lastmod>" . $today . "</lastmod>\n";
	echo "  </sitemap>\n";
}

// AI Search Engine Sitemap (referenced for AI crawlers to find)
if ($aiSitemapEnabled === 1) {
	echo "  <!-- AI Search Engine Sitemap (Perplexity, Claude, ChatGPT, etc.) -->\n";
	echo "  <sitemap>\n";
	echo "    <loc>" . htmlspecialchars($baseUrl . '/ai-sitemap.xml') . "</loc>\n";
	echo "    <lastmod>" . $today . "</lastmod>\n";
	echo "  </sitemap>\n";
}

if ($sitemapAmp === 1) {
	for ($i = 1; $i <= $ampParts; $i++) {
		$loc = $ampParts > 1 ? ($baseUrl . '/sitemap-amp.xml?part=' . $i) : ($baseUrl . '/sitemap-amp.xml');
		echo "  <sitemap>\n";
		echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
		echo "    <lastmod>" . $today . "</lastmod>\n";
		echo "  </sitemap>\n";
	}
}

echo '</sitemapindex>' . "\n";

$sitemapXml = ob_get_clean();
@file_put_contents($sitemapCacheFile, $sitemapXml);
echo $sitemapXml;
?>


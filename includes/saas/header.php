<?php
/**
 * SaaS Public Layout — Header
 * Lightweight head section for all new SaaS marketing pages.
 * Include this at the top of every pages/saas/*.php file.
 *
 * Pages must set these variables before including:
 *   $pageTitle       string  (required)
 *   $pageDescription string  (optional)
 *   $pageKeywords    string  (optional)
 *   $canonicalUrl    string  (optional – auto-built if omitted)
 *   $bodyClass       string  (optional)
 *   $jsonLdSchema    string  (optional – JSON-LD blocks to inject in <head>)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/globals.php';
if (!function_exists('url')) {
    require_once __DIR__ . '/../helpers.php';
}
if (function_exists('ensureFrontendSessionStarted')) {
    ensureFrontendSessionStarted();
} elseif (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    startFrontendSession();
}

// ── SEO settings (cached) ──────────────────────────────────────────────────
$seoDefaults = [
    'seo_meta_title'           => 'HAIPULSE — Business Software for UAE Teams',
    'seo_meta_description'     => 'Run CRM, Accounting, HR, and Shipping from one platform built for UAE operations.',
    'seo_og_image'             => '',
    'seo_og_site_name'         => 'HAIPULSE',
    'seo_twitter_site'         => '',
    'seo_google_analytics'     => '',
    'seo_google_tag_manager'   => '',
    'seo_google_site_verification' => '',
    'seo_bing_verification'    => '',
    'seo_robots_meta'          => 'index,follow',
];

$seoSettings = $seoDefaults;
$_seoCacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_seo_settings_cache_v1.json';

if (is_file($_seoCacheFile) && (time() - filemtime($_seoCacheFile) < 300)) {
    $_cached = @file_get_contents($_seoCacheFile);
    if ($_cached !== false) {
        $_decoded = json_decode($_cached, true);
        if (is_array($_decoded) && isset($_decoded['settings']) && is_array($_decoded['settings'])) {
            $seoSettings = array_merge($seoDefaults, $_decoded['settings']);
        }
    }
}

if ($seoSettings === $seoDefaults) {
    $mysqli = $GLOBALS['DB']['MSQLI'] ?? null;
    if ($mysqli instanceof mysqli) {
        $slugs = array_keys($seoDefaults);
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $stmt = $mysqli->prepare("SELECT setting_slug, setting_value FROM `" . DB::SYSTEM_SETTINGS . "` WHERE setting_slug IN ($placeholders)");
        if ($stmt) {
            $stmt->bind_param(str_repeat('s', count($slugs)), ...$slugs);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                if (($r['setting_slug'] ?? '') !== '') {
                    $seoSettings[$r['setting_slug']] = (string)($r['setting_value'] ?? '');
                }
            }
            $stmt->close();
            @file_put_contents($_seoCacheFile, json_encode(['settings' => $seoSettings], JSON_UNESCAPED_SLASHES));
        }
    }
}

// ── Page-level variables ──────────────────────────────────────────────────
$pageTitle       = $pageTitle ?? $seoSettings['seo_meta_title'];
$pageDescription = $pageDescription ?? $seoSettings['seo_meta_description'];
$pageKeywords    = $pageKeywords ?? '';
$bodyClass       = trim((string)($bodyClass ?? ''));

$scheme       = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host         = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
$requestPath  = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
$canonicalUrl = $canonicalUrl ?? ($scheme . '://' . $host . $requestPath);

$ogImage      = $seoSettings['seo_og_image'] ?? '';
$ogSiteName   = $seoSettings['seo_og_site_name'] ?? 'HAIPULSE';
$twitterSite  = $seoSettings['seo_twitter_site'] ?? '';
$ga           = $seoSettings['seo_google_analytics'] ?? '';
$gtm          = $seoSettings['seo_google_tag_manager'] ?? '';
$gscVerify    = $seoSettings['seo_google_site_verification'] ?? '';
$bingVerify   = $seoSettings['seo_bing_verification'] ?? '';
$metaRobots   = $seoSettings['seo_robots_meta'] ?? 'index,follow';

// base href for assets
$basePath = $GLOBALS['basePath'] ?? '';
$baseHref = rtrim($scheme . '://' . $host . ($basePath !== '' ? '/' . ltrim($basePath, '/') : ''), '/') . '/';
?>
<!DOCTYPE html>
<html lang="en-AE">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
  <?php if ($pageKeywords !== ''): ?>
  <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords, ENT_QUOTES, 'UTF-8'); ?>">
  <?php endif; ?>
  <meta name="robots" content="<?php echo htmlspecialchars($metaRobots, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">

  <!-- Open Graph -->
  <meta property="og:type"        content="website">
  <meta property="og:title"       content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:url"         content="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:site_name"   content="<?php echo htmlspecialchars($ogSiteName, ENT_QUOTES, 'UTF-8'); ?>">
  <?php if ($ogImage !== ''): ?>
  <meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
  <?php endif; ?>

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
  <?php if ($twitterSite !== ''): ?>
  <meta name="twitter:site" content="<?php echo htmlspecialchars($twitterSite, ENT_QUOTES, 'UTF-8'); ?>">
  <?php endif; ?>

  <?php if ($gscVerify !== ''): ?>
  <meta name="google-site-verification" content="<?php echo htmlspecialchars($gscVerify, ENT_QUOTES, 'UTF-8'); ?>">
  <?php endif; ?>
  <?php if ($bingVerify !== ''): ?>
  <meta name="msvalidate.01" content="<?php echo htmlspecialchars($bingVerify, ENT_QUOTES, 'UTF-8'); ?>">
  <?php endif; ?>

  <base href="<?php echo htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8'); ?>">

  <!-- Bootstrap 5.3 -->
  <link href="assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <!-- Phosphor Icons -->
  <link href="assets/css/icons.css" rel="stylesheet">
  <!-- SaaS Design System -->
  <link href="assets/css/saas.css" rel="stylesheet">

  <?php if (isset($jsonLdSchema) && $jsonLdSchema !== ''): ?>
  <?php echo $jsonLdSchema; ?>
  <?php endif; ?>

  <?php if ($ga !== ''): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($ga, ENT_QUOTES, 'UTF-8'); ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo htmlspecialchars($ga, ENT_QUOTES, 'UTF-8'); ?>');</script>
  <?php endif; ?>
  <?php if ($gtm !== ''): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($gtm, ENT_QUOTES, 'UTF-8'); ?>"></script>
  <?php endif; ?>
</head>
<body class="saas-page<?php echo $bodyClass !== '' ? ' ' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') : ''; ?>">

<?php include __DIR__ . '/saas-navbar.php'; ?>

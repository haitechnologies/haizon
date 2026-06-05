<?php
// Always include database and globals for local/live compatibility
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

// Load SEO defaults with lightweight file cache to avoid repeated DB lookups on every page render.
$seoSettingsDefaults = [
    'seo_meta_title' => 'UAE Business Directory - Find Companies, Services & Businesses in UAE',
    'seo_meta_description' => 'Discover and connect with businesses across UAE. Search companies, read reviews, and find the best services in Dubai, Abu Dhabi, Sharjah and all Emirates.',
    'seo_meta_keywords' => 'UAE business directory, Dubai companies, Abu Dhabi businesses, UAE services, business listings UAE',
    'seo_og_image' => '',
    'seo_og_site_name' => 'UAE Business Directory',
    'seo_twitter_site' => '',
    'seo_google_analytics' => '',
    'seo_google_tag_manager' => '',
    'seo_google_site_verification' => '',
    'seo_bing_verification' => '',
    'seo_robots_meta' => 'index,follow',
];

$seoSettings = $seoSettingsDefaults;
$seoCacheTtl = 300;
$seoCacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_seo_settings_cache_v1.json';

if (is_file($seoCacheFile) && (time() - filemtime($seoCacheFile) < $seoCacheTtl)) {
    $cachedJson = @file_get_contents($seoCacheFile);
    if ($cachedJson !== false) {
        $decoded = json_decode($cachedJson, true);
        if (is_array($decoded) && isset($decoded['settings']) && is_array($decoded['settings'])) {
            $seoSettings = array_merge($seoSettingsDefaults, $decoded['settings']);
        }
    }
}

if ($seoSettings === $seoSettingsDefaults) {
    $mysqli = $GLOBALS['DB']['MSQLI'] ?? null;
    if ($mysqli instanceof mysqli) {
        $slugs = array_keys($seoSettingsDefaults);
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $sql = "SELECT setting_slug, setting_value FROM `" . DB::SYSTEM_SETTINGS . "` WHERE setting_slug IN ($placeholders)";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $types = str_repeat('s', count($slugs));
            $stmt->bind_param($types, ...$slugs);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $slug = (string)($row['setting_slug'] ?? '');
                if ($slug !== '') {
                    $seoSettings[$slug] = (string)($row['setting_value'] ?? '');
                }
            }
            $stmt->close();
            @file_put_contents($seoCacheFile, json_encode(['settings' => $seoSettings], JSON_UNESCAPED_SLASHES));
        }
    }
}

$dbSeoTitle = (string)($seoSettings['seo_meta_title'] ?? $seoSettingsDefaults['seo_meta_title']);
$dbSeoDescription = (string)($seoSettings['seo_meta_description'] ?? $seoSettingsDefaults['seo_meta_description']);
$dbSeoKeywords = (string)($seoSettings['seo_meta_keywords'] ?? $seoSettingsDefaults['seo_meta_keywords']);
$dbOgImage = (string)($seoSettings['seo_og_image'] ?? '');
$dbOgSiteName = (string)($seoSettings['seo_og_site_name'] ?? $seoSettingsDefaults['seo_og_site_name']);
$dbTwitterSite = (string)($seoSettings['seo_twitter_site'] ?? '');
$dbGoogleAnalytics = (string)($seoSettings['seo_google_analytics'] ?? '');
$dbGoogleTagManager = (string)($seoSettings['seo_google_tag_manager'] ?? '');
$dbGoogleSiteVerification = (string)($seoSettings['seo_google_site_verification'] ?? '');
$dbBingVerification = (string)($seoSettings['seo_bing_verification'] ?? '');
$dbRobotsMeta = (string)($seoSettings['seo_robots_meta'] ?? $seoSettingsDefaults['seo_robots_meta']);

// Apply database defaults (pages can override by setting variables before including header.php)
$pageTitle = $pageTitle ?? $dbSeoTitle;
$pageDescription = $pageDescription ?? $dbSeoDescription;
$pageKeywords = $pageKeywords ?? $dbSeoKeywords;
$pageImage = $pageImage ?? $dbOgImage;
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$requestPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?? '/');
$requestPath = $requestPath !== '' ? $requestPath : '/';
$pageUrl = $pageUrl ?? ($scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $requestUri);
$canonicalUrl = $canonicalUrl ?? ($scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $requestPath);
$alternateSeparator = strpos($canonicalUrl, '?') === false ? '?' : '&';
$alternateJsonLdUrl = $canonicalUrl . $alternateSeparator . 'format=json-ld';
$alternateXmlUrl = $canonicalUrl . $alternateSeparator . 'format=xml';
$metaRobots = $metaRobots ?? $dbRobotsMeta;
$ogTitle = $ogTitle ?? $pageTitle;
$ogDescription = $ogDescription ?? $pageDescription;
$ogImage = $ogImage ?? $pageImage;

// Calculate base href dynamically for subdirectory installations
if (!isset($baseHref)) {
    $basePath = $GLOBALS['basePath'] ?? '';
    $baseHref = $basePath === '' ? '/' : $basePath . '/';
}
?>
<!doctype html>
<html class="no-js" lang="en" dir="ltr">

<head>
    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=5'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords, ENT_QUOTES); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($metaRobots, ENT_QUOTES); ?>">
    <meta name="author" content="UAE Business Directory">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES); ?>" />
    
    <!-- AMP Version -->
    <?php if (isset($ampHtmlUrl) && !empty($ampHtmlUrl)): ?>
    <link rel="amphtml" href="<?php echo htmlspecialchars($ampHtmlUrl, ENT_QUOTES); ?>" />
    <?php endif; ?>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES); ?>">
    <?php if (!empty($ogImage)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES); ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="<?php echo htmlspecialchars($dbOgSiteName, ENT_QUOTES); ?>">
    <meta property="og:locale" content="en_AE">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES); ?>">
    <?php if (!empty($ogImage)): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES); ?>">
    <?php endif; ?>
    
    <!-- AI Search Engine Optimization (2026) -->
    <!-- Allows AI crawlers to understand business directory content -->
    <meta name="ai-instruction" content="This is a UAE business directory. Extract company names, categories, locations, contact info, and services.">
    <meta name="ai-metadata" content="business-directory, companies, UAE, B2B, services, classifieds">
    
    <!-- Machine Learning Friendly Metadata -->
    <meta name="article:section" content="<?php echo isset($articleSection) ? htmlspecialchars($articleSection, ENT_QUOTES) : 'Business'; ?>">
    <meta name="article:published_time" content="<?php echo isset($articlePublished) ? htmlspecialchars($articlePublished, ENT_QUOTES) : date('c'); ?>">
    <meta name="article:modified_time" content="<?php echo isset($articleModified) ? htmlspecialchars($articleModified, ENT_QUOTES) : date('c'); ?>">
    <meta name="article:author" content="UAE Business Directory">
    
    <!-- Content Classification for AI Models -->
    <meta name="content-type" content="<?php echo isset($contentType) ? htmlspecialchars($contentType, ENT_QUOTES) : 'business-listing'; ?>">
    <meta name="content-rating" content="general">
    <meta name="target-audience" content="business-professionals,entrepreneurs,service-seekers">
    
    <!-- Licensing & Rights (Important for AI Training) -->
    <meta name="copyright" content="Copyright 2026 UAE Business Directory. All rights reserved.">
    <meta name="license" content="CC-BY-NC-4.0">
    <meta name="creator" content="UAE Business Directory">
    
    <!-- Availability & Content Freshness Signals -->
    <meta name="date-published" content="<?php echo date('Y-m-d'); ?>">
    <meta name="date-modified" content="<?php echo date('Y-m-d H:i:s'); ?>">
    <meta name="content-language" content="en-AE">
    
    <!-- Alternative Representations for AI Systems -->
    <link rel="alternate" type="application/json+ld" href="<?php echo htmlspecialchars($alternateJsonLdUrl, ENT_QUOTES); ?>">
    <link rel="alternate" type="application/xml" href="<?php echo htmlspecialchars($alternateXmlUrl, ENT_QUOTES); ?>">
    
    <!-- Favicon -->
    <?php
    // Dynamic favicon from system settings
    require_once __DIR__ . '/../../config/database.php';
    $favicon = getSystemSetting('favicon', '');
    if (!empty($favicon) && file_exists(__DIR__ . '/../../uploads/system_settings/' . $favicon)) {
        $favicon_url = url('uploads/system_settings/' . $favicon);
        echo '<link rel="icon" href="' . htmlspecialchars($favicon_url, ENT_QUOTES) . '" type="image/x-icon" />';
        echo '<link rel="shortcut icon" type="image/x-icon" href="' . htmlspecialchars($favicon_url, ENT_QUOTES) . '" />';
    } else {
        $favicon_url = url('favicon.ico');
        echo '<link rel="icon" href="' . htmlspecialchars($favicon_url, ENT_QUOTES) . '" type="image/x-icon" />';
        echo '<link rel="shortcut icon" type="image/x-icon" href="' . htmlspecialchars($favicon_url, ENT_QUOTES) . '" />';
    }
    ?>
    <base href="<?php echo htmlspecialchars($baseHref, ENT_QUOTES); ?>">

    <!-- Title -->
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?></title>

    <!-- Bootstrap 5.3.8 - Local version (self-hosted, no CDN dependency) -->
    <link id="style" href="assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Mobile Optimization CSS (responsive design, touch-friendly, performance) -->
    <link href="assets/css/mobile-optimization.css" rel="stylesheet">

    <!-- Icons & Final Design Css -->
    <link href="assets/css/icons.css" rel="stylesheet" />
    <link href="assets/css/final.css" rel="stylesheet" />
    <link href="assets/css/public-mobile-fix.css" rel="stylesheet" />
    <link href="assets/css/responsive-bootstrap-redesign.css" rel="stylesheet" />
    <link href="assets/css/bizdire-public-integration.css" rel="stylesheet" />

    <!-- Structured Data (JSON-LD) -->
    <?php
    // Output structured data if provided
    if (isset($jsonLdSchema)) {
        echo $jsonLdSchema;
    }
    ?>
    
    <!-- Twitter Handle (from Dashboard SEO settings) -->
    <?php if (!empty($dbTwitterSite)): ?>
    <meta name="twitter:site" content="<?php echo htmlspecialchars($dbTwitterSite, ENT_QUOTES); ?>">
    <?php endif; ?>
    
    <!-- Search Console Verification (from Dashboard SEO settings) -->
    <?php if (!empty($dbGoogleSiteVerification)): ?>
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($dbGoogleSiteVerification, ENT_QUOTES); ?>">
    <?php endif; ?>
    
    <?php if (!empty($dbBingVerification)): ?>
    <meta name="msvalidate.01" content="<?php echo htmlspecialchars($dbBingVerification, ENT_QUOTES); ?>">
    <?php endif; ?>
    
    <!-- Google Analytics (from Dashboard SEO settings) -->
    <?php if (!empty($dbGoogleAnalytics)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($dbGoogleAnalytics, ENT_QUOTES); ?>"></script>
    <script nonce="<?php echo $cspNonce ?? ''; ?>">
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo htmlspecialchars($dbGoogleAnalytics, ENT_QUOTES); ?>');
    </script>
    <?php endif; ?>
    
    <!-- Google Tag Manager (from Dashboard SEO settings) -->
    <?php if (!empty($dbGoogleTagManager)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($dbGoogleTagManager, ENT_QUOTES); ?>"></script>
    <?php endif; ?>

    <!-- JSON-LD Schema Markup (Auto-Detection) -->
    <?php include __DIR__ . '/../seo/jsonld_schema_helper.php'; ?>

</head>

<?php $bodyClass = trim((string)($bodyClass ?? '')); ?>
<?php
$bodyClassTokens = preg_split('/\s+/', $bodyClass, -1, PREG_SPLIT_NO_EMPTY);
$currentScriptName = basename((string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
$globalSearchAllowedBodyClasses = [
    'page-company-detail',
    'page-blog-detail',
    'page-about',
    'page-trade',
    'page-tips',
];
$globalSearchAllowedScripts = [
    'company-detail.php',
    'blog-details.php',
    'about.php',
    'trade.php',
    'tips.php',
    'partner.php',
];
$shouldShowGlobalSearchStrip =
    count(array_intersect($bodyClassTokens, $globalSearchAllowedBodyClasses)) > 0 ||
    in_array($currentScriptName, $globalSearchAllowedScripts, true);
?>
<body class="hai-public responsive-bootstrap-redesign<?php echo $bodyClass !== '' ? ' ' . htmlspecialchars($bodyClass, ENT_QUOTES) : ''; ?>">

    <?php include __DIR__ . '/navbar.php'; ?>
    <?php if ($shouldShowGlobalSearchStrip): ?>
    <?php include __DIR__ . '/../partials/public-global-search-strip.php'; ?>
    <?php endif; ?>


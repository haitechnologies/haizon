<?php
/**
 * HAIPULSE - Business Directory & Classifieds Platform
 * Front-End Router (CLEAN - NEW DESIGN ONLY)
 * 
 * Routes all requests to appropriate page templates.
 * Only new design pages included - old template variants removed.
 */

// Initialize frontend error logging
require_once __DIR__ . '/config/logging.php';

// Initialize page load timer
if (!isset($GLOBALS['pageStartTime'])) {
    $GLOBALS['pageStartTime'] = microtime(true);
}

/**
 * Issue a permanent redirect and terminate request processing.
 */
function redirectPermanent($targetPath, array $queryParams = []) {
    $basePath = $GLOBALS['basePath'] ?? '';
    $target = $basePath . $targetPath;

    if (!empty($queryParams)) {
        $target .= '?' . http_build_query($queryParams);
    }

    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $target);
    exit;
}

/**
 * Check if a published, active company exists for the provided slug.
 */
function companySlugExists($slug) {
    static $cache = [];
    static $dbReady = false;
    static $conn = null;
    static $companiesTableExists = null;

    $normalized = strtolower(trim((string) $slug));
    if ($normalized === '' || !preg_match('/^[a-z0-9._-]+$/', $normalized)) {
        return false;
    }

    if (array_key_exists($normalized, $cache)) {
        return $cache[$normalized];
    }

    if (!$dbReady) {
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/classes/DB.php';
        $conn = $GLOBALS['DB']['MSQLI'] ?? null;
        $dbReady = true;
    }

    if (!$conn instanceof mysqli) {
        $cache[$normalized] = false;
        return false;
    }

    if ($companiesTableExists === null) {
        $tableStmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
        if ($tableStmt) {
            $tableName = DB::COMPANIES;
            $tableStmt->bind_param('s', $tableName);
            $tableStmt->execute();
            $tableStmt->store_result();
            $companiesTableExists = $tableStmt->num_rows > 0;
            $tableStmt->close();
        } else {
            $companiesTableExists = false;
        }
    }

    if (!$companiesTableExists) {
        $cache[$normalized] = false;
        return false;
    }

    $sql = "SELECT id FROM `" . DB::COMPANIES . "` WHERE slug = ? AND is_active = 1 AND (publish = 1 OR verified = 1) LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $cache[$normalized] = false;
        return false;
    }

    $stmt->bind_param('s', $normalized);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();

    $cache[$normalized] = $exists;
    return $exists;
}

/**
 * Load manual replacement redirects for missing company slugs.
 */
function getCompanySlugRedirectMap() {
    static $redirectMap = null;

    if ($redirectMap !== null) {
        return $redirectMap;
    }

    $redirectMap = [];
    $configPath = __DIR__ . '/config/company_slug_redirects.php';
    if (is_file($configPath)) {
        $loaded = require $configPath;
        if (is_array($loaded)) {
            foreach ($loaded as $slug => $targetPath) {
                $normalizedSlug = strtolower(trim((string) $slug));
                $normalizedTarget = trim((string) $targetPath);
                if ($normalizedSlug !== '' && $normalizedTarget !== '') {
                    $redirectMap[$normalizedSlug] = $normalizedTarget;
                }
            }
        }
    }

    return $redirectMap;
}

/**
 * Load explicit retirement list for missing company slugs.
 */
function getRetiredCompanySlugSet() {
    static $retiredSlugs = null;

    if ($retiredSlugs !== null) {
        return $retiredSlugs;
    }

    $retiredSlugs = [];
    $configPath = __DIR__ . '/config/company_slug_retired.php';
    if (is_file($configPath)) {
        $loaded = require $configPath;
        if (is_array($loaded)) {
            foreach ($loaded as $slug) {
                $normalizedSlug = strtolower(trim((string) $slug));
                if ($normalizedSlug !== '') {
                    $retiredSlugs[$normalizedSlug] = true;
                }
            }
        }
    }

    return $retiredSlugs;
}

/**
 * Resolve a missing company slug to either a replacement redirect or retirement marker.
 */
function resolveMissingCompanySlugAction($slug) {
    $normalized = strtolower(trim((string) $slug));
    if ($normalized === '') {
        return null;
    }

    $redirectMap = getCompanySlugRedirectMap();
    if (isset($redirectMap[$normalized])) {
        return [
            'type' => 'redirect',
            'target' => $redirectMap[$normalized],
        ];
    }

    $retiredSlugs = getRetiredCompanySlugSet();
    if (isset($retiredSlugs[$normalized])) {
        return [
            'type' => 'gone',
        ];
    }

    return null;
}

/**
 * Send a 410 Gone response using the public 404 template.
 */
function renderGoneResponse($path) {
    http_response_code(410);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    $GLOBALS['error_status_code'] = 410;
    $GLOBALS['error_page_variant'] = 'retired';

    if (isset($GLOBALS['frontendLogger'])) {
        $GLOBALS['frontendLogger']->log404($path, $_SERVER['HTTP_REFERER'] ?? 'direct');
    }

    include __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . '404.php';
    exit;
}

/**
 * Send a silent 410 Gone response â€” no logging, no template.
 *
 * Use this for high-volume known-bad URL patterns (legacy slugs, bot probes)
 * where logging would only generate noise without diagnostic value.
 */
function renderSilentGoneResponse() {
    http_response_code(410);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    exit;
}

/**
 * Determine if a 404 should be excluded from frontend error logging.
 * Keep responses intact, only suppress noisy non-actionable log entries.
 */
function shouldSuppressFrontend404Log(string $path, string $userAgent = '', string $referrer = ''): bool {
    $normalizedPath = strtolower(trim($path));
    $normalizedUa = strtolower($userAgent);
    $normalizedReferrer = strtolower($referrer);

    // High-volume single-token probes with no business value.
    if (preg_match('#^/(no|c)/?$#i', $normalizedPath)) {
        return true;
    }

    // External domains injected into path, e.g. /www.example.com
    if (preg_match('#^/(?:www\.)?[a-z0-9-]+(?:\.[a-z0-9-]+)+/?$#i', $normalizedPath)) {
        return true;
    }

    // Legacy malformed company slugs with plus signs from old exports.
    if (preg_match('#^/company/.+\+.+$#i', $normalizedPath)) {
        return true;
    }

    // Missing article images requested by crawlers are noisy and non-user facing.
    if (strpos($normalizedPath, '/uploads/articles/') === 0) {
        $isBotRequest = (
            strpos($normalizedUa, 'googlebot') !== false ||
            strpos($normalizedUa, 'mediapartners-google') !== false ||
            strpos($normalizedUa, 'tiktokspider') !== false ||
            strpos($normalizedUa, 'deadlinkchecker') !== false ||
            strpos($normalizedUa, 'python-requests') !== false
        );

        if ($isBotRequest || strpos($normalizedReferrer, '/amp/') !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Attempt to resolve an unrecognised company slug by prefix-matching against the DB.
 *
 * Old URLs were generated as "{company-name}-{category-slug}-{city}" while the DB
 * stores "{company-name}-{address}-{city}".  When an exact slug lookup fails, try
 * progressively longer prefixes (3 â†’ 6 tokens) until exactly one active, published
 * company matches.  We require the longest unique prefix to minimise false positives.
 *
 * Returns the canonical DB slug on an unambiguous match, or null otherwise.
 */
function resolveCompanySlugByPrefix(string $slug): ?string {
    static $dbReady  = false;
    static $conn     = null;
    static $cache    = [];
    static $companiesTableExists = null;

    $slug = strtolower(trim($slug));
    if ($slug === '') {
        return null;
    }

    if (array_key_exists($slug, $cache)) {
        return $cache[$slug];
    }

    // Filter empty tokens produced by double-hyphens (e.g. "salons--dubai").
    $tokens = array_values(array_filter(explode('-', $slug), static fn($t) => $t !== ''));
    $total  = count($tokens);

    // Need at least 3 tokens to attempt a meaningful prefix match.
    if ($total < 3) {
        $cache[$slug] = null;
        return null;
    }

    if (!$dbReady) {
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/classes/DB.php';
        $conn    = $GLOBALS['DB']['MSQLI'] ?? null;
        $dbReady = true;
    }

    if (!$conn instanceof mysqli) {
        $cache[$slug] = null;
        return null;
    }

    if ($companiesTableExists === null) {
        $tableStmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
        if ($tableStmt) {
            $tableName = DB::COMPANIES;
            $tableStmt->bind_param('s', $tableName);
            $tableStmt->execute();
            $tableStmt->store_result();
            $companiesTableExists = $tableStmt->num_rows > 0;
            $tableStmt->close();
        } else {
            $companiesTableExists = false;
        }
    }

    if (!$companiesTableExists) {
        $cache[$slug] = null;
        return null;
    }

    $sql  = "SELECT slug FROM `" . DB::COMPANIES . "` WHERE slug LIKE ? AND is_active = 1 AND (publish = 1 OR verified = 1) LIMIT 2";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $cache[$slug] = null;
        return null;
    }

    $canonical = null;

    // Try prefix lengths from 3 up to min(6, total-1) tokens.
    // Stop at the shortest prefix that produces exactly one match.
    $maxLen = min(6, $total - 1);
    for ($len = 3; $len <= $maxLen; $len++) {
        $like = implode('-', array_slice($tokens, 0, $len)) . '%';
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result->fetch_all(MYSQLI_ASSOC);

        if (count($rows) === 1) {
            $canonical = $rows[0]['slug'];
            break; // Unambiguous match found â€” stop searching.
        }
        // Multiple matches â†’ prefix is too short, try longer.
        // Zero matches â†’ this prefix is not in the DB, no point continuing.
        if (count($rows) === 0) {
            break;
        }
    }

    $stmt->close();

    $cache[$slug] = $canonical;
    return $canonical;
}

// ============================================
// SAAS PUBLIC ROUTES
// ============================================
// New SaaS marketing site. Legacy public directory/classifieds routes
// are handled below with 301 redirects.
$routes = [
    // SaaS marketing pages
    '/'           => 'pages/saas/home.php',
    '/crm'        => 'pages/saas/crm.php',
    '/hr'         => 'pages/saas/hr.php',
    '/accounting' => 'pages/saas/accounting.php',
    '/shipping'   => 'pages/saas/shipping.php',
    '/all-in-one' => 'pages/saas/all-in-one.php',
    '/pricing'    => 'pages/saas/pricing.php',

    // Utility / technical
    '/captcha-image' => 'pages/captcha-image.php',
    '/contact'       => 'pages/contact.php',
    '/about'         => 'pages/about.php',
    '/unsubscribe'   => 'pages/unsubscribe.php',

    // Legal
    '/privacy-policy' => 'pages/privacy-policy.php',
    '/terms-of-use'   => 'pages/terms-of-use.php',
    '/cookies-policy' => 'pages/cookies-policy.php',
    '/refund-policy'  => 'pages/refund-policy.php',
    '/gdpr'           => 'pages/gdpr.php',
    '/uae-pdpl'       => 'pages/uae-pdpl.php',
    '/ccpa'           => 'pages/ccpa.php',
    '/accessibility'  => 'pages/accessibility.php',
    '/security'       => 'pages/security.php',
    '/data-request'   => 'pages/data-request.php',

    // Sitemaps (kept for technical / SEO continuity)
    '/sitemap.xml'          => 'pages/sitemap.php',
    '/sitemap_index.xml'    => 'pages/sitemap-index.php',
    '/sitemap-static.xml'   => 'pages/sitemap-static.php',
    '/sitemap'              => 'pages/sitemap.php',
];

// ============================================
// PATH PARSING & NORMALIZATION
// ============================================
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = $path === null ? '/' : $path;
$path = $path === '/' ? '/' : rtrim($path, '/');

// Handle subdirectory installations (e.g., /haipulse/index.php)
$basePath = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
$basePath = str_replace('\\', '/', $basePath);
$basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
$GLOBALS['basePath'] = $basePath;

if ($basePath !== '' && $path !== '/' && stripos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
    $path = $path === '' ? '/' : $path;
}

// Handle direct access to index.php (should redirect to homepage)
if ($path === '/index.php' || $path === 'index.php') {
    $path = '/';
}

// Support environments/links that include index.php in the URL path.
if (stripos($path, '/index.php/') === 0) {
    $path = substr($path, strlen('/index.php'));
    $path = $path === '' ? '/' : $path;
}

// ============================================
// LEGACY PUBLIC 301 REDIRECTS
// All old directory / classifieds / blog paths redirect to SaaS destinations.
// ============================================
$_legacyRedirects = [
    '/software'             => '/',
    '/software-pricing'     => '/pricing',
    '/listings'             => '/',
    '/blog'                 => '/',
    '/blog/submit'          => '/contact',
    '/categories'           => '/',
    '/business-directory'   => '/',
    '/services'             => '/all-in-one',
    '/partners'             => '/contact',
    '/ads'                  => '/',
    '/ad-click'             => '/',
    '/tips'                 => '/',
    '/trade'                => '/',
    '/trade/hs-codes'       => '/',
    '/trade/hs-code-finder' => '/',
    '/trending'             => '/',
    '/add-business'         => '/',
    '/company-detail'       => '/',
    '/category'             => '/',
    '/subscribe-checkout'   => '/pricing',
    '/subscribe-success'    => '/pricing',
    '/subscribe-cancel'     => '/pricing',
    '/login'                => '/dashboard/',
    '/register'             => '/dashboard/',
    '/forgot-password'      => '/dashboard/',
    '/reset-password'       => '/dashboard/',
    '/logout'               => '/dashboard/',
    '/verify-email'         => '/dashboard/',
    '/verify-email-pending' => '/dashboard/',
    '/account/settings'     => '/dashboard/',
    '/account/profile'      => '/dashboard/',
    '/account/search-analytics' => '/dashboard/',
    '/user-settings'        => '/dashboard/',
    '/my-favorites'         => '/dashboard/',
    '/my-searches'          => '/dashboard/',
    '/my-posts'             => '/dashboard/',
    '/search-history'       => '/dashboard/',
    '/search-analytics'     => '/dashboard/',
    '/search/analytics'     => '/dashboard/',
    '/email-preferences'    => '/dashboard/',
    '/underconstruction'    => '/',
    '/about/amp'            => '/about',
    '/contact/amp'          => '/contact',
    '/blog/amp'             => '/',
    '/listings/amp'         => '/',
    '/sitemap-hs-codes.xml' => '/sitemap.xml',
    '/sitemap-companies.xml'=> '/sitemap.xml',
    '/sitemap-blog.xml'     => '/sitemap.xml',
    '/sitemap-categories.xml' => '/sitemap.xml',
    '/sitemap-amp.xml'      => '/sitemap.xml',
    '/ai-sitemap.xml'       => '/sitemap.xml',
    '/pricing'              => '/pricing', // keep canonical
];

if (isset($_legacyRedirects[$path])) {
    $target = $_legacyRedirects[$path];
    // Only redirect if not already the canonical destination
    if ($target !== $path) {
        redirectPermanent($target);
    }
}

$isDashboardPath = ($path === '/dashboard' || stripos($path, '/dashboard/') === 0);

// Keep legacy dashboard-* aliases on frontend-safe routes.
if ($path === '/dashboard') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $basePath . '/dashboard/');
    exit;
}
if ($path === '/dashboard-favorites') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $basePath . '/my-favorites');
    exit;
}
if ($path === '/dashboard-searches') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $basePath . '/my-searches');
    exit;
}
if ($path === '/dashboard-history') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $basePath . '/search-history');
    exit;
}

// Legacy search paths → SaaS homepage
if ($path === '/search' || $path === '/search/advanced') {
    redirectPermanent('/');
}

if (preg_match('#^/search/(.+)$#i', $path, $legacySearchMatches) && !preg_match('#^/search/analytics/?$#i', $path)) {
    redirectPermanent('/');
}

// Legacy classifieds, business submission, shortlinks → SaaS homepage
if ($path === '/classifieds') {
    redirectPermanent('/');
}

if ($path === '/add-company' || $path === '/add-company-thanks' || $path === '/create-business-listing') {
    redirectPermanent('/');
}

if (preg_match('#^/bit/[a-z0-9_-]+$#i', $path)) {
    redirectPermanent('/');
}

$masterSitemapFilename = 'sitemap.xml';
if (!function_exists('getSystemSetting')) {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/globals.php';
}
if (function_exists('getSystemSetting')) {
    $masterSitemapFilename = trim((string)getSystemSetting('master_sitemap_filename', 'sitemap.xml'));
}
if (!preg_match('/^[a-z0-9][a-z0-9._-]*\.xml$/i', $masterSitemapFilename)) {
    $masterSitemapFilename = 'sitemap.xml';
}
$masterSitemapPath = '/' . ltrim($masterSitemapFilename, '/');

if ($path === '/sitemap.xml' && $masterSitemapPath !== '/sitemap.xml') {
    redirectPermanent($masterSitemapPath);
}

// ============================================
// ROUTE MATCHING
// ============================================
$routeParams = [];
$template = $routes[$path] ?? null;

if ($template === null && $path === $masterSitemapPath) {
    $_GET['format'] = 'xml';
    $template = 'pages/sitemap.php';
}

// Dynamic routes (regex patterns)
if ($template === null) {
    // -----------------------------------------------------------------------
    // EARLY SUPPRESSIONS â€” noisy bot/legacy patterns that should never log.
    // -----------------------------------------------------------------------

    // Old data had phone numbers embedded in URL paths (e.g. /listing/oud/tel:037351339
    // or /company/tel:0097143325333). None of these are valid routes; return 410 Gone
    // silently so they stop appearing in error logs.
    if (strpos($path, '/tel:') !== false) {
        http_response_code(410);
        exit;
    }

    // Suppress requests for external data feed files that scrapers send
    // (e.g. /gse/companies_2gis_4.xml, /gse/companies_yello_6.xml).
    if (strpos($path, '/gse/') === 0) {
        http_response_code(410);
        exit;
    }

    // Suppress browser/crawler well-known probes (e.g. /.well-known/traffic-advice).
    if (strpos($path, '/.well-known/') === 0) {
        http_response_code(410);
        exit;
    }

    // Suppress missing static asset requests that fall through to the router
    // (e.g. /assets/images/logo.png when file doesn't exist on disk).
    // The .htaccess should serve real files directly, but missing ones reach here.
    if (strpos($path, '/assets/') === 0) {
        http_response_code(404);
        exit;
    }

    // -----------------------------------------------------------------------
    // AMP Routes (must come first to prevent conflicts)
    // Legacy article AMP: /article/{slug}/amp or /articles/{slug}/amp -> /blog/{slug}/amp
    if (preg_match('#^/articles?/([a-z0-9._-]+)/amp$#i', $path, $matches)) {
        redirectPermanent('/blog/' . strtolower($matches[1]) . '/amp');
    }
    // Blog detail AMP: /blog/{slug}/amp
    elseif (preg_match('#^/blog/([a-z0-9._-]+)/amp$#i', $path, $matches)) {
        $template = 'pages/amp/blog-detail-amp.php';
        $routeParams['blog_slug'] = $matches[1];
    }
    // Company detail AMP: /company/{slug}/amp
    elseif (preg_match('#^/company/([a-z0-9._-]+)/amp$#i', $path, $matches)) {
        $companySlug = strtolower($matches[1]);
        if (!companySlugExists($companySlug)) {
            $missingSlugAction = resolveMissingCompanySlugAction($companySlug);
            if (is_array($missingSlugAction) && ($missingSlugAction['type'] ?? '') === 'redirect') {
                redirectPermanent((string) $missingSlugAction['target']);
            }
            if (is_array($missingSlugAction) && ($missingSlugAction['type'] ?? '') === 'gone') {
                renderGoneResponse($path);
            }
            // Fuzzy fallback: old URLs used {name}-{category}-{city} format.
            $canonicalSlug = resolveCompanySlugByPrefix($companySlug);
            if ($canonicalSlug !== null && $canonicalSlug !== $companySlug) {
                redirectPermanent('/company/' . $canonicalSlug . '/amp');
            }
            // No resolution - silent 410 so these legacy slugs never appear in
            // error logs. Use renderSilentGoneResponse() not renderGoneResponse()
            // to avoid hundreds of log entries per hour for old-format URLs.
            renderSilentGoneResponse();
        }

        $template = 'pages/amp/company-detail-amp.php';
        $routeParams['company_slug'] = $companySlug;
    }
    // HS code detail AMP: /trade/hs-code/{code}/amp
    elseif (preg_match('#^/trade/hs-code/([a-z0-9.]+)/amp$#i', $path, $matches)) {
        $template = 'pages/amp/hs-code-detail-amp.php';
        // Keep numeric index for legacy page access and named key for newer handlers.
        $routeParams[0] = $matches[1];
        $routeParams['hs_code'] = $matches[1];
    }
    // Dynamic company page: /company/{slug}
    elseif (preg_match('#^/company/([a-z0-9._-]+)$#i', $path, $matches)) {
        $companySlug = strtolower($matches[1]);
        if (!companySlugExists($companySlug)) {
            $missingSlugAction = resolveMissingCompanySlugAction($companySlug);
            if (is_array($missingSlugAction) && ($missingSlugAction['type'] ?? '') === 'redirect') {
                redirectPermanent((string) $missingSlugAction['target']);
            }
            if (is_array($missingSlugAction) && ($missingSlugAction['type'] ?? '') === 'gone') {
                renderGoneResponse($path);
            }
            // Fuzzy fallback: old URLs used {name}-{category}-{city} format.
            $canonicalSlug = resolveCompanySlugByPrefix($companySlug);
            if ($canonicalSlug !== null && $canonicalSlug !== $companySlug) {
                redirectPermanent('/company/' . $canonicalSlug);
            }
            // No resolution - silent 410 so these legacy slugs never appear in
            // error logs. Use renderSilentGoneResponse() not renderGoneResponse()
            // to avoid hundreds of log entries per hour for old-format URLs.
            renderSilentGoneResponse();
        }

        $template = 'pages/company-detail.php';
        $routeParams['company_slug'] = $companySlug;
    }
    // Legacy AMP company route: /amp/company/{slug} -> /company/{slug}/amp
    elseif (preg_match('#^/amp/company/([a-z0-9._-]+)$#i', $path, $matches)) {
        redirectPermanent('/company/' . strtolower($matches[1]) . '/amp');
    }
    // Legacy listing route: /listing/{slug}[/page] -> /company/{slug} if valid, else /listings
    elseif (preg_match('#^/listing/([a-z0-9._-]+)(?:/\d+)?$#i', $path, $matches)) {
        $legacySlug = strtolower($matches[1]);
        if (companySlugExists($legacySlug)) {
            redirectPermanent('/company/' . $legacySlug);
        }

        redirectPermanent('/listings');
    }
    // Dynamic category page: /category/{slug}
    elseif (preg_match('#^/category/([a-z0-9._-]+)$#i', $path, $matches)) {
        $template = 'pages/category.php';
        $routeParams['category_slug'] = $matches[1];
    }
    // Dynamic subcategory page: /subcategory/{slug}
    elseif (preg_match('#^/subcategory/([a-z0-9._-]+)$#i', $path, $matches)) {
        $template = 'pages/subcategory.php';
        $routeParams['subcategory_slug'] = $matches[1];
    }
    // Dynamic service page: /service/{slug}
    elseif (preg_match('#^/service/([a-z0-9._-]+)$#i', $path, $matches)) {
        $template = 'pages/service.php';
        $routeParams['service_slug'] = $matches[1];
    }
    // Dynamic blog category page: /blog/category/{slug}
    elseif (preg_match('#^/blog/category/([a-z0-9._-]+)$#i', $path, $matches)) {
        $template = 'pages/blog-category.php';
        $routeParams['blog_category_slug'] = $matches[1];
    }
    // Dynamic partner page: /partner/{slug}
    elseif (preg_match('#^/partner/([a-z0-9._-]+)$#i', $path, $matches)) {
        $template = 'pages/partner.php';
        $routeParams['partner_slug'] = $matches[1];
    }
    // Deprecated blog archive pages now permanently redirect to the main blog listing.
    elseif (preg_match('#^/blog/archive/(\d{4})/(\d{1,2})$#i', $path)) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/blog'));
        exit;
    }
    // Deprecated blog author pages now permanently redirect to the main blog listing.
    elseif (preg_match('#^/blog/author/([a-z0-9._-]+)$#i', $path)) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/blog'));
        exit;
    }
    // Deprecated compare page now permanently redirects to listings.
    elseif ($path === '/compare') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/listings'));
        exit;
    }
    // Deprecated reviews page now permanently redirects to listings.
    elseif ($path === '/reviews') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/listings'));
        exit;
    }
    // Legacy article route: /article/{slug} or /articles/{slug} -> /blog/{slug}
    elseif (preg_match('#^/articles?/([a-z0-9._-]+)$#i', $path, $matches)) {
        redirectPermanent('/blog/' . strtolower($matches[1]));
    }
    // Dynamic blog page: /blog/{slug}
    elseif (preg_match('#^/blog/([a-z0-9._-]+)$#i', $path, $matches)) {
        $template = 'pages/blog-details.php';
        $routeParams['blog_slug'] = $matches[1];
    }
    // Dynamic HS code page: /trade/hs-code/{code}
    elseif (preg_match('#^/trade/hs-code/([a-z0-9.]+)$#i', $path, $matches)) {
        $template = 'pages/hs-code.php';
        // Keep numeric index for legacy page access and named key for newer handlers.
        $routeParams[0] = $matches[1];
        $routeParams['hs_code'] = $matches[1];
    }
    // Dynamic HS codes level filter: /trade/hs-codes/level/{2,4,6,8,10}
    elseif (preg_match('#^/trade/hs-codes/level/(\d+)$#i', $path, $matches)) {
        $template = 'pages/hs-codes.php';
        $routeParams['hs_level'] = intval($matches[1]);
    }
    // Dynamic CMS pages: /page/{slug}
    elseif (preg_match('#^/page/([a-z0-9._-]+)$#i', $path, $matches)) {
        $template = 'pages/page.php';
        $routeParams['page_slug'] = $matches[1];
    }
    // Sitemap XML endpoints
    elseif ($path === '/sitemap.xml') {
        $_GET['format'] = 'xml';
        $template = 'pages/sitemap.php';
    }

    // Legacy malformed URLs containing an external site segment map by company slug.
    if ($template === null && preg_match('#^/([a-z0-9._-]+)/www\.[^/]+$#i', $path, $matches)) {
        $legacySlug = strtolower($matches[1]);
        if (companySlugExists($legacySlug)) {
            redirectPermanent('/company/' . $legacySlug);
        }
    }

    // Legacy root slugs map to canonical company pages when slug exists.
    if ($template === null && preg_match('#^/([a-z0-9._-]+)$#i', $path, $matches)) {
        $legacySlug = strtolower($matches[1]);
        if (companySlugExists($legacySlug)) {
            redirectPermanent('/company/' . $legacySlug);
        }
    }
}

// ============================================
// 404 HANDLING
// ============================================
if ($template === null) {
    http_response_code(404);
    
    // Log 404 error
    if (
        isset($GLOBALS['frontendLogger']) &&
        !shouldSuppressFrontend404Log(
            $path,
            (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            (string)($_SERVER['HTTP_REFERER'] ?? '')
        )
    ) {
        $GLOBALS['frontendLogger']->log404($path, $_SERVER['HTTP_REFERER'] ?? 'direct');
    }
    
    $template = 'pages/404.php';
}

// ============================================
// TEMPLATE LOADING
// ============================================
$templatePath = __DIR__ . DIRECTORY_SEPARATOR . $template;

if (!is_file($templatePath)) {
    http_response_code(404);
    
    if (
        isset($GLOBALS['frontendLogger']) &&
        !shouldSuppressFrontend404Log(
            $path,
            (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            (string)($_SERVER['HTTP_REFERER'] ?? '')
        )
    ) {
        $GLOBALS['frontendLogger']->log404($path, $_SERVER['HTTP_REFERER'] ?? 'direct');
    }
    
    $templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . '404.php';
}

// Store route parameters globally
$GLOBALS['route_params'] = $routeParams;

// Load PHP template
if (strtolower(substr($templatePath, -4)) === '.php') {
    include $templatePath;
    return;
}

// Fallback: Load static HTML file (rare case)
$html = file_get_contents($templatePath);
if ($html === false) {
    http_response_code(500);
    echo 'Template render failed.';
    exit;
}

// Add base href if missing
if (stripos($html, '<base ') === false) {
    $baseTag = "  <base href=\"{$baseHref}\">\n";
    $html = preg_replace('/<head(\\b[^>]*)>/i', '<head$1>' . "\n" . $baseTag, $html, 1);
}

echo $html;



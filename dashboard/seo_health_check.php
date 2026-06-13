<?php
/**
 * SEO Health Check Dashboard
 * 
 * Comprehensive SEO audit covering:
 * - robots.txt configuration (frontend/dashboard)
 * - Sitemap endpoints & handlers
 * - AI search engines compatibility (GPTBot, Claude, Perplexity, etc.)
 * - Structured data / JSON-LD schema implementation
 * - Security headers (CSP, X-Frame-Options, etc.)
 * - Meta tags (Open Graph, Twitter Cards, viewport)
 * - Core Web Vitals & Performance hints
 * - Mobile-first indexing & responsive design
 * - Canonical URLs & duplicate content prevention
 * - Real-time endpoint testing
 * 
 * Last Updated: March 10, 2026
 * Standards: Google 2026, AI search engines, Core Web Vitals
 */

include('admin_elements/admin_header.php');

$module = 'seo';
$module_caption = 'SEO Health Check';
$checks = [];

$projectRoot = realpath(__DIR__ . '/..');
$settingsPageUrl = 'global_settings.php?tab=seo';

$globalSeoConfig = [
    'sitemap_enabled' => (int)getSystemSetting('sitemap_enabled', 1),
    'ai_sitemap_enabled' => (int)getSystemSetting('ai_sitemap_enabled', 1),
    // Decommissioned: sitemap_companies & sitemap_blogs
    'sitemap_categories' => (int)getSystemSetting('sitemap_categories', 1),
    'sitemap_hs_codes' => (int)getSystemSetting('sitemap_hs_codes', 1),
    'sitemap_amp' => (int)getSystemSetting('sitemap_amp', 0),
    'seo_hsts_required' => (int)getSystemSetting('seo_hsts_required', 1),
    'seo_ai_policy_mode' => (string)getSystemSetting('seo_ai_policy_mode', 'inherit'),
];

$masterSitemapFilename = trim((string)getSystemSetting('master_sitemap_filename', 'sitemap.xml'));
if (!preg_match('/^[a-z0-9][a-z0-9._-]*\.xml$/i', $masterSitemapFilename)) {
    $masterSitemapFilename = 'sitemap.xml';
}
$masterSitemapPath = '/' . ltrim($masterSitemapFilename, '/');

/**
 * Resolve a project-relative path to absolute path safely.
 */
function seo_abs_path($projectRoot, $relativePath) {
    return rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
}

/**
 * Add a simple file existence detail line to a check block.
 */
function seo_append_file_detail(&$check, $projectRoot, $relativePath, $label) {
    $abs = seo_abs_path($projectRoot, $relativePath);
    if (is_file($abs)) {
        $check['details'][] = '✓ ' . $label . ' (' . $relativePath . ')';
        return true;
    }

    $check['status'] = ($check['status'] === 'error') ? 'error' : 'warning';
    $check['details'][] = '⚠ ' . $label . ' missing (' . $relativePath . ')';
    return false;
}

// ============================================================================
// ROBOTS.TXT CHECKS
// ============================================================================

$checks['frontend_robots'] = [
    'name' => 'Frontend robots.txt',
    'path' => '../robots.txt',
    'status' => 'unchecked',
    'message' => '',
    'details' => []
];

if (file_exists('../robots.txt')) {
    $checks['frontend_robots']['status'] = 'success';
    $checks['frontend_robots']['message'] = 'File exists and is readable';
    $checks['frontend_robots']['details'] = [
        'File Size: ' . number_format(filesize('../robots.txt')) . ' bytes',
        'Last Modified: ' . date('Y-m-d H:i:s', filemtime('../robots.txt')),
        'Readable: ✓'
    ];
    
    // Check content
    $robots_content = file_get_contents('../robots.txt');
    if (strpos($robots_content, 'User-agent:') !== false && strpos($robots_content, 'Disallow:') !== false) {
        $checks['frontend_robots']['details'][] = 'Valid robots.txt format detected';
    } else {
        $checks['frontend_robots']['status'] = 'warning';
        $checks['frontend_robots']['details'][] = '⚠️ robots.txt format may be invalid';
    }
} else {
    $checks['frontend_robots']['status'] = 'error';
    $checks['frontend_robots']['message'] = 'File not found or not readable';
}

$checks['dashboard_robots'] = [
    'name' => 'Dashboard robots.txt',
    'path' => 'robots.txt',
    'status' => 'unchecked',
    'message' => '',
    'details' => []
];

if (file_exists('robots.txt')) {
    $checks['dashboard_robots']['status'] = 'info';
    $checks['dashboard_robots']['message'] = 'Dashboard is protected from crawlers';
    $checks['dashboard_robots']['details'] = [
        'File Size: ' . number_format(filesize('robots.txt')) . ' bytes',
        'Last Modified: ' . date('Y-m-d H:i:s', filemtime('robots.txt')),
        'Purpose: Prevent admin area indexing'
    ];
} else {
    $checks['dashboard_robots']['status'] = 'warning';
    $checks['dashboard_robots']['message'] = 'No robots.txt in dashboard (but /dashboard disallowed in frontend robots.txt)';
}

// ============================================================================
// SENSITIVE PATHS CHECKS
// ============================================================================

$sensitive_paths = [
    '/dashboard' => 'Admin Area',
    '/config' => 'Configuration Files',
    '/classes' => 'Class Files',
    '/vendor' => 'Vendor Dependencies',
];

$checks['sensitive_paths'] = [
    'name' => 'Sensitive Paths Protection',
    'status' => 'success',
    'message' => 'Checking if sensitive paths are blocked from crawlers...',
    'details' => []
];

if (file_exists('../robots.txt')) {
    $robots_content = file_get_contents('../robots.txt');
    foreach ($sensitive_paths as $path => $description) {
        if (strpos($robots_content, "Disallow: $path") !== false) {
            $checks['sensitive_paths']['details'][] = "✓ $path ($description) - Blocked";
        } else {
            $checks['sensitive_paths']['status'] = 'warning';
            $checks['sensitive_paths']['details'][] = "⚠️ $path ($description) - Not explicitly blocked";
        }
    }
}

// ============================================================================
// SITEMAP CHECKS
// ============================================================================

$checks['sitemap_config'] = [
    'name' => 'Sitemap Configuration',
    'status' => 'success',
    'message' => 'Checking sitemap settings from Global Settings > SEO tab...',
    'details' => []
];

$sitemap_settings = [
    'sitemap_enabled' => 'Main Sitemap',
    'ai_sitemap_enabled' => 'AI Sitemap',
    // Decommissioned: sitemap_companies & sitemap_blogs
    'sitemap_categories' => 'Categories Included',
    'sitemap_hs_codes' => 'HS Codes Sitemap Included',
    'sitemap_amp' => 'AMP Sitemap Enabled',
];

foreach ($sitemap_settings as $key => $label) {
    $value = isset($globalSeoConfig[$key]) ? (string)$globalSeoConfig[$key] : '0';
    $status = ($value == '1') ? '✓ Enabled' : '✗ Disabled';
    $checks['sitemap_config']['details'][] = "$label: $status";
}

$checks['sitemap_config']['details'][] = '';
$checks['sitemap_config']['details'][] = 'Config source: dashboard/global_settings.php?tab=seo';

// ============================================================================
// SITEMAP ENDPOINT FILES CHECK
// ============================================================================

$checks['sitemap_endpoints'] = [
    'name' => 'Sitemap Endpoint Handlers',
    'status' => 'success',
    'message' => 'Checking sitemap handler files (sitemaps are dynamically generated via routing)',
    'details' => []
];

seo_append_file_detail($checks['sitemap_endpoints'], $projectRoot, 'pages/sitemap.php', 'Main sitemap handler');
seo_append_file_detail($checks['sitemap_endpoints'], $projectRoot, 'pages/sitemap-index.php', 'Sitemap index handler');
seo_append_file_detail($checks['sitemap_endpoints'], $projectRoot, 'pages/sitemap-hs-codes.php', 'HS codes sitemap handler');
seo_append_file_detail($checks['sitemap_endpoints'], $projectRoot, 'pages/sitemap-static.php', 'Static sitemap handler');
// Decommissioned: sitemap-companies.php & sitemap-blog.php
seo_append_file_detail($checks['sitemap_endpoints'], $projectRoot, 'pages/sitemap-categories.php', 'Categories sitemap handler');
seo_append_file_detail($checks['sitemap_endpoints'], $projectRoot, 'pages/sitemap-amp.php', 'AMP sitemap handler');
seo_append_file_detail($checks['sitemap_endpoints'], $projectRoot, 'pages/ai-sitemap.php', 'AI sitemap handler');

// Add note about dynamic generation
$checks['sitemap_endpoints']['details'][] = '';
$checks['sitemap_endpoints']['details'][] = 'ℹ️ Sitemaps are dynamically generated at:';
$checks['sitemap_endpoints']['details'][] = '   • ' . $masterSitemapPath . ' - Main sitemap';
$checks['sitemap_endpoints']['details'][] = '   • /sitemap_index.xml - Sitemap index';
$checks['sitemap_endpoints']['details'][] = '   • /sitemap-hs-codes.xml - HS codes sitemap';
$checks['sitemap_endpoints']['details'][] = '   • /sitemap-static.xml - Static pages sitemap';
// Decommissioned: sitemap-companies.xml & sitemap-blog.xml
$checks['sitemap_endpoints']['details'][] = '   • /sitemap-categories.xml - Categories sitemap';
$checks['sitemap_endpoints']['details'][] = '   • /sitemap-amp.xml - AMP sitemap';
$checks['sitemap_endpoints']['details'][] = '   • /ai-sitemap.xml - AI sitemap';

// ============================================================================
// AI CRAWLER / FEED CHECKS
// ============================================================================

$checks['ai_crawler_assets'] = [
    'name' => 'AI Crawler Assets',
    'status' => 'success',
    'message' => 'Checking AI-specific sitemap/feed endpoints and docs...',
    'details' => []
];

seo_append_file_detail($checks['ai_crawler_assets'], $projectRoot, 'ai/seo-implementation-guide.md', 'SEO implementation guide');
seo_append_file_detail($checks['ai_crawler_assets'], $projectRoot, 'ROBOTS_SITEMAPS.txt', 'Robots/sitemap reference');

// ============================================================================
// ROUTE HANDLER COVERAGE CHECKS
// ============================================================================

$checks['route_handlers'] = [
    'name' => 'SEO Route Handler Coverage',
    'status' => 'success',
    'message' => 'Validating key route handler files from current frontend router...',
    'details' => []
];

$criticalRouteFiles = [
    'index.php' => 'Frontend router',
    'pages/search.php' => 'Search page',
    'pages/advanced-search.php' => 'Advanced search page',
    'pages/search-analytics.php' => 'Search analytics page',
    // Decommissioned: blog.php, blog-details.php, company-detail.php, blog-detail-amp.php, company-detail-amp.php
    'pages/amp/hs-code-detail-amp.php' => 'AMP HS code detail page'
];

foreach ($criticalRouteFiles as $relativePath => $label) {
    seo_append_file_detail($checks['route_handlers'], $projectRoot, $relativePath, $label);
}

// ============================================================================
// CODEBASE INTEGRITY CHECKS (ADD/REMOVE HERE)
// ============================================================================

$checks['codebase_integrity'] = [
    'name' => 'Codebase Integrity Checks',
    'status' => 'success',
    'message' => 'Validating critical codebase files used by SEO and public discovery...',
    'details' => []
];

$codebaseFileChecks = [
    'index.php' => 'Frontend router',
    'pages/services.php' => 'Services listing page',
    'pages/service.php' => 'Service detail page',
    'pages/search.php' => 'Search page',
    // Decommissioned: blog.php, company-detail.php
    'pages/sitemap.php' => 'Main sitemap handler',
    'pages/ai-sitemap.php' => 'AI sitemap handler',
    'config/seo_helpers.php' => 'SEO helper functions',
    'dashboard/sitemaps.php' => 'Sitemaps admin page'
];

foreach ($codebaseFileChecks as $relativePath => $label) {
    seo_append_file_detail($checks['codebase_integrity'], $projectRoot, $relativePath, $label);
}

// Endpoints to test in real-time panel: keep this list as the source of truth for
// add/remove codebase endpoint checks.
$codebaseEndpointChecks = [
    ['name' => 'index.php', 'url' => '../index.php', 'category' => 'route_handlers'],
    ['name' => 'pages/search.php', 'url' => '../pages/search.php', 'category' => 'route_handlers'],
    // Decommissioned: blog.php, company-detail.php
    ['name' => 'pages/services.php', 'url' => '../pages/services.php', 'category' => 'codebase_integrity'],
    ['name' => 'pages/service.php', 'url' => '../pages/service.php', 'category' => 'codebase_integrity'],
    ['name' => 'pages/ai-sitemap.php', 'url' => '../pages/ai-sitemap.php', 'category' => 'codebase_integrity']
];

// ============================================================================
// ROBOTS.TXT RULES SUMMARY
// ============================================================================

$checks['robots_rules'] = [
    'name' => 'Robots.txt Rules Summary',
    'status' => 'info',
    'message' => 'Active crawler rules in frontend robots.txt',
    'details' => []
];

if (file_exists('../robots.txt')) {
    $robots_content = file_get_contents('../robots.txt');
    
    // Parse blocked paths
    preg_match_all('/^Disallow:\s*(.+)$/m', $robots_content, $blocked_paths);
    if (!empty($blocked_paths[1])) {
        $checks['robots_rules']['details'][] = 'Blocked Paths: ' . count($blocked_paths[1]);
        foreach (array_slice($blocked_paths[1], 0, 5) as $path) {
            $checks['robots_rules']['details'][] = "  • " . trim($path);
        }
        if (count($blocked_paths[1]) > 5) {
            $checks['robots_rules']['details'][] = "  ... and " . (count($blocked_paths[1]) - 5) . " more";
        }
    }
    
    // Parse blocked bots
    preg_match_all('/^User-agent:\s*([^\n]+)$/m', $robots_content, $user_agents);
    if (!empty($user_agents[1])) {
        $checks['robots_rules']['details'][] = '';
        $checks['robots_rules']['details'][] = 'Configured User Agents: ' . count($user_agents[1]);
        $unique_agents = array_unique($user_agents[1]);
        foreach (array_slice($unique_agents, 0, 10) as $agent) {
            $agent = trim($agent);
            if (!empty($agent)) {
                $checks['robots_rules']['details'][] = "  • " . $agent;
            }
        }
    }
}

// ============================================================================
// AI SEARCH ENGINES COMPATIBILITY
// ============================================================================

$checks['ai_search_engines'] = [
    'name' => 'AI Search Engines Compatibility',
    'status' => 'success',
    'message' => 'Checking AI crawler support (GPTBot, Claude, Perplexity, etc.)',
    'details' => [],
    'code_snippet' => ''
];

$checks['ai_search_engines']['details'][] = 'Configured AI Policy Mode: ' . ($globalSeoConfig['seo_ai_policy_mode'] ?: 'inherit');

$ai_user_agents = [
    'GPTBot' => 'OpenAI ChatGPT',
    'ChatGPT-User' => 'OpenAI ChatGPT User Agent',
    'Claudebot' => 'Anthropic Claude',
    'PerplexityBot' => 'Perplexity AI',
    'Copilot' => 'Microsoft Copilot',
    'CCBot' => 'Common Crawl (AI training)',
    'anthropic-ai' => 'Anthropic AI',
    'Google-Extended' => 'Google AI Training',
    'Applebot-Extended' => 'Apple AI Training',
    'Omgilibot' => 'Omgili AI'
];

if (file_exists('../robots.txt')) {
    $robots_content = file_get_contents('../robots.txt');
    $ai_allowed = 0;
    $ai_blocked = 0;
    $ai_inherited = 0;
    $ai_explicit = 0;
    $wildcard_section = '';

    if (preg_match('/User-agent:\s*\*.*?(?=User-agent:|$)/is', $robots_content, $wildcard_matches)) {
        $wildcard_section = $wildcard_matches[0];
    }
    
    foreach ($ai_user_agents as $bot => $name) {
        if (preg_match('/User-agent:\s*' . preg_quote($bot, '/') . '/i', $robots_content)) {
            $ai_explicit++;
            // Check if it's allowed or blocked
            $pattern = '/User-agent:\s*' . preg_quote($bot, '/') . '.*?(?=User-agent:|$)/is';
            if (preg_match($pattern, $robots_content, $matches)) {
                $bot_section = $matches[0];
                if (stripos($bot_section, 'Disallow: /') !== false && stripos($bot_section, 'Disallow: /dashboard') === false) {
                    $checks['ai_search_engines']['details'][] = "⚠ $name ($bot) - Blocked from entire site";
                    $ai_blocked++;
                } else {
                    $checks['ai_search_engines']['details'][] = "✓ $name ($bot) - Allowed with restrictions";
                    $ai_allowed++;
                }
            }
        } else {
            // If specific bot rules are absent, inherit behavior from User-agent: * when present.
            if (!empty($wildcard_section)) {
                if (stripos($wildcard_section, 'Disallow: /') !== false && stripos($wildcard_section, 'Disallow: /dashboard') === false) {
                    $checks['ai_search_engines']['details'][] = "⚠ $name ($bot) - Inherited block from User-agent: *";
                    $ai_blocked++;
                } else {
                    $checks['ai_search_engines']['details'][] = "ℹ $name ($bot) - Inherits rules from User-agent: * (not explicitly configured)";
                    $ai_inherited++;
                }
            } else {
                $checks['ai_search_engines']['details'][] = "⚠ $name ($bot) - Not explicitly configured";
            }
        }
    }
    
    $checks['ai_search_engines']['details'][] = '';
    $ai_not_configured = count($ai_user_agents) - $ai_allowed - $ai_blocked - $ai_inherited;
    $checks['ai_search_engines']['details'][] = "Summary: $ai_allowed allowed, $ai_blocked blocked, $ai_inherited inherited, $ai_not_configured not configured";

    if ($ai_not_configured > 0 || $ai_inherited > 0) {
        $checks['ai_search_engines']['details'][] = '';
        $checks['ai_search_engines']['details'][] = 'Suggested explicit robots.txt policy (copy/paste):';
        $checks['ai_search_engines']['code_snippet'] = "User-agent: GPTBot\nAllow: /\n\nUser-agent: Claudebot\nAllow: /\n\nUser-agent: PerplexityBot\nAllow: /\n\nUser-agent: Google-Extended\nAllow: /\n\nDisallow: /dashboard\nDisallow: /config\nDisallow: /vendor";
    }
    
    if ($ai_blocked > 3) {
        $checks['ai_search_engines']['status'] = 'warning';
        $checks['ai_search_engines']['message'] = 'Multiple AI search engines are blocked - may reduce AI search visibility';
    } elseif ($ai_inherited >= 5 && $ai_explicit <= 2) {
        $checks['ai_search_engines']['status'] = 'warning';
        $checks['ai_search_engines']['message'] = 'AI crawler rules mostly inherited from wildcard - explicit bot rules recommended';
        $checks['ai_search_engines']['details'][] = 'ℹ Recommendation: Add explicit blocks for key AI bots (Google-Extended, GPTBot, Claudebot, PerplexityBot) for predictable policy behavior.';
    }
}

// ============================================================================
// STRUCTURED DATA / JSON-LD SCHEMA
// ============================================================================

$checks['structured_data'] = [
    'name' => 'Structured Data (JSON-LD Schema)',
    'status' => 'success',
    'message' => 'Checking for schema.org markup implementation',
    'details' => []
];

// Check if JSONLDSchema class exists
if (file_exists($projectRoot . '/classes/JSONLDSchema.php')) {
    $checks['structured_data']['details'][] = '✓ JSONLDSchema class implemented';
    
    // Check key page handlers for schema implementation
    $schema_pages = [
        // Decommissioned: company-detail.php, blog-details.php
        'pages/home.php' => 'WebSite schema',
        'pages/search.php' => 'SearchAction schema'
    ];
    
    foreach ($schema_pages as $page => $schema_type) {
        $page_path = seo_abs_path($projectRoot, $page);
        if (file_exists($page_path)) {
            $content = file_get_contents($page_path);
            if (stripos($content, 'JSONLDSchema') !== false || stripos($content, 'application/ld+json') !== false) {
                $checks['structured_data']['details'][] = "✓ $schema_type - Implemented in $page";
            } else {
                $checks['structured_data']['status'] = 'warning';
                $checks['structured_data']['details'][] = "⚠ $schema_type - Not found in $page";
            }
        }
    }
    
    $checks['structured_data']['details'][] = '';
    $checks['structured_data']['details'][] = 'Rich results types supported:';
    // Decommissioned: Organization & Article schemas
    $checks['structured_data']['details'][] = '  • BreadcrumbList';
    $checks['structured_data']['details'][] = '  • WebSite with SearchAction';
    $checks['structured_data']['details'][] = '  • Product/Offer (for listings)';
} else {
    $checks['structured_data']['status'] = 'warning';
    $checks['structured_data']['message'] = 'JSONLDSchema class not found';
    $checks['structured_data']['details'][] = '⚠ Structured data implementation class missing';
    $checks['structured_data']['details'][] = 'Recommendation: Implement JSON-LD schema for rich results';
}

// ============================================================================
// SECURITY HEADERS & SEO PROTECTION
// ============================================================================

$checks['security_headers'] = [
    'name' => 'Security Headers (SEO Protection)',
    'status' => 'success',
    'message' => 'Checking HTTP security headers in .htaccess',
    'details' => []
];

$htaccess_path = seo_abs_path($projectRoot, '.htaccess');
if (file_exists($htaccess_path)) {
    $htaccess_content = file_get_contents($htaccess_path);
    
    $security_headers = [
        'Content-Security-Policy' => 'Prevents XSS attacks',
        'X-Frame-Options' => 'Prevents clickjacking',
        'X-Content-Type-Options' => 'Prevents MIME sniffing',
        'Referrer-Policy' => 'Controls referrer information',
        'Permissions-Policy' => 'Restricts browser features'
    ];
    
    foreach ($security_headers as $header => $description) {
        if (stripos($htaccess_content, $header) !== false) {
            $checks['security_headers']['details'][] = "✓ $header - $description";
        } else {
            $checks['security_headers']['status'] = 'warning';
            $checks['security_headers']['details'][] = "⚠ $header - Not configured ($description)";
        }
    }
    
    // Check for HSTS and report accurately for local HTTP vs HTTPS environments.
    if (stripos($htaccess_content, 'Strict-Transport-Security') !== false) {
        $checks['security_headers']['details'][] = '✓ HSTS (Strict-Transport-Security) - HTTPS enforcement';
    } else {
        $is_https_request = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        );

        if ($is_https_request && (int)$globalSeoConfig['seo_hsts_required'] === 1) {
            $checks['security_headers']['status'] = ($checks['security_headers']['status'] === 'error') ? 'error' : 'warning';
            $checks['security_headers']['details'][] = '⚠ HSTS - Required by SEO settings but not detected in .htaccess (could be configured in Apache/Nginx/vhost)';
        } else {
            $checks['security_headers']['details'][] = 'ℹ HSTS - Optional/Skipped based on environment or SEO settings; verify on production HTTPS if required.';
        }
    }
} else {
    $checks['security_headers']['status'] = 'error';
    $checks['security_headers']['details'][] = '✗ .htaccess file not found';
}

$checks['security_headers']['details'][] = 'HSTS required toggle: ' . ((int)$globalSeoConfig['seo_hsts_required'] === 1 ? 'Enabled' : 'Disabled') . ' (Global Settings > SEO)';

// ============================================================================
// META TAGS & SOCIAL MEDIA
// ============================================================================

$checks['meta_tags'] = [
    'name' => 'Meta Tags & Social Media Integration',
    'status' => 'success',
    'message' => 'Checking for essential SEO meta tags and social sharing',
    'details' => []
];

// Check the header include file FIRST (where most meta tags are centralized)
$header_path = seo_abs_path($projectRoot, 'includes/layout/header.php');
$og_found = false;
$twitter_found = false;
$viewport_found = false;
$canonical_found = false;

if (file_exists($header_path)) {
    $header_content = file_get_contents($header_path);
    
    // Check for Open Graph
    if (stripos($header_content, 'og:title') !== false && stripos($header_content, 'og:description') !== false) {
        $og_found = true;
    }
    
    // Check for Twitter Cards
    if (stripos($header_content, 'twitter:card') !== false && stripos($header_content, 'twitter:title') !== false) {
        $twitter_found = true;
    }
    
    // Check for viewport
    if (stripos($header_content, 'viewport') !== false) {
        $viewport_found = true;
    }
    
    // Check for canonical
    if (stripos($header_content, 'rel="canonical"') !== false || stripos($header_content, "rel='canonical'") !== false) {
        $canonical_found = true;
    }
}

// Also check key pages (as fallback or for page-specific overrides)
$meta_check_pages = [
    'pages/home.php' => 'Homepage',
    'pages/company-detail.php' => 'Company Details',
    'pages/blog-details.php' => 'Blog Details'
];

foreach ($meta_check_pages as $page => $page_name) {
    $page_path = seo_abs_path($projectRoot, $page);
    if (file_exists($page_path)) {
        $content = file_get_contents($page_path);
        
        // Check for Open Graph
        if (!$og_found && stripos($content, 'og:title') !== false) {
            $og_found = true;
        }
        
        // Check for Twitter Cards
        if (!$twitter_found && stripos($content, 'twitter:card') !== false) {
            $twitter_found = true;
        }
        
        // Check for viewport
        if (!$viewport_found && stripos($content, 'viewport') !== false) {
            $viewport_found = true;
        }
        
        // Check for canonical
        if (!$canonical_found && (stripos($content, 'rel="canonical"') !== false || stripos($content, "rel='canonical'") !== false)) {
            $canonical_found = true;
        }
    }
}

if ($og_found) {
    $checks['meta_tags']['details'][] = '✓ Open Graph tags - Facebook/LinkedIn sharing optimized';
} else {
    $checks['meta_tags']['status'] = 'warning';
    $checks['meta_tags']['details'][] = '⚠ Open Graph tags - Missing (og:title, og:description, og:image)';
}

if ($twitter_found) {
    $checks['meta_tags']['details'][] = '✓ Twitter Card tags - Twitter sharing optimized';
} else {
    $checks['meta_tags']['status'] = 'warning';
    $checks['meta_tags']['details'][] = '⚠ Twitter Card tags - Missing (twitter:card, twitter:title)';
}

if ($viewport_found) {
    $checks['meta_tags']['details'][] = '✓ Viewport meta tag - Mobile-responsive';
} else {
    $checks['meta_tags']['status'] = 'error';
    $checks['meta_tags']['details'][] = '✗ Viewport meta tag - CRITICAL: Required for mobile SEO';
}

if ($canonical_found) {
    $checks['meta_tags']['details'][] = '✓ Canonical link tags - Preventing duplicate content issues';
} else {
    $checks['meta_tags']['status'] = ($checks['meta_tags']['status'] === 'error') ? 'error' : 'warning';
    $checks['meta_tags']['details'][] = '⚠ Canonical link tags - Missing from pages';
}

$checks['meta_tags']['details'][] = '';
$checks['meta_tags']['details'][] = 'Source checked: includes/layout/header.php (+ fallback page checks)';
$checks['meta_tags']['details'][] = '';
$checks['meta_tags']['details'][] = '  • <meta name="description"> - Page description';
$checks['meta_tags']['details'][] = '  • <meta name="robots"> - Indexing instructions';
$checks['meta_tags']['details'][] = '  • <link rel="canonical"> - Preferred URL';
$checks['meta_tags']['details'][] = '  • <meta property="og:*"> - Open Graph for social';
$checks['meta_tags']['details'][] = '  • <meta name="twitter:*"> - Twitter Cards';

// ============================================================================
// CORE WEB VITALS & PERFORMANCE
// ============================================================================

$checks['performance'] = [
    'name' => 'Core Web Vitals & Performance',
    'status' => 'info',
    'message' => 'Performance optimization hints for Google ranking factors',
    'details' => []
];

// Check for AMP implementation
$amp_dir = seo_abs_path($projectRoot, 'pages/amp');
if (is_dir($amp_dir)) {
    $amp_files = glob($amp_dir . '/*.php');
    $checks['performance']['details'][] = '✓ AMP pages implemented (' . count($amp_files) . ' pages) - Faster mobile loading';
} else {
    $checks['performance']['details'][] = 'ℹ AMP pages - Not implemented (optional but recommended for mobile)';
}

// Check for image optimization hints
if (file_exists($projectRoot . '/config/images.php')) {
    $checks['performance']['details'][] = '✓ Image upload handler - Check for WebP support & compression';
} else {
    $checks['performance']['details'][] = 'ℹ Image optimization - Verify WebP format and lazy loading';
}

$checks['performance']['details'][] = '';
$checks['performance']['details'][] = 'Core Web Vitals to monitor:';
$checks['performance']['details'][] = '  • LCP (Largest Contentful Paint) - Target: < 2.5s';
$checks['performance']['details'][] = '  • FID (First Input Delay) - Target: < 100ms';
$checks['performance']['details'][] = '  • CLS (Cumulative Layout Shift) - Target: < 0.1';
$checks['performance']['details'][] = '';
$checks['performance']['details'][] = 'Performance checklist:';
$checks['performance']['details'][] = '  • Enable Gzip/Brotli compression';
$checks['performance']['details'][] = '  • Minify CSS/JavaScript';
$checks['performance']['details'][] = '  • Optimize images (WebP, compression, lazy loading)';
$checks['performance']['details'][] = '  • Use CDN for static assets';
$checks['performance']['details'][] = '  • Enable browser caching';
$checks['performance']['details'][] = '  • Implement service worker for PWA';

// ============================================================================
// MOBILE-FIRST & RESPONSIVE DESIGN
// ============================================================================

$checks['mobile_seo'] = [
    'name' => 'Mobile-First Indexing & Responsive Design',
    'status' => 'success',
    'message' => 'Google prioritizes mobile version for indexing',
    'details' => []
];

// Check for responsive framework
if (file_exists($projectRoot . '/assets/css/bootstrap.min.css') || 
    (file_exists($projectRoot . '/includes/layout/header.php') && 
     stripos(file_get_contents($projectRoot . '/includes/layout/header.php'), 'bootstrap') !== false)) {
    $checks['mobile_seo']['details'][] = '✓ Bootstrap 5 detected - Responsive framework implemented';
} else {
    $checks['mobile_seo']['status'] = 'warning';
    $checks['mobile_seo']['details'][] = '⚠ Responsive framework - Verify mobile-responsive CSS';
}

// Check header for viewport meta
if (file_exists($projectRoot . '/includes/layout/header.php')) {
    $header_content = file_get_contents($projectRoot . '/includes/layout/header.php');
    if (stripos($header_content, 'viewport') !== false) {
        $checks['mobile_seo']['details'][] = '✓ Viewport meta tag in header - Mobile-optimized';
    } else {
        $checks['mobile_seo']['status'] = 'error';
        $checks['mobile_seo']['details'][] = '✗ Viewport meta tag - CRITICAL: Missing from header';
    }
}

$checks['mobile_seo']['details'][] = '';
$checks['mobile_seo']['details'][] = 'Mobile SEO checklist:';
$checks['mobile_seo']['details'][] = '  • Responsive design (fluid layouts)';
$checks['mobile_seo']['details'][] = '  • Touch-friendly buttons (min 48x48px)';
$checks['mobile_seo']['details'][] = '  • Readable font sizes (16px minimum)';
$checks['mobile_seo']['details'][] = '  • Fast mobile page speed (< 3s load time)';
$checks['mobile_seo']['details'][] = '  • Avoid intrusive interstitials';
$checks['mobile_seo']['details'][] = '  • Test with Google Mobile-Friendly Test';

// ============================================================================
// CANONICAL URLS & DUPLICATE CONTENT
// ============================================================================

$checks['canonical_urls'] = [
    'name' => 'Canonical URLs & Duplicate Content Prevention',
    'status' => 'success',
    'message' => 'Checking for canonical URL implementation',
    'details' => []
];

// Check .htaccess for URL canonicalization
if (file_exists($htaccess_path)) {
    $htaccess_content = file_get_contents($htaccess_path);
    
    if (stripos($htaccess_content, 'RewriteRule') !== false) {
        $checks['canonical_urls']['details'][] = '✓ URL rewriting enabled - Clean URLs implemented';
    }
    
    if (stripos($htaccess_content, 'index.php') !== false && stripos($htaccess_content, '301') !== false) {
        $checks['canonical_urls']['details'][] = '✓ Index.php redirects - Prevents duplicate content';
    }
    
    if (stripos($htaccess_content, 'www') !== false) {
        $checks['canonical_urls']['details'][] = 'ℹ WWW canonicalization - Verify www vs non-www preference';
    }
}

// Check for canonical link implementation in pages
$check_pages = ['pages/home.php'];
$canonical_found = false;

foreach ($check_pages as $page) {
    $page_path = seo_abs_path($projectRoot, $page);
    if (file_exists($page_path)) {
        $content = file_get_contents($page_path);
        if (stripos($content, 'rel="canonical"') !== false) {
            $canonical_found = true;
            break;
        }
    }
}

if ($canonical_found) {
    $checks['canonical_urls']['details'][] = '✓ Canonical link tags - Duplicate content prevention';
} else {
    $checks['canonical_urls']['status'] = 'warning';
    $checks['canonical_urls']['details'][] = '⚠ Canonical link tags - Missing from pages';
}

$checks['canonical_urls']['details'][] = '';
$checks['canonical_urls']['details'][] = 'Best practices:';
$checks['canonical_urls']['details'][] = '  • Add <link rel="canonical"> to all pages';
$checks['canonical_urls']['details'][] = '  • Use absolute URLs for canonical links';
$checks['canonical_urls']['details'][] = '  • Redirect old URLs with 301 redirects';
$checks['canonical_urls']['details'][] = '  • Avoid URL parameters for sorting/filtering';

// ============================================================================
// RECOMMENDATIONS
// ============================================================================

$checks['recommendations'] = [
    'name' => 'Recommendations',
    'status' => 'info',
    'message' => 'Suggested improvements for SEO health',
    'details' => [
        '✓ Monitor sitemap generation frequency',
        '✓ Verify crawlers can access sitemaps (test URLs: ' . $masterSitemapPath . ', /ai-sitemap.xml)',
        '✓ Keep AI sitemap and AI feed routes aligned with robots.txt entries',
        '✓ Allow AI search engines (GPTBot, Claude, Perplexity) for AI discovery',
        '✓ Implement JSON-LD structured data on all key pages',
        '✓ Test structured data with Google Rich Results Test',
        '✓ Add Open Graph and Twitter Card meta tags',
        '✓ Monitor Core Web Vitals in Google Search Console',
        '✓ Run Lighthouse audits monthly (Performance, SEO, Accessibility)',
        '✓ Test mobile-friendliness with Google Mobile-Friendly Test',
        '✓ Check server logs for crawlers accessing blocked paths',
        '✓ Review blocked user agents periodically',
        '✓ Ensure robots.txt is accessible at /robots.txt (HTTP root)',
        '✓ Validate robots.txt syntax at https://www.robotstxt.org/robocheck.html',
        '✓ Submit sitemaps to Google Search Console and Bing Webmaster',
        '✓ Monitor crawl statistics in GSC',
        '✓ Test sitemap endpoints in browser to verify dynamic generation',
        '✓ Enable HTTPS and implement HSTS header',
        '✓ Add canonical URLs to prevent duplicate content',
        '✓ Optimize images (WebP format, compression, lazy loading)',
        '✓ Implement service worker for offline support (PWA)',
    ]
];

?>

<style>
    .seo-health-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.75rem 1.25rem;
        border-radius: 8px;
        margin-bottom: 0.75rem;
    }

    .seo-health-header h1 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .seo-health-header p {
        margin: 0.25rem 0 0 0;
        opacity: 0.9;
        font-size: 0.82rem;
    }

    .check-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem 1rem;
        margin-bottom: 0.6rem;
        transition: box-shadow 0.2s ease;
    }

    .check-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    }

    .check-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.35rem;
    }

    .check-title {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .check-badge {
        display: inline-block;
        padding: 0.15rem 0.55rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
    }

    .check-badge.success { background: #d1fae5; color: #065f46; }
    .check-badge.warning { background: #fef3c7; color: #92400e; }
    .check-badge.error   { background: #fee2e2; color: #7f1d1d; }
    .check-badge.info    { background: #dbeafe; color: #0c4a6e; }

    .check-message {
        color: #6b7280;
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
    }

    .check-details {
        background: #f9fafb;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        border-left: 3px solid #e5e7eb;
    }

    .check-details ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .check-details li {
        padding: 0.1rem 0;
        color: #374151;
        font-size: 0.8rem;
        font-family: 'Courier New', monospace;
    }

    .check-details li:before {
        content: '→ ';
        color: #9ca3af;
        margin-right: 0.3rem;
    }

    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .status-item {
        background: white;
        padding: 0.6rem 0.75rem;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        text-align: center;
    }

    .status-item .status-number {
        font-size: 1.75rem;
        font-weight: 700;
        color: #667eea;
    }

    .status-item .status-label {
        color: #6b7280;
        font-size: 0.78rem;
        margin-top: 0.15rem;
    }

    .icon-check { color: #10b981; }
    .icon-warning { color: #f59e0b; }
    .icon-error { color: #ef4444; }
    .icon-info { color: #3b82f6; }
</style>

    <?php
    $statusCounts = [
        'success' => 0,
        'warning' => 0,
        'error' => 0,
        'info' => 0
    ];

    foreach ($checks as $checkItem) {
        $status = $checkItem['status'] ?? 'info';
        if (!isset($statusCounts[$status])) {
            $status = 'info';
        }
        $statusCounts[$status]++;
    }
    ?>

<div class="content-wrapper">
    <div class="content-inner">
        <div class="content">

            <!-- Hero Header -->
            <div class="seo-health-header">
                <h1><i class="ph-magnifying-glass"></i> SEO Health Check</h1>
                <p>Comprehensive SEO audit for 2026 standards: AI search engines, Core Web Vitals, structured data, and more</p>
            </div>
            
            <!-- Control Bar -->
            <div class="d-flex gap-2 mb-2">
                <a href="<?php echo e($settingsPageUrl); ?>" class="btn btn-success" title="Open Global Settings SEO tab">
                    <i class="ph-sliders me-1"></i>Global SEO Settings
                </a>
                <button class="btn btn-primary" id="test-all-endpoints" title="Run real-time tests for all SEO endpoints">
                    <i class="ph-play me-1"></i>Run Real-Time Tests
                </button>
                <button class="btn btn-warning" id="retest-failed" style="display:none;" title="Retest failed endpoints">
                    <i class="ph-arrow-clockwise me-1"></i>Retest Failed
                </button>
                <button class="btn btn-secondary" id="clear-test-results" title="Clear all test results">
                    <i class="ph-eraser me-1"></i>Clear Results
                </button>
            </div>
            
            <!-- Progress Bar -->
            <div id="test-progress-container" style="display:none;" class="mb-3">
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="test-progress-bar" role="progressbar" style="width:0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        <span id="test-progress-text">0%</span>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0" id="test-status-text">Preparing tests...</p>
            </div>

            <!-- Status Summary -->
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-number" style="color: #10b981;" id="stat-success">
                        <?php echo (int)$statusCounts['success']; ?>
                    </div>
                    <div class="status-label">Checks Passed</div>
                </div>
                <div class="status-item">
                    <div class="status-number" style="color: #667eea;" id="stat-warning">
                        <?php echo (int)$statusCounts['warning']; ?>
                    </div>
                    <div class="status-label">Needs Attention</div>
                </div>
                <div class="status-item">
                    <div class="status-number" style="color: #8b5cf6;" id="stat-error">
                        <?php echo (int)$statusCounts['error']; ?>
                    </div>
                    <div class="status-label">Errors</div>
                </div>
                <div class="status-item">
                    <div class="status-number" style="color: #3b82f6;" id="stat-tested">0</div>
                    <div class="status-label">Endpoints Tested</div>
                </div>
            </div>

            <!-- Checks -->
            <?php foreach ($checks as $key => $check): ?>
                <div class="check-card" data-check-key="<?php echo htmlspecialchars($key); ?>">
                    <div class="check-header">
                        <h3 class="check-title">
                            <?php 
                            $icons = [
                                'success' => '<i class="ph-check-circle icon-check"></i>',
                                'warning' => '<i class="ph-warning-circle icon-warning"></i>',
                                'error' => '<i class="ph-x-circle icon-error"></i>',
                                'info' => '<i class="ph-info icon-info"></i>'
                            ];
                            echo $icons[$check['status']] ?? '';
                            ?>
                            <?php echo $check['name']; ?>
                        </h3>
                        <span class="check-badge <?php echo $check['status']; ?>">
                            <?php echo strtoupper($check['status']); ?>
                        </span>
                    </div>

                    <p class="check-message"><?php echo $check['message']; ?></p>

                    <?php if (!empty($check['details'])): ?>
                        <div class="check-details">
                            <ul>
                                <?php foreach ($check['details'] as $detail): ?>
                                    <li><?php echo htmlspecialchars($detail); ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if (!empty($check['code_snippet'])): ?>
                                <div style="background: #111827; color: #10b981; margin-top: 1rem; padding: 0.85rem; border-radius: 6px; overflow-x: auto;">
                                    <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-size: 0.85rem; line-height: 1.45;"><?php echo htmlspecialchars($check['code_snippet']); ?></pre>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- File Contents Section -->
            <div class="check-card" style="margin-top: 2rem; border-top: 2px solid #e5e7eb; padding-top: 2rem;">
                <h3 class="check-title" style="margin-bottom: 1.5rem;">
                    <i class="ph-file-text"></i> Frontend robots.txt Content
                </h3>
                <div style="background: #1f2937; color: #10b981; padding: 1rem; border-radius: 6px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 0.85rem; line-height: 1.5;">
                    <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">
<?php 
if (file_exists('../robots.txt')) {
    echo htmlspecialchars(file_get_contents('../robots.txt'));
} else {
    echo 'File not found';
}
?>
                    </pre>
                </div>
            </div>

            <!-- Validation Tools -->
            <div class="check-card" style="margin-top: 2rem;">
                <h3 class="check-title" style="margin-bottom: 1.5rem;">
                    <i class="ph-link-external"></i> SEO Testing & Validation Tools
                </h3>
                
                <h6 class="mb-2 mt-3">Search Engine Tools</h6>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <a href="https://search.google.com/search-console" target="_blank" class="btn btn-sm btn-outline-primary" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-google-logo"></i> Google Search Console
                    </a>
                    <a href="https://tools.bing.com/webmaster" target="_blank" class="btn btn-sm btn-outline-primary" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-chart-line"></i> Bing Webmaster Tools
                    </a>
                    <a href="https://search.google.com/test/rich-results" target="_blank" class="btn btn-sm btn-outline-success" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-star"></i> Rich Results Test
                    </a>
                    <a href="https://validator.schema.org/" target="_blank" class="btn btn-sm btn-outline-success" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-code"></i> Schema Validator
                    </a>
                </div>
                
                <h6 class="mb-2 mt-3">Performance & Mobile</h6>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <a href="https://pagespeed.web.dev/" target="_blank" class="btn btn-sm btn-outline-warning" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-gauge"></i> PageSpeed Insights
                    </a>
                    <a href="https://search.google.com/test/mobile-friendly" target="_blank" class="btn btn-sm btn-outline-warning" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-device-mobile"></i> Mobile-Friendly Test
                    </a>
                    <a href="https://search.google.com/test/amp" target="_blank" class="btn btn-sm btn-outline-warning" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-lightning"></i> AMP Validator
                    </a>
                    <a href="https://web.dev/measure/" target="_blank" class="btn btn-sm btn-outline-warning" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-lighthouse-logo"></i> Lighthouse CI
                    </a>
                </div>
                
                <h6 class="mb-2 mt-3">Technical SEO</h6>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <a href="https://www.robotstxt.org/robocheck.html" target="_blank" class="btn btn-sm btn-outline-info" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-check-circle"></i> Robots.txt Validator
                    </a>
                    <a href="https://www.xml-sitemaps.com/" target="_blank" class="btn btn-sm btn-outline-info" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-sitemap"></i> Sitemap Generator
                    </a>
                    <a href="https://securityheaders.com/" target="_blank" class="btn btn-sm btn-outline-info" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-shield-check"></i> Security Headers
                    </a>
                    <a href="https://www.ssllabs.com/ssltest/" target="_blank" class="btn btn-sm btn-outline-info" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-lock"></i> SSL Server Test
                    </a>
                </div>
                
                <h6 class="mb-2 mt-3">Social Media & Content</h6>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <a href="https://developers.facebook.com/tools/debug/" target="_blank" class="btn btn-sm btn-outline-secondary" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-facebook-logo"></i> Facebook Debugger
                    </a>
                    <a href="https://cards-dev.twitter.com/validator" target="_blank" class="btn btn-sm btn-outline-secondary" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-twitter-logo"></i> Twitter Card Validator
                    </a>
                    <a href="https://www.linkedin.com/post-inspector/" target="_blank" class="btn btn-sm btn-outline-secondary" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-linkedin-logo"></i> LinkedIn Inspector
                    </a>
                    <a href="https://validator.w3.org/" target="_blank" class="btn btn-sm btn-outline-secondary" style="text-decoration: none; display: inline-block; padding: 0.75rem 1rem;">
                        <i class="ph-code"></i> HTML Validator
                    </a>
                </div>
            </div>

        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<script>
$(function() {
    const codebaseEndpointChecks = <?php echo json_encode($codebaseEndpointChecks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    // Endpoints to test
    const testEndpoints = [
        { name: 'robots.txt', url: '../robots.txt', category: 'frontend_robots' },
        { name: '<?php echo e($masterSitemapFilename); ?>', url: '../<?php echo e($masterSitemapFilename); ?>', category: 'sitemap_endpoints' },
        { name: 'sitemap_index.xml', url: '../sitemap_index.xml', category: 'sitemap_endpoints' },
        { name: 'sitemap-hs-codes.xml', url: '../sitemap-hs-codes.xml', category: 'sitemap_endpoints' },
        { name: 'sitemap-static.xml', url: '../sitemap-static.xml', category: 'sitemap_endpoints' },
        { name: 'sitemap-companies.xml', url: '../sitemap-companies.xml', category: 'sitemap_endpoints' },
        { name: 'sitemap-blog.xml', url: '../sitemap-blog.xml', category: 'sitemap_endpoints' },
        { name: 'sitemap-categories.xml', url: '../sitemap-categories.xml', category: 'sitemap_endpoints' },
        { name: 'sitemap-amp.xml', url: '../sitemap-amp.xml', category: 'sitemap_endpoints' },
        { name: 'ai-sitemap.xml', url: '../ai-sitemap.xml', category: 'sitemap_endpoints' },
        { name: 'pages/sitemap.php', url: '../pages/sitemap.php', category: 'sitemap_endpoints' },
        { name: 'pages/sitemap-index.php', url: '../pages/sitemap-index.php', category: 'sitemap_endpoints' },
        { name: 'pages/sitemap-hs-codes.php', url: '../pages/sitemap-hs-codes.php', category: 'sitemap_endpoints' },
        { name: 'pages/sitemap-static.php', url: '../pages/sitemap-static.php', category: 'sitemap_endpoints' },
        { name: 'pages/sitemap-companies.php', url: '../pages/sitemap-companies.php', category: 'sitemap_endpoints' },
        { name: 'pages/sitemap-blog.php', url: '../pages/sitemap-blog.php', category: 'sitemap_endpoints' },
        { name: 'pages/sitemap-categories.php', url: '../pages/sitemap-categories.php', category: 'sitemap_endpoints' },
        { name: 'pages/sitemap-amp.php', url: '../pages/sitemap-amp.php', category: 'sitemap_endpoints' },
        { name: 'pages/ai-sitemap.php', url: '../pages/ai-sitemap.php', category: 'ai_crawler_assets' },
        ...codebaseEndpointChecks
    ];

    let testsCompleted = 0;
    let testsTotal = 0;
    let testsPassed = 0;
    let testsFailed = 0;
    let failedEndpoints = [];
    let originalCardStates = {};

    /**
     * Store original card states on page load
     */
    $(function() {
        $('.check-card[data-check-key]').each(function() {
            const key = $(this).data('check-key');
            const $badge = $(this).find('.check-badge');
            const badgeClass = $badge.attr('class').match(/\b(success|warning|error|info)\b/);
            const badgeText = $badge.text();
            
            originalCardStates[key] = {
                badgeClass: badgeClass ? badgeClass[1] : 'info',
                badgeText: badgeText
            };
        });
    });

    /**
     * Update summary cards by scanning all check card statuses
     */
    function testEndpoint(endpoint) {
        return $.ajax({
            url: endpoint.url,
            method: 'HEAD',
            timeout: 8000
        })
        .done(function(data, textStatus, xhr) {
            const statusCode = xhr.status;
            
            if (statusCode >= 200 && statusCode < 400) {
                testsPassed++;
                updateEndpointStatus(endpoint, 'success', statusCode);
            } else {
                testsFailed++;
                failedEndpoints.push(endpoint);
                updateEndpointStatus(endpoint, 'warning', statusCode);
            }
            
            testsCompleted++;
            updateProgress();
        })
        .fail(function(xhr) {
            testsFailed++;
            failedEndpoints.push(endpoint);
            updateEndpointStatus(endpoint, 'error', xhr.status || 'ERR');
            testsCompleted++;
            updateProgress();
        });
    }

    /**
     * Update summary cards by scanning all check card statuses
     */
    function updateSummaryCards() {
        let successCount = 0;
        let warningCount = 0;
        let errorCount = 0;
        
        // Scan all check cards and count their current status
        $('.check-card[data-check-key]').each(function() {
            const $badge = $(this).find('.check-badge');
            if ($badge.hasClass('success')) {
                successCount++;
            } else if ($badge.hasClass('warning')) {
                warningCount++;
            } else if ($badge.hasClass('error')) {
                errorCount++;
            }
        });
        
        // Update the summary stat cards
        $('#stat-success').text(successCount);
        $('#stat-warning').text(warningCount);
        $('#stat-error').text(errorCount);
    }

    /**
     * Update endpoint status in the check card
     */
    function updateEndpointStatus(endpoint, status, code) {
        const $card = $('[data-check-key="' + endpoint.category + '"]');
        if ($card.length) {
            const $badge = $card.find('.check-badge');
            const $details = $card.find('.check-details ul');
            
            // Update badge if needed
            if (status === 'error' && !$badge.hasClass('error')) {
                $badge.removeClass('success warning info').addClass('error').text('ERROR');
                $card.find('.icon-check, .icon-warning, .icon-info').removeClass('icon-check icon-warning icon-info').addClass('icon-error');
            }
            
            // Add test result to details
            let statusIcon = '';
            if (status === 'success') statusIcon = '✓';
            else if (status === 'warning') statusIcon = '⚠';
            else statusIcon = '✗';
            
            const resultText = `${statusIcon} ${endpoint.name}: HTTP ${code}`;
            
            // Check if result already exists, update it
            let $existingResult = $details.find('li').filter(function() {
                return $(this).text().indexOf(endpoint.name) !== -1;
            });
            
            if ($existingResult.length) {
                $existingResult.text(resultText);
            } else {
                $details.append('<li>' + resultText + '</li>');
            }
        }
        
        // Update summary cards after changing a card's status
        updateSummaryCards();
    }

    /**
     * Update progress display
     */
    function updateProgress() {
        const percent = testsTotal > 0 ? Math.round((testsCompleted / testsTotal) * 100) : 0;
        $('#test-progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);
        $('#test-progress-text').text(percent + '%');
        $('#stat-tested').text(testsCompleted);
        $('#test-status-text').text(`Testing endpoints... ${testsCompleted}/${testsTotal} (Passed: ${testsPassed}, Failed: ${testsFailed})`);
    }

    /**
     * Test all endpoints
     */
    $('#test-all-endpoints').on('click', function() {
        testsCompleted = 0;
        testsPassed = 0;
        testsFailed = 0;
        testsTotal = testEndpoints.length;
        failedEndpoints = [];

        $('#test-progress-container').show();
        $(this).prop('disabled', true);
        $('#retest-failed').hide();
        $('#test-status-text').text('Starting real-time tests...');

        let index = 0;

        function processNext() {
            if (index < testEndpoints.length) {
                testEndpoint(testEndpoints[index]).always(function() {
                    index++;
                    setTimeout(processNext, 300); // 300ms delay between tests
                });
            } else {
                $('#test-all-endpoints').prop('disabled', false);
                $('#test-status-text').text(`Test complete! Passed: ${testsPassed}/${testsTotal} | Failed: ${testsFailed}`);
                
                if (testsFailed > 0) {
                    $('#retest-failed').show();
                }
                
                if (typeof Noty !== 'undefined') {
                    new Noty({
                        text: `SEO endpoint testing complete!<br>Passed: ${testsPassed} | Failed: ${testsFailed}`,
                        type: testsFailed > 0 ? 'warning' : 'success',
                        timeout: 4000
                    }).show();
                }
            }
        }

        processNext();
    });

    /**
     * Retest failed endpoints
     */
    $('#retest-failed').on('click', function() {
        if (failedEndpoints.length === 0) {
            if (typeof Noty !== 'undefined') {
                new Noty({
                    text: 'No failed endpoints to retest!',
                    type: 'info',
                    timeout: 2000
                }).show();
            }
            return;
        }

        testsCompleted = 0;
        testsPassed = 0;
        testsFailed = 0;
        testsTotal = failedEndpoints.length;
        const endpointsToRetest = [...failedEndpoints];
        failedEndpoints = [];

        $('#test-progress-container').show();
        $('#test-status-text').text('Retesting failed endpoints...');

        let index = 0;

        function processNext() {
            if (index < endpointsToRetest.length) {
                testEndpoint(endpointsToRetest[index]).always(function() {
                    index++;
                    setTimeout(processNext, 300);
                });
            } else {
                $('#test-status-text').text(`Retest complete! Fixed: ${testsPassed} | Still failing: ${testsFailed}`);
                
                if (testsFailed === 0) {
                    $('#retest-failed').hide();
                }
                
                if (typeof Noty !== 'undefined') {
                    new Noty({
                        text: `Retest complete!<br>Fixed: ${testsPassed} | Still failing: ${testsFailed}`,
                        type: testsFailed > 0 ? 'warning' : 'success',
                        timeout: 4000
                    }).show();
                }
            }
        }

        processNext();
    });

    /**
     * Clear test results
     */
    $('#clear-test-results').on('click', function() {
        if (!confirm('Clear all test results?')) return;
        
        // Reset counters
        testsCompleted = 0;
        testsPassed = 0;
        testsFailed = 0;
        failedEndpoints = [];
        
        // Reset display
        $('#test-progress-container').hide();
        $('#test-progress-bar').css('width', '0%');
        $('#test-progress-text').text('0%');
        $('#stat-tested').text('0');
        $('#retest-failed').hide();
        
        // Restore original card states and clear test results
        $('.check-card[data-check-key]').each(function() {
            const key = $(this).data('check-key');
            const $badge = $(this).find('.check-badge');
            
            // Restore original badge status if we have it stored
            if (originalCardStates[key]) {
                $badge.removeClass('success warning error info')
                      .addClass(originalCardStates[key].badgeClass)
                      .text(originalCardStates[key].badgeText);
                
                // Restore icon
                const $icon = $(this).find('.check-header i');
                $icon.removeClass('icon-check icon-warning icon-error icon-info');
                
                if (originalCardStates[key].badgeClass === 'success') {
                    $icon.addClass('icon-check');
                } else if (originalCardStates[key].badgeClass === 'warning') {
                    $icon.addClass('icon-warning');
                } else if (originalCardStates[key].badgeClass === 'error') {
                    $icon.addClass('icon-error');
                } else {
                    $icon.addClass('icon-info');
                }
            }
        });
        
        // Clear test results from check cards
        $('.check-details ul li').each(function() {
            const text = $(this).text();
            if (text.match(/^[✓⚠✗]/)) {
                $(this).remove();
            }
        });
        
        // Update summary cards to reflect original state
        updateSummaryCards();
        
        if (typeof Noty !== 'undefined') {
            new Noty({
                text: 'Test results cleared',
                type: 'info',
                timeout: 2000
            }).show();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

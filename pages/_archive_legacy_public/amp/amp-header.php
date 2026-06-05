<?php
/**
 * AMP Header Template
 * Reusable header for all AMP pages with full SEO optimization
 */

// Default values
$pageTitle = $pageTitle ?? 'HaiPulse - Business Directory';
$pageDescription = $pageDescription ?? 'Find verified businesses in the UAE';
$canonicalUrl = $canonicalUrl ?? 'https://www.haipulse.com/';
$ampUrl = $ampUrl ?? '';
$schemaData = $schemaData ?? [];

// SEO enhancements
$pageKeywords = $pageKeywords ?? 'UAE business directory, companies in UAE, business listings';
$ogTitle = $ogTitle ?? $pageTitle;
$ogDescription = $ogDescription ?? $pageDescription;
$ogImage = $ogImage ?? getFullUrl('/assets/images/brand/logo.png');
$ogType = $ogType ?? 'website';
$twitterCard = $twitterCard ?? 'summary_large_image';
$twitterTitle = $twitterTitle ?? $pageTitle;
$twitterDescription = $twitterDescription ?? $pageDescription;
$twitterImage = $twitterImage ?? $ogImage;
$metaRobots = $metaRobots ?? 'index,follow';
$pageUrl = $pageUrl ?? $canonicalUrl;
$isAmpUserLoggedIn = function_exists('isFrontendUserLoggedIn') ? isFrontendUserLoggedIn() : false;
$ampComponents = $ampComponents ?? [];

$hasAmpImageComponent = false;
foreach ($ampComponents as $component) {
  if (!empty($component['name']) && $component['name'] === 'amp-img') {
    $hasAmpImageComponent = true;
    break;
  }
}

if (!$hasAmpImageComponent) {
  $ampComponents[] = ['name' => 'amp-img', 'src' => 'https://cdn.ampproject.org/v0/amp-img-0.1.js'];
}

$brandLogoUrl = getFullUrl('/assets/images/brand/logo.png');
$currentRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$ampPrimaryLinks = [
  ['label' => 'Listings', 'href' => url('/listings/amp')],
  ['label' => 'HS Codes', 'href' => url('/trade/hs-codes/amp')],
  ['label' => 'Blog', 'href' => url('/blog/amp')],
  ['label' => 'About', 'href' => url('/about/amp')],
  ['label' => 'Contact', 'href' => url('/contact/amp')],
];

$ampQuickLinks = [
  ['label' => 'All Companies', 'href' => url('/listings/amp')],
  ['label' => 'Trade HS Codes', 'href' => url('/trade/hs-codes/amp')],
  ['label' => 'Latest Blog', 'href' => url('/blog/amp')],
  ['label' => 'Contact Support', 'href' => url('/contact/amp')],
  ['label' => 'AMP Sitemap', 'href' => url('/sitemap-amp.xml')],
];
?>
<!doctype html>
<html âš¡ lang="en">
<head>
  <meta charset="utf-8">
  <script async src="https://cdn.ampproject.org/v0.js"></script>
  
  <!-- AMP Components -->
  <?php if (!empty($ampComponents)): ?>
    <?php foreach ($ampComponents as $component): ?>
      <script async custom-element="<?php echo htmlspecialchars($component['name'], ENT_QUOTES); ?>" 
              src="<?php echo htmlspecialchars($component['src'], ENT_QUOTES); ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>
  
  <!-- Primary Meta Tags -->
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="robots" content="<?php echo htmlspecialchars($metaRobots, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="author" content="HaiPulse - Business Directory">
  <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
  
  <!-- Canonical & AMP -->
  <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
  
  <!-- Mobile Web App -->
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="UAE Business">
  <meta name="theme-color" content="#0f4ad8">
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?php echo url('/assets/images/brand/favicon.png'); ?>">
  <link rel="apple-touch-icon" href="<?php echo url('/assets/images/brand/logo.png'); ?>">
  
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="<?php echo htmlspecialchars($ogType, ENT_QUOTES); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES); ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES); ?>">
  <meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES); ?>">
  <meta property="og:image:alt" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES); ?>">
  <meta property="og:site_name" content="HaiPulse - Business Directory">
  <meta property="og:locale" content="en_AE">
  
  <!-- Twitter Card -->
  <meta name="twitter:card" content="<?php echo htmlspecialchars($twitterCard, ENT_QUOTES); ?>">
  <meta name="twitter:url" content="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES); ?>">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($twitterTitle, ENT_QUOTES); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($twitterDescription, ENT_QUOTES); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($twitterImage, ENT_QUOTES); ?>">
  <meta name="twitter:image:alt" content="<?php echo htmlspecialchars($twitterTitle, ENT_QUOTES); ?>">
  
  <!-- Schema.org structured data -->
  <?php if (!empty($schemaData)): ?>
    <?php
    // Handle both single schema and array of schemas
    $schemas = $schemaData;
  
    // If $schemaData is a single schema (has @context), wrap it in an array
    if (isset($schemaData['@context'])) {
      $schemas = [$schemaData];
    }
  
    // Output each schema in a separate script tag
    foreach ($schemas as $schema):
      if (!empty($schema) && is_array($schema)):
    ?>
    <script type="application/ld+json">
    <?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php
      endif;
    endforeach;
    ?>
  <?php endif; ?>
  
  <!-- AMP Boilerplate -->
  <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
  
  <!-- Custom styles (inline, max 50KB) -->
  <style amp-custom>
    /* Reset & Base */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      font-size: 16px;
      line-height: 1.6;
      color: #333;
      background: #f8f9fa;
    }
    
    /* Container */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 16px;
    }
    
    .container-narrow {
      max-width: 960px;
      margin: 0 auto;
      padding: 0 16px;
    }
    
    /* Header */
    .header {
      background: #fff;
      border-bottom: 1px solid #e0e0e0;
      padding: 16px 0;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    
    .header-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .logo {
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      color: #0f4ad8;
      font-weight: 700;
      font-size: 1.2rem;
    }

    .logo-image {
      border-radius: 6px;
      overflow: hidden;
      flex-shrink: 0;
      background: #ffffff;
      border: 1px solid #d8e2f7;
    }
    
    .logo-badge {
      background: #0f4ad8;
      color: white;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.9rem;
    }
    
    .nav-menu {
      display: flex;
      gap: 16px;
      align-items: center;
    }
    
    .nav-link {
      text-decoration: none;
      color: #666;
      font-size: 0.95rem;
      padding: 8px 12px;
      border-radius: 4px;
      transition: background 0.2s;
      border: 1px solid transparent;
    }
    
    .nav-link:hover {
      background: #f0f0f0;
      color: #0f4ad8;
    }

    .nav-link-active {
      color: #0f4ad8;
      background: #eaf0ff;
      border-color: #cddcff;
    }

    .amp-quick-nav {
      background: #ffffff;
      border-bottom: 1px solid #e6e9f0;
      padding: 10px 0;
    }

    .amp-quick-nav-inner {
      display: flex;
      gap: 8px;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: thin;
      padding-bottom: 2px;
    }

    .amp-quick-nav-link {
      display: inline-block;
      white-space: nowrap;
      text-decoration: none;
      color: #1b3f8b;
      background: #eef3ff;
      border: 1px solid #d9e4ff;
      border-radius: 999px;
      font-size: 0.82rem;
      line-height: 1.2;
      padding: 7px 12px;
    }

    .amp-footer {
      background: #2c3e50;
      color: #fff;
      padding: 32px 0;
      margin-top: 48px;
    }

    .amp-footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 24px;
      margin-bottom: 24px;
    }

    .amp-footer-title {
      font-size: 1rem;
      margin-bottom: 12px;
      font-weight: 600;
    }

    .amp-footer-text {
      color: #c3ccda;
      font-size: 0.9rem;
      line-height: 1.5;
    }

    .amp-footer-links {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .amp-footer-links-spaced {
      margin-top: 10px;
    }

    .amp-footer-link {
      color: #c3ccda;
      text-decoration: none;
      font-size: 0.9rem;
    }

    .amp-footer-link:hover {
      color: #ffffff;
    }

    .amp-footer-meta {
      padding-top: 20px;
      border-top: 1px solid #42556a;
      text-align: center;
    }

    .amp-footer-meta-text {
      color: #aab5c5;
      font-size: 0.85rem;
      margin: 0;
    }

    .context-nav {
      background: #f7faff;
      border: 1px solid #d8e5ff;
      border-radius: 10px;
      padding: 14px;
      margin: 16px;
    }

    .context-nav-tight {
      margin: 0 0 16px;
    }

    .context-nav-title {
      font-size: 0.92rem;
      font-weight: 600;
      color: #13346f;
      margin-bottom: 10px;
    }

    .context-nav-links {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .context-nav-link {
      display: inline-block;
      text-decoration: none;
      color: #1247a6;
      border: 1px solid #cddfff;
      background: #ffffff;
      border-radius: 999px;
      padding: 7px 11px;
      font-size: 0.84rem;
      line-height: 1.2;
    }
    
    /* Main Content */
    .main-content {
      padding: 24px 0;
      min-height: 60vh;
    }
    
    /* Cards */
    .card {
      background: white;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .card:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .card-header {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 8px;
      color: #0f4ad8;
    }
    
    .card-description {
      color: #666;
      font-size: 0.95rem;
      line-height: 1.5;
    }
    
    /* Buttons */
    .btn {
      display: inline-block;
      padding: 10px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s;
      border: none;
      cursor: pointer;
      font-size: 1rem;
    }
    
    .btn-primary {
      background: #0f4ad8;
      color: white;
    }
    
    .btn-primary:hover {
      background: #0d3eb5;
    }
    
    .btn-secondary {
      background: #f0f0f0;
      color: #333;
    }
    
    .btn-secondary:hover {
      background: #e0e0e0;
    }
    
    /* Forms */
    .form-group {
      margin-bottom: 16px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
      color: #333;
    }
    
    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      font-family: inherit;
    }
    
    .form-control:focus {
      outline: none;
      border-color: #0f4ad8;
      box-shadow: 0 0 0 3px rgba(15, 74, 216, 0.1);
    }
    
    /* Utility Classes */
    .text-center { text-align: center; }
    .text-muted { color: #999; }
    .mb-1 { margin-bottom: 8px; }
    .mb-2 { margin-bottom: 16px; }
    .mb-3 { margin-bottom: 24px; }
    .mt-1 { margin-top: 8px; }
    .mt-2 { margin-top: 16px; }
    .mt-3 { margin-top: 24px; }
    
    /* Responsive */
    @media (max-width: 768px) {
      .header {
        position: static;
      }

      .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .nav-menu {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }

      .nav-link {
        font-size: 0.85rem;
        padding: 6px 10px;
      }
      
      .container {
        padding: 0 12px;
      }
    }

    <?php
    if (!empty($pageCustomCss) && is_string($pageCustomCss)) {
      echo "\n" . trim($pageCustomCss) . "\n";
    }
    ?>
  </style>
</head>
<body>
  
  <!-- Header -->
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="logo">
          <span class="logo-image">
            <amp-img
              src="<?php echo htmlspecialchars($brandLogoUrl, ENT_QUOTES, 'UTF-8'); ?>"
              width="34"
              height="34"
              layout="fixed"
              alt="HaiPulse - Business Directory Logo">
            </amp-img>
          </span>
          <span>HaiPulse - Business Directory</span>
        </a>
        <nav class="nav-menu">
          <?php foreach ($ampPrimaryLinks as $navItem): ?>
            <?php
              $navHref = (string)($navItem['href'] ?? '#');
              $navPath = parse_url($navHref, PHP_URL_PATH) ?: '';
              $isActive = $currentRequestPath !== '' && $navPath !== '' && strpos($currentRequestPath, $navPath) === 0;
            ?>
            <a href="<?php echo htmlspecialchars($navHref, ENT_QUOTES, 'UTF-8'); ?>" class="nav-link<?php echo $isActive ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars((string)$navItem['label'], ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endforeach; ?>
          <?php if ($isAmpUserLoggedIn): ?>
            <a href="<?php echo htmlspecialchars(url('/account/profile'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">My Account</a>
            <a href="<?php echo htmlspecialchars(url('/logout'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">Logout</a>
          <?php else: ?>
            <a href="<?php echo htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">Login</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>

  <div class="amp-quick-nav">
    <div class="container">
      <div class="amp-quick-nav-inner">
        <?php foreach ($ampQuickLinks as $quickItem): ?>
          <a href="<?php echo htmlspecialchars((string)($quickItem['href'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="amp-quick-nav-link"><?php echo htmlspecialchars((string)($quickItem['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endforeach; ?>
        <a href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" class="amp-quick-nav-link">Full Site</a>
      </div>
    </div>
  </div>
  
  <!-- Main Content Start -->
  <main class="main-content">



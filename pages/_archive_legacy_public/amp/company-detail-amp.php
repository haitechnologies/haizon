<?php
/**
 * AMP Page: Company Detail
 * Route: /company/{slug}/amp
 * 
 * Mobile-optimized company detail page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// config/database.php may be loaded earlier inside function scope (router checks),
// so ensure this template still has a connection handle in global scope.
if (!isset($conn) || !($conn instanceof mysqli)) {
  $conn = $GLOBALS['DB']['MSQLI'] ?? null;
}

if (!($conn instanceof mysqli)) {
  http_response_code(500);
  echo 'Database connection unavailable.';
  exit;
}

// Get company slug from route
$slug = $GLOBALS['route_params']['company_slug'] ?? null;

if (!$slug) {
    header('Location: ' . url('/listings'));
    exit;
}

// Query company details
$query = "
    SELECT 
        comp.id,
        comp.company_name,
        comp.slug,
      comp.primary_category_id,
        comp.company_profile AS description,
        comp.city AS emirate,
        comp.city,
        comp.address,
        comp.telephone AS phone,
        comp.email,
        comp.website,
        comp.verified,
        comp.meta_keywords AS keywords,
        comp.lat,
        comp.lng,
        IFNULL(cat.name, 'Business') AS category_name,
        cat.slug AS category_slug
    FROM `" . DB::COMPANIES . "` comp
    LEFT JOIN `" . DB::CATEGORIES . "` cat ON cat.id = comp.primary_category_id
    WHERE comp.is_active = 1 AND (comp.publish = 1 OR comp.verified = 1) AND comp.slug = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $slug);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
  header('Location: ' . url('/listings/amp'));
    exit;
}

$similarCompanies = [];
try {
  $similarQuery = "
    SELECT
      comp.id,
      comp.company_name,
      comp.slug,
      comp.city,
      IFNULL(cat.name, 'Business') AS category_name,
      comp.verified,
      comp.company_profile AS description
    FROM `" . DB::COMPANIES . "` comp
    LEFT JOIN `" . DB::CATEGORIES . "` cat ON cat.id = comp.primary_category_id
    WHERE comp.is_active = 1
      AND comp.publish = 1
      AND comp.id <> ?
      AND comp.primary_category_id = ?
    ORDER BY comp.verified DESC, comp.id DESC
    LIMIT 5
  ";

  $similarStmt = $conn->prepare($similarQuery);
  $similarStmt->bind_param('ii', $company['id'], $company['primary_category_id']);
  $similarStmt->execute();
  $similarResult = $similarStmt->get_result();
  while ($row = $similarResult->fetch_assoc()) {
    $similarCompanies[] = $row;
  }
  $similarStmt->close();

  if (count($similarCompanies) < 3 && !empty($company['city'])) {
    $existingIds = [$company['id']];
    foreach ($similarCompanies as $item) {
      $existingIds[] = (int)$item['id'];
    }

    $remainingSlots = 5 - count($similarCompanies);
    if ($remainingSlots > 0) {
      $placeholders = implode(',', array_fill(0, count($existingIds), '?'));
      $fallbackQuery = "
        SELECT
          comp.id,
          comp.company_name,
          comp.slug,
          comp.city,
          IFNULL(cat.name, 'Business') AS category_name,
          comp.verified,
          comp.company_profile AS description
        FROM `" . DB::COMPANIES . "` comp
        LEFT JOIN `" . DB::CATEGORIES . "` cat ON cat.id = comp.primary_category_id
        WHERE comp.is_active = 1
          AND comp.publish = 1
          AND LOWER(comp.city) = LOWER(?)
          AND comp.id NOT IN ($placeholders)
        ORDER BY comp.verified DESC, comp.id DESC
        LIMIT ?
      ";

      $fallbackStmt = $conn->prepare($fallbackQuery);
      $fallbackTypes = 's' . str_repeat('i', count($existingIds)) . 'i';
      $fallbackParams = array_merge([$company['city']], $existingIds, [$remainingSlots]);
      $fallbackStmt->bind_param($fallbackTypes, ...$fallbackParams);
      $fallbackStmt->execute();
      $fallbackResult = $fallbackStmt->get_result();
      while ($row = $fallbackResult->fetch_assoc()) {
        $similarCompanies[] = $row;
      }
      $fallbackStmt->close();
    }
  }
} catch (Throwable $e) {
  error_log('AMP Company detail: similar companies load failed - ' . $e->getMessage());
}

// Format data
$companyName = display_text($company['company_name'] ?? '');
$location = trim(($company['city'] ?: '') . ($company['city'] && $company['emirate'] ? ', ' : '') . ($company['emirate'] ? ucwords(str_replace('-', ' ', $company['emirate'])) : ''));

// Page meta
$pageTitle = $companyName . ' - HAIPULSE';
$pageDescription = substr(display_text($company['description'] ?: 'View business details for ' . $companyName), 0, 160);
$pageKeywords = $companyName . ', ' . ($company['category_name'] ?? 'business') . ', UAE business, ' . ($company['city'] ?? 'UAE');
$canonicalUrl = url('/company/' . $company['slug']);
$pageUrl = url('/company/' . $company['slug'] . '/amp');

// Open Graph
$ogTitle = $companyName . ' - HAIPULSE';
$ogDescription = $pageDescription;
$ogImage = getFullUrl('/assets/images/brand/logo.png');
$ogType = 'business.business';

// Twitter Card
$twitterCard = 'summary';
$twitterTitle = $ogTitle;
$twitterDescription = $pageDescription;
$twitterImage = $ogImage;

// Schema.org LocalBusiness
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => $companyName,
    'url' => $canonicalUrl
];

if ($company['description']) {
    $schemaData['description'] = $company['description'];
}

if ($company['phone']) {
    $schemaData['telephone'] = $company['phone'];
}

if ($company['email']) {
    $schemaData['email'] = $company['email'];
}

if ($location) {
    $schemaData['address'] = [
        '@type' => 'PostalAddress',
        'addressLocality' => $location,
        'addressCountry' => 'AE'
    ];
}

if ($company['lat'] && $company['lng']) {
    $schemaData['geo'] = [
      '@type' => 'GeoCoordinates',
      'latitude' => $company['lat'],
      'longitude' => $company['lng']
    ];
  }

  // Add additional business details
  if ($company['website']) {
    $schemaData['url'] = $company['website'];
  }

  // Add breadcrumb schema
  $breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => getFullUrl('/')
      ],
      [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => 'Listings',
        'item' => getFullUrl('/listings')
      ],
      [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $companyName,
        'item' => $pageUrl
      ]
    ]
  ];

  // Combine schemas
  $schemaData = [
    $schemaData,
    $breadcrumbSchema
  ];

// AMP components
$ampComponents = [
  ['name' => 'amp-bind', 'src' => 'https://cdn.ampproject.org/v0/amp-bind-0.1.js']
];
if ($company['lat'] && $company['lng']) {
    $ampComponents[] = ['name' => 'amp-iframe', 'src' => 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js'];
  $ampComponents[] = ['name' => 'amp-img', 'src' => 'https://cdn.ampproject.org/v0/amp-img-0.1.js'];
}

$pageCustomCss = <<<'CSS'
  /* Company Detail Styles */
  .company-header {
    background: linear-gradient(135deg, #0f4ad8 0%, #1e5fd8 100%);
    color: white;
    padding: 32px 16px;
    margin-bottom: 24px;
  }
  
  .company-name {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 12px;
    line-height: 1.3;
  }
  
  .company-category {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 6px 14px;
    border-radius: 16px;
    font-size: 0.9rem;
    margin-bottom: 12px;
  }
  
  .company-location {
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    opacity: 0.95;
  }
  
  .verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #4caf50;
    color: white;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 0.85rem;
    font-weight: 500;
    margin-top: 12px;
  }
  
  .content-section {
    background: white;
    padding: 24px 16px;
    margin-bottom: 16px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .section-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #333;
    padding-bottom: 12px;
    border-bottom: 2px solid #e9ecef;
  }
  
  .description-text {
    line-height: 1.8;
    color: #444;
  }
  
  .description-text p {
    margin-bottom: 16px;
  }
  
  .contact-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  
  .contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
  }
  
  .contact-item:last-child {
    border-bottom: none;
  }
  
  .contact-icon {
    font-size: 1.3rem;
    min-width: 30px;
    text-align: center;
  }
  
  .contact-label {
    font-weight: 600;
    color: #666;
    min-width: 80px;
  }
  
  .contact-value {
    color: #333;
    word-break: break-word;
  }
  
  .contact-value a {
    color: #1e5fd8;
    text-decoration: none;
  }

  .contact-reveal-btn {
    width: 100%;
    margin: 0;
    border: 1px solid #d0d7e2;
    background: #f8fafc;
    color: #1e5fd8;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 6px;
    line-height: 1.2;
    cursor: pointer;
    text-align: left;
  }

  .contact-reveal-panel {
    padding-top: 8px;
  }

  .contact-reveal-link {
    display: inline-block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0f4ad8;
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 6px;
    background: #eef4ff;
    border: 1px solid #d7e4ff;
  }

  .similar-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .similar-card {
    border: 1px solid #e4e9f2;
    border-radius: 8px;
    padding: 12px;
    background: #fbfcff;
  }

  .similar-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 6px;
  }

  .similar-title a {
    color: #1e5fd8;
    text-decoration: none;
  }

  .similar-meta {
    font-size: 0.82rem;
    color: #657287;
    margin-bottom: 6px;
  }

  .similar-snippet {
    font-size: 0.88rem;
    color: #444;
    line-height: 1.5;
  }
  
  .map-container {
    width: 100%;
    height: 300px;
    border-radius: 8px;
    overflow: hidden;
  }
  
  .action-buttons {
    display: flex;
    gap: 12px;
    padding: 16px;
    flex-wrap: wrap;
  }
  
  .action-btn {
    flex: 1;
    min-width: 140px;
    padding: 12px 20px;
    border-radius: 6px;
    text-align: center;
    text-decoration: none;
    font-weight: 500;
    border: none;
    cursor: pointer;
  }
  
  .btn-primary {
    background: #1e5fd8;
    color: white;
  }
  
  .btn-secondary {
    background: #6c757d;
    color: white;
  }
  
  .view-regular {
    text-align: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 6px;
    margin: 16px;
  }
  
  .view-regular a {
    color: #1e5fd8;
    text-decoration: none;
    font-weight: 500;
  }

  .bottom-nav {
    text-align: center;
    margin: 24px 0;
  }

  .bottom-nav-link {
    color: #1e5fd8;
    text-decoration: none;
    font-weight: 500;
    display: inline-block;
    margin: 0 8px 8px;
  }
CSS;

include __DIR__ . '/amp-header.php';
?>

<!-- Company Header -->
<div class="company-header">
  <div class="company-category">
    <?= htmlspecialchars($company['category_name'], ENT_QUOTES) ?>
  </div>
  
  <h1 class="company-name"><?= htmlspecialchars($companyName, ENT_QUOTES) ?></h1>
  
  <?php if ($location): ?>
  <div class="company-location">
    <span>Location:</span>
    <span><?= htmlspecialchars($location, ENT_QUOTES) ?></span>
  </div>
  <?php endif; ?>
  
  <?php if ($company['verified']): ?>
  <div class="verified-badge">
    <span>Verified Business</span>
  </div>
  <?php endif; ?>
</div>

<!-- Description -->
<?php if (!empty($company['description'])): ?>
<div class="content-section">
  <h2 class="section-title">About</h2>
  <div class="description-text">
    <?php
    // Strip scripts and inline handlers for AMP compliance
    $description = $company['description'];
    $description = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $description);
    $description = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $description);
    $description = preg_replace('/\s*style\s*=\s*["\'][^"\']*["\']/i', '', $description);
    echo $description;
    ?>
  </div>
</div>
<?php endif; ?>

<!-- Contact Information -->
<?php if ($company['phone'] || $company['email'] || $company['website'] || $company['address']): ?>
<div class="content-section">
  <h2 class="section-title">Contact Information</h2>
  <amp-state id="companyContactState">
    <script type="application/json">{"phone": false, "email": false}</script>
  </amp-state>
  
  <ul class="contact-list">
    <?php if ($company['phone']): ?>
    <li class="contact-item">
      <div class="contact-icon">Contact</div>
      <div class="contact-value">
        <button
          type="button"
          class="contact-reveal-btn"
          on="tap:AMP.setState({companyContactState: {phone: !companyContactState.phone}})">
          Click to view phone
        </button>
        <div class="contact-reveal-panel" [hidden]="!companyContactState.phone" hidden>
          <a class="contact-reveal-link" href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', (string)$company['phone']), ENT_QUOTES) ?>">
            <?= htmlspecialchars($company['phone'], ENT_QUOTES) ?>
          </a>
        </div>
      </div>
    </li>
    <?php endif; ?>
    
    <?php if ($company['email']): ?>
    <li class="contact-item">
      <div class="contact-icon">Contact</div>
      <div class="contact-value">
        <button
          type="button"
          class="contact-reveal-btn"
          on="tap:AMP.setState({companyContactState: {email: !companyContactState.email}})">
          Click to view email
        </button>
        <div class="contact-reveal-panel" [hidden]="!companyContactState.email" hidden>
          <a class="contact-reveal-link" href="mailto:<?= htmlspecialchars($company['email'], ENT_QUOTES) ?>">
            <?= htmlspecialchars($company['email'], ENT_QUOTES) ?>
          </a>
        </div>
      </div>
    </li>
    <?php endif; ?>
    
    <?php if ($company['website']): ?>
    <li class="contact-item">
      <div class="contact-icon">Website</div>
      <div class="contact-label">Website:</div>
      <div class="contact-value">
        <a href="<?= htmlspecialchars($company['website'], ENT_QUOTES) ?>" target="_blank" rel="noopener">
          <?= htmlspecialchars($company['website'], ENT_QUOTES) ?>
        </a>
      </div>
    </li>
    <?php endif; ?>
    
    <?php if ($company['address']): ?>
    <li class="contact-item">
      <div class="contact-icon">Address</div>
      <div class="contact-label">Address:</div>
      <div class="contact-value">
        <?= htmlspecialchars($company['address'], ENT_QUOTES) ?>
      </div>
    </li>
    <?php endif; ?>
  </ul>
</div>
<?php endif; ?>

<!-- Map -->
<?php if ($company['lat'] && $company['lng']): ?>
<div class="content-section">
  <h2 class="section-title">Location Map</h2>
  
  <amp-iframe
    width="600"
    height="300"
    layout="responsive"
    sandbox="allow-scripts allow-same-origin"
    src="https://www.google.com/maps?q=<?= $company['lat'] ?>,<?= $company['lng'] ?>&output=embed"
    frameborder="0">
    <amp-img
      layout="fill"
      src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 300'%3E%3Crect width='600' height='300' fill='%23f0f0f0'/%3E%3C/svg%3E"
      placeholder>
    </amp-img>
  </amp-iframe>
</div>
<?php endif; ?>

<?php if (!empty($similarCompanies)): ?>
<div class="content-section">
  <h2 class="section-title">Similar Companies</h2>
  <div class="similar-grid">
    <?php foreach ($similarCompanies as $similar): ?>
    <article class="similar-card">
      <h3 class="similar-title">
        <a href="<?= url('/company/' . urlencode((string)$similar['slug']) . '/amp') ?>">
          <?= htmlspecialchars((string)$similar['company_name'], ENT_QUOTES) ?>
        </a>
      </h3>
      <div class="similar-meta">
        <?= htmlspecialchars((string)($similar['category_name'] ?? 'Business'), ENT_QUOTES) ?>
        <?php if (!empty($similar['city'])): ?>
          | <?= htmlspecialchars((string)$similar['city'], ENT_QUOTES) ?>
        <?php endif; ?>
        <?php if (!empty($similar['verified'])): ?>
          | Verified
        <?php endif; ?>
      </div>
      <?php if (!empty($similar['description'])): ?>
      <div class="similar-snippet">
        <?= htmlspecialchars(truncateText(strip_tags((string)$similar['description']), 120), ENT_QUOTES) ?>
      </div>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="action-buttons">
  <?php if ($company['website']): ?>
  <a href="<?= htmlspecialchars($company['website'], ENT_QUOTES) ?>" class="action-btn btn-secondary" target="_blank" rel="noopener">
    Visit Website
  </a>
  <?php endif; ?>
</div>

<!-- View Full Version -->
<div class="view-regular">
  <a href="<?= $canonicalUrl ?>">View Full Website Version</a>
</div>

<!-- Back to Listings -->
<div class="bottom-nav">
  <a href="<?= url('/listings/amp') ?>" class="bottom-nav-link">Back to Listings</a>
  <a href="<?= url('/trade/hs-codes/amp') ?>" class="bottom-nav-link">Explore HS Codes</a>
  <a href="<?= url('/contact/amp') ?>" class="bottom-nav-link">Contact for Inquiry</a>
</div>

<?php include __DIR__ . '/amp-footer.php'; ?>


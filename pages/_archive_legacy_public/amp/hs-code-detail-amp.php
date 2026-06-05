<?php
/**
 * AMP Page: Individual HS Code Detail
 * Route: /trade/hs-code/{code}/amp
 * 
 * Mobile-optimized detail page for specific HS code
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/frontend/HSCodes.php';

$code = $GLOBALS['route_params'][0] ?? null;

if (!$code) {
    header('Location: ' . url('/trade/hs-codes/amp'));
    exit;
}

$hsCodesModel = new HSCodes($conn);

// Get HS code with both English and Arabic data
$hsCode = $hsCodesModel->getByCode($code);

if (!$hsCode) {
  header('Location: ' . url('/trade/hs-codes/amp'));
    exit;
}

// Get child codes
$childCodes = $hsCodesModel->getChildren($hsCode['id'], 'en', 20);

// Get related categories
$categories = $hsCodesModel->getRelatedCategories($hsCode['id'], 10);

// Get related companies
$companies = $hsCodesModel->getRelatedCompanies($hsCode['id'], 20);

// Determine descriptions (both languages)
$descriptionEn = (string)($hsCode['desc_en'] ?? '');
$shortDescEn = (string)($hsCode['short_en'] ?? '');
$descriptionAr = (string)($hsCode['desc_ar'] ?? '');
$shortDescAr = (string)($hsCode['short_ar'] ?? '');

if ($shortDescEn === '') {
  $shortDescEn = $descriptionEn !== '' ? $descriptionEn : (string)($hsCode['code'] ?? '');
}

if ($descriptionEn === '') {
  $descriptionEn = $shortDescEn;
}

if ($shortDescAr === '') {
  $shortDescAr = $descriptionAr !== '' ? $descriptionAr : $shortDescEn;
}

if ($descriptionAr === '') {
  $descriptionAr = $shortDescAr;
}

// Page meta
$safeCode = (string)($hsCode['code'] ?? '');
$oldCodeValue = trim((string)($hsCode['old_code'] ?? ''));
$pageTitle = "HS Code {$safeCode} - {$shortDescEn} (AMP)";
$pageDescription = $descriptionEn ?: "Detailed information about HS Code {$safeCode}";
$canonicalUrl = getFullUrl('/trade/hs-code/' . rawurlencode($safeCode));
$pageKeywords = implode(', ', array_filter([
  'HS Code ' . $safeCode,
  $shortDescEn,
  $shortDescAr,
  'UAE HS code',
  'trade classification',
  'customs tariff UAE'
]));
$pageUrl = getFullUrl('/trade/hs-code/' . rawurlencode($safeCode) . '/amp');
$ampUrl = $pageUrl;

$breadcrumbItems = [
  ['name' => 'Home', 'url' => getFullUrl('/')],
  ['name' => 'Trade', 'url' => getFullUrl('/trade')],
  ['name' => 'HS Codes', 'url' => getFullUrl('/trade/hs-codes')],
  ['name' => 'HS Code ' . $safeCode, 'url' => getFullUrl('/trade/hs-code/' . rawurlencode($safeCode))]
];

$schemaData = [
  [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    '@id' => getFullUrl('/trade/hs-code/' . rawurlencode($safeCode)) . '#webpage',
    'url' => getFullUrl('/trade/hs-code/' . rawurlencode($safeCode)),
    'name' => str_replace(' (AMP)', '', $pageTitle),
    'description' => $pageDescription,
    'mainEntity' => ['@id' => getFullUrl('/trade/hs-code/' . rawurlencode($safeCode)) . '#term'],
    'breadcrumb' => ['@id' => getFullUrl('/trade/hs-code/' . rawurlencode($safeCode)) . '#breadcrumb'],
    'inLanguage' => 'en-AE'
  ],
  [
    '@context' => 'https://schema.org',
    '@type' => 'DefinedTerm',
    '@id' => getFullUrl('/trade/hs-code/' . rawurlencode($safeCode)) . '#term',
    'name' => 'HS Code ' . $safeCode,
    'termCode' => $safeCode,
    'description' => $descriptionEn,
    'url' => getFullUrl('/trade/hs-code/' . rawurlencode($safeCode)),
    'identifier' => [
      '@type' => 'PropertyValue',
      'propertyID' => 'HS Code',
      'value' => $safeCode
    ],
    'inDefinedTermSet' => [
      '@type' => 'DefinedTermSet',
      'name' => 'UAE HS Codes Directory',
      'url' => getFullUrl('/trade/hs-codes')
    ]
  ],
  [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    '@id' => getFullUrl('/trade/hs-code/' . rawurlencode($safeCode)) . '#breadcrumb',
    'itemListElement' => []
  ]
];

if ($shortDescAr !== '') {
  $schemaData[1]['alternateName'] = $shortDescAr;
}

if (!empty($hsCode['parent_code'])) {
  $schemaData[1]['broader'] = [
    '@type' => 'DefinedTerm',
    'termCode' => (string)$hsCode['parent_code'],
    'url' => getFullUrl('/trade/hs-code/' . rawurlencode((string)$hsCode['parent_code']))
  ];
}

foreach ($breadcrumbItems as $index => $breadcrumbItem) {
  $schemaData[2]['itemListElement'][] = [
    '@type' => 'ListItem',
    'position' => $index + 1,
    'name' => $breadcrumbItem['name'],
    'item' => $breadcrumbItem['url']
  ];
}

// AMP components
$ampComponents = [
    ['name' => 'amp-accordion', 'src' => 'https://cdn.ampproject.org/v0/amp-accordion-0.1.js']
];

$pageCustomCss = <<<'CSS'
  /* HS Code Detail Styles */
  .hero-section {
    background: linear-gradient(135deg, #0f4ad8 0%, #1e5fd8 100%);
    color: white;
    padding: 32px 16px;
    margin-bottom: 24px;
    border-radius: 8px;
  }
  
  .hero-code {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 12px;
  }
  
  .hero-description {
    font-size: 1.1rem;
    opacity: 0.95;
    line-height: 1.5;
  }
  
  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
  }
  
  .info-box {
    background: white;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    text-align: center;
  }
  
  .info-label {
    font-size: 0.85rem;
    color: #999;
    margin-bottom: 4px;
  }
  
  .info-value {
    font-size: 1.2rem;
    font-weight: 600;
    color: #0f4ad8;
  }
  
  .section-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #333;
  }
  
  .child-code-item {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
    text-decoration: none;
    display: block;
    color: inherit;
  }
  
  .child-code-item:hover {
    border-color: #0f4ad8;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .child-code-number {
    font-weight: 600;
    color: #0f4ad8;
    font-size: 1.05rem;
    margin-bottom: 4px;
  }
  
  .child-code-desc {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.4;
  }
  
  .company-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    color: inherit;
  }
  
  .company-card:hover {
    border-color: #0f4ad8;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .company-logo {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #0f4ad8;
    flex-shrink: 0;
  }
  
  .company-info {
    flex: 1;
  }
  
  .company-name {
    font-weight: 600;
    margin-bottom: 2px;
    color: #333;
  }
  
  .company-location {
    font-size: 0.85rem;
    color: #999;
  }
  
  .category-tag {
    display: inline-block;
    padding: 6px 12px;
    background: #f0f0f0;
    border-radius: 20px;
    text-decoration: none;
    color: #666;
    font-size: 0.85rem;
    margin: 4px;
    transition: all 0.2s;
  }
  
  .category-tag:hover {
    background: #0f4ad8;
    color: white;
  }
  
  .breadcrumb {
    background: white;
    padding: 12px 0;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 24px;
  }
  
  .breadcrumb-list {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    font-size: 0.85rem;
  }
  
  .breadcrumb-item::after {
    content: '›';
    margin-left: 8px;
    color: #999;
  }
  
  .breadcrumb-item:last-child::after {
    content: '';
  }
  
  .breadcrumb-link {
    color: #0f4ad8;
    text-decoration: none;
  }
  
  amp-accordion section {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 8px;
    overflow: hidden;
  }
  
  amp-accordion section[expanded] {
    border-color: #0f4ad8;
  }
  
  amp-accordion h3 {
    background: white;
    padding: 12px 16px;
    margin: 0;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  amp-accordion section > div {
    padding: 16px;
    background: #fafafa;
  }
  
  .lang-toggle-inline {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
  }
  
  .lang-btn-inline {
    padding: 6px 16px;
    border: 1px solid #ddd;
    border-radius: 20px;
    text-decoration: none;
    color: #666;
    font-size: 0.85rem;
    background: white;
  }
  
  .lang-btn-inline.active {
    background: #0f4ad8;
    color: white;
    border-color: #0f4ad8;
  }
CSS;

include __DIR__ . '/amp-header.php';
?>

<div class="container">
  
  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <ul class="breadcrumb-list">
      <li class="breadcrumb-item"><a href="<?php echo url('/'); ?>" class="breadcrumb-link">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url('/trade'); ?>" class="breadcrumb-link">Trade</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url('/trade/hs-codes/amp'); ?>" class="breadcrumb-link">HS Codes</a></li>
      <?php if (!empty($hsCode['parent_code'])): ?>
        <li class="breadcrumb-item">
          <a href="<?php echo url('/trade/hs-code/' . $hsCode['parent_code'] . '/amp'); ?>" class="breadcrumb-link">
            <?php echo htmlspecialchars((string)$hsCode['parent_code'], ENT_QUOTES); ?>
          </a>
        </li>
      <?php endif; ?>
      <li class="breadcrumb-item"><?php echo htmlspecialchars($safeCode, ENT_QUOTES); ?></li>
    </ul>
  </div>
  
  <!-- Hero Section -->
  <div class="hero-section">
    <div class="hero-code">HS Code <?php echo htmlspecialchars($safeCode, ENT_QUOTES); ?></div>
    <div class="hero-description">
      <?php echo htmlspecialchars($shortDescEn, ENT_QUOTES); ?>
    </div>
    <?php if ($oldCodeValue !== ''): ?>
    <div style="margin-top: 12px; display: inline-block; background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.28); border-radius: 999px; padding: 6px 11px; font-size: 0.82rem;">
      Old HS Code: <?php echo htmlspecialchars($oldCodeValue, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($shortDescAr)): ?>
    <div style="margin-top: 16px; opacity: 0.9; font-size: 1rem; direction: rtl; text-align: right;">
      <?php echo htmlspecialchars($shortDescAr, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>
  </div>
  
  <!-- Info Grid -->
  <div class="info-grid">
    <div class="info-box">
      <div class="info-label">Level</div>
      <div class="info-value"><?php echo $hsCode['level']; ?>-digit</div>
    </div>
    <?php if (!empty($childCodes)): ?>
    <div class="info-box">
      <div class="info-label">Sub-codes</div>
      <div class="info-value"><?php echo count($childCodes); ?></div>
    </div>
    <?php endif; ?>
    <?php if (!empty($categories)): ?>
    <div class="info-box">
      <div class="info-label">Categories</div>
      <div class="info-value"><?php echo count($categories); ?></div>
    </div>
    <?php endif; ?>
    <?php if (!empty($companies)): ?>
    <div class="info-box">
      <div class="info-label">Companies</div>
      <div class="info-value"><?php echo count($companies); ?></div>
    </div>
    <?php endif; ?>
  </div>
  
  <!-- Parent Code Link -->
  <?php if (!empty($hsCode['parent_code'])): ?>
  <div class="card mb-3">
    <div style="font-size: 0.85rem; color: #999; margin-bottom: 4px;">
      Parent Code
    </div>
    <a href="<?php echo url('/trade/hs-code/' . urlencode($hsCode['parent_code']) . '/amp'); ?>" 
       style="color: #0f4ad8; text-decoration: none; font-weight: 600;">
      HS Code <?php echo htmlspecialchars((string)$hsCode['parent_code'], ENT_QUOTES); ?>
    </a>
  </div>
  <?php endif; ?>
  
  <!-- Child Codes (Accordion) -->
  <?php if (!empty($childCodes)): ?>
  <div class="mb-3">
    <h2 class="section-title">
      Sub-Classifications (<?php echo count($childCodes); ?>)
    </h2>
    
    <amp-accordion disable-session-states>
      <?php foreach (array_slice($childCodes, 0, 10) as $child): ?>
        <?php 
        $childDesc = (string)($child['long_desc'] ?? $child['short_desc'] ?? '');
        ?>
        <section>
          <h3>
            <span><?php echo htmlspecialchars((string)($child['code'] ?? ''), ENT_QUOTES); ?></span>
            <span style="color: #999;">›</span>
          </h3>
          <div>
            <p style="margin-bottom: 12px; color: #666;">
              <?php echo htmlspecialchars($childDesc, ENT_QUOTES); ?>
            </p>
            <a href="<?php echo htmlspecialchars(url('/trade/hs-code/' . urlencode((string)($child['code'] ?? '')) . '/amp'), ENT_QUOTES, 'UTF-8'); ?>" 
               class="btn btn-secondary btn-sm">
              View Details →
            </a>
          </div>
        </section>
      <?php endforeach; ?>
    </amp-accordion>
    
    <?php if (count($childCodes) > 10): ?>
    <div class="text-center mt-2">
      <a href="<?php echo htmlspecialchars(url('/trade/hs-codes/amp?parent_id=' . (int)$hsCode['id']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
        View All (<?php echo count($childCodes); ?>)
      </a>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  
  <!-- Related Companies -->
  <?php if (!empty($companies)): ?>
  <div class="mb-3">
    <h2 class="section-title">
      UAE Companies (<?php echo count($companies); ?>)
    </h2>
    
    <?php foreach ($companies as $company): ?>
      <a href="<?php echo htmlspecialchars(url('/company/' . urlencode($company['slug'] ?? '') . '/amp'), ENT_QUOTES, 'UTF-8'); ?>" class="company-card">
        <div class="company-logo">
          <?php echo strtoupper(substr($company['name'] ?? 'C', 0, 1)); ?>
        </div>
        <div class="company-info">
          <div class="company-name"><?php echo htmlspecialchars($company['name'] ?? 'Company', ENT_QUOTES); ?></div>
          <div class="company-location">
            <?php echo htmlspecialchars($company['city'] ?? 'UAE', ENT_QUOTES); ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  
  <!-- Related Categories -->
  <?php if (!empty($categories)): ?>
  <div class="mb-3">
    <h2 class="section-title">
      Related Business Categories
    </h2>
    
    <div class="card">
      <div style="display: flex; flex-wrap: wrap; gap: 4px;">
        <?php foreach ($categories as $cat): ?>
          <a href="<?php echo htmlspecialchars(url('/category/' . urlencode($cat['slug'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" class="category-tag">
            <?php echo htmlspecialchars($cat['name'] ?? 'Category', ENT_QUOTES); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <!-- Additional Information -->
  <div class="card mb-3" style="background: #f8f9fa;">
    <h3 style="font-size: 1rem; margin-bottom: 12px; font-weight: 600;">
      Additional Information
    </h3>
    <div style="font-size: 0.9rem; color: #666; line-height: 1.6;">
      <p style="margin-bottom: 8px;">
        HS codes are used to classify products in international trade for customs and tariff purposes. These codes help determine customs duties, taxes, and regulatory requirements in the UAE and GCC regions.
      </p>
      <p style="margin: 0;">
        رموز النظام المتناسق (HS) تُستخدم لتصنيف المنتجات في التجارة الدولية وتحديد التعريفات الجمركية والمتطلبات التنظيمية في دول الإمارات ودول مجلس التعاون الخليج.
      </p>
    </div>
  </div>

  <div class="context-nav context-nav-tight">
    <div class="context-nav-title">Continue with related AMP pages</div>
    <div class="context-nav-links">
      <a href="<?php echo url('/trade/hs-codes/amp'); ?>" class="context-nav-link">All HS Codes</a>
      <a href="<?php echo url('/listings/amp'); ?>" class="context-nav-link">Browse Companies</a>
      <a href="<?php echo url('/blog/amp'); ?>" class="context-nav-link">Read Trade Blog</a>
      <a href="<?php echo url('/contact/amp'); ?>" class="context-nav-link">Ask Support</a>
    </div>
  </div>
  
  <!-- Navigation -->
  <div class="card text-center">
    <a href="<?php echo htmlspecialchars(url('/trade/hs-codes/amp'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary" style="width: 100%; margin-bottom: 8px;">
      ← Back to Directory
    </a>
    <a href="<?php echo htmlspecialchars(url('/trade/hs-code/' . urlencode($safeCode)), ENT_QUOTES, 'UTF-8'); ?>" 
       style="color: #0f4ad8; font-size: 0.85rem; text-decoration: none;">
      View full version
    </a>
  </div>
  
</div>

<?php include __DIR__ . '/amp-footer.php'; ?>

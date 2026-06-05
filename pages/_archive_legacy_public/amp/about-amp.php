<?php
/**
 * AMP Page: About
 * Route: /about/amp
 * 
 * Mobile-optimized about page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$totalHsCodes = 0;
if (isset($conn) && $conn instanceof mysqli) {
  $countResult = $conn->query("SELECT COUNT(*) AS total FROM `" . DB::HS_CODES . "`");
  if ($countResult && ($countRow = $countResult->fetch_assoc())) {
    $totalHsCodes = (int)($countRow['total'] ?? 0);
  }
}

// Page meta
$pageTitle = 'About - UAE Business Directory';
$pageDescription = 'Learn about UAE Business Directory - connecting customers with verified businesses across the UAE';
$pageKeywords = 'about us, UAE business directory, company information, business platform';
$canonicalUrl = url('/about');
$pageUrl = url('/about/amp');

// Open Graph
$ogTitle = $pageTitle;
$ogDescription = $pageDescription;
$ogImage = getFullUrl('/assets/images/brand/logo.png');
$ogType = 'website';

// Twitter Card
$twitterCard = 'summary';
$twitterTitle = $pageTitle;
$twitterDescription = $pageDescription;
$twitterImage = $ogImage;

// Schema.org AboutPage
$schemaData = [
  '@context' => 'https://schema.org',
  '@type' => 'AboutPage',
  'name' => 'About UAE Business Directory',
  'description' => $pageDescription,
  'url' => $canonicalUrl,
  'mainEntity' => [
    '@type' => 'Organization',
    'name' => 'UAE Business Directory',
    'url' => getFullUrl('/'),
    'logo' => getFullUrl('/assets/images/brand/logo.png'),
    'description' => 'Leading business directory platform in the United Arab Emirates',
    'address' => [
      '@type' => 'PostalAddress',
      'addressLocality' => 'Dubai',
      'addressCountry' => 'AE'
    ]
  ]
];

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
      'name' => 'About',
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
$ampComponents = [];

include __DIR__ . '/amp-header.php';
?>
<div style="background:linear-gradient(120deg,#05243a 0%,#0b3a59 55%,#17608d 100%);color:#fff;border-radius:12px;padding:34px 18px;margin:0 16px 18px;">
  <div style="display:inline-block;background:rgba(255,255,255,0.15);padding:5px 12px;border-radius:999px;font-size:12px;letter-spacing:.07em;text-transform:uppercase;font-weight:600;">About HAIPULSE</div>
  <h1 style="margin:14px 0 10px;font-size:2rem;line-height:1.2;">Built To Make UAE Businesses Easier To Find, Compare, And Trust</h1>
  <p style="margin:0;color:rgba(255,255,255,0.92);">HAIPULSE helps customers discover trusted companies and gives businesses a reliable way to improve visibility and generate qualified leads.</p>
</div>

<div class="container">
  <div class="card" style="border-radius:12px;padding:22px 18px;">
    <h2 style="margin:0 0 10px;font-size:1.45rem;color:#1a2b3b;">Who We Are</h2>
    <p style="margin:0 0 10px;color:#4e5d6b;">HAIPULSE is a practical discovery platform focused on clear categories, location-aware browsing, and verified business information across the UAE.</p>
    <p style="margin:0;color:#4e5d6b;">From startups to established enterprises, our directory is designed to help buyers make faster decisions and help businesses get discovered by the right audience.</p>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0 18px;">
    <div class="card" style="margin:0;border-left:3px solid #0d6efd;"><div style="font-size:1.4rem;font-weight:700;color:#11263a;"><?php echo $totalHsCodes > 0 ? number_format($totalHsCodes) . '+' : '13,000+'; ?></div><div style="color:#637383;">Total HS Codes</div></div>
    <div class="card" style="margin:0;border-left:3px solid #0d6efd;"><div style="font-size:1.4rem;font-weight:700;color:#11263a;">50+</div><div style="color:#637383;">Business Categories</div></div>
    <div class="card" style="margin:0;border-left:3px solid #0d6efd;"><div style="font-size:1.4rem;font-weight:700;color:#11263a;">7</div><div style="color:#637383;">Emirates Covered</div></div>
    <div class="card" style="margin:0;border-left:3px solid #0d6efd;"><div style="font-size:1.4rem;font-weight:700;color:#11263a;">24/7</div><div style="color:#637383;">Platform Availability</div></div>
  </div>

  <div class="card" style="border-radius:12px;padding:22px 18px;">
    <h2 style="margin:0 0 14px;font-size:1.45rem;color:#1a2b3b;">Why Businesses Choose HAIPULSE</h2>
    <div style="display:grid;grid-template-columns:1fr;gap:10px;">
      <div style="border:1px solid #ecf1f6;border-radius:10px;padding:12px;"><strong>Verified Presence:</strong> Structured business profiles that increase buyer confidence.</div>
      <div style="border:1px solid #ecf1f6;border-radius:10px;padding:12px;"><strong>Local Discovery:</strong> Category and emirate-focused visibility for relevant demand.</div>
      <div style="border:1px solid #ecf1f6;border-radius:10px;padding:12px;"><strong>Growth-Oriented:</strong> Built to improve digital presence and lead quality.</div>
      <div style="border:1px solid #ecf1f6;border-radius:10px;padding:12px;"><strong>B2B and B2C Ready:</strong> Useful for consumers, professionals, and procurement teams.</div>
    </div>
  </div>

  <div class="card" style="border-radius:12px;padding:22px 18px;">
    <h2 style="margin:0 0 14px;font-size:1.45rem;color:#1a2b3b;">How The Platform Works</h2>
    <ol style="margin:0 0 0 18px;padding:0;color:#2b3c4e;">
      <li style="margin-bottom:8px;"><strong>Create Listing:</strong> Add company profile, category, and contact details.</li>
      <li style="margin-bottom:8px;"><strong>Improve Visibility:</strong> Appear in relevant local and category searches.</li>
      <li><strong>Generate Leads:</strong> Receive inquiries from customers actively searching for providers.</li>
    </ol>
  </div>

  <div style="text-align:center;background:#f5f9ff;border:1px solid #dce9ff;border-radius:12px;padding:20px 16px;margin-bottom:16px;">
    <h3 style="margin:0 0 8px;color:#1a2b3b;">Ready To Grow Your Business Presence?</h3>
    <p style="margin:0 0 14px;color:#5b6d7f;">Join HAIPULSE and get listed where businesses and customers search first.</p>
    <a href="<?php echo url('/add-business'); ?>" class="btn btn-primary" style="margin-right:8px;">Add Your Business</a>
    <a href="<?php echo url('/contact/amp'); ?>" class="btn btn-secondary">Contact Team</a>
  </div>

  <div class="context-nav context-nav-tight">
    <div class="context-nav-title">Explore HAIPULSE on AMP</div>
    <div class="context-nav-links">
      <a href="<?php echo url('/listings/amp'); ?>" class="context-nav-link">Browse Companies</a>
      <a href="<?php echo url('/trade/hs-codes/amp'); ?>" class="context-nav-link">HS Codes</a>
      <a href="<?php echo url('/blog/amp'); ?>" class="context-nav-link">Business Blog</a>
      <a href="<?php echo url('/contact/amp'); ?>" class="context-nav-link">Contact Support</a>
    </div>
  </div>

  <div style="text-align:center;padding:12px;background:#f8f9fa;border-radius:8px;margin-bottom:8px;">
    <a href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" style="color:#1e5fd8;text-decoration:none;font-weight:600;">View Full Website Version</a>
  </div>
</div>

<?php include __DIR__ . '/amp-footer.php'; ?>


<?php
/**
 * Page: Premium Advertising
 * Route: /ads
 * Description: Information about advertising opportunities and packages
 */

$pageTitle = 'Premium Advertising - UAE Business Directory';
$pageDescription = 'Promote your business with our premium advertising packages and reach thousands of potential customers';
$bodyClass = 'page-ads';

require_once __DIR__ . '/../includes/helpers.php';

$canonicalUrl = getFullUrl('/ads');
$pageKeywords = implode(', ', [
  'UAE business advertising',
  'Dubai directory advertising',
  'featured listings UAE',
  'banner ads UAE business directory',
  'sponsored business content UAE'
]);
$ogTitle = $pageTitle;
$ogDescription = $pageDescription;
$ogImage = getFullUrl('/assets/images/brand/logo.png');
$contentType = 'website';
$articleSection = 'Advertising';

$adsSchema = [
  '@context' => 'https://schema.org',
  '@type' => 'WebPage',
  'name' => $pageTitle,
  'description' => $pageDescription,
  'url' => $canonicalUrl,
  'inLanguage' => 'en-AE'
];

$breadcrumbs = [
  ['name' => 'Home', 'url' => getFullUrl('/')],
  ['name' => 'Advertising', 'url' => $canonicalUrl]
];

$jsonLdSchema = '<script type="application/ld+json">' . json_encode($adsSchema, JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/PublicAds.php';

$publicAdsModel = new PublicAds($conn);
$adsPageShowcaseAds = $publicAdsModel->getAdsForSlot('ads_page', [
  'page_type' => 'ads',
  'tags' => ['advertising', 'software', 'saas', 'promotion']
], 3);
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main class="main-content">
    <!-- Hero Section -->
    <div class="ads-hero">
      <div class="container-narrow">
        <h1 class="ads-hero__title">Grow Your Business</h1>
        <p class="ads-hero__text">
          Reach thousands of potential customers actively searching for services in UAE
        </p>
        <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES); ?>" class="btn-ui ads-hero__cta">Get Started Today</a>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="container-narrow ads-content">
      <?php
        $publicAds = $adsPageShowcaseAds;
        $publicAdSlot = 'inline';
        $publicAdHeading = 'Live software ad showcase';
        include __DIR__ . '/../includes/partials/public-ad-slot.php';
      ?>

      <div class="ads-stats">
        <div class="ads-stat">
          <div class="ads-stat__value">50K+</div>
          <div class="muted">Monthly Visitors</div>
        </div>
        <div class="ads-stat">
          <div class="ads-stat__value">10K+</div>
          <div class="muted">Listed Businesses</div>
        </div>
        <div class="ads-stat">
          <div class="ads-stat__value">95%</div>
          <div class="muted">Customer Satisfaction</div>
        </div>
      </div>

      <!-- Advertising Options -->
      <h2 class="ads-section-title">Advertising Options</h2>
      
      <div class="ads-plan-grid">
        <div class="card-ui ads-plan-card">
          <div class="ads-plan-icon">🎯</div>
          <h3>Featured Listings</h3>
          <p class="muted ads-plan-desc">Stand out with featured placement at the top of category pages and search results.</p>
          <ul class="ads-check-list">
            <li>✓ Priority placement</li>
            <li>✓ Enhanced visibility</li>
            <li>✓ Featured badge</li>
            <li>✓ 3x more views</li>
          </ul>
          <div class="ads-price">AED 299/month</div>
          <a href="<?php echo htmlspecialchars(url('/contact?subject=advertising&ad_plan=featured-listings&source=ads-page'), ENT_QUOTES); ?>" class="btn-ui btn-primary-ui ads-full-cta">Learn More</a>
        </div>

        <div class="card-ui ads-plan-card">
          <div class="ads-plan-icon">📱</div>
          <h3>Banner Ads</h3>
          <p class="muted ads-plan-desc">Display banner advertisements across high-traffic pages throughout the directory.</p>
          <ul class="ads-check-list">
            <li>✓ Homepage placement</li>
            <li>✓ Category pages</li>
            <li>✓ Custom design support</li>
            <li>✓ Performance tracking</li>
          </ul>
          <div class="ads-price">From AED 499/month</div>
          <a href="<?php echo htmlspecialchars(url('/contact?subject=advertising&ad_plan=banner-ads&source=ads-page'), ENT_QUOTES); ?>" class="btn-ui btn-primary-ui ads-full-cta">Learn More</a>
        </div>

        <div class="card-ui ads-plan-card">
          <div class="ads-plan-icon">💼</div>
          <h3>Sponsored Content</h3>
          <p class="muted ads-plan-desc">Share your story with sponsored blog posts and featured articles about your business.</p>
          <ul class="ads-check-list">
            <li>✓ Professional copywriting</li>
            <li>✓ SEO optimization</li>
            <li>✓ Social media promotion</li>
            <li>✓ Long-term visibility</li>
          </ul>
          <div class="ads-price">Custom Pricing</div>
          <a href="<?php echo htmlspecialchars(url('/contact?subject=advertising&ad_plan=sponsored-content&source=ads-page'), ENT_QUOTES); ?>" class="btn-ui btn-primary-ui ads-full-cta">Learn More</a>
        </div>
      </div>

      <!-- Why Advertise Section -->
      <div class="card-ui ads-why-card">
        <h2 class="ads-section-title">Why Advertise With Us?</h2>
        <div class="ads-why-grid">
          <div class="ads-why-item">
            <h4>🎯 Targeted Audience</h4>
            <p class="muted">Reach customers actively searching for businesses in your category and location.</p>
          </div>
          <div class="ads-why-item">
            <h4>📊 Analytics Dashboard</h4>
            <p class="muted">Track your ad performance with detailed analytics and insights.</p>
          </div>
          <div class="ads-why-item">
            <h4>💪 Flexible Packages</h4>
            <p class="muted">Choose from monthly, quarterly, or annual packages that fit your budget.</p>
          </div>
          <div class="ads-why-item">
            <h4>🚀 Quick Setup</h4>
            <p class="muted">Get your ads live within 24-48 hours with our dedicated support team.</p>
          </div>
        </div>
      </div>

      <!-- CTA Section -->
      <div class="ads-cta">
        <h2>Ready to Get Started?</h2>
        <p>Contact our advertising team today to discuss custom packages tailored to your business needs.</p>
        <div class="ads-cta-actions">
          <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES); ?>" class="btn-ui">Contact Sales</a>
        </div>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>

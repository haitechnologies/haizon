<?php
/**
 * Page: Safety Tips
 * Route: /tips
 * Description: Safety tips for users engaging with businesses
 */

$pageTitle = 'Safety Tips - UAE Business Directory';
$pageDescription = 'Important safety guidelines when engaging with businesses online';
$bodyClass = 'page-tips';

require_once __DIR__ . '/../includes/helpers.php';

$canonicalUrl = getFullUrl('/tips');
$pageKeywords = implode(', ', [
  'UAE business safety tips',
  'avoid business scams UAE',
  'safe online business transactions',
  'verify company credentials UAE',
  'business directory safety guide'
]);
$ogTitle = $pageTitle;
$ogDescription = $pageDescription;
$ogImage = getFullUrl('/assets/images/brand/logo.png');
$contentType = 'article';
$articleSection = 'Safety';

$tipsSchema = [
  '@context' => 'https://schema.org',
  '@type' => 'WebPage',
  'name' => $pageTitle,
  'description' => $pageDescription,
  'url' => $canonicalUrl,
  'inLanguage' => 'en-AE'
];

$breadcrumbs = [
  ['name' => 'Home', 'url' => getFullUrl('/')],
  ['name' => 'Safety Tips', 'url' => $canonicalUrl]
];

$jsonLdSchema = '<script type="application/ld+json">' . json_encode($tipsSchema, JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main class="main-content">
    <div class="container-narrow tips-shell">
      <h1 class="tips-title">Safety Tips</h1>
      <p class="muted tips-subtitle">
        Follow these guidelines to stay safe when engaging with businesses online.
      </p>

      <div class="card-ui tips-card">
        <h2 class="tips-card-title">🔍 Research Before You Buy</h2>
        <ul class="tips-list">
          <li>Check business reviews and ratings from multiple sources</li>
          <li>Verify business registration and licenses</li>
          <li>Look for contact information and physical address</li>
          <li>Search for the company name online to check reputation</li>
        </ul>
      </div>

      <div class="card-ui tips-card">
        <h2 class="tips-card-title">💳 Secure Payment Methods</h2>
        <ul class="tips-list">
          <li>Use secure payment methods with buyer protection (credit cards, PayPal)</li>
          <li>Avoid wire transfers or cash payments to unknown parties</li>
          <li>Never share your full credit card details via email or chat</li>
          <li>Keep records of all transactions and receipts</li>
          <li>Be wary of requests for upfront payment before service delivery</li>
        </ul>
      </div>

      <div class="card-ui tips-card">
        <h2 class="tips-card-title">🚨 Red Flags to Watch For</h2>
        <ul class="tips-list">
          <li><strong>Deals that seem too good to be true</strong> - Extremely low prices may indicate scams</li>
          <li><strong>Pressure to act quickly</strong> - Legitimate businesses don't rush you</li>
          <li><strong>Poor communication</strong> - Unprofessional emails, grammar errors, or evasive answers</li>
          <li><strong>Requests for personal information</strong> - Be cautious about sharing ID or sensitive data</li>
          <li><strong>No verifiable contact details</strong> - Businesses should have legitimate contact methods</li>
        </ul>
      </div>

      <div class="card-ui tips-card">
        <h2 class="tips-card-title">📋 Verify Business Credentials</h2>
        <ul class="tips-list">
          <li>Check if the business has proper trade licenses</li>
          <li>Verify registration with Dubai Economy or relevant authorities</li>
          <li>Look for industry certifications and memberships</li>
          <li>Confirm physical office location when possible</li>
        </ul>
      </div>

      <div class="card-ui tips-card">
        <h2 class="tips-card-title">🛡️ Protect Your Privacy</h2>
        <ul class="tips-list">
          <li>Only provide necessary personal information</li>
          <li>Use strong, unique passwords for business accounts</li>
          <li>Enable two-factor authentication where available</li>
          <li>Be cautious about downloading files or clicking links from unknown businesses</li>
          <li>Review privacy policies before sharing data</li>
        </ul>
      </div>

      <div class="card-ui tips-card">
        <h2 class="tips-card-title">📞 Meeting in Person</h2>
        <ul class="tips-list">
          <li>Meet in public places during business hours</li>
          <li>Bring a friend or family member if possible</li>
          <li>Tell someone where you're going and when you'll return</li>
          <li>Trust your instincts - if something feels wrong, leave</li>
        </ul>
      </div>

      <div class="tips-warning">
        <h3 class="tips-warning-title">⚠️ Report Suspicious Activity</h3>
        <p class="tips-warning-copy">
          If you encounter fraudulent activity or suspicious behavior, report it immediately to 
          <a href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES); ?>" class="tips-warning-link">our support team</a> 
          and to UAE authorities at <strong>Dubai Police: 901</strong> or <strong>Abu Dhabi Police: 999</strong>.
        </p>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>

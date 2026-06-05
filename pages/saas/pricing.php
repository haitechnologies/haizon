<?php
/**
 * Page: Pricing
 * Route: /pricing
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/globals.php';
require_once __DIR__ . '/../../includes/helpers.php';

$saasCatalog = require __DIR__ . '/../../config/saas_catalog.php';
$systems     = $saasCatalog['systems'] ?? [];
$plans       = $saasCatalog['plans'] ?? [];

$pageTitle       = 'Pricing — HAIPULSE Business Software for UAE Teams';
$pageDescription = 'Compare HAIPULSE pricing plans for CRM, HR, Accounting, and Shipping. Choose a rollout package for your UAE team and request activation.';
$pageKeywords    = 'HAIPULSE pricing, business software pricing UAE, CRM pricing UAE, HR accounting pricing UAE';
$bodyClass       = 'page-saas-pricing';
$canonicalUrl    = getFullUrl('/pricing');

$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'Pricing', 'url' => $canonicalUrl],
];

$jsonLdSchema  = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebPage',
    'name' => $pageTitle, 'description' => $pageDescription, 'url' => $canonicalUrl, 'inLanguage' => 'en-AE',
], JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../../includes/saas/header.php';

// Suite-to-plan inclusion matrix
$suiteMatrix = [
    'CRM'        => ['starter' => true,  'growth' => true,  'enterprise' => true],
    'Accounting' => ['starter' => true,  'growth' => true,  'enterprise' => true],
    'HR'         => ['starter' => false, 'growth' => true,  'enterprise' => true],
    'Shipping'   => ['starter' => false, 'growth' => false, 'enterprise' => true],
];

$faqs = [
    ['q' => 'Are prices per user or per organization?',
     'a' => 'Prices are per organization. Each plan includes a fixed number of admin users as listed. Additional seats can be discussed on the Growth and Enterprise plans.'],
    ['q' => 'Can I start with one suite and add more later?',
     'a' => 'Yes. You can launch a single suite and upgrade your plan at any time. Data remains connected across suites as you expand.'],
    ['q' => 'Is there a setup or onboarding fee?',
     'a' => 'Standard and implementation onboarding is included in the plan. Custom rollouts, data migrations, and dedicated workshops are available at the Enterprise level.'],
    ['q' => 'What does the free trial or demo look like?',
     'a' => 'We offer a guided walkthrough of the platform with your data setup in mind. Contact the team to schedule a hands-on session before committing to a plan.'],
    ['q' => 'Can I cancel or change plans?',
     'a' => 'Annual plans are billed upfront. Monthly plans can be cancelled at the end of the billing period. Plan changes take effect on your next billing cycle.'],
];
?>

<main>
  <!-- HERO -->
  <section class="saas-hero">
    <div class="saas-container">
      <span class="saas-eyebrow">Pricing</span>
      <h1>Pricing for teams rolling out HAIPULSE as their operating platform.</h1>
      <p class="saas-hero__lead">
        Start with one suite or deploy the full stack. Every package includes workspace setup,
        access controls, and a guided handoff into your live operation.
      </p>
    </div>
  </section>

  <!-- PRICING CARDS -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-pricing-grid">
        <?php foreach ($plans as $planKey => $plan): ?>
          <article id="plan-<?php echo htmlspecialchars($planKey, ENT_QUOTES, 'UTF-8'); ?>"
                   class="saas-card saas-pricing-card<?php echo $planKey === 'growth' ? ' saas-pricing-card--featured' : ''; ?>"
                   style="position:relative;">
            <?php if ($planKey === 'growth'): ?>
              <div class="saas-pricing-badge">Most popular</div>
            <?php endif; ?>

            <span class="saas-pricing-plan-tag"><?php echo htmlspecialchars($plan['label'], ENT_QUOTES, 'UTF-8'); ?></span>

            <div class="saas-pricing-price">
              <?php if (!empty($plan['price_monthly_aed'])): ?>
                <strong>AED <?php echo number_format((float)$plan['price_monthly_aed'], 0); ?></strong>
                <span>/ month</span>
              <?php else: ?>
                <strong>Custom</strong>
                <span>annual &amp; rollout-based</span>
              <?php endif; ?>
            </div>

            <?php if (!empty($plan['price_annual_aed'])): ?>
              <p class="saas-pricing-annual">
                AED <?php echo number_format((float)$plan['price_annual_aed'], 0); ?> billed annually
              </p>
            <?php endif; ?>

            <p class="saas-pricing-best-for saas-muted"><?php echo htmlspecialchars($plan['best_for'], ENT_QUOTES, 'UTF-8'); ?></p>

            <ul class="saas-check-list saas-pricing-checks">
              <?php foreach (($plan['includes'] ?? []) as $feature): ?>
                <li><?php echo htmlspecialchars($feature, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>

            <a href="<?php echo htmlspecialchars(url('/contact?plan=' . rawurlencode($planKey) . '&source=pricing'), ENT_QUOTES, 'UTF-8'); ?>"
               class="saas-btn <?php echo $planKey === 'growth' ? 'saas-btn-primary' : 'saas-btn-ghost'; ?> saas-btn-block">
              <?php echo $planKey === 'enterprise' ? 'Contact us' : 'Get started'; ?>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- SUITE COMPARISON TABLE -->
  <section class="saas-section">
    <div class="saas-container-narrow">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">What's included</span>
        <h2>Suite coverage by plan</h2>
        <p class="saas-muted">See which operational suites are included in each pricing tier.</p>
      </div>
      <div class="table-responsive">
        <table class="saas-compare-table" aria-label="Suite coverage by plan">
          <thead>
            <tr>
              <th>Suite</th>
              <?php foreach ($plans as $p): ?>
                <th><?php echo htmlspecialchars($p['label'], ENT_QUOTES, 'UTF-8'); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($suiteMatrix as $suiteName => $coverage): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($suiteName, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <?php foreach (array_keys($plans) as $planKey): ?>
                  <td>
                    <?php if ($coverage[$planKey] ?? false): ?>
                      <span class="check-yes" aria-label="Included">&#10003;</span>
                    <?php else: ?>
                      <span class="check-no" aria-label="Not included">&ndash;</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">FAQ</span>
        <h2>Common questions</h2>
      </div>
      <div class="saas-faq">
        <?php foreach ($faqs as $faq): ?>
          <details class="saas-faq-item">
            <summary><?php echo htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8'); ?></summary>
            <p><?php echo htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8'); ?></p>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="saas-cta-section">
    <div class="saas-container">
      <h2>Need a walkthrough before choosing a plan?</h2>
      <p>We'll map your operation to the right suites, seat count, and onboarding schedule before you commit.</p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/contact?source=pricing-cta'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Request a consultation</a>
        <a href="<?php echo htmlspecialchars(url('/all-in-one'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Explore the full platform</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../includes/saas/footer.php'; ?>

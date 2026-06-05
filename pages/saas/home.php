<?php
/**
 * Page: HAIPULSE SaaS Homepage
 * Route: /
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/globals.php';
require_once __DIR__ . '/../../includes/helpers.php';

$saasCatalog       = require __DIR__ . '/../../config/saas_catalog.php';
$systems           = $saasCatalog['systems'] ?? [];
$plans             = $saasCatalog['plans'] ?? [];
$platformHighlights = $saasCatalog['platform']['highlights'] ?? [];

$pageTitle       = 'HAIPULSE — Business Software Platform for UAE Teams';
$pageDescription = 'Run CRM, HR, Accounting, and Shipping from one connected admin workspace. Built for UAE operations teams.';
$pageKeywords    = 'business software UAE, CRM UAE, accounting software UAE, HR software UAE, ERP UAE';
$bodyClass       = 'page-saas-home';
$canonicalUrl    = getFullUrl('/');

$jsonLdSchema = json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'WebSite',
    'name'        => 'HAIPULSE',
    'description' => $pageDescription,
    'url'         => $canonicalUrl,
    'inLanguage'  => 'en-AE',
], JSON_UNESCAPED_SLASHES);
$jsonLdSchema = '<script type="application/ld+json">' . $jsonLdSchema . '</script>';

include __DIR__ . '/../../includes/saas/header.php';

$productPages = [
    'crm'        => ['label' => 'CRM',        'icon' => 'ph-users-three',            'color' => '#3b6fd4'],
    'hr'         => ['label' => 'HR',          'icon' => 'ph-identification-card',    'color' => '#16a34a'],
    'accounting' => ['label' => 'Accounting',  'icon' => 'ph-currency-circle-dollar', 'color' => '#d97706'],
    'shipping'   => ['label' => 'Shipping',    'icon' => 'ph-package',                'color' => '#7c3aed'],
];
?>

<main>
  <!-- HERO ------------------------------------------------------------------>
  <section class="saas-hero">
    <div class="saas-container">
      <span class="saas-eyebrow">One platform. Four suites.</span>
      <h1>Operate your entire business from&nbsp;one workspace.</h1>
      <p class="saas-hero__lead">
        HAIPULSE brings CRM, HR, Accounting, and Shipping into a single admin platform
        built for UAE operations. Start with one department. Expand as you grow.
      </p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">View pricing</a>
        <a href="<?php echo htmlspecialchars(url('/contact?source=hero'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Book a demo</a>
      </div>
      <div class="saas-stats">
        <div class="saas-stat"><strong>4</strong><span>core suites</span></div>
        <div class="saas-stat"><strong>1</strong><span>shared workspace</span></div>
        <div class="saas-stat"><strong>Multi-org</strong><span>organization switching</span></div>
        <div class="saas-stat"><strong>Role-based</strong><span>access control</span></div>
      </div>
    </div>
  </section>

  <!-- PLATFORM HIGHLIGHTS STRIP ------------------------------------------->
  <section class="saas-highlights-strip" aria-label="Platform highlights">
    <div class="saas-container">
      <ul class="saas-highlights-list" role="list">
        <?php foreach ($platformHighlights as $h): ?>
          <li><?php echo htmlspecialchars($h, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- PRODUCT SUITE CARDS -------------------------------------------------->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">Product suites</span>
        <h2>Choose the modules your team actually needs</h2>
        <p class="saas-muted">Every suite maps directly to operational workflows present in HAIPULSE today.</p>
      </div>
      <div class="saas-suite-grid">
        <?php foreach ($systems as $key => $system): ?>
          <?php $page = $productPages[$key] ?? null; ?>
          <article class="saas-card saas-suite-card saas-card-lift">
            <div class="saas-suite-icon" style="background:<?php echo htmlspecialchars(($page['color'] ?? '#3b6fd4') . '1a', ENT_QUOTES, 'UTF-8'); ?>; color:<?php echo htmlspecialchars($page['color'] ?? '#3b6fd4', ENT_QUOTES, 'UTF-8'); ?>;">
              <i class="ph <?php echo htmlspecialchars($system['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            </div>
            <div>
              <h3><?php echo htmlspecialchars($system['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="saas-muted" style="font-size:0.9rem; margin-bottom:12px;">
                <?php echo htmlspecialchars($system['summary'], ENT_QUOTES, 'UTF-8'); ?>
              </p>
              <ul class="saas-check-list">
                <?php foreach (($system['modules'] ?? []) as $module): ?>
                  <li><?php echo htmlspecialchars($module, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="saas-suite-footer">
              <a href="<?php echo htmlspecialchars(url('/' . $key), ENT_QUOTES, 'UTF-8'); ?>"
                 class="saas-btn saas-btn-ghost saas-btn-sm saas-btn-block">
                Explore <?php echo htmlspecialchars($system['label'], ENT_QUOTES, 'UTF-8'); ?> &rarr;
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ROLLOUT STEPS -------------------------------------------------------->
  <section class="saas-section">
    <div class="saas-container-narrow">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">How it works</span>
        <h2>Roll out in practical stages</h2>
        <p class="saas-muted">Launch one workflow, then connect teams as operations mature.</p>
      </div>
      <div class="saas-steps">
        <article class="saas-card saas-step-card">
          <div class="saas-step-pill">1</div>
          <h3>Launch one department</h3>
          <p>Start with CRM or Accounting for the most immediate operational lift and measurable ROI.</p>
        </article>
        <article class="saas-card saas-step-card">
          <div class="saas-step-pill">2</div>
          <h3>Connect people and process</h3>
          <p>Add HR to formalize users, attendance, leave, payroll, and approval workflows.</p>
        </article>
        <article class="saas-card saas-step-card">
          <div class="saas-step-pill">3</div>
          <h3>Extend into operations</h3>
          <p>Bring Shipping, stock management, and commercial logistics into the same admin layer.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- PRICING PREVIEW ----------------------------------------------------->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">Pricing</span>
        <h2>A package that matches your rollout</h2>
        <p class="saas-muted">Transparent SaaS packages for teams moving off spreadsheets and fragmented tools.</p>
      </div>
      <div class="saas-pricing-grid">
        <?php foreach ($plans as $planKey => $plan): ?>
          <article class="saas-card saas-pricing-card<?php echo $planKey === 'growth' ? ' saas-pricing-card--featured' : ''; ?>" style="position:relative;">
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
                <span>rollout-based</span>
              <?php endif; ?>
            </div>
            <p class="saas-pricing-best-for saas-muted"><?php echo htmlspecialchars($plan['best_for'], ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="<?php echo htmlspecialchars(url('/pricing#plan-' . rawurlencode($planKey)), ENT_QUOTES, 'UTF-8'); ?>"
               class="saas-btn <?php echo $planKey === 'growth' ? 'saas-btn-primary' : 'saas-btn-ghost'; ?> saas-btn-block">
              See full package
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA ------------------------------------------------------------------>
  <section class="saas-cta-section">
    <div class="saas-container">
      <span class="saas-eyebrow" style="color:rgba(255,255,255,0.6);">Get started</span>
      <h2>Ready to run your business on one platform?</h2>
      <p>Talk to the team and map your operation to the right suites, admin seats, and onboarding plan.</p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">See pricing</a>
        <a href="<?php echo htmlspecialchars(url('/contact?source=cta'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Request a consultation</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../includes/saas/footer.php'; ?>

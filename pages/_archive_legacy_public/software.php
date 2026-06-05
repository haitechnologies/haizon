<?php
/**
 * Page: Business Software Platform
 * Route: /software
 * Description: Public SaaS landing page for HAIPULSE backend modules.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';

$saasCatalog = require __DIR__ . '/../config/saas_catalog.php';
$systems = $saasCatalog['systems'] ?? [];
$plans = $saasCatalog['plans'] ?? [];
$platformHighlights = $saasCatalog['platform']['highlights'] ?? [];

$pageTitle = 'HAIPULSE Software - CRM, Accounting, HR and Shipping in One Platform';
$pageDescription = 'Discover the HAIPULSE business software platform for UAE teams. Run CRM, accounting, HR, and shipping operations from one admin workspace.';
$pageKeywords = 'business software UAE, CRM UAE, accounting software UAE, HR software UAE, shipping operations software';
$bodyClass = 'page-software';

$canonicalUrl = getFullUrl('/software');
$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'Software', 'url' => $canonicalUrl],
];

$softwareSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $pageTitle,
    'description' => $pageDescription,
    'url' => $canonicalUrl,
    'inLanguage' => 'en-AE',
];

$jsonLdSchema = '<script type="application/ld+json">' . json_encode($softwareSchema, JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../includes/layout/header.php';
?>

<main class="main-content">
  <section class="software-hero">
    <div class="container-narrow software-hero__inner">
      <div class="software-hero__content">
        <span class="software-eyebrow">HAIPULSE business software</span>
        <h1>Operate CRM, accounting, HR, and shipping from one workspace.</h1>
        <p>
          HAIPULSE turns the backend you already run into a customer-facing SaaS platform for sales, finance,
          workforce, and operations teams. Launch one department first, then expand into a connected suite.
        </p>
        <div class="software-hero__actions">
          <a href="<?php echo htmlspecialchars(url('/software-pricing'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">View SaaS pricing</a>
          <a href="<?php echo htmlspecialchars(url('/contact?subject=software-sales&source=software-hero'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui software-btn-secondary">Book a demo</a>
        </div>
      </div>

      <div class="card-ui software-hero__panel">
        <div class="software-stat-grid">
          <div class="software-stat">
            <strong><?php echo count($systems); ?></strong>
            <span>core suites</span>
          </div>
          <div class="software-stat">
            <strong>1</strong>
            <span>shared admin workspace</span>
          </div>
          <div class="software-stat">
            <strong>Multi-org</strong>
            <span>organization switching</span>
          </div>
          <div class="software-stat">
            <strong>Role-based</strong>
            <span>access control built in</span>
          </div>
        </div>

        <div class="software-live-list">
          <div class="software-live-list__title">Platform coverage</div>
          <ul>
            <?php foreach ($platformHighlights as $highlight): ?>
              <li><?php echo htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <section class="container-narrow software-section">
    <div class="section-head">
      <h2>Choose the systems your team actually needs</h2>
      <p class="muted">Every suite maps directly to backend modules already present in HAIPULSE.</p>
    </div>

    <div class="software-suite-grid">
      <?php foreach ($systems as $systemKey => $system): ?>
        <article class="card-ui software-suite-card">
          <div class="software-suite-card__icon">
            <i class="ph <?php echo htmlspecialchars($system['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
          </div>
          <div class="software-suite-card__body">
            <h3><?php echo htmlspecialchars($system['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="muted"><?php echo htmlspecialchars($system['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
            <ul class="software-check-list">
              <?php foreach (($system['modules'] ?? []) as $module): ?>
                <li><?php echo htmlspecialchars($module, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="software-suite-card__actions">
            <a href="<?php echo htmlspecialchars(url('/contact?subject=software-sales&suite=' . rawurlencode($systemKey) . '&source=software-suite'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui software-btn-secondary w-100">Talk to sales</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="container-narrow software-section">
    <div class="section-head">
      <h2>Roll out the platform in practical stages</h2>
      <p class="muted">Start with one workflow, then add connected teams as the operation matures.</p>
    </div>

    <div class="software-roadmap">
      <article class="card-ui software-roadmap__step">
        <span class="software-step-pill">Step 1</span>
        <h3>Launch one department</h3>
        <p class="muted">Start with CRM or Accounting for the most immediate operational lift.</p>
      </article>
      <article class="card-ui software-roadmap__step">
        <span class="software-step-pill">Step 2</span>
        <h3>Connect people and process</h3>
        <p class="muted">Add HR and permissions to formalize users, policies, and approvals.</p>
      </article>
      <article class="card-ui software-roadmap__step">
        <span class="software-step-pill">Step 3</span>
        <h3>Extend into operations</h3>
        <p class="muted">Bring shipping, stock, and commercial workflows into the same admin layer.</p>
      </article>
    </div>
  </section>

  <section class="container-narrow software-section">
    <div class="section-head">
      <h2>Start with a pricing model that matches your rollout</h2>
      <p class="muted">Transparent SaaS packages for teams moving from spreadsheets and fragmented tools.</p>
    </div>

    <div class="software-plan-preview">
      <?php foreach ($plans as $planKey => $plan): ?>
        <article class="card-ui software-plan-preview__card<?php echo $planKey === 'growth' ? ' software-plan-preview__card--featured' : ''; ?>">
          <span class="software-plan-preview__label"><?php echo htmlspecialchars($plan['label'], ENT_QUOTES, 'UTF-8'); ?></span>
          <div class="software-plan-preview__price">
            <?php if (!empty($plan['price_monthly_aed'])): ?>
              AED <?php echo number_format((float)$plan['price_monthly_aed'], 0); ?><small>/month</small>
            <?php else: ?>
              Custom
            <?php endif; ?>
          </div>
          <p class="muted"><?php echo htmlspecialchars($plan['best_for'], ENT_QUOTES, 'UTF-8'); ?></p>
          <a href="<?php echo htmlspecialchars(url('/software-pricing#plan-' . rawurlencode($planKey)), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui <?php echo $planKey === 'growth' ? 'btn-primary-ui' : 'software-btn-secondary'; ?> w-100">See package</a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="container-narrow software-cta card-ui">
    <div>
      <span class="software-eyebrow">Implementation support</span>
      <h2>Need a walkthrough of the modules for your business?</h2>
      <p class="muted mb-0">We can map your operation to the right suites, admin seats, and onboarding plan before rollout.</p>
    </div>
    <div class="software-cta__actions">
      <a href="<?php echo htmlspecialchars(url('/software-pricing'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">See pricing</a>
      <a href="<?php echo htmlspecialchars(url('/contact?subject=software-sales&source=software-cta'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui software-btn-secondary">Request consultation</a>
    </div>
  </section>
</main>

<style>
  .page-software .software-hero {
    padding: 36px 12px 18px;
  }

  .page-software .software-hero__inner {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.95fr);
    gap: 24px;
    align-items: stretch;
  }

  .page-software .software-hero__content,
  .page-software .software-hero__panel,
  .page-software .software-cta {
    border-radius: 24px;
  }

  .page-software .software-hero__content {
    background: linear-gradient(145deg, #0f172a 0%, #1d4ed8 48%, #0ea5e9 100%);
    color: #fff;
    padding: 36px;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.16);
  }

  .page-software .software-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-weight: 700;
    opacity: 0.78;
    margin-bottom: 14px;
  }

  .page-software .software-hero h1 {
    font-size: clamp(2rem, 4vw, 3.4rem);
    line-height: 1.05;
    margin-bottom: 16px;
  }

  .page-software .software-hero p {
    font-size: 1.05rem;
    max-width: 60ch;
    color: rgba(255, 255, 255, 0.88);
  }

  .page-software .software-hero__actions,
  .page-software .software-cta__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
  }

  .page-software .software-btn-secondary {
    background: #fff;
    color: #0f172a;
    border: 1px solid rgba(15, 23, 42, 0.12);
  }

  .page-software .software-btn-secondary:hover {
    color: #0f172a;
    transform: translateY(-1px);
  }

  .page-software .software-hero__panel {
    padding: 28px;
    background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
    border: 1px solid rgba(14, 165, 233, 0.14);
  }

  .page-software .software-stat-grid,
  .page-software .software-suite-grid,
  .page-software .software-roadmap,
  .page-software .software-plan-preview {
    display: grid;
    gap: 16px;
  }

  .page-software .software-stat-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-bottom: 20px;
  }

  .page-software .software-stat {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 18px;
    padding: 16px;
  }

  .page-software .software-stat strong,
  .page-software .software-plan-preview__price {
    display: block;
    font-size: 1.4rem;
    color: #0f172a;
  }

  .page-software .software-stat span,
  .page-software .software-live-list li,
  .page-software .software-check-list li {
    color: #475569;
  }

  .page-software .software-live-list__title {
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 10px;
  }

  .page-software .software-live-list ul,
  .page-software .software-check-list {
    margin: 0;
    padding-left: 18px;
  }

  .page-software .software-section {
    margin-top: 36px;
  }

  .page-software .software-suite-grid,
  .page-software .software-plan-preview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .page-software .software-suite-card,
  .page-software .software-plan-preview__card,
  .page-software .software-roadmap__step {
    padding: 24px;
    height: 100%;
  }

  .page-software .software-suite-card {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }

  .page-software .software-suite-card__icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
    color: #0f172a;
  }

  .page-software .software-suite-card__body h3,
  .page-software .software-roadmap__step h3 {
    margin-bottom: 10px;
    color: #0f172a;
  }

  .page-software .software-roadmap {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .page-software .software-step-pill,
  .page-software .software-plan-preview__label {
    display: inline-flex;
    padding: 6px 10px;
    border-radius: 999px;
    background: #e0f2fe;
    color: #0f172a;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 12px;
  }

  .page-software .software-plan-preview__card--featured {
    border: 1px solid #0ea5e9;
    box-shadow: 0 20px 36px rgba(14, 165, 233, 0.16);
  }

  .page-software .software-plan-preview__price small {
    font-size: 0.88rem;
    margin-left: 6px;
    color: #64748b;
  }

  .page-software .software-cta {
    margin: 36px auto 0;
    padding: 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
    border: 1px solid rgba(249, 115, 22, 0.14);
  }

  @media (max-width: 991.98px) {
    .page-software .software-hero__inner,
    .page-software .software-suite-grid,
    .page-software .software-roadmap,
    .page-software .software-plan-preview {
      grid-template-columns: 1fr;
    }

    .page-software .software-cta {
      flex-direction: column;
      align-items: flex-start;
    }
  }

  @media (max-width: 575.98px) {
    .page-software .software-hero {
      padding-left: 0;
      padding-right: 0;
    }

    .page-software .software-hero__content,
    .page-software .software-hero__panel,
    .page-software .software-suite-card,
    .page-software .software-plan-preview__card,
    .page-software .software-roadmap__step,
    .page-software .software-cta {
      padding: 20px;
      border-radius: 20px;
    }
  }
</style>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
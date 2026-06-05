<?php
/**
 * Page: HAIPULSE Software Pricing
 * Route: /software-pricing
 * Description: Public pricing page for HAIPULSE SaaS suites.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';

$saasCatalog = require __DIR__ . '/../config/saas_catalog.php';
$systems = $saasCatalog['systems'] ?? [];
$plans = $saasCatalog['plans'] ?? [];

$pageTitle = 'HAIPULSE Software Pricing - SaaS Plans for CRM, Accounting, HR and Shipping';
$pageDescription = 'Compare HAIPULSE software pricing plans for CRM, accounting, HR, and shipping operations. Choose a rollout package and request activation.';
$pageKeywords = 'software pricing UAE, CRM pricing UAE, accounting SaaS pricing, HR software pricing, ERP pricing UAE';
$bodyClass = 'page-software-pricing page-pricing';

$canonicalUrl = getFullUrl('/software-pricing');
$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'Software', 'url' => getFullUrl('/software')],
    ['name' => 'Software Pricing', 'url' => $canonicalUrl],
];

$pricingSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $pageTitle,
    'description' => $pageDescription,
    'url' => $canonicalUrl,
    'inLanguage' => 'en-AE',
];

$jsonLdSchema = '<script type="application/ld+json">' . json_encode($pricingSchema, JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../includes/layout/header.php';
?>

<main class="main-content">
  <section class="pricing-hero software-pricing-hero">
    <div class="container-narrow">
      <span class="software-pricing-eyebrow">SaaS pricing</span>
      <h1>Pricing for teams rolling out HAIPULSE as their operating platform.</h1>
      <p>
        Start with one suite or deploy the full stack. Every package includes workspace setup, access controls,
        and a guided handoff into your live operation.
      </p>
    </div>
  </section>

  <section class="container-narrow software-pricing-section">
    <div class="software-pricing-grid">
      <?php foreach ($plans as $planKey => $plan): ?>
        <article id="plan-<?php echo htmlspecialchars($planKey, ENT_QUOTES, 'UTF-8'); ?>" class="card-ui software-pricing-card<?php echo $planKey === 'growth' ? ' software-pricing-card--featured' : ''; ?>">
          <div class="software-pricing-card__top">
            <span class="software-pricing-plan-tag"><?php echo htmlspecialchars($plan['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="software-pricing-card__price">
              <?php if (!empty($plan['price_monthly_aed'])): ?>
                <strong>AED <?php echo number_format((float)$plan['price_monthly_aed'], 0); ?></strong>
                <span>per month</span>
              <?php else: ?>
                <strong>Custom</strong>
                <span>annual and rollout-based pricing</span>
              <?php endif; ?>
            </div>
            <?php if (!empty($plan['price_annual_aed'])): ?>
              <p class="software-pricing-card__annual">AED <?php echo number_format((float)$plan['price_annual_aed'], 0); ?> billed annually</p>
            <?php endif; ?>
            <p class="muted mb-0"><?php echo htmlspecialchars($plan['best_for'], ENT_QUOTES, 'UTF-8'); ?></p>
          </div>

          <ul class="software-pricing-checks">
            <?php foreach (($plan['includes'] ?? []) as $feature): ?>
              <li><?php echo htmlspecialchars($feature, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>

          <div class="software-pricing-card__actions">
            <a href="<?php echo htmlspecialchars(url('/contact?subject=software-sales&plan=' . rawurlencode($planKey) . '&source=software-pricing'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui <?php echo $planKey === 'growth' ? 'btn-primary-ui' : 'software-pricing-btn-secondary'; ?> w-100">
              <?php echo $planKey === 'enterprise' ? 'Talk to sales' : 'Request activation'; ?>
            </a>
            <a href="<?php echo htmlspecialchars(url('/software'), ENT_QUOTES, 'UTF-8'); ?>" class="software-pricing-link">Review modules</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="container-narrow software-pricing-section">
    <div class="section-head">
      <h2>Included platform coverage</h2>
      <p class="muted">The suite below reflects the backend systems currently available in HAIPULSE.</p>
    </div>

    <div class="software-pricing-suite-table-wrap card-ui">
      <table class="software-pricing-suite-table">
        <thead>
          <tr>
            <th>Suite</th>
            <th>Operational coverage</th>
            <th>Best fit</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($systems as $systemKey => $system): ?>
            <tr>
              <td>
                <strong><?php echo htmlspecialchars($system['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
              </td>
              <td><?php echo htmlspecialchars(implode(', ', $system['modules'] ?? []), ENT_QUOTES, 'UTF-8'); ?></td>
              <td>
                <a href="<?php echo htmlspecialchars(url('/contact?subject=software-sales&suite=' . rawurlencode($systemKey) . '&source=software-pricing-table'), ENT_QUOTES, 'UTF-8'); ?>" class="software-pricing-inline-link">Ask about <?php echo htmlspecialchars($system['label'], ENT_QUOTES, 'UTF-8'); ?></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="container-narrow software-pricing-section">
    <div class="card-ui software-pricing-process">
      <div>
        <span class="software-pricing-eyebrow">Activation flow</span>
        <h2>How rollout works</h2>
      </div>
      <div class="software-pricing-process__grid">
        <article>
          <strong>1. Select package</strong>
          <p class="muted mb-0">Choose the plan or suite that matches your current team size and workflow scope.</p>
        </article>
        <article>
          <strong>2. Confirm setup details</strong>
          <p class="muted mb-0">We align organizations, admin seats, and the systems you want enabled first.</p>
        </article>
        <article>
          <strong>3. Activate and onboard</strong>
          <p class="muted mb-0">Your workspace is provisioned and your team receives guided onboarding into production use.</p>
        </article>
      </div>
    </div>
  </section>
</main>

<style>
  .page-software-pricing .software-pricing-hero {
    margin: 10px auto 24px;
  }

  .page-software-pricing .software-pricing-eyebrow {
    display: inline-flex;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-size: 0.78rem;
    font-weight: 700;
    margin-bottom: 12px;
  }

  .page-software-pricing .software-pricing-section {
    margin-top: 32px;
  }

  .page-software-pricing .software-pricing-grid,
  .page-software-pricing .software-pricing-process__grid {
    display: grid;
    gap: 16px;
  }

  .page-software-pricing .software-pricing-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .page-software-pricing .software-pricing-card,
  .page-software-pricing .software-pricing-process {
    padding: 24px;
  }

  .page-software-pricing .software-pricing-card {
    display: flex;
    flex-direction: column;
    gap: 18px;
    height: 100%;
  }

  .page-software-pricing .software-pricing-card--featured {
    border: 1px solid #0ea5e9;
    box-shadow: 0 22px 44px rgba(14, 165, 233, 0.16);
  }

  .page-software-pricing .software-pricing-plan-tag {
    display: inline-flex;
    padding: 6px 10px;
    border-radius: 999px;
    background: #e0f2fe;
    color: #0f172a;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
    margin-bottom: 10px;
  }

  .page-software-pricing .software-pricing-card__price strong {
    display: block;
    font-size: 2rem;
    color: #0f172a;
    line-height: 1;
  }

  .page-software-pricing .software-pricing-card__price span,
  .page-software-pricing .software-pricing-card__annual,
  .page-software-pricing .software-pricing-checks li,
  .page-software-pricing .software-pricing-link,
  .page-software-pricing .software-pricing-inline-link {
    color: #475569;
  }

  .page-software-pricing .software-pricing-checks {
    margin: 0;
    padding-left: 18px;
    display: grid;
    gap: 10px;
  }

  .page-software-pricing .software-pricing-card__actions {
    margin-top: auto;
    display: grid;
    gap: 12px;
  }

  .page-software-pricing .software-pricing-btn-secondary {
    background: #fff;
    color: #0f172a;
    border: 1px solid rgba(15, 23, 42, 0.12);
  }

  .page-software-pricing .software-pricing-suite-table-wrap {
    overflow-x: auto;
    padding: 0;
  }

  .page-software-pricing .software-pricing-suite-table {
    width: 100%;
    border-collapse: collapse;
  }

  .page-software-pricing .software-pricing-suite-table th,
  .page-software-pricing .software-pricing-suite-table td {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    vertical-align: top;
    text-align: left;
  }

  .page-software-pricing .software-pricing-suite-table th {
    color: #0f172a;
    font-size: 0.92rem;
  }

  .page-software-pricing .software-pricing-process {
    background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
    border: 1px solid rgba(249, 115, 22, 0.16);
  }

  .page-software-pricing .software-pricing-process__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-top: 18px;
  }

  @media (max-width: 991.98px) {
    .page-software-pricing .software-pricing-grid,
    .page-software-pricing .software-pricing-process__grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 575.98px) {
    .page-software-pricing .software-pricing-card,
    .page-software-pricing .software-pricing-process {
      padding: 20px;
    }

    .page-software-pricing .software-pricing-suite-table th,
    .page-software-pricing .software-pricing-suite-table td {
      padding: 14px 16px;
      min-width: 180px;
    }
  }
</style>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>
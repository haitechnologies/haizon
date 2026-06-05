<?php
/**
 * Page: All-in-One Platform
 * Route: /all-in-one
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/globals.php';
require_once __DIR__ . '/../../includes/helpers.php';

$saasCatalog       = require __DIR__ . '/../../config/saas_catalog.php';
$systems           = $saasCatalog['systems'] ?? [];
$platformHighlights = $saasCatalog['platform']['highlights'] ?? [];

$pageTitle       = 'All-in-One Business Platform — CRM, HR, Accounting & Shipping — HAIPULSE';
$pageDescription = 'HAIPULSE is the all-in-one business software platform for UAE teams. Run every department — CRM, HR, Accounting, and Shipping — from a single connected admin workspace.';
$pageKeywords    = 'all-in-one business software UAE, ERP UAE, integrated business platform UAE, CRM accounting HR shipping UAE';
$bodyClass       = 'page-saas-allinone';
$canonicalUrl    = getFullUrl('/all-in-one');

$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'All-in-One', 'url' => $canonicalUrl],
];

$jsonLdSchema  = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebPage',
    'name' => $pageTitle, 'description' => $pageDescription, 'url' => $canonicalUrl, 'inLanguage' => 'en-AE',
], JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../../includes/saas/header.php';

$suiteDetails = [
    'crm'        => ['route' => '/crm',        'color' => '#3b6fd4', 'light' => '#e8effc'],
    'hr'         => ['route' => '/hr',          'color' => '#16a34a', 'light' => '#dcfce7'],
    'accounting' => ['route' => '/accounting',  'color' => '#d97706', 'light' => '#fef3c7'],
    'shipping'   => ['route' => '/shipping',    'color' => '#7c3aed', 'light' => '#ede9fe'],
];
?>

<main>
  <!-- HERO -->
  <section class="saas-product-hero">
    <div class="saas-container">
      <nav aria-label="Breadcrumb">
        <ol class="saas-breadcrumb" role="list">
          <li><a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>">Home</a></li>
          <li>All-in-One</li>
        </ol>
      </nav>
      <span class="saas-eyebrow">All-in-One Platform</span>
      <h1>Every department. One workspace. No&nbsp;integrations&nbsp;needed.</h1>
      <p class="saas-hero__lead">
        HAIPULSE connects your CRM, HR, Accounting, and Shipping into a single admin platform.
        One login, shared data, unified reporting — built for UAE operations teams.
      </p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">View Enterprise plan</a>
        <a href="<?php echo htmlspecialchars(url('/contact?subject=all-in-one&source=allinone-hero'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Book a walkthrough</a>
      </div>
    </div>
  </section>

  <!-- PLATFORM HIGHLIGHTS STRIP -->
  <section class="saas-highlights-strip" aria-label="Platform highlights">
    <div class="saas-container">
      <ul class="saas-highlights-list" role="list">
        <?php foreach ($platformHighlights as $h): ?>
          <li><?php echo htmlspecialchars($h, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- SUITE BREAKDOWN -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">What's inside</span>
        <h2>Four suites. One connected platform.</h2>
        <p class="saas-muted">Every module is already built and live — nothing to integrate, no separate licences.</p>
      </div>
      <div class="saas-suite-grid">
        <?php foreach ($systems as $key => $system):
          $detail = $suiteDetails[$key] ?? ['route' => '/' . $key, 'color' => '#3b6fd4', 'light' => '#e8effc'];
        ?>
          <article class="saas-card saas-suite-card saas-card-lift">
            <div class="saas-suite-icon"
                 style="background:<?php echo htmlspecialchars($detail['light'], ENT_QUOTES, 'UTF-8'); ?>;
                        color:<?php echo htmlspecialchars($detail['color'], ENT_QUOTES, 'UTF-8'); ?>;">
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
              <a href="<?php echo htmlspecialchars(url($detail['route']), ENT_QUOTES, 'UTF-8'); ?>"
                 class="saas-btn saas-btn-ghost saas-btn-sm saas-btn-block">
                Explore <?php echo htmlspecialchars($system['label'], ENT_QUOTES, 'UTF-8'); ?> details &rarr;
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- WHY UNIFIED -->
  <section class="saas-section">
    <div class="saas-container-narrow">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">Why all-in-one</span>
        <h2>What you gain when every department shares one platform</h2>
      </div>
      <div class="row g-4">
        <?php
        $benefits = [
            ['icon' => 'ph-link', 'title' => 'Connected data',
             'desc' => 'A customer in CRM, an employee in HR, an invoice in Accounting, and a shipment in Logistics can all reference the same core records.'],
            ['icon' => 'ph-lock-key', 'title' => 'Unified access control',
             'desc' => 'One permission model governs every module. Add or restrict access per user without managing multiple platforms.'],
            ['icon' => 'ph-arrow-counter-clockwise', 'title' => 'No integration overhead',
             'desc' => 'No APIs to maintain, no sync delays, no data duplication. All modules are native and talk to each other out of the box.'],
            ['icon' => 'ph-buildings', 'title' => 'Multi-organization support',
             'desc' => 'Run multiple organizations from a single HAIPULSE login. Switch context without logging out.'],
        ];
        foreach ($benefits as $b): ?>
          <div class="col-md-6">
            <div class="saas-card d-flex gap-3 h-100" style="flex-direction:row; align-items:flex-start;">
              <div class="saas-feature-icon flex-shrink-0" style="margin:0;">
                <i class="ph <?php echo htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
              </div>
              <div>
                <h3 style="font-size:1rem; margin-bottom:6px;"><?php echo htmlspecialchars($b['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="saas-muted mb-0" style="font-size:0.9rem;"><?php echo htmlspecialchars($b['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ROLLOUT STEPS -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container-narrow">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">Rollout approach</span>
        <h2>You don't have to launch everything on day one</h2>
      </div>
      <div class="saas-steps">
        <article class="saas-card saas-step-card">
          <div class="saas-step-pill">1</div>
          <h3>Start with one suite</h3>
          <p>Launch CRM or Accounting to get your team productive and familiar with the platform quickly.</p>
        </article>
        <article class="saas-card saas-step-card">
          <div class="saas-step-pill">2</div>
          <h3>Expand to people</h3>
          <p>Add HR when you're ready to formalise attendance, leave, and payroll within the same workspace.</p>
        </article>
        <article class="saas-card saas-step-card">
          <div class="saas-step-pill">3</div>
          <h3>Go full operations</h3>
          <p>Activate Shipping to coordinate logistics, cargo, and carriers alongside your finance and CRM data.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="saas-cta-section">
    <div class="saas-container">
      <span class="saas-eyebrow" style="color:rgba(255,255,255,0.6);">Enterprise plan</span>
      <h2>Ready to run your entire business on HAIPULSE?</h2>
      <p>Talk to the team about your departments, headcount, and full rollout plan for the complete suite.</p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/pricing#plan-enterprise'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">View Enterprise plan</a>
        <a href="<?php echo htmlspecialchars(url('/contact?subject=all-in-one&source=allinone-cta'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Request a consultation</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../includes/saas/footer.php'; ?>

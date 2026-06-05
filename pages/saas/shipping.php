<?php
/**
 * Page: Shipping Suite
 * Route: /shipping
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/globals.php';
require_once __DIR__ . '/../../includes/helpers.php';

$saasCatalog = require __DIR__ . '/../../config/saas_catalog.php';

$pageTitle       = 'Shipping & Logistics Software for UAE Teams — HAIPULSE';
$pageDescription = 'Manage shipping advices, invoices, stocks, ports, carriers, and consignees from one logistics workspace built for UAE operations.';
$pageKeywords    = 'shipping software UAE, logistics management UAE, freight tracking UAE, shipping operations software UAE';
$bodyClass       = 'page-saas-shipping';
$canonicalUrl    = getFullUrl('/shipping');

$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'Shipping', 'url' => $canonicalUrl],
];

$jsonLdSchema  = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebPage',
    'name' => $pageTitle, 'description' => $pageDescription, 'url' => $canonicalUrl, 'inLanguage' => 'en-AE',
], JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../../includes/saas/header.php';

$features = [
    ['icon' => 'ph-note-pencil', 'title' => 'Shipping Advices',
     'desc' => 'Create and track shipping advice notes with vessel, port, carrier, and cargo details in a structured workflow.'],
    ['icon' => 'ph-receipt', 'title' => 'Shipping Invoices',
     'desc' => 'Generate shipping invoices tied to advice notes and link them directly to your accounting module.'],
    ['icon' => 'ph-stack', 'title' => 'Shipping Stocks',
     'desc' => 'Track cargo and stock movements across shipments, monitor in-transit inventory, and reconcile on delivery.'],
    ['icon' => 'ph-anchor', 'title' => 'Ports & Routes',
     'desc' => 'Maintain port and route master data for consistent reference across advices, invoices, and reports.'],
    ['icon' => 'ph-truck', 'title' => 'Carriers',
     'desc' => 'Manage carrier records including contact details, rates, and service lanes for reuse across shipments.'],
    ['icon' => 'ph-users-four', 'title' => 'Consignees & Shippers',
     'desc' => 'Centralise consignee and shipper profiles so party details are accurate and reusable on every shipment.'],
];
?>

<main>
  <!-- HERO -->
  <section class="saas-product-hero" style="background: linear-gradient(135deg, #1a2e4a 0%, #2e1a4a 100%);">
    <div class="saas-container">
      <nav aria-label="Breadcrumb">
        <ol class="saas-breadcrumb" role="list">
          <li><a href="<?php echo htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>">Home</a></li>
          <li>Shipping</li>
        </ol>
      </nav>
      <span class="saas-eyebrow">Shipping Suite</span>
      <h1>Coordinate shipping operations and logistics from one connected workspace.</h1>
      <p class="saas-hero__lead">
        HAIPULSE Shipping covers the full logistics cycle — advices, invoices, stocks, ports,
        carriers, and parties — integrated with accounting and customer records.
      </p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Get started</a>
        <a href="<?php echo htmlspecialchars(url('/contact?subject=shipping-demo&source=shipping-hero'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">Book a demo</a>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="saas-section saas-section--alt">
    <div class="saas-container">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">What's included</span>
        <h2>Shipping module coverage</h2>
        <p class="saas-muted">All shipping modules run inside HAIPULSE alongside your CRM and accounting data.</p>
      </div>
      <div class="saas-features-grid">
        <?php foreach ($features as $f): ?>
          <article class="saas-feature-card" style="--saas-accent:#7c3aed; --saas-accent-light:#ede9fe;">
            <div class="saas-feature-icon" style="background:#ede9fe; color:#7c3aed;">
              <i class="ph <?php echo htmlspecialchars($f['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            </div>
            <h3><?php echo htmlspecialchars($f['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars($f['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- WHO IT IS FOR -->
  <section class="saas-section">
    <div class="saas-container-narrow">
      <div class="saas-section-head text-center">
        <span class="saas-eyebrow">Who it's for</span>
        <h2>For UAE freight and logistics operations teams</h2>
      </div>
      <div class="row g-4">
        <?php
        $audiences = [
            ['title' => 'Freight coordinators', 'desc' => 'Manage end-to-end shipment lifecycle from advice creation through delivery and invoicing.'],
            ['title' => 'Operations managers', 'desc' => 'Track carrier performance, port activity, and shipment status across multiple concurrent consignments.'],
            ['title' => 'Trading companies', 'desc' => 'Connect shipping flows to CRM customers and accounting invoices for a joined-up commercial record.'],
        ];
        foreach ($audiences as $a): ?>
          <div class="col-md-4">
            <div class="saas-card h-100">
              <h3 style="font-size:1rem; margin-bottom:8px;"><?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="saas-muted mb-0" style="font-size:0.9rem;"><?php echo htmlspecialchars($a['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="saas-cta-section">
    <div class="saas-container">
      <h2>Ready to manage logistics from HAIPULSE?</h2>
      <p>Contact the team to discuss your shipment volume, carrier setup, and rollout plan.</p>
      <div class="saas-hero__actions">
        <a href="<?php echo htmlspecialchars(url('/contact?subject=shipping&source=shipping-cta'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-primary saas-btn-lg">Contact sales</a>
        <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>"
           class="saas-btn saas-btn-outline saas-btn-lg">See pricing</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../includes/saas/footer.php'; ?>

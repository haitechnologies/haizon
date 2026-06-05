<?php
/**
 * Page: Business Listing Pricing Plans
 * Route: /pricing
 * Description: Silver / Gold / Platinum business listing packages with
 *              Stripe-powered checkout and 1-month free trial on annual plans.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/BusinessListingPlan.php';

// Redirect /pages/pricing.php â†’ /pricing
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if (preg_match('#/pages/pricing\.php$#i', $requestPath)) {
    $q = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . preg_replace('#/pages/pricing\.php$#i', '/pricing', $requestPath) . ($q !== '' ? "?$q" : ''), true, 302);
    exit;
}

$pageTitle       = 'Business Listing Plans â€“ HAIPULSE';
$pageDescription = 'Register your business on HAIPULSE with Silver, Gold or Platinum plans. Enjoy a 1-month free trial on every annual subscription. No long-term commitment.';
$bodyClass       = 'page-pricing';
$userId          = isset($_SESSION['frontend_user_id']) ? (int)$_SESSION['frontend_user_id'] : 0;

// Load plans from DB (falls back gracefully if table not yet migrated)
$plansKeyed = [];
try {
    $plansKeyed = BusinessListingPlan::getAllPlans($conn);
} catch (Throwable $e) { /* migration not yet run */ }

// Hardcoded fallback (matches seed data in migration file)
$defaults = [
    'free'     => ['plan_slug'=>'free',     'plan_name'=>'Free',     'tagline'=>'Lifetime access to explore the directory',                            'monthly_price_orig'=>0,   'monthly_price'=>0,   'annual_price_orig'=>0,     'annual_price'=>0,     'has_free_trial'=>0,'free_trial_days'=>null,'max_banners'=>0,'max_pages'=>0,'max_categories'=>1,'max_keywords'=>0,'max_brands'=>0,'has_iso_accolades'=>0,'has_green_badge'=>0,'has_verified_mark'=>0,'has_video_banner'=>0,'has_theme_custom'=>0,'has_services_section'=>0,'has_priority_ranking'=>0],
    'silver'   => ['plan_slug'=>'silver',   'plan_name'=>'Silver',   'tagline'=>'Start strong with essential tools to boost your online visibility', 'monthly_price_orig'=>55,  'monthly_price'=>50,  'annual_price_orig'=>660,   'annual_price'=>600,   'has_free_trial'=>1,'free_trial_days'=>30,'max_banners'=>1,'max_pages'=>1,'max_categories'=>3,'max_keywords'=>3,'max_brands'=>3,'has_iso_accolades'=>0,'has_green_badge'=>0,'has_verified_mark'=>0,'has_video_banner'=>0,'has_theme_custom'=>0,'has_services_section'=>0,'has_priority_ranking'=>0],
    'gold'     => ['plan_slug'=>'gold',     'plan_name'=>'Gold',     'tagline'=>'Expand reach with powerful features for more visibility and growth','monthly_price_orig'=>165, 'monthly_price'=>150, 'annual_price_orig'=>1980,  'annual_price'=>1800,  'has_free_trial'=>1,'free_trial_days'=>30,'max_banners'=>3,'max_pages'=>3,'max_categories'=>6,'max_keywords'=>6,'max_brands'=>6,'has_iso_accolades'=>1,'has_green_badge'=>1,'has_verified_mark'=>1,'has_video_banner'=>0,'has_theme_custom'=>0,'has_services_section'=>0,'has_priority_ranking'=>1],
    'platinum' => ['plan_slug'=>'platinum', 'plan_name'=>'Platinum', 'tagline'=>'Premium exposure to dominate your industry with top-tier benefits', 'monthly_price_orig'=>275, 'monthly_price'=>250, 'annual_price_orig'=>3300,  'annual_price'=>3000,  'has_free_trial'=>1,'free_trial_days'=>30,'max_banners'=>6,'max_pages'=>6,'max_categories'=>12,'max_keywords'=>12,'max_brands'=>12,'has_iso_accolades'=>1,'has_green_badge'=>1,'has_verified_mark'=>1,'has_video_banner'=>1,'has_theme_custom'=>1,'has_services_section'=>1,'has_priority_ranking'=>1],
];
foreach ($defaults as $slug => $def) {
    if (!isset($plansKeyed[$slug])) {
        $plansKeyed[$slug] = $def;
    }
}
$paid = ['silver' => $plansKeyed['silver'], 'gold' => $plansKeyed['gold'], 'platinum' => $plansKeyed['platinum']];

$pageUrl = getFullUrl('/pricing');
$canonicalUrl = $pageUrl;
$pageKeywords = implode(', ', [
    'business listing plans UAE',
    'Dubai business directory pricing',
    'Silver Gold Platinum listing plans',
    'UAE business advertising packages',
    'company listing subscription UAE',
    'business directory membership Dubai'
]);
$ogTitle = $pageTitle;
$ogDescription = $pageDescription;
$ogImage = getFullUrl('/assets/images/brand/logo.png');
$contentType = 'pricing';
$articleSection = 'Business';

$offerItems = [];
$position = 1;
foreach ($paid as $slug => $plan) {
    $offerItems[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'item' => [
            '@type' => 'Offer',
            'name' => $plan['plan_name'] . ' Business Listing Plan',
            'description' => $plan['tagline'],
            'priceCurrency' => 'AED',
            'price' => (string)($plan['annual_price'] ?? 0),
            'url' => $pageUrl . '#plan-' . $slug,
            'availability' => 'https://schema.org/InStock',
            'category' => 'Business Directory Subscription',
            'eligibleDuration' => [
                '@type' => 'QuantitativeValue',
                'value' => 1,
                'unitText' => 'year'
            ]
        ]
    ];
}

$jsonLdScripts = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        '@id' => $pageUrl . '#webpage',
        'url' => $pageUrl,
        'name' => $pageTitle,
        'description' => $pageDescription,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'HAIPULSE',
            'url' => getFullUrl('/')
        ],
        'breadcrumb' => ['@id' => $pageUrl . '#breadcrumb'],
        'mainEntity' => ['@id' => $pageUrl . '#plans'],
        'inLanguage' => 'en-AE'
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        '@id' => $pageUrl . '#breadcrumb',
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
                'name' => 'Pricing',
                'item' => $pageUrl
            ]
        ]
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        '@id' => $pageUrl . '#plans',
        'name' => 'Business Listing Plans',
        'numberOfItems' => count($offerItems),
        'itemListElement' => $offerItems
    ]
];

$jsonLdSchema = '';
foreach ($jsonLdScripts as $jsonLdScript) {
    $jsonLdSchema .= '<script type="application/ld+json">' . json_encode($jsonLdScript, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

include __DIR__ . '/../includes/layout/header.php';
?>

<?php
function pYes() { return '<span class="feature-badge yes" aria-hidden="true">âœ“</span><span class="visually-hidden">Yes</span>'; }
function pNo()  { return '<span class="feature-badge no" aria-hidden="true">âœ•</span><span class="visually-hidden">No</span>'; }
?>

<!-- HERO -->
<section class="pricing-hero">
    <div class="container">
        <h1>List Your Business &amp; Grow Faster</h1>
        <p>Get clear visibility limits by plan: <strong>100 guest, 1,000 registered, 5,000 Silver, 25,000 Gold, 100,000 Platinum</strong>.<br>Register today and enjoy a <strong>1-month free trial</strong> on every annual plan.</p>
        <div class="billing-toggle-wrap">
            <label for="billingToggle">Monthly</label>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="billingToggle" checked>
            </div>
            <label for="billingToggle">Annual <span class="save-badge">Save up to AED 300</span></label>
        </div>
    </div>
</section>

<!-- PLAN CARDS -->
<section class="plan-cards">
    <div class="container">
        <div class="row g-4 justify-content-center">
        <?php
        $cardMeta = [
            'silver'   => ['icon'=>'ðŸ¥ˆ','btnClass'=>'btn-outline-secondary','popular'=>false],
            'gold'     => ['icon'=>'ðŸ¥‡','btnClass'=>'btn-primary','popular'=>true],
            'platinum' => ['icon'=>'ðŸ’Ž','btnClass'=>'btn-purple','popular'=>false],
        ];
        $searchLimitsByPlan = [
            'silver' => 5000,
            'gold' => 25000,
            'platinum' => 100000,
        ];
        foreach ($paid as $slug => $plan):
            $meta = $cardMeta[$slug];
            $searchLimit = (int)($searchLimitsByPlan[$slug] ?? 0);
        ?>
        <div class="col-lg-4 col-md-6">
            <div class="plan-card<?= $meta['popular'] ? ' popular' : '' ?>">
                <?php if ($meta['popular']): ?><div class="popular-ribbon">Popular</div><?php endif; ?>
                <div class="plan-header">
                    <span class="plan-slug-badge badge-<?= $slug ?>"><?= $meta['icon'] ?> <?= htmlspecialchars($plan['plan_name'], ENT_QUOTES) ?></span>
                    <div class="plan-price">
                        <span class="orig" id="orig-<?= $slug ?>">AED <?= number_format($plan['annual_price_orig']) ?>/yr</span>
                        <span class="amount" id="amount-<?= $slug ?>">AED <?= number_format($plan['annual_price']) ?></span>
                        <span class="period" id="period-<?= $slug ?>">/year</span>
                    </div>
                    <?php if ($plan['has_free_trial']): ?>
                        <p class="trial-note" id="trial-<?= $slug ?>">ðŸŽ Includes 1-month free trial</p>
                    <?php endif; ?>
                    <p class="plan-tagline"><?= htmlspecialchars($plan['tagline'], ENT_QUOTES) ?></p>
                </div>
                <div class="plan-features">
                    <ul>
                        <li><span class="plan-check">âœ”</span> Promotional Banners <span class="feat-qty"><?= $plan['max_banners'] ?></span></li>
                        <li><span class="plan-check">âœ”</span> Dedicated Landing Pages <span class="feat-qty"><?= $plan['max_pages'] ?></span></li>
                        <li><span class="plan-check">âœ”</span> Business Categories <span class="feat-qty"><?= $plan['max_categories'] ?></span></li>
                        <li><span class="plan-check">âœ”</span> SEO Keywords <span class="feat-qty"><?= $plan['max_keywords'] ?></span></li>
                        <li><span class="plan-check">âœ”</span> Brand / Product Listings <span class="feat-qty"><?= $plan['max_brands'] ?></span></li>
                        <li><span class="plan-check">âœ”</span> Directory Results per Browse/Search <span class="feat-qty"><?= number_format($searchLimit) ?></span></li>
                        <li><span class="plan-check">âœ”</span> Company Profile &amp; Logo</li>
                        <li><span class="plan-check">âœ”</span> Social Media + WhatsApp + Map</li>
                        <?php if ($plan['has_iso_accolades']): ?><li><span class="plan-check">âœ”</span> ISO Certs &amp; Accolades</li><?php endif; ?>
                        <?php if ($plan['has_green_badge']): ?><li><span class="plan-check">âœ”</span> Green Product Badge</li><?php endif; ?>
                        <?php if ($plan['has_verified_mark']): ?><li><span class="plan-check">âœ”</span> Verified Business Mark</li><?php endif; ?>
                        <?php if ($plan['has_priority_ranking']): ?><li><span class="plan-check">âœ”</span> Priority Search Ranking</li><?php endif; ?>
                        <?php if ($plan['has_video_banner']): ?><li><span class="plan-check">âœ”</span> Video Banner</li><?php endif; ?>
                        <?php if ($plan['has_theme_custom']): ?><li><span class="plan-check">âœ”</span> Theme Customisation</li><?php endif; ?>
                        <?php if ($plan['has_services_section']): ?><li><span class="plan-check">âœ”</span> Dedicated Services Section</li><?php endif; ?>
                    </ul>
                </div>
                <div class="plan-cta">
                    <?php if ($userId > 0): ?>
                        <a href="<?= url('/subscribe-checkout?plan=' . $slug . '&cycle=annual') ?>"
                           class="btn <?= $meta['btnClass'] ?> w-100" id="cta-<?= $slug ?>">
                           Start 1-Month Free Trial
                        </a>
                    <?php else: ?>
                        <a href="<?= url('/register?redirect=pricing&plan=' . $slug) ?>"
                           class="btn <?= $meta['btnClass'] ?> w-100" id="cta-<?= $slug ?>">
                           Get Started Free
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <!-- Free plan strip -->
        <div class="row mt-4">
            <div class="col-lg-10 mx-auto">
                <div class="free-plan-card">
                    <div class="row align-items-center">
                        <div class="col-md-8 text-md-start">
                            <h5 class="mb-1 fw-bold">ðŸ†“ Free Plan â€” Lifetime Access</h5>
                            <p class="mb-0 text-muted">List your business for free with a basic profile, logo, and 1 category. Directory visibility includes up to 100 results as guest, and up to 1,000 results with a free registered account. Upgrade anytime to unlock higher limits and premium features.</p>
                        </div>
                        <div class="col-md-4 text-center mt-3 mt-md-0">
                            <a href="<?= url('/add-business') ?>" class="btn btn-outline-dark px-5">Create Free Listing</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- COMPARISON TABLE -->
<section class="comparison-section">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">Full Feature Comparison</h2>
        <p class="text-center text-muted mb-5">See exactly what each plan includes</p>
        <div class="table-responsive">
            <table class="table table-bordered comparison-table">
                <thead>
                    <tr>
                        <th class="pricing-feature-col">Feature</th>
                        <th class="text-center">Free</th>
                        <th class="text-center silver-col">ðŸ¥ˆ Silver</th>
                        <th class="text-center gold-col">ðŸ¥‡ Gold</th>
                        <th class="text-center plat-col">ðŸ’Ž Platinum</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="cat-row"><th colspan="5">Pricing</th></tr>
                    <tr><td>Annual Price</td><td class="center">Free</td><td class="center"><strong>AED 600/yr</strong></td><td class="center gold-col"><strong>AED 1,800/yr</strong></td><td class="center plat-col"><strong>AED 3,000/yr</strong></td></tr>
                    <tr><td>Monthly Price</td><td class="center">Free</td><td class="center">AED 50/mo</td><td class="center gold-col">AED 150/mo</td><td class="center plat-col">AED 250/mo</td></tr>
                    <tr><td>1-Month Free Trial (Annual)</td><td class="center"><?= pNo() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>

                    <tr class="cat-row"><th colspan="5">Content Limits</th></tr>
                    <tr><td>Directory Results per Browse/Search</td><td class="center">100 guest / 1,000 registered</td><td class="center">5,000</td><td class="center gold-col">25,000</td><td class="center plat-col">100,000</td></tr>
                    <tr><td>Promotional Banners (images)</td><td class="center">0</td><td class="center">1</td><td class="center gold-col">3</td><td class="center plat-col">6</td></tr>
                    <tr><td>Dedicated Landing Pages</td><td class="center">0</td><td class="center">1</td><td class="center gold-col">3</td><td class="center plat-col">6</td></tr>
                    <tr><td>Business Categories</td><td class="center">1</td><td class="center">3</td><td class="center gold-col">6</td><td class="center plat-col">12</td></tr>
                    <tr><td>SEO Keywords</td><td class="center">0</td><td class="center">3</td><td class="center gold-col">6</td><td class="center plat-col">12</td></tr>
                    <tr><td>Brand / Product Listings</td><td class="center">0</td><td class="center">3</td><td class="center gold-col">6</td><td class="center plat-col">12</td></tr>

                    <tr class="cat-row"><th colspan="5">Standard Features</th></tr>
                    <tr><td>Company Profile &amp; Description</td><td class="center"><?= pYes() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Company Logo</td><td class="center"><?= pYes() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Years in Business</td><td class="center"><?= pNo() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Email &amp; Phone Number</td><td class="center"><?= pNo() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Website Link</td><td class="center"><?= pNo() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Address, PO Box &amp; Fax</td><td class="center"><?= pNo() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Social Media (FB, LinkedIn, IG, X, YT)</td><td class="center"><?= pNo() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>WhatsApp Integration</td><td class="center"><?= pNo() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Google Maps Location Pin</td><td class="center"><?= pNo() ?></td><td class="center"><?= pYes() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>

                    <tr class="cat-row"><th colspan="5">Premium Features</th></tr>
                    <tr><td>ISO Certifications &amp; Accolades</td><td class="center"><?= pNo() ?></td><td class="center"><?= pNo() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Green Product / Eco Badge</td><td class="center"><?= pNo() ?></td><td class="center"><?= pNo() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Verified Business Mark</td><td class="center"><?= pNo() ?></td><td class="center"><?= pNo() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Priority Search Ranking</td><td class="center"><?= pNo() ?></td><td class="center"><?= pNo() ?></td><td class="center gold-col"><?= pYes() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>

                    <tr class="cat-row"><th colspan="5">Platinum Exclusive</th></tr>
                    <tr><td>Video Banner</td><td class="center"><?= pNo() ?></td><td class="center"><?= pNo() ?></td><td class="center gold-col"><?= pNo() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Theme Colour Customisation</td><td class="center"><?= pNo() ?></td><td class="center"><?= pNo() ?></td><td class="center gold-col"><?= pNo() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                    <tr><td>Dedicated Services Page</td><td class="center"><?= pNo() ?></td><td class="center"><?= pNo() ?></td><td class="center gold-col"><?= pNo() ?></td><td class="center plat-col"><?= pYes() ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- TRUST STRIP -->
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-3"><div class="fs-2">ðŸ”’</div><h6 class="fw-bold mt-2">Secure Payments</h6><p class="text-muted small mb-0">Powered by Stripe. Card data never touches our servers.</p></div>
            <div class="col-md-3"><div class="fs-2">ðŸ”„</div><h6 class="fw-bold mt-2">Cancel Anytime</h6><p class="text-muted small mb-0">No lock-ins. Cancel from your dashboard with one click.</p></div>
            <div class="col-md-3"><div class="fs-2">ðŸŽ</div><h6 class="fw-bold mt-2">1-Month Free Trial</h6><p class="text-muted small mb-0">Try any paid annual plan free for 30 days.</p></div>
            <div class="col-md-3"><div class="fs-2">ðŸ“ž</div><h6 class="fw-bold mt-2">Dedicated Support</h6><p class="text-muted small mb-0">We help you get the most from your listing.</p></div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="faq-section">
    <div class="container">
        <div class="col-lg-8 mx-auto">
            <h2 class="text-center fw-bold mb-2">Frequently Asked Questions</h2>
            <p class="text-center text-muted mb-5">Everything you need to know</p>
            <div class="accordion" id="pricingFaq">
                <div class="accordion-item border-0 mb-2 rounded shadow-sm">
                    <h2 class="accordion-header"><button class="accordion-button rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">How does the 1-month free trial work?</button></h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFaq">
                        <div class="accordion-body text-muted">When you subscribe to any annual plan (Silver, Gold, or Platinum) your first 30 days are completely free. Your card is charged only after the trial ends. Cancel during the trial and you will not be billed.</div>
                    </div>
                </div>
                <div class="accordion-item border-0 mb-2 rounded shadow-sm">
                    <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">Monthly vs Annual â€“ what is the difference?</button></h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body text-muted">Annual billing saves you money (up to AED 300/year on Platinum) and includes the free 30-day trial. Monthly billing is slightly higher per month and does not include the trial period.</div>
                    </div>
                </div>
                <div class="accordion-item border-0 mb-2 rounded shadow-sm">
                    <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">What are Banners and Brands?</button></h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body text-muted"><strong>Banners</strong> are promotional images displayed on your listing page â€” perfect for showcasing products or offers.<br><br><strong>Brands</strong> are the product brands or supplier names you deal in. Listing them helps buyers who search by brand name find your business.</div>
                    </div>
                </div>
                <div class="accordion-item border-0 mb-2 rounded shadow-sm">
                    <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">What are SEO Keywords?</button></h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body text-muted">Keywords are the search terms your business should appear for. Adding industry-specific keywords improves your visibility in our search engine and in Google through structured data we add to your page automatically.</div>
                    </div>
                </div>
                <div class="accordion-item border-0 mb-2 rounded shadow-sm">
                    <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">How many results can I view on each plan?</button></h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body text-muted">Directory browse/search limits are: Guest 100, Registered 1,000, Silver 5,000, Gold 25,000, and Platinum 100,000 results.</div>
                    </div>
                </div>
                <div class="accordion-item border-0 mb-2 rounded shadow-sm">
                    <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">Can I upgrade or downgrade?</button></h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body text-muted">Yes. Upgrades take effect immediately. Downgrades take effect at the end of your current billing period.</div>
                    </div>
                </div>
                <div class="accordion-item border-0 rounded shadow-sm">
                    <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">What payment methods are accepted?</button></h2>
                    <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body text-muted">We accept all major credit/debit cards (Visa, Mastercard, Amex) via Stripe. Payments are secure and your card data is never stored on our servers.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BOTTOM CTA -->
<section class="py-5 text-center pricing-bottom-cta">
    <div class="container">
        <h2 class="fw-bold mb-3">Ready to grow your business?</h2>
        <p class="mb-4 opacity-75">Join thousands of UAE businesses already on HAIPULSE.</p>
        <a href="<?= url('/register') ?>" class="btn btn-warning btn-lg fw-bold px-5 me-2">Start Free Trial</a>
        <a href="<?= url('/contact') ?>"  class="btn btn-outline-light btn-lg px-5">Talk to Us</a>
    </div>
</section>

<script>
(function() {
    const toggle = document.getElementById('billingToggle');
    if (!toggle) return;
    const plans = {
        silver:   { monthly:{amount:'AED 50',  period:'/month',orig:'AED 55/mo',  cta:'Subscribe Monthly'},
                    annual: {amount:'AED 600', period:'/year', orig:'AED 660/yr',  cta:'Start 1-Month Free Trial'} },
        gold:     { monthly:{amount:'AED 150', period:'/month',orig:'AED 165/mo', cta:'Subscribe Monthly'},
                    annual: {amount:'AED 1,800',period:'/year',orig:'AED 1,980/yr',cta:'Start 1-Month Free Trial'} },
        platinum: { monthly:{amount:'AED 250', period:'/month',orig:'AED 275/mo', cta:'Subscribe Monthly'},
                    annual: {amount:'AED 3,000',period:'/year',orig:'AED 3,300/yr',cta:'Start 1-Month Free Trial'} },
    };
    function update() {
        const isAnnual = toggle.checked;
        const cycle = isAnnual ? 'annual' : 'monthly';
        Object.keys(plans).forEach(function(slug) {
            const d = plans[slug][cycle];
            const q = function(id){ return document.getElementById(id); };
            if (q('amount-'+slug)) q('amount-'+slug).textContent = d.amount;
            if (q('period-'+slug)) q('period-'+slug).textContent = d.period;
            if (q('orig-'+slug))   q('orig-'+slug).textContent   = d.orig;
            if (q('trial-'+slug))  q('trial-'+slug).style.display = isAnnual ? '' : 'none';
            if (q('cta-'+slug)) {
                q('cta-'+slug).textContent = d.cta;
                var href = q('cta-'+slug).getAttribute('href') || '';
                q('cta-'+slug).setAttribute('href', href.replace(/cycle=(monthly|annual)/, 'cycle='+cycle));
            }
        });
    }
    toggle.addEventListener('change', update);
    update();
})();
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>



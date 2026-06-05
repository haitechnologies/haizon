<?php
/**
 * HS Code Detail Page
 * Route: /trade/hs-code/{code}
 * 
 * Displays detailed information about a specific HS (Harmonized System) code
 * including descriptions, duty rates, related companies, and trading info.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/HSCodes.php';
require_once __DIR__ . '/../classes/frontend/Companies.php';
require_once __DIR__ . '/../classes/frontend/PublicAds.php';
require_once __DIR__ . '/../includes/helpers.php';

$basePath = $GLOBALS['basePath'] ?? '';
$hsCode = $GLOBALS['route_params'][0] ?? null;

// Validate HS code format (supports 2-14 digits, with optional dotted segments like 8703.23.11)
if (!$hsCode || !preg_match('/^[0-9]{2,14}(?:\.[0-9]{2})*$/', $hsCode)) {
    http_response_code(404);
    include __DIR__ . '/../pages/404.php';
    exit;
}


$hsCodesModel = new HSCodes($conn);
$publicAdsModel = new PublicAds($conn);

// Get HS code details (always fetch both EN and AR data)
$codeDetails = $hsCodesModel->getByCode($hsCode);

// Increment view count for this HS code (if found)
if ($codeDetails && !empty($codeDetails['id'])) {
    $stmt = $conn->prepare("UPDATE " . DB::HS_CODES . " SET views = views + 1 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $codeDetails['id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Get HS code details (always fetch both EN and AR data)
$codeDetails = $hsCodesModel->getByCode($hsCode);

if (!$codeDetails) {
    // Graceful fallback: code may be a partial/legacy format not stored as an exact row.
    // Redirect to searchable HS listing instead of hard 404.
    header('Location: ' . url('/trade/hs-codes?search=' . rawurlencode((string)$hsCode)), true, 302);
    exit;
}

// Get child codes (next level down)
$childCodes = $hsCodesModel->getAll([
    'parent_id' => $codeDetails['id'],
    'lang' => 'en',
    'limit' => 10
]);

$similarCategoryCodes = $hsCodesModel->getSimilarCodesByCategories((int)$codeDetails['id'], 'en', 10);

// Page metadata
$codeDisplay = $codeDetails['code'] ?? $hsCode;
$descriptionEn = (string)($codeDetails['desc_en'] ?? '');
$shortDescEn = (string)($codeDetails['short_en'] ?? '');
$descriptionAr = (string)($codeDetails['desc_ar'] ?? '');
$shortDescAr = (string)($codeDetails['short_ar'] ?? '');
$dutyRate = (string)($codeDetails['duty_rate'] ?? 'N/A');

if ($shortDescEn === '') {
    $shortDescEn = $descriptionEn !== '' ? $descriptionEn : (string)$codeDisplay;
}

if ($descriptionEn === '') {
    $descriptionEn = $shortDescEn;
}

if ($shortDescAr === '') {
    $shortDescAr = $descriptionAr !== '' ? $descriptionAr : $shortDescEn;
}

if ($descriptionAr === '') {
    $descriptionAr = $shortDescAr;
}

$hsSidebarAds = $publicAdsModel->getAdsForSlot('hs_sidebar', [
    'page_type' => 'hs-code',
    'keyword' => $codeDisplay ?? $hsCode,
    'tags' => ['software', 'inventory', 'trade', 'import', 'export', 'customs']
], 1);

$pageTitle = "HS Code {$codeDisplay} - {$shortDescEn} | UAE Trade Portal";
$pageDescription = "Complete information about HS Code {$codeDisplay}: {$shortDescEn}. Browse tariff rates, related products, and UAE importers/exporters.";
$pageKeywords = implode(', ', array_filter([
    'HS Code ' . $codeDisplay,
    $shortDescEn,
    $shortDescAr,
    'UAE HS code',
    'customs tariff UAE',
    'import export classification',
    'trade code ' . $codeDisplay
]));
$pageUrl = getFullUrl('/trade/hs-code/' . rawurlencode($codeDisplay));
$canonicalUrl = $pageUrl;
$ampHtmlUrl = getFullUrl('/trade/hs-code/' . rawurlencode($codeDisplay) . '/amp');
$ogTitle = $pageTitle;
$ogDescription = $pageDescription;
$ogImage = getFullUrl('/assets/images/brand/logo.png');
$contentType = 'trade-classification';
$articleSection = 'Trade';

$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'Trade', 'url' => getFullUrl('/trade')],
    ['name' => 'HS Codes', 'url' => getFullUrl('/trade/hs-codes')],
    ['name' => 'HS Code ' . $codeDisplay, 'url' => $pageUrl]
];

$webPageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    '@id' => $pageUrl . '#webpage',
    'url' => $pageUrl,
    'name' => $pageTitle,
    'description' => $pageDescription,
    'breadcrumb' => ['@id' => $pageUrl . '#breadcrumb'],
    'mainEntity' => ['@id' => $pageUrl . '#term'],
    'about' => ['@id' => $pageUrl . '#term'],
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => 'HAIPULSE',
        'url' => getFullUrl('/')
    ],
    'inLanguage' => 'en-AE'
];

$definedTermSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'DefinedTerm',
    '@id' => $pageUrl . '#term',
    'name' => 'HS Code ' . $codeDisplay,
    'termCode' => $codeDisplay,
    'description' => $descriptionEn,
    'url' => $pageUrl,
    'identifier' => [
        '@type' => 'PropertyValue',
        'propertyID' => 'HS Code',
        'value' => $codeDisplay
    ],
    'inDefinedTermSet' => [
        '@type' => 'DefinedTermSet',
        'name' => 'UAE HS Codes Directory',
        'url' => getFullUrl('/trade/hs-codes')
    ]
];

if ($shortDescAr !== '') {
    $definedTermSchema['alternateName'] = $shortDescAr;
}

if (!empty($codeDetails['parent_code'])) {
    $definedTermSchema['broader'] = [
        '@type' => 'DefinedTerm',
        'termCode' => (string)$codeDetails['parent_code'],
        'url' => getFullUrl('/trade/hs-code/' . rawurlencode((string)$codeDetails['parent_code']))
    ];
}

if (!empty($childCodes)) {
    $childItemList = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        '@id' => $pageUrl . '#children',
        'name' => 'Sub-codes for HS Code ' . $codeDisplay,
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'numberOfItems' => count($childCodes),
        'itemListElement' => []
    ];

    foreach ($childCodes as $index => $child) {
        $childCode = (string)($child['code'] ?? '');
        if ($childCode === '') {
            continue;
        }

        $childDescription = (string)($child['short_desc'] ?? $child['long_desc'] ?? $childCode);
        $childItemList['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => getFullUrl('/trade/hs-code/' . rawurlencode($childCode)),
            'name' => 'HS Code ' . $childCode,
            'description' => $childDescription
        ];
    }
}

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    '@id' => $pageUrl . '#breadcrumb',
    'itemListElement' => []
];

foreach ($breadcrumbs as $index => $breadcrumb) {
    $breadcrumbSchema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $breadcrumb['name'],
        'item' => $breadcrumb['url']
    ];
}

$jsonLdScripts = [
    $webPageSchema,
    $definedTermSchema,
    $breadcrumbSchema
];

if (!empty($childItemList['itemListElement'])) {
    $jsonLdScripts[] = $childItemList;
}

$jsonLdSchema = '';
foreach ($jsonLdScripts as $jsonLdScript) {
    $jsonLdSchema .= '<script type="application/ld+json">' . json_encode($jsonLdScript, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

include __DIR__ . '/../includes/layout/header.php';
?>

<style>
    .hs-detail-page .hs-hero {
        background: linear-gradient(120deg, #0b4cc2 0%, #1466d9 100%);
        color: #fff;
        border-radius: 12px;
        padding: 28px;
    }

    .hs-detail-page .hs-code-label {
        display: inline-block;
        font-family: monospace;
        font-size: 1.05rem;
        letter-spacing: 0.3px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 8px;
        padding: 6px 10px;
        margin-bottom: 10px;
    }

    .hs-detail-page .hs-hero h1 {
        margin: 0 0 10px;
        font-size: 2rem;
    }

    .hs-detail-page .hero-subtitle {
        margin: 0;
        opacity: 0.92;
        line-height: 1.65;
        max-width: 900px;
    }

    .hs-detail-page .meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f5f8ff;
        color: #1f2937;
        border: 1px solid #d9e5ff;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 0.86rem;
        margin-right: 8px;
        margin-bottom: 8px;
    }

    .hs-detail-page .detail-card {
        border: 1px solid #edf0f5;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
    }

    .hs-detail-page .detail-card .card-header {
        background: #fff;
        border-bottom: 1px solid #edf0f5;
        font-weight: 600;
    }

    .hs-detail-page .kv-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px dashed #e8ecf4;
    }

    .hs-detail-page .kv-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .hs-detail-page .kv-key {
        color: #6b7280;
        font-weight: 600;
        min-width: 120px;
    }

    .hs-detail-page .child-code-link {
        display: block;
        border: 1px solid #edf0f5;
        border-radius: 10px;
        padding: 12px;
        text-decoration: none;
        color: inherit;
        transition: all 0.15s ease;
    }

    .hs-detail-page .child-code-link:hover {
        border-color: #cfdcff;
        background: #f8fbff;
    }

    .hs-detail-page .sidebar-btn + .sidebar-btn {
        margin-top: 10px;
    }

    @media (max-width: 991.98px) {
        .hs-detail-page .hs-hero {
            padding: 20px;
        }

        .hs-detail-page .hs-hero h1 {
            font-size: 1.65rem;
        }
    }
</style>

<main id="main-content" class="hs-detail-page py-4">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="<?php echo htmlspecialchars($basePath . '/', ENT_QUOTES, 'UTF-8'); ?>"><span itemprop="name">Home</span></a>
                    <meta itemprop="position" content="1">
                </li>
                <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="<?php echo htmlspecialchars($basePath . '/trade', ENT_QUOTES, 'UTF-8'); ?>"><span itemprop="name">Trade</span></a>
                    <meta itemprop="position" content="2">
                </li>
                <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="<?php echo htmlspecialchars($basePath . '/trade/hs-codes', ENT_QUOTES, 'UTF-8'); ?>"><span itemprop="name">HS Codes</span></a>
                    <meta itemprop="position" content="3">
                </li>
                <li class="breadcrumb-item active" aria-current="page" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name"><?php echo htmlspecialchars($codeDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
                    <meta itemprop="item" content="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <meta itemprop="position" content="4">
                </li>
            </ol>
        </nav>

        <section class="hs-hero mb-4">
            <div class="mb-2">
                <span class="hs-code-label">HS <?php echo htmlspecialchars($codeDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
                <h1>HS Code Details</h1>
            </div>
            <p class="hero-subtitle"><?php echo htmlspecialchars($shortDescEn, ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="hero-subtitle hs-hero-subtitle-ar">
                <?php echo htmlspecialchars($shortDescAr, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </section>

        <div class="mb-3">
            <span class="meta-pill"><i class="fa fa-hashtag"></i> <?php echo intval($codeDetails['level']); ?>-digit</span>
            <span class="meta-pill"><i class="fa fa-percent"></i> Duty: <?php echo htmlspecialchars($dutyRate, ENT_QUOTES, 'UTF-8'); ?>%</span>
            <?php if (!empty($codeDetails['vgn_mat'])): ?>
                <span class="meta-pill"><i class="fa fa-cube"></i> <?php echo htmlspecialchars((string)$codeDetails['vgn_mat'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card detail-card mb-4">
                    <div class="card-header">Code Information</div>
                    <div class="card-body">
                        <div class="kv-row">
                            <div class="kv-key">HS Code</div>
                            <div><code><?php echo htmlspecialchars((string)($codeDetails['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></div>
                        </div>
                        <?php if (!empty($codeDetails['old_code'])): ?>
                        <div class="kv-row">
                            <div class="kv-key">Previous Code</div>
                            <div><code><?php echo htmlspecialchars((string)$codeDetails['old_code'], ENT_QUOTES, 'UTF-8'); ?></code></div>
                        </div>
                        <?php endif; ?>
                        <div class="kv-row">
                            <div class="kv-key">Description (EN)</div>
                            <div><?php echo htmlspecialchars($shortDescEn, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php if (!empty($shortDescAr)): ?>
                        <div class="kv-row">
                            <div class="kv-key">Description (AR)</div>
                            <div class="hs-rtl"><?php echo htmlspecialchars($shortDescAr, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="kv-row">
                            <div class="kv-key">Duty Rate</div>
                            <div><?php echo htmlspecialchars($dutyRate, ENT_QUOTES, 'UTF-8'); ?>%</div>
                        </div>
                        <div class="kv-row">
                            <div class="kv-key">Level</div>
                            <div><?php echo intval($codeDetails['level']); ?> digits</div>
                        </div>
                        <?php if (!empty($codeDetails['vgn_mat'])): ?>
                        <div class="kv-row">
                            <div class="kv-key">Material / VGN</div>
                            <div><?php echo htmlspecialchars((string)$codeDetails['vgn_mat'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($codeDetails['parent_code'])): ?>
                        <div class="kv-row">
                            <div class="kv-key">Parent Code</div>
                            <div>
                                <a href="<?php echo htmlspecialchars($basePath . '/trade/hs-code/' . urlencode($codeDetails['parent_code']), ENT_QUOTES, 'UTF-8'); ?>" class="hs-parent-link">
                                    <?php echo htmlspecialchars((string)$codeDetails['parent_code'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($similarCategoryCodes)): ?>
                <div class="card detail-card mb-4">
                    <div class="card-header">Similar HS Category Codes</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($similarCategoryCodes as $similarCode): ?>
                                <?php
                                $similarCodeValue = (string)($similarCode['code'] ?? '');
                                $similarDesc = (string)($similarCode['short_desc'] ?? $similarCode['long_desc'] ?? '');
                                $sharedCategories = (int)($similarCode['shared_categories'] ?? 0);
                                $matchedCategories = trim((string)($similarCode['matched_categories'] ?? ''));
                                ?>
                                <div class="col-md-6">
                                    <a class="child-code-link h-100" href="<?php echo htmlspecialchars($basePath . '/trade/hs-code/' . urlencode($similarCodeValue), ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="fw-semibold text-primary"><?php echo htmlspecialchars($similarCodeValue, ENT_QUOTES, 'UTF-8'); ?></div>
                                            <small class="text-muted">Lvl <?php echo (int)($similarCode['level'] ?? 0); ?></small>
                                        </div>
                                        <?php if ($similarDesc !== ''): ?>
                                            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($similarDesc, ENT_QUOTES, 'UTF-8'); ?></small>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center mt-2 gap-2">
                                            <small class="text-primary"><i class="fa fa-tags me-1"></i><?php echo $sharedCategories > 0 ? $sharedCategories . ' shared categor' . ($sharedCategories === 1 ? 'y' : 'ies') : 'Similar code family'; ?></small>
                                            <small class="text-muted">View code <i class="fa fa-arrow-right ms-1"></i></small>
                                        </div>
                                        <?php if ($matchedCategories !== ''): ?>
                                            <small class="text-muted d-block mt-2">Matched: <?php echo htmlspecialchars($matchedCategories, ENT_QUOTES, 'UTF-8'); ?></small>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($childCodes)): ?>
                <div class="card detail-card mb-4">
                    <div class="card-header">Sub-codes (<?php echo count($childCodes); ?>)</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($childCodes as $child):
                                $childCode = (string)($child['code'] ?? '');
                                $childDesc = (string)($child['short_desc'] ?? $child['long_desc'] ?? '');
                            ?>
                            <div class="col-md-6">
                                <a class="child-code-link" href="<?php echo htmlspecialchars($basePath . '/trade/hs-code/' . urlencode($childCode), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="fw-semibold text-primary"><?php echo htmlspecialchars($childCode, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if ($childDesc !== ''): ?>
                                        <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($childDesc, ENT_QUOTES, 'UTF-8'); ?></small>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <aside class="col-lg-4">
                <div class="card detail-card mb-3">
                    <div class="card-header">Quick Reference</div>
                    <div class="card-body">
                        <div class="kv-row">
                            <div class="kv-key">Status</div>
                            <div>Active</div>
                        </div>
                        <div class="kv-row">
                            <div class="kv-key">Region</div>
                            <div>UAE / GCC</div>
                        </div>
                        <div class="kv-row">
                            <div class="kv-key">Parent Code</div>
                            <div><?php echo !empty($codeDetails['parent_code']) ? htmlspecialchars((string)$codeDetails['parent_code'], ENT_QUOTES, 'UTF-8') : '-'; ?></div>
                        </div>
                        <div class="kv-row">
                            <div class="kv-key">Code Level</div>
                            <div><?php echo intval($codeDetails['level']); ?> digits</div>
                        </div>
                    </div>
                </div>

                <?php
                    $publicAds = $hsSidebarAds;
                    $publicAdSlot = 'sidebar';
                    $publicAdHeading = 'Trade software recommendation';
                    include __DIR__ . '/../includes/partials/public-ad-slot.php';
                ?>

                <div class="card detail-card mb-3">
                    <div class="card-header">Quick HS Code Search</div>
                    <div class="card-body">
                        <form method="get" action="<?php echo htmlspecialchars($basePath . '/trade/hs-code/', ENT_QUOTES, 'UTF-8'); ?>" class="d-flex gap-2" onsubmit="
                            var code = this.elements['hs_code'].value.trim();
                            if (!code) return false;
                            window.location.href = '<?php echo htmlspecialchars($basePath . '/trade/hs-code/', ENT_QUOTES, 'UTF-8'); ?>' + encodeURIComponent(code);
                            return false;
                        ">
                            <input type="text" name="hs_code" class="form-control" placeholder="Enter HS Code" maxlength="14" pattern="[0-9.]{2,14}" title="2-14 digits or dots" required>
                            <button type="submit" class="btn btn-primary">Go</button>
                        </form>
                    </div>
                </div>

                <div class="card detail-card">
                    <div class="card-header">Quick Actions</div>
                    <div class="card-body">
                        <a href="<?php echo htmlspecialchars($basePath . '/trade/hs-codes?search=' . urlencode($codeDisplay), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary w-100 sidebar-btn">
                            View Related Codes
                        </a>
                        <a href="<?php echo htmlspecialchars($basePath . '/trade/hs-codes', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light w-100 sidebar-btn">
                            HS Code Finder
                        </a>
                        <a href="<?php echo htmlspecialchars($basePath . '/trade/hs-codes', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary w-100 sidebar-btn">
                            Back to Directory
                        </a>
                        <a href="<?php echo htmlspecialchars($basePath . '/listings?keyword=' . urlencode('HS Code ' . $codeDisplay), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary w-100 sidebar-btn">
                            Find Companies
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>


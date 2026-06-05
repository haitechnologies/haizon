<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$aboutStatsCacheTtl = 900;
$aboutStatsCacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haipulse_about_stats_v1.json';
$aboutStats = [
    'total_hs_codes' => 0,
    'total_companies' => 0,
];

if (is_file($aboutStatsCacheFile) && (time() - (int)@filemtime($aboutStatsCacheFile) < $aboutStatsCacheTtl)) {
    $cachedStatsJson = @file_get_contents($aboutStatsCacheFile);
    if ($cachedStatsJson !== false) {
        $decodedStats = json_decode($cachedStatsJson, true);
        if (is_array($decodedStats)) {
            $aboutStats['total_hs_codes'] = (int)($decodedStats['total_hs_codes'] ?? 0);
            $aboutStats['total_companies'] = (int)($decodedStats['total_companies'] ?? 0);
        }
    }
}

if (($aboutStats['total_hs_codes'] === 0 || $aboutStats['total_companies'] === 0) && isset($conn) && $conn instanceof mysqli) {
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM `" . DB::HS_CODES . "`");
    if ($countResult && ($countRow = $countResult->fetch_assoc())) {
        $aboutStats['total_hs_codes'] = (int)($countRow['total'] ?? 0);
    }

    $companiesResult = $conn->query(
        "SELECT COUNT(*) AS total FROM `" . DB::COMPANIES . "` WHERE publish = 1 AND is_active = 1"
    );
    if ($companiesResult && ($companiesRow = $companiesResult->fetch_assoc())) {
        $aboutStats['total_companies'] = (int)($companiesRow['total'] ?? 0);
    }

    @file_put_contents($aboutStatsCacheFile, json_encode($aboutStats, JSON_UNESCAPED_SLASHES));
}

$totalHsCodes = (int)$aboutStats['total_hs_codes'];
$totalCompanies = (int)$aboutStats['total_companies'];

$pageTitle = 'About - HAIPULSE';
$pageDescription = 'Learn about UAE Business Directory - connecting customers with verified businesses across the UAE';
$bodyClass = 'page-about';
$ampHtmlUrl = url('/about/amp');

// Generate JSON-LD structured data for rich results
$aboutSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    'name' => 'About UAE Business Directory',
    'description' => $pageDescription,
    'url' => getFullUrl('/about')
];
$jsonLdSchema = '<script type="application/ld+json">' . json_encode($aboutSchema, JSON_UNESCAPED_SLASHES) . '</script>';

// Add breadcrumb schema
$breadcrumbs = [
    ['name' => 'Home', 'url' => getFullUrl('/')],
    ['name' => 'About', 'url' => getFullUrl('/about')]
];
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../includes/layout/header.php';
?>

<section class="about-hero">
    <div class="container position-relative about-hero-inner">
        <span class="about-badge">About HAIPULSE</span>
        <h1>Built To Make UAE Businesses Easier To Find, Compare, And Trust</h1>
        <p>HAIPULSE is a modern business directory and trade platform helping customers discover verified companies while giving businesses a reliable way to grow their visibility and inbound leads.</p>
        <ol class="breadcrumb about-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url('/'); ?>">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">About</li>
        </ol>
    </div>
</section>

<section class="sptb">
    <div class="container">
        <div class="about-surface p-4 p-lg-5 mb-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h2 class="mb-3">Who We Are</h2>
                    <p class="mb-3 text-muted">We focus on practical discovery: clear categories, location-aware browsing, and trusted business information. Our platform is designed for both B2B and B2C use cases across the UAE market.</p>
                    <p class="mb-0 text-muted">From startups and service providers to established enterprises, HAIPULSE offers structured listings that improve discoverability and help buyers make faster decisions.</p>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="about-kpi">
                                <h4><?php echo $totalHsCodes > 0 ? number_format($totalHsCodes) . '+' : '13,000+'; ?></h4>
                                <p>Total HS Codes</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="about-kpi">
                                <h4>50+</h4>
                                <p>Business Categories</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="about-kpi">
                                <h4><?php echo $totalCompanies > 0 ? number_format($totalCompanies) . '+' : '10,000+'; ?></h4>
                                <p>Total Companies</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="about-kpi">
                                <h4>24/7</h4>
                                <p>Platform Availability</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-title center-block text-center mb-4">
            <h2>Why Businesses Choose HAIPULSE</h2>
            <p class="text-muted mb-0">A professional, performance-focused platform for visibility and trust.</p>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="about-pillar">
                    <span class="about-icon"><i class="fa fa-shield"></i></span>
                    <h5 class="mb-2">Verified Presence</h5>
                    <p class="text-muted mb-0">Structured profiles with essential details to increase buyer confidence.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="about-pillar">
                    <span class="about-icon"><i class="fa fa-map-marker"></i></span>
                    <h5 class="mb-2">Local Discovery</h5>
                    <p class="text-muted mb-0">Location-based browsing across all emirates to capture relevant demand.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="about-pillar">
                    <span class="about-icon"><i class="fa fa-line-chart"></i></span>
                    <h5 class="mb-2">Growth-Oriented</h5>
                    <p class="text-muted mb-0">Designed to improve visibility, lead quality, and digital credibility.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="about-pillar">
                    <span class="about-icon"><i class="fa fa-users"></i></span>
                    <h5 class="mb-2">B2B + B2C Ready</h5>
                    <p class="text-muted mb-0">Useful for professionals, consumers, and procurement teams alike.</p>
                </div>
            </div>
        </div>

        <div class="section-title center-block text-center mb-4">
            <h2>How The Platform Works</h2>
            <p class="text-muted mb-0">Simple steps for businesses to get discovered by the right audience.</p>
        </div>
        <div class="row g-4 about-process mb-5">
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body p-4">
                        <span class="about-step">1</span>
                        <h5 class="mb-2">Create Listing</h5>
                        <p class="text-muted mb-0">Add business profile information, category, services, and contact details.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body p-4">
                        <span class="about-step">2</span>
                        <h5 class="mb-2">Improve Visibility</h5>
                        <p class="text-muted mb-0">Optimize profile quality and appear in relevant local and category searches.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-body p-4">
                        <span class="about-step">3</span>
                        <h5 class="mb-2">Generate Leads</h5>
                        <p class="text-muted mb-0">Receive inquiries from customers actively searching for trusted providers.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="about-cta p-4 p-lg-5 text-center">
            <h3 class="mb-2">Ready To Grow Your Business Presence?</h3>
            <p class="text-muted mb-4">Join HAIPULSE and get listed where customers and companies search first.</p>
            <a href="<?php echo url('/add-business'); ?>" class="btn btn-primary btn-lg me-2">Add Your Business</a>
            <a href="<?php echo url('/contact'); ?>" class="btn btn-outline-primary btn-lg">Contact Our Team</a>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/layout/footer.php'; ?>



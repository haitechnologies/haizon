<?php
/**
 * Page: Trade Portal Landing
 * Route: /trade
 * 
 * Gateway to trade intelligence features including HS codes,
 * import/export data, and business matching.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/PublicAds.php';
require_once __DIR__ . '/../includes/helpers.php';

$publicAdsModel = new PublicAds($conn);
$tradeFeatureAds = $publicAdsModel->getAdsForSlot('trade_feature', [
	'page_type' => 'trade',
	'tags' => ['software', 'trade', 'inventory', 'erp', 'compliance']
], 1);

$pageTitle = "Trade Portal - HS Codes & International Trade | UAE Business Directory";
$pageDescription = "Access comprehensive trade intelligence including 13,000+ HS codes, import/export data, and connect with UAE trading companies.";
$bodyClass = 'page-trade';

$tradePageUrl = getFullUrl('/trade');
$breadcrumbs = [
	['name' => 'Home', 'url' => getFullUrl('/')],
	['name' => 'Trade Portal', 'url' => $tradePageUrl]
];

$tradeSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'WebPage',
	'name' => 'UAE Trade Intelligence Portal',
	'description' => $pageDescription,
	'url' => $tradePageUrl,
	'isPartOf' => [
		'@type' => 'WebSite',
		'name' => 'HAIPULSE',
		'url' => getFullUrl('/')
	],
	'about' => [
		'@type' => 'DefinedTermSet',
		'name' => 'UAE Harmonized System Codes',
		'url' => getFullUrl('/trade/hs-codes')
	]
];

$jsonLdSchema = '<script type="application/ld+json">' . json_encode($tradeSchema, JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../includes/layout/header.php';
?>

<style>
	.trade-page .bannerimg {
		margin: 10px auto 30px;
		border-radius: 20px;
		overflow: hidden;
		width: calc(100% - 24px);
		max-width: 1200px;
	}

	.trade-page .bannerimg .header-text {
		padding-top: 14px;
		padding-bottom: 14px;
	}

	@media (max-width: 768px) {
		.trade-page .bannerimg {
			width: calc(100% - 12px);
			margin-bottom: 22px;
			border-radius: 14px;
		}
	}
</style>

<!--Breadcrumb-->
<div class="trade-page">
<section>
	<div class="bannerimg cover-image bg-background3 sptb-2" data-bs-image-src="assets/images/banners/banner2.jpg">
		<div class="header-text mb-0">
			<div class="container">
				<div class="text-center text-white">
					<h1 class="">UAE Trade Intelligence Portal</h1>
					<ol class="breadcrumb text-center">
						<li class="breadcrumb-item"><a href="<?php echo url('/'); ?>">Home</a></li>
						<li class="breadcrumb-item active text-white" aria-current="page">Trade Portal</li>
					</ol>
				</div>
			</div>
		</div>
	</div>
</section>
<!--/Breadcrumb-->

<!--Trade Portal Overview-->
<section class="sptb">
	<div class="container">
		<div class="section-title center-block text-center">
			<h2>Your Gateway to International Trade Data</h2>
			<p>Access comprehensive trade intelligence including 13,000+ HS codes, import/export data, and connect with UAE trading companies</p>
		</div>
		
		<div class="row">
			<!-- HS Codes Directory -->
			<div class="col-lg-4 col-md-6 col-sm-12 mb-4">
				<a href="<?php echo url('/trade/hs-codes'); ?>" class="text-decoration-none">
					<div class="card overflow-hidden h-100">
						<div class="card-body text-center">
							<div class="feature-icon mb-4">
								<i class="fa fa-archive fs-1 text-primary"></i>
							</div>
							<h4 class="font-weight-semibold mb-3">HS Codes Directory</h4>
							<p class="text-muted mb-3">Browse 13,449 Harmonized System codes with bilingual descriptions and duty rates.</p>
							<span class="btn btn-primary btn-sm">Explore Codes <i class="fa fa-arrow-right ms-2"></i></span>
						</div>
					</div>
				</a>
			</div>

			<!-- Trade Insights -->
			<div class="col-lg-4 col-md-6 col-sm-12 mb-4">
				<a href="<?php echo url('/blog?tag=trade-insights'); ?>" class="text-decoration-none">
					<div class="card overflow-hidden h-100">
						<div class="card-body text-center">
							<div class="feature-icon mb-4">
								<i class="fa fa-bar-chart fs-1 text-secondary"></i>
							</div>
							<h4 class="font-weight-semibold mb-3">Trade Insights</h4>
							<p class="text-muted mb-3">Market intelligence, trends, and analytics for UAE trade.</p>
							<span class="btn btn-secondary btn-sm">View Insights <i class="fa fa-arrow-right ms-2"></i></span>
						</div>
					</div>
				</a>
			</div>

			<!-- Business Matching -->
			<div class="col-lg-4 col-md-6 col-sm-12 mb-4">
				<a href="<?php echo url('/listings?source=trade-matching'); ?>" class="text-decoration-none">
					<div class="card overflow-hidden h-100">
						<div class="card-body text-center">
							<div class="feature-icon mb-4">
								<i class="fa fa-handshake-o fs-1 text-info"></i>
							</div>
							<h4 class="font-weight-semibold mb-3">Business Matching</h4>
							<p class="text-muted mb-3">Connect buyers with suppliers based on HS code requirements.</p>
							<span class="btn btn-info btn-sm text-white">Find Matches <i class="fa fa-arrow-right ms-2"></i></span>
						</div>
					</div>
				</a>
			</div>
		</div>
	</div>
</section>
<!--/Trade Portal Overview-->

<!--Stats Section-->
<section class="sptb bg-white">
	<div class="container">
		<div class="section-title center-block text-center">
			<h2>Trade Portal by the Numbers</h2>
			<p>Comprehensive trade data at your fingertips</p>
		</div>
		<div class="row text-center">
			<div class="col-lg-3 col-md-6 col-sm-6 mb-4">
				<div class="card">
					<div class="card-body">
						<h2 class="counter fs-2 mb-0 text-primary font-weight-bold">13449</h2>
						<p class="text-muted mb-0">HS Codes</p>
					</div>
				</div>
			</div>
			<div class="col-lg-3 col-md-6 col-sm-6 mb-4">
				<div class="card">
					<div class="card-body">
						<h2 class="counter fs-2 mb-0 text-secondary font-weight-bold">26898</h2>
						<p class="text-muted mb-0">Translations</p>
					</div>
				</div>
			</div>
			<div class="col-lg-3 col-md-6 col-sm-6 mb-4">
				<div class="card">
					<div class="card-body">
						<h2 class="fs-2 mb-0 text-info font-weight-bold"><span class="counter">746</span><span class="trade-sup">K+</span></h2>
						<p class="text-muted mb-0">UAE Companies</p>
					</div>
				</div>
			</div>
			<div class="col-lg-3 col-md-6 col-sm-6 mb-4">
				<div class="card">
					<div class="card-body">
						<h2 class="counter fs-2 mb-0 text-success font-weight-bold">2</h2>
						<p class="text-muted mb-0">Languages</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--/Stats Section-->

<section class="sptb trade-ad-section">
	<div class="container">
		<?php
			$publicAds = $tradeFeatureAds;
			$publicAdSlot = 'wide';
			$publicAdHeading = 'Software for importers, exporters, and distributors';
			include __DIR__ . '/../includes/partials/public-ad-slot.php';
		?>
	</div>
</section>

<!--What is HS Code-->
<section class="sptb">
	<div class="container">
		<div class="row">
			<div class="col-lg-6 mb-4">
				<h3 class="mb-4">
					<i class="fa fa-book text-primary me-2"></i>
					What is the Harmonized System (HS)?
				</h3>
				<p class="leading-normal fs-16 mb-4">
					The Harmonized System (HS) is an internationally standardized system of names and numbers 
					for classifying traded products. It was developed by the World Customs Organization (WCO) 
					and is used by over 200 countries.
				</p>
				<p class="leading-normal fs-16">
					HS codes are essential for international trade, helping to determine duties, taxes, 
					and trade statistics. They provide a universal language for customs authorities worldwide.
				</p>
			</div>
			<div class="col-lg-6">
				<h4 class="mb-3">HS Codes Are Used For:</h4>
				<ul class="list-unstyled">
					<li class="mb-2">
						<i class="fa fa-check-circle text-success me-2"></i>
						Customs tariff calculation
					</li>
					<li class="mb-2">
						<i class="fa fa-check-circle text-success me-2"></i>
						International trade statistics
					</li>
					<li class="mb-2">
						<i class="fa fa-check-circle text-success me-2"></i>
						Rules of origin determination
					</li>
					<li class="mb-2">
						<i class="fa fa-check-circle text-success me-2"></i>
						Trade policy monitoring
					</li>
					<li class="mb-2">
						<i class="fa fa-check-circle text-success me-2"></i>
						Import/export documentation
					</li>
					<li class="mb-2">
						<i class="fa fa-check-circle text-success me-2"></i>
						Risk assessment and compliance
					</li>
				</ul>
			</div>
		</div>
	</div>
</section>
<!--/What is HS Code-->

<!--CTA Section-->
<section class="sptb bg-primary">
	<div class="container text-center">
		<div class="row justify-content-center">
			<div class="col-lg-8">
				<h2 class="text-white mb-3">Ready to Explore Trade Data?</h2>
				<p class="text-white-50 fs-18 mb-4">
					Browse our comprehensive HS code directory and connect with UAE trading companies
				</p>
				<div class="d-flex gap-3 justify-content-center flex-wrap">
					<a href="<?php echo url('/trade/hs-codes'); ?>" class="btn btn-white btn-lg">
						<i class="fa fa-search me-2"></i>Browse HS Codes
					</a>
					<a href="<?php echo url('/listings'); ?>" class="btn btn-outline-light btn-lg">
						<i class="fa fa-building me-2"></i>Browse Companies
					</a>
				</div>
			</div>
		</div>
	</div>
</section>
<!--/CTA Section-->

</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>


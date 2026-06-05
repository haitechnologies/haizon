<?php
/**
 * Page: HS Codes Browser
 * Route: /trade/hs-codes
 * 
 * Browse all HS (Harmonized System) trade codes
 * with search, filtering, and hierarchical navigation.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

require_once __DIR__ . '/../classes/frontend/HSCodes.php';
require_once __DIR__ . '/../classes/frontend/Searches.php';

$searchesLogger = new Searches($conn);

// Log HS code search to hai_searches (unified table only)
// This must occur after $search, $isCodeSearch, $parsedCodes, and $totalCodesRaw are set
// so we move the logging logic below, after $totalCodesRaw is computed


$hsCodesModel = new HSCodes($conn);

// ============================================
// HANDLE BACKWARD-COMPATIBLE REDIRECTS
// ============================================
// Redirect old query-param URLs to clean URLs for SEO
if (isset($_GET['level']) && !isset($_GET['page']) && !isset($_GET['search']) && empty($_GET['search'])) {
    $newLevel = intval($_GET['level']);
    if (in_array($newLevel, [2, 4, 6, 8, 10])) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/trade/hs-codes/level/' . $newLevel));
        exit;
    }
}

// ============================================
// GET PARAMETERS
// ============================================
// Optional level filter kept for backward compatibility only.
$routeLevel = isset($GLOBALS['route_params']['hs_level']) ? intval($GLOBALS['route_params']['hs_level']) : null;
$level = null;
if ($routeLevel && in_array($routeLevel, [2, 4, 6, 8, 10])) {
	$level = $routeLevel;
} elseif (isset($_GET['level'])) {
	$candidateLevel = intval($_GET['level']);
	if (in_array($candidateLevel, [2, 4, 6, 8, 10])) {
		$level = $candidateLevel;
	}
}

$lang = $_GET['lang'] ?? 'en';
$search = trim((string)($_GET['search'] ?? ''));
$manualSearchFlag = (string)($_GET['manual_search'] ?? '') === '1';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$maxVisibleCodes = 50;

// Allow simple single/multiple code input: comma, space, semicolon, or newline separated.
$rawParts = preg_split('/[\s,;|]+/', $search);
$parsedCodes = [];
if (is_array($rawParts)) {
	foreach ($rawParts as $part) {
		$candidate = trim((string)$part);
		if ($candidate !== '' && preg_match('/^[0-9]{2,14}(?:\.[0-9]{2})*$/', $candidate)) {
			$parsedCodes[] = $candidate;
		}
	}
}
$parsedCodes = array_values(array_unique($parsedCodes));

$hasLetters = preg_match('/[A-Za-z\x{0600}-\x{06FF}]/u', $search) === 1;
$isCodeSearch = !empty($parsedCodes) && !$hasLetters;

// Load the first 50 records by default instead of rendering the full catalog.
$perPage = 50;

$baseOptions = [
    'lang' => $lang,
	'search' => $isCodeSearch ? '' : $search,
	'codes' => $isCodeSearch ? $parsedCodes : [],
];

if ($level !== null && !$isCodeSearch && $search === '') {
	$baseOptions['level'] = $level;
}

$totalCodesRaw = $hsCodesModel->getCount($baseOptions);

// Log only explicit manual submissions on page 1.
if (!empty($search) && $manualSearchFlag && $page === 1 && isset($_GET['search'])) {
	$userId = null;
	if (function_exists('getFrontendUserId') && isFrontendUserLoggedIn()) {
		$userId = getFrontendUserId();
	}
	$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
	$resultCount = isset($totalCodesRaw) ? (int)$totalCodesRaw : null;
	$searchType = $isCodeSearch ? 'hs_code' : 'manual';
	$searchesLogger->recordSearch($search, $ipAddress, $userId, $resultCount, $searchType);
}
$totalCodes = min($totalCodesRaw, $maxVisibleCodes);
$totalPages = max(1, (int)ceil($totalCodes / $perPage));
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;
$queryLimit = max(0, min($perPage, $maxVisibleCodes - $offset));

$hsCodes = $queryLimit > 0
	? $hsCodesModel->getAll(array_merge($baseOptions, [
		'limit' => $queryLimit,
		'offset' => $offset
	]))
	: [];

$popularHsCodes = [];
try {
	if (isset($conn) && $conn instanceof mysqli) {
		$query = "
			SELECT
				h.id,
				h.code,
				h.old_code,
				h.level,
				COALESCE(NULLIF(TRIM(t_en.short_desc), ''), NULLIF(TRIM(t_en.long_desc), ''), CONCAT('HS Code ', h.code)) AS label,
				COALESCE(NULLIF(TRIM(t_en.long_desc), ''), NULLIF(TRIM(t_en.short_desc), ''), '') AS full_desc,
				COALESCE(NULLIF(TRIM(t_ar.short_desc), ''), NULLIF(TRIM(t_ar.long_desc), ''), '') AS label_ar,
				COALESCE(NULLIF(TRIM(t_ar.long_desc), ''), NULLIF(TRIM(t_ar.short_desc), ''), '') AS full_desc_ar,
				COUNT(DISTINCT chc.category_id) AS category_count
			FROM " . DB::HS_CODES . " h
			LEFT JOIN " . DB::HS_CODE_TEXTS . " t_en ON t_en.hs_code_id = h.id AND t_en.lang = 'en'
			LEFT JOIN " . DB::HS_CODE_TEXTS . " t_ar ON t_ar.hs_code_id = h.id AND t_ar.lang = 'ar'
			LEFT JOIN " . DB::CATEGORY_HS_CODES . " chc ON chc.hs_code_id = h.id
			GROUP BY h.id, h.code, h.old_code, h.level, t_en.short_desc, t_en.long_desc, t_ar.short_desc, t_ar.long_desc
			ORDER BY category_count DESC, h.code ASC
			LIMIT 8
		";
		$result = $conn->query($query);
		if ($result) {
			$popularHsCodes = $result->fetch_all(MYSQLI_ASSOC);
		}
	}
} catch (Throwable $e) {
	error_log('HS Codes page: Failed to load popular HS codes - ' . $e->getMessage());
}

// Enforce exactly up to 8 unique, non-empty HS codes in the popular cards section.
if (!empty($popularHsCodes)) {
	$seenPopularCodes = [];
	$normalizedPopular = [];
	foreach ($popularHsCodes as $row) {
		$codeKey = trim((string)($row['code'] ?? ''));
		if ($codeKey === '' || isset($seenPopularCodes[$codeKey])) {
			continue;
		}
		$seenPopularCodes[$codeKey] = true;
		$normalizedPopular[] = $row;
		if (count($normalizedPopular) >= 8) {
			break;
		}
	}
	$popularHsCodes = $normalizedPopular;
}

$activeFilterText = '';
if ($level !== null) {
	$activeFilterText = ($lang === 'ar' ? 'فلتر المستوى ' : 'Level filter ') . $level;
}

// Page meta
$pageTitle = "HS Codes - International Trade Classification | UAE Business Directory";
$pageDescription = "Browse 13,000+ Harmonized System (HS) codes used in international trade. Find product classifications, duty rates, and related UAE companies.";

$hsCodesPageUrl = getFullUrl('/trade/hs-codes');
$breadcrumbs = [
	['name' => 'Home', 'url' => getFullUrl('/')],
	['name' => 'Trade Portal', 'url' => getFullUrl('/trade')],
	['name' => 'HS Codes', 'url' => $hsCodesPageUrl]
];

$hsCodesCollectionSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'CollectionPage',
	'name' => 'HS Codes Directory',
	'description' => $pageDescription,
	'url' => $hsCodesPageUrl,
	'mainEntity' => [
		'@type' => 'DefinedTermSet',
		'name' => 'Harmonized System Codes',
		'description' => 'Searchable catalog of HS codes used in international trade.',
		'url' => $hsCodesPageUrl
	]
];

$jsonLdSchema = '<script type="application/ld+json">' . json_encode($hsCodesCollectionSchema, JSON_UNESCAPED_SLASHES) . '</script>';
$jsonLdSchema .= "\n" . generateBreadcrumbSchema($breadcrumbs);

include __DIR__ . '/../includes/layout/header.php';
?>

<style>
	.hs-hero {
		background: linear-gradient(135deg, #0b3a78 0%, #0e5cc5 52%, #10a4a5 100%);
		padding: 42px 0;
		color: #fff;
	}
	.hs-hero .hs-hero-panel {
		background: rgba(255, 255, 255, 0.12);
		border: 1px solid rgba(255, 255, 255, 0.24);
		border-radius: 18px;
		padding: 22px;
		backdrop-filter: blur(6px);
	}
	.hs-hero h2 {
		font-size: 2rem;
		font-weight: 800;
		margin-bottom: 10px;
	}
	.hs-hero p {
		opacity: 0.95;
		margin-bottom: 0;
		line-height: 1.65;
	}
	.hs-summary-pills {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 10px;
		margin-top: 18px;
	}
	.hs-summary-pill {
		background: rgba(255, 255, 255, 0.18);
		border: 1px solid rgba(255, 255, 255, 0.25);
		border-radius: 12px;
		padding: 10px 12px;
		font-size: 0.88rem;
	}
	.hs-summary-pill strong {
		display: block;
		font-size: 1rem;
	}
	.hs-browser-card {
		background: #fff;
		border: 1px solid #e2ebf7;
		border-radius: 16px;
		box-shadow: 0 14px 40px rgba(12, 44, 91, 0.07);
	}
	.hs-browser-card .card-body {
		padding: 24px;
	}
	.hs-section-title {
		font-size: 1.2rem;
		font-weight: 800;
		color: #17335c;
	}
	.hs-muted {
		color: #667e99;
		font-size: 0.92rem;
	}
	.hs-level-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
		gap: 12px;
	}
	.hs-level-card {
		display: block;
		padding: 14px;
		border-radius: 14px;
		border: 1px solid #d9e6f8;
		background: #f7fbff;
		text-decoration: none;
		color: #1f3a61;
		transition: all .18s ease;
		height: 100%;
	}
	.hs-level-card.active,
	.hs-level-card:hover {
		transform: translateY(-2px);
		border-color: #7eacec;
		box-shadow: 0 12px 28px rgba(14, 76, 162, 0.14);
		background: #eef5ff;
	}
	.hs-level-code {
		display: inline-block;
		background: #e3efff;
		color: #0b5ed7;
		border-radius: 999px;
		padding: 5px 10px;
		font-weight: 800;
		font-size: 0.8rem;
		margin-bottom: 8px;
	}
	.hs-level-name {
		font-weight: 700;
		font-size: 0.95rem;
		margin-bottom: 6px;
	}
	.hs-level-hint {
		font-size: 0.84rem;
		color: #607896;
		line-height: 1.45;
	}
	.hs-level-total {
		font-size: 0.8rem;
		margin-top: 8px;
		color: #375f90;
	}
	.hs-category-list {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
	}
	.hs-category-chip {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 8px 12px;
		border-radius: 999px;
		background: #f5f8fc;
		border: 1px solid #dbe7f5;
		text-decoration: none;
		color: #29496f;
		font-size: 0.84rem;
		font-weight: 700;
	}
	.hs-category-chip:hover {
		background: #edf4fe;
		border-color: #9fc1ef;
	}
	.hs-results-header {
		padding: 14px 16px;
		border: 1px solid #e2ebf7;
		border-radius: 12px;
		background: #fafcff;
	}
	.hs-result-card {
		border: 1px solid #dce8f7;
		border-radius: 14px;
		transition: all .16s ease;
	}
	.hs-result-card:hover {
		transform: translateY(-2px);
		box-shadow: 0 12px 26px rgba(14, 67, 140, 0.12);
		border-color: #91b6eb;
	}
	.hs-result-card .card-body {
		padding: 16px;
	}
	.hs-result-code {
		font-size: 1.15rem;
		font-weight: 800;
		line-height: 1.2;
	}
	.hs-result-meta {
		font-size: 0.78rem;
		color: #5f7692;
	}
	@media (max-width: 767.98px) {
		.hs-hero {
			padding: 28px 0;
		}
		.hs-hero h2 {
			font-size: 1.5rem;
		}
		.hs-summary-pills {
			grid-template-columns: 1fr;
		}
		.hs-browser-card .card-body {
			padding: 16px;
		}
	}

	.popular-hs-showcase {
		padding: 36px 0 46px;
		background: #f6f9fc;
		border-top: 1px solid #e6edf5;
		border-bottom: 1px solid #e6edf5;
	}
	.popular-hs-shell {
		background: #ffffff;
		border: 1px solid #e2eaf3;
		border-radius: 16px;
		padding: 24px;
		box-shadow: 0 10px 28px rgba(15, 39, 72, 0.08);
	}
	.popular-hs-header {
		display: flex;
		justify-content: space-between;
		align-items: flex-end;
		gap: 16px;
		margin-bottom: 20px;
	}
	.popular-hs-kicker {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-size: 0.76rem;
		font-weight: 700;
		letter-spacing: 0.06em;
		text-transform: uppercase;
		color: #1f5ea8;
		margin-bottom: 8px;
	}
	.popular-hs-title {
		margin: 0;
		font-size: 1.75rem;
		font-weight: 700;
		color: #1c3553;
	}
	.popular-hs-subtitle {
		margin: 8px 0 0;
		max-width: 700px;
		font-size: 0.94rem;
		line-height: 1.6;
		color: #5f7389;
	}
	.popular-hs-header .btn {
		border-radius: 10px;
		padding: 9px 16px;
		font-weight: 700;
		white-space: nowrap;
	}
	.popular-hs-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
		gap: 14px;
	}
	.popular-hs-card {
		display: flex;
		flex-direction: column;
		gap: 12px;
		padding: 16px;
		border-radius: 12px;
		background: #ffffff;
		border: 1px solid #dde7f2;
		box-shadow: 0 4px 14px rgba(26, 58, 92, 0.06);
		text-decoration: none;
		color: inherit;
		transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
		height: 100%;
	}
	.popular-hs-card:hover {
		transform: translateY(-2px);
		border-color: #b7cbe4;
		box-shadow: 0 10px 22px rgba(20, 56, 112, 0.12);
	}
	.popular-hs-card-head {
		display: flex;
		justify-content: space-between;
		gap: 8px;
		align-items: flex-start;
	}
	.popular-hs-code-pill {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 6px 10px;
		border-radius: 999px;
		background: #edf3fb;
		color: #204a7a;
		font-weight: 800;
		font-size: 0.86rem;
	}
	.popular-hs-level {
		font-size: 0.7rem;
		font-weight: 700;
		letter-spacing: 0.05em;
		text-transform: uppercase;
		color: #607c9f;
		background: #f7f9fc;
		border: 1px solid #e0e8f2;
		border-radius: 999px;
		padding: 5px 9px;
	}
	.popular-hs-oldcode {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		font-size: 0.72rem;
		color: #8b5e00;
		background: #fff6df;
		padding: 4px 9px;
		border-radius: 999px;
		width: fit-content;
	}
	.popular-hs-name {
		font-size: 0.96rem;
		font-weight: 700;
		line-height: 1.4;
		color: #243d5a;
	}
	.popular-hs-name-ar {
		font-size: 0.82rem;
		line-height: 1.45;
		color: #5e738d;
		direction: rtl;
		text-align: right;
		padding: 8px 10px;
		background: #f5f8fc;
		border-radius: 10px;
	}
	.popular-hs-desc {
		font-size: 0.84rem;
		line-height: 1.55;
		color: #667b93;
		flex-grow: 1;
	}
	.popular-hs-meta {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 10px;
		padding-top: 9px;
		border-top: 1px solid #e7edf4;
	}
	.popular-hs-tag {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 5px 9px;
		border-radius: 999px;
		background: #eef8f1;
		color: #247445;
		font-size: 0.74rem;
		font-weight: 700;
	}
	.popular-hs-linktext {
		font-size: 0.8rem;
		font-weight: 700;
		color: #1f5ea8;
	}
	@media (max-width: 767.98px) {
		.popular-hs-shell {
			padding: 18px;
			border-radius: 14px;
		}
		.popular-hs-header {
			flex-direction: column;
			align-items: stretch;
		}
		.popular-hs-title {
			font-size: 1.4rem;
		}
		.popular-hs-grid {
			grid-template-columns: 1fr;
		}
	}

	.hs-info-section {
		padding: 56px 0;
	}
	.hs-info-title {
		margin-bottom: 22px;
	}
	.hs-info-lead {
		margin-bottom: 0;
		line-height: 1.8;
		max-width: 96%;
	}
	.hs-info-list li {
		margin-bottom: 12px;
		line-height: 1.6;
	}
	.hs-info-list li:last-child {
		margin-bottom: 0;
	}
	@media (max-width: 767.98px) {
		.hs-info-section {
			padding: 42px 0;
		}
		.hs-info-title {
			margin-bottom: 16px;
		}
		.hs-info-lead {
			max-width: 100%;
			margin-bottom: 10px;
		}
	}
</style>

<section class="hs-hero" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
	<div class="container">
		<div class="row g-3 align-items-stretch">
			<div class="col-12">
				<div class="hs-hero-panel h-100">
					<h2><?php echo $lang === 'ar' ? 'ابحث عن رمز HS الصحيح بسرعة' : 'Find the right HS code faster'; ?></h2>
					<p>
						<?php echo $lang === 'ar'
							? 'استخدم بحث الكلمات أو أدخل رموز متعددة دفعة واحدة للوصول إلى التصنيف المناسب لمنتجك بسرعة.'
							: 'Use keyword search or paste multiple codes at once to quickly find the right product classification.'; ?>
					</p>
				</div>
			</div>
		</div>
	</div>
</section>

<!--HS Codes Browser-->
<section class="sptb" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
	<div class="container">
		<div class="hs-browser-card mb-4">
			<div class="card-body">
				<div class="row g-4">
					<div class="col-12">
						<h3 class="hs-section-title mb-2"><?php echo $lang === 'ar' ? 'بحث ذكي عن رموز HS' : 'Smart HS code search'; ?></h3>
						<p class="hs-muted mb-3"><?php echo $lang === 'ar' ? 'ابحث بالرمز أو بالوصف، ويمكنك إدخال عدة رموز دفعة واحدة.' : 'Search by code or description, and paste multiple codes in one go.'; ?></p>
						<form method="GET" action="<?php echo url('/trade/hs-codes'); ?>" class="row g-3">
							<input type="hidden" name="manual_search" value="1">
							<input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang, ENT_QUOTES); ?>">

							<div class="col-12">
								<label class="form-label fw-semibold"><?php echo $lang === 'ar' ? 'رمز HS أو كلمات مفتاحية' : 'HS code or product keywords'; ?></label>
								<textarea name="search" rows="3" class="form-control form-control-lg" placeholder="<?php echo $lang === 'ar' ? 'مثال: 730890900010, 850440110000 أو steel bolts' : 'Example: 730890900010, 850440110000 or steel bolts'; ?>"><?php echo htmlspecialchars($search, ENT_QUOTES); ?></textarea>
								<small class="text-muted"><?php echo $lang === 'ar' ? 'افصل بين الرموز باستخدام فاصلة أو مسافة أو سطر جديد.' : 'Separate multiple codes with comma, space, or new line.'; ?></small>
							</div>

							<div class="col-md-6 d-grid">
								<button type="submit" class="btn btn-primary btn-lg">
									<i class="fa fa-search me-1"></i> <?php echo $lang === 'ar' ? 'بحث الآن' : 'Search now'; ?>
								</button>
							</div>

							<div class="col-md-6 d-grid">
								<a href="<?php echo url('/trade/hs-codes') . '?lang=' . urlencode($lang); ?>" class="btn btn-outline-secondary btn-lg">
									<i class="fa fa-refresh me-1"></i> <?php echo $lang === 'ar' ? 'إعادة تعيين' : 'Reset'; ?>
								</a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<!-- Results Header -->
		<div class="hs-results-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
			<h3 class="mb-0 hs-section-title">
				<?php if ($isCodeSearch): ?>
					<?php echo $lang === 'ar' ? 'نتائج الرموز المطلوبة' : 'Requested HS Code Results'; ?>
				<?php elseif (!empty($search)): ?>
					<?php echo $lang === 'ar' ? 'نتائج البحث بالكلمات' : 'Keyword Search Results'; ?>
				<?php else: ?>
					<?php echo $lang === 'ar' ? 'جميع رموز HS' : 'All HS Codes'; ?>
				<?php endif; ?>
			</h3>
			<div class="d-flex align-items-center gap-2">
				<span class="badge bg-secondary"><?php echo number_format($totalCodes); ?> <?php echo $lang === 'ar' ? 'رموز' : 'codes'; ?></span>
				<?php if ($level !== null): ?>
					<span class="badge bg-light text-dark"><?php echo $lang === 'ar' ? 'فلتر المستوى ' : 'Level filter '; ?><?php echo $level; ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php if ($isCodeSearch): ?>
			<p class="text-muted mb-3 small">
				<?php echo $lang === 'ar'
					? 'تم إجراء مطابقة مباشرة للرموز المدخلة. إذا لم يظهر رمز، تحقق من تنسيقه.'
					: 'Exact matching is applied for entered codes. If a code is missing, verify its format.'; ?>
			</p>
		<?php elseif (!empty($search)): ?>
			<p class="text-muted mb-3 small">
				<?php echo $lang === 'ar' ? 'يتم البحث عبر جميع مستويات HS لعرض أفضل النتائج.' : 'Search runs across all HS levels for better matches.'; ?>
			</p>
		<?php endif; ?>

		<!-- HS Codes Grid -->
		<?php if (count($hsCodes) > 0): ?>
			<div class="row">
				<?php foreach ($hsCodes as $code): ?>
					<?php
					$codeValue = (string)($code['code'] ?? '');
					$parentCode = (string)($code['parent_code'] ?? '');
					$shortDescValue = (string)($code['short_desc'] ?? $code['long_desc'] ?? '');
					$dutyRateValue = (string)($code['duty_rate'] ?? '');
					?>
					<div class="col-lg-4 col-md-6 mb-4">
						<a href="<?php echo url('/trade/hs-code/' . $codeValue); ?>" class="text-decoration-none">
							<div class="card h-100 hs-result-card">
								<div class="card-body">
									<div class="d-flex align-items-start mb-3">
										<span class="fs-3 me-3 text-primary"><i class="fa fa-cube"></i></span>
										<div class="flex-fill">
											<div class="hs-result-code text-primary mb-1"><?php echo htmlspecialchars($codeValue, ENT_QUOTES); ?></div>
											<div class="hs-result-meta">
												Level <?php echo $code['level']; ?> 
												<?php if ($parentCode !== ''): ?>
													• Parent: <?php echo htmlspecialchars($parentCode, ENT_QUOTES); ?>
												<?php endif; ?>
											</div>
										</div>
									</div>
									
									<?php if ($shortDescValue !== ''): ?>
										<p class="text-dark mb-3 small"><?php echo htmlspecialchars($shortDescValue, ENT_QUOTES); ?></p>
									<?php endif; ?>
									
									<div class="d-flex justify-content-between align-items-center">
										<?php if ($dutyRateValue !== ''): ?>
											<small class="text-muted"><i class="fa fa-percent me-1"></i> <?php echo $lang === 'ar' ? 'الرسوم: ' : 'Duty: '; ?><?php echo htmlspecialchars($dutyRateValue, ENT_QUOTES); ?>%</small>
										<?php else: ?>
											<span></span>
										<?php endif; ?>
										<small class="text-primary fw-semibold"><?php echo $lang === 'ar' ? 'عرض التفاصيل' : 'View details'; ?> <i class="fa fa-arrow-right ms-1"></i></small>
									</div>
								</div>
							</div>
						</a>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Pagination -->
			<?php if ($totalPages > 1): ?>
				<nav aria-label="Page navigation" class="mt-4">
					<ul class="pagination justify-content-center">
						<?php if ($page > 1): ?>
							<li class="page-item">
								<a class="page-link" href="<?php echo url('/trade/hs-codes') . '?lang=' . $lang . '&level=' . $level . ($search ? '&search=' . urlencode($search) : '') . '&page=' . ($page - 1); ?>">
									<i class="fa fa-angle-left"></i> Previous
								</a>
							</li>
						<?php endif; ?>
						
						<?php
						$startPage = max(1, $page - 2);
						$endPage = min($totalPages, $page + 2);
						
						for ($i = $startPage; $i <= $endPage; $i++):
						?>
							<li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
								<a class="page-link" href="<?php echo url('/trade/hs-codes') . '?lang=' . $lang . '&level=' . $level . ($search ? '&search=' . urlencode($search) : '') . '&page=' . $i; ?>">
									<?php echo $i; ?>
								</a>
							</li>
						<?php endfor; ?>
						
						<?php if ($page < $totalPages): ?>
							<li class="page-item">
								<a class="page-link" href="<?php echo url('/trade/hs-codes') . '?lang=' . $lang . '&level=' . $level . ($search ? '&search=' . urlencode($search) : '') . '&page=' . ($page + 1); ?>">
									Next <i class="fa fa-angle-right"></i>
								</a>
							</li>
						<?php endif; ?>
					</ul>
				</nav>
			<?php endif; ?>

		<?php else: ?>
			<div class="card">
				<div class="card-body text-center py-5">
					<div class="fs-1 mb-3">🔍</div>
					<h4><?php echo $lang === 'ar' ? 'لم يتم العثور على نتائج' : 'No Results Found'; ?></h4>
					<?php if ($isCodeSearch): ?>
						<p class="text-muted"><?php echo $lang === 'ar' ? 'تحقق من تنسيق الرمز. مثال صحيح: 730890900010' : 'Please check code format. Valid example: 730890900010'; ?></p>
					<?php else: ?>
						<p class="text-muted"><?php echo $lang === 'ar' ? 'جرب كلمات رئيسية مختلفة أو رمز HS أدق' : 'Try different keywords or a more specific HS code'; ?></p>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

	</div>
</section>
<!--/HS Codes Browser-->

<!--Info Section-->
<section class="hs-info-section bg-primary">
	<div class="container text-white">
		<h3 class="text-white hs-info-title"><?php echo $lang === 'ar' ? '🌍 ما هو نظام HS؟' : '🌍 What is the HS System?'; ?></h3>
		<div class="row">
			<div class="col-lg-6 mb-3">
				<p class="leading-normal hs-info-lead">
					<?php if ($lang === 'ar'): ?>
						النظام المنسق (HS) هو نظام تصنيف دولي موحد للمنتجات التجارية. يستخدمه أكثر من 200 دولة في الجمارك والتجارة الدولية.
					<?php else: ?>
						The Harmonized System (HS) is an international standardized system of names and numbers to classify traded products. 
						It's used by over 200 countries for customs and international trade.
					<?php endif; ?>
				</p>
			</div>
			<div class="col-lg-6">
				<ul class="list-unstyled hs-info-list">
					<li class="mb-2"><i class="fa fa-check-circle me-2"></i> <?php echo $lang === 'ar' ? 'التعريفة الجمركية وحساب الرسوم' : 'Customs tariffs and duty calculation'; ?></li>
					<li class="mb-2"><i class="fa fa-check-circle me-2"></i> <?php echo $lang === 'ar' ? 'إحصائيات التجارة الدولية' : 'International trade statistics'; ?></li>
					<li class="mb-2"><i class="fa fa-check-circle me-2"></i> <?php echo $lang === 'ar' ? 'قواعد المنشأ وتقييم المخاطر' : 'Rules of origin and risk assessment'; ?></li>
					<li class="mb-2"><i class="fa fa-check-circle me-2"></i> <?php echo $lang === 'ar' ? 'الامتثال والتوثيق' : 'Compliance and documentation'; ?></li>
				</ul>
			</div>
		</div>
	</div>
</section>
<!--/Info Section-->


<section class="popular-hs-showcase">
	<div class="container">
		<div class="popular-hs-shell">
			<div class="popular-hs-header">
				<div>
					<div class="popular-hs-kicker"><i class="fa fa-cube"></i> Trade Intelligence</div>
					<h2 class="popular-hs-title">Most Popular HS Codes</h2>
					<p class="popular-hs-subtitle">Explore the HS codes businesses search most often across UAE trade workflows. Each card highlights high-interest classifications with fast access to details, related code history, and market relevance.</p>
				</div>
				<a href="<?php echo url('/trade/hs-codes'); ?>" class="btn btn-primary">
					<i class="fa fa-search me-2"></i>Find Your HS Code
				</a>
			</div>
			<?php if (!empty($popularHsCodes)): ?>
				<div class="popular-hs-grid">
					<?php foreach ($popularHsCodes as $hs): ?>
						<?php
						$code = trim((string)($hs['code'] ?? ''));
						if ($code === '') {
							continue;
						}
						$oldCode = !empty($hs['old_code']) ? htmlspecialchars(trim($hs['old_code']), ENT_QUOTES, 'UTF-8') : '';
						$hsUrl = htmlspecialchars(url('/trade/hs-code/' . $code), ENT_QUOTES, 'UTF-8');
						$label = htmlspecialchars((string)($hs['label'] ?? ''), ENT_QUOTES, 'UTF-8');
						$labelAr = !empty($hs['label_ar']) ? htmlspecialchars((string)($hs['label_ar'] ?? ''), ENT_QUOTES, 'UTF-8') : '';
						$desc = !empty($hs['full_desc']) ? htmlspecialchars(substr($hs['full_desc'], 0, 60), ENT_QUOTES, 'UTF-8') : '';
						$descAr = !empty($hs['full_desc_ar']) ? htmlspecialchars(substr($hs['full_desc_ar'], 0, 60), ENT_QUOTES, 'UTF-8') : '';
						$catCount = (int)($hs['category_count'] ?? 0);
						$codeLevel = (int)($hs['level'] ?? 0);
						$displayDesc = $desc ?: $descAr;
						$isTruncated = (strlen($hs['full_desc'] ?? '') > 60 || strlen($hs['full_desc_ar'] ?? '') > 60);
						?>
						<a href="<?php echo $hsUrl; ?>" class="popular-hs-card" title="<?php echo $label; ?>">
							<div class="popular-hs-card-head">
								<div class="popular-hs-code-pill">
									<i class="fa fa-cube"></i>
									HS <?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>
								</div>
								<?php if ($codeLevel > 0): ?>
									<span class="popular-hs-level">Level <?php echo $codeLevel; ?></span>
								<?php endif; ?>
							</div>
							<?php if ($oldCode): ?>
								<div class="popular-hs-oldcode" title="Previous HS Code">
									<i class="fa fa-history"></i> Old: <?php echo $oldCode; ?>
								</div>
							<?php endif; ?>
							<div class="popular-hs-name"><?php echo $label; ?></div>
							<?php if ($labelAr): ?>
								<div class="popular-hs-name-ar" title="Arabic Name">
									<?php echo $labelAr; ?>
								</div>
							<?php endif; ?>
							<?php if ($displayDesc): ?>
								<div class="popular-hs-desc" title="<?php echo $displayDesc; ?>">
									<?php echo $displayDesc; ?><?php echo $isTruncated ? '...' : ''; ?>
								</div>
							<?php else: ?>
								<div class="popular-hs-desc text-muted">
									<em>View details</em>
								</div>
							<?php endif; ?>
							<div class="popular-hs-meta">
								<?php if ($catCount > 0): ?>
									<span class="popular-hs-tag" title="<?php echo $catCount; ?> categories using this code">
										<i class="fa fa-tag"></i> <?php echo $catCount; ?>
									</span>
								<?php else: ?>
									<span class="popular-hs-tag popular-hs-tag-alt">
										<i class="fa fa-line-chart"></i> Popular lookup
									</span>
								<?php endif; ?>
								<span class="popular-hs-linktext">View details <i class="fa fa-arrow-right ms-1"></i></span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="text-center py-4">
					<a href="<?php echo url('/trade/hs-codes'); ?>" class="btn btn-outline-primary btn-lg">
						<i class="fa fa-search me-2"></i>Browse Complete HS Codes Directory
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<script>
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>


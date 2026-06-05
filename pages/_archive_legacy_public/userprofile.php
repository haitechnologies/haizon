<?php
require_once __DIR__ . '/../config/session.php';
startFrontendSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/SubscriptionTier.php';
require_once __DIR__ . '/../classes/BusinessListingPlan.php';
require_once __DIR__ . '/../classes/frontend/UserSettings.php';
require_once __DIR__ . '/../classes/frontend/Favorites.php';
require_once __DIR__ . '/../classes/frontend/SavedSearches.php';
require_once __DIR__ . '/../classes/frontend/UserSearchHistory.php';

if (empty($_SESSION['frontend_user_id'])) {
	header('Location: ' . url('/login?redirect=' . rawurlencode('/account/profile')));
	exit;
}

$userId = (int)$_SESSION['frontend_user_id'];

$userModel = new UserSettings($conn);
$favoritesModel = new Favorites($conn);
$savedSearchesModel = new SavedSearches($conn);
$searchHistoryModel = new UserSearchHistory($conn);

$flashErrors = [];
$flashSuccess = '';
$allowedTabs = ['overview', 'search', 'listing', 'account'];
$activeTab = (string)($_GET['tab'] ?? 'overview');
if (!in_array($activeTab, $allowedTabs, true)) {
	$activeTab = 'overview';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$profileAction = (string)($_POST['profile_action'] ?? '');
	$activeTab = 'account';

	if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
		$flashErrors[] = 'Security validation failed. Please refresh and try again.';
	} elseif ($profileAction === 'update_profile') {
		$fullName = trim((string)($_POST['full_name'] ?? ''));
		$mobile = trim((string)($_POST['mobile'] ?? ''));

		if ($fullName === '') {
			$flashErrors[] = 'Full name is required.';
		} else {
			$updated = $userModel->updateProfile($userId, [
				'full_name' => $fullName,
				'mobile' => $mobile,
			]);
			if ($updated) {
				$_SESSION['frontend_user_name'] = $fullName;
				$flashSuccess = 'Profile details updated successfully.';
			} else {
				$flashErrors[] = 'Could not update profile right now. Please try again.';
			}
		}
	} elseif ($profileAction === 'change_password') {
		$currentPassword = (string)($_POST['current_password'] ?? '');
		$newPassword = (string)($_POST['new_password'] ?? '');
		$confirmPassword = (string)($_POST['confirm_password'] ?? '');

		if (!$userModel->verifyPassword($userId, $currentPassword)) {
			$flashErrors[] = 'Current password is incorrect.';
		} elseif (strlen($newPassword) < 8) {
			$flashErrors[] = 'New password must be at least 8 characters.';
		} elseif ($newPassword !== $confirmPassword) {
			$flashErrors[] = 'New password and confirmation do not match.';
		} else {
			$changed = $userModel->changePassword($userId, $newPassword);
			if ($changed) {
				$flashSuccess = 'Password changed successfully.';
			} else {
				$flashErrors[] = 'Password update failed. Please try again.';
			}
		}
	}
}

$user = $userModel->getUserInfo($userId);
if (!$user) {
	session_destroy();
	header('Location: ' . url('/login'));
	exit;
}

$userName = trim((string)($user['full_name'] ?? ''));
if ($userName === '') {
	$userName = trim((string)($_SESSION['frontend_user_name'] ?? ''));
}
if ($userName === '') {
	$userName = 'Member';
}

$userInitials = '';
foreach (preg_split('/\s+/', $userName) as $part) {
	if ($part !== '') {
		$userInitials .= strtoupper(substr($part, 0, 1));
	}
	if (strlen($userInitials) >= 2) {
		break;
	}
}
if ($userInitials === '') {
	$userInitials = 'HU';
}

$userTier = SubscriptionTier::getUserTier($userId, $conn);
if ($userTier === SubscriptionTier::TIER_FREE) {
	$userTier = SubscriptionTier::TIER_REGISTERED;
}

$tierLabels = [
	SubscriptionTier::TIER_REGISTERED => 'Registered',
	SubscriptionTier::TIER_PRO => 'Pro',
	SubscriptionTier::TIER_ENTERPRISE => 'Enterprise',
	SubscriptionTier::TIER_FREE => 'Free',
];

$tierDescriptions = [
	SubscriptionTier::TIER_REGISTERED => 'Core search tools, saved searches, and direct messaging for active buyers.',
	SubscriptionTier::TIER_PRO => 'High-volume research tools with contact access, exports, and bulk operations.',
	SubscriptionTier::TIER_ENTERPRISE => 'Full access for teams that need unlimited discovery and priority support.',
	SubscriptionTier::TIER_FREE => 'Starter access for browsing the public directory.',
];

$tierFeatures = SubscriptionTier::getTierFeatures($userTier);

$favorites = [];
try {
	$favorites = $favoritesModel->getUserFavorites($userId);
} catch (Throwable $e) {
	error_log('userprofile favorites load warning: ' . $e->getMessage());
}
$recentFavorites = array_slice($favorites, 0, 3);
$favoritesCount = count($favorites);

$savedSearches = [];
$savedSearchCount = 0;
try {
	$savedSearches = $savedSearchesModel->getUserSearches($userId, ['limit' => 4]);
	$savedSearchCount = $savedSearchesModel->getUserSearchCount($userId);
} catch (Throwable $e) {
	error_log('userprofile saved searches load warning: ' . $e->getMessage());
}

$recentSearches = [];
$frequentSearches = [];
$recentSearchCount = 0;
try {
	$recentSearches = $searchHistoryModel->getUserHistory($userId, ['limit' => 4, 'days' => 90]);
	$frequentSearches = $searchHistoryModel->getFrequentSearches($userId, ['limit' => 4, 'days' => 90]);
	$recentSearchCount = $searchHistoryModel->getTotalSearchCount($userId, 30);
} catch (Throwable $e) {
	error_log('userprofile search history load warning: ' . $e->getMessage());
}

$listingSummary = [
	'total' => 0,
	'active' => 0,
	'live' => 0,
	'draft' => 0,
];
$ownedCompanies = [];

$summarySql = "SELECT
				  COUNT(*) AS total,
				  SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count,
				  SUM(CASE WHEN is_active = 1 AND publish = 1 THEN 1 ELSE 0 END) AS live_count,
				  SUM(CASE WHEN is_active = 0 OR publish = 0 THEN 1 ELSE 0 END) AS draft_count
			   FROM `" . DB::COMPANIES . "`
			   WHERE owner_user_id = ? OR created_by = ?";
try {
	$summaryStmt = $conn->prepare($summarySql);
	if ($summaryStmt) {
		$summaryStmt->bind_param('ii', $userId, $userId);
		$summaryStmt->execute();
		$summaryRow = $summaryStmt->get_result()->fetch_assoc() ?: [];
		$summaryStmt->close();

		$listingSummary['total'] = (int)($summaryRow['total'] ?? 0);
		$listingSummary['active'] = (int)($summaryRow['active_count'] ?? 0);
		$listingSummary['live'] = (int)($summaryRow['live_count'] ?? 0);
		$listingSummary['draft'] = (int)($summaryRow['draft_count'] ?? 0);
	}
} catch (Throwable $e) {
	error_log('userprofile listing summary load warning: ' . $e->getMessage());
}

$companiesSql = "SELECT id, company_name, slug, city, state, verified, is_active, publish, updated_at
				 FROM `" . DB::COMPANIES . "`
				 WHERE owner_user_id = ? OR created_by = ?
				 ORDER BY updated_at DESC, id DESC
				 LIMIT 4";
try {
	$companiesStmt = $conn->prepare($companiesSql);
	if ($companiesStmt) {
		$companiesStmt->bind_param('ii', $userId, $userId);
		$companiesStmt->execute();
		$companiesResult = $companiesStmt->get_result();
		while ($row = $companiesResult->fetch_assoc()) {
			$ownedCompanies[] = $row;
		}
		$companiesStmt->close();
	}
} catch (Throwable $e) {
	error_log('userprofile companies load warning: ' . $e->getMessage());
}

$planDefaults = [
	'free' => [
		'plan_slug' => 'free',
		'plan_name' => 'Free',
		'tagline' => 'Basic company presence for getting discovered in the directory.',
		'max_banners' => 0,
		'max_pages' => 0,
		'max_categories' => 1,
		'max_keywords' => 0,
		'max_brands' => 0,
		'has_iso_accolades' => 0,
		'has_green_badge' => 0,
		'has_verified_mark' => 0,
		'has_video_banner' => 0,
		'has_theme_custom' => 0,
		'has_services_section' => 0,
		'has_priority_ranking' => 0,
		'annual_price' => 0,
	],
	'silver' => [
		'plan_slug' => 'silver',
		'plan_name' => 'Silver',
		'tagline' => 'Start strong with essential growth tools and cleaner visibility.',
		'max_banners' => 1,
		'max_pages' => 1,
		'max_categories' => 3,
		'max_keywords' => 3,
		'max_brands' => 3,
		'has_iso_accolades' => 0,
		'has_green_badge' => 0,
		'has_verified_mark' => 0,
		'has_video_banner' => 0,
		'has_theme_custom' => 0,
		'has_services_section' => 0,
		'has_priority_ranking' => 0,
		'annual_price' => 600,
	],
	'gold' => [
		'plan_slug' => 'gold',
		'plan_name' => 'Gold',
		'tagline' => 'Higher visibility, stronger trust signals, and better ranking power.',
		'max_banners' => 3,
		'max_pages' => 3,
		'max_categories' => 6,
		'max_keywords' => 6,
		'max_brands' => 6,
		'has_iso_accolades' => 1,
		'has_green_badge' => 1,
		'has_verified_mark' => 1,
		'has_video_banner' => 0,
		'has_theme_custom' => 0,
		'has_services_section' => 0,
		'has_priority_ranking' => 1,
		'annual_price' => 1800,
	],
	'platinum' => [
		'plan_slug' => 'platinum',
		'plan_name' => 'Platinum',
		'tagline' => 'Premium exposure with the full set of high-impact presentation tools.',
		'max_banners' => 6,
		'max_pages' => 6,
		'max_categories' => 12,
		'max_keywords' => 12,
		'max_brands' => 12,
		'has_iso_accolades' => 1,
		'has_green_badge' => 1,
		'has_verified_mark' => 1,
		'has_video_banner' => 1,
		'has_theme_custom' => 1,
		'has_services_section' => 1,
		'has_priority_ranking' => 1,
		'annual_price' => 3000,
	],
];

$plans = [];
try {
	$plans = BusinessListingPlan::getAllPlans($conn);
} catch (Throwable $e) {
	$plans = [];
}
foreach ($planDefaults as $slug => $planDefault) {
	$plans[$slug] = array_merge($planDefault, $plans[$slug] ?? []);
}

$primaryCompany = $ownedCompanies[0] ?? null;
$currentPlanSlug = 'free';
$currentSubscription = null;
if ($primaryCompany) {
	try {
		$currentSubscription = BusinessListingPlan::getCompanySubscription($conn, (int)$primaryCompany['id']);
	} catch (Throwable $e) {
		$currentSubscription = null;
	}
	if (!empty($currentSubscription['plan_slug'])) {
		$currentPlanSlug = (string)$currentSubscription['plan_slug'];
	}
}

$currentPlan = $plans[$currentPlanSlug] ?? $plans['free'];
$planOrder = ['free', 'silver', 'gold', 'platinum'];
$currentPlanIndex = array_search($currentPlanSlug, $planOrder, true);
if ($currentPlanIndex === false) {
	$currentPlanIndex = 0;
}
$upgradePlans = [];
foreach ($planOrder as $slug) {
	$slugIndex = array_search($slug, $planOrder, true);
	if ($slugIndex !== false && $slugIndex > $currentPlanIndex && isset($plans[$slug])) {
		$upgradePlans[] = $plans[$slug];
	}
}
$upgradePlans = array_slice($upgradePlans, 0, 2);

$searchCapabilityRows = [
	['label' => 'Results per search', 'value' => number_format((int)($tierFeatures['results_per_search'] ?? 0))],
	['label' => 'Saved searches', 'value' => (int)($tierFeatures['saved_searches'] ?? 0) >= 9999 ? 'Unlimited' : (string)(int)($tierFeatures['saved_searches'] ?? 0)],
	['label' => 'CSV export rows / month', 'value' => (int)($tierFeatures['csv_export_rows_per_month'] ?? 0) >= 9999 ? 'Unlimited' : number_format((int)($tierFeatures['csv_export_rows_per_month'] ?? 0))],
	['label' => 'Advanced filters', 'value' => !empty($tierFeatures['advanced_filters']) ? 'Included' : 'Upgrade required'],
	['label' => 'Contact emails', 'value' => !empty($tierFeatures['contact_emails']) ? 'Included' : 'Upgrade required'],
	['label' => 'Phone numbers', 'value' => !empty($tierFeatures['phone_numbers']) ? 'Included' : 'Upgrade required'],
	['label' => 'Direct messaging', 'value' => !empty($tierFeatures['direct_messaging']) ? 'Included' : 'Upgrade required'],
	['label' => 'Bulk operations', 'value' => !empty($tierFeatures['bulk_operations']) ? 'Included' : 'Upgrade required'],
];

$listingFeatureRows = [
	['label' => 'Promotional banners', 'value' => (string)(int)($currentPlan['max_banners'] ?? 0)],
	['label' => 'Dedicated landing pages', 'value' => (string)(int)($currentPlan['max_pages'] ?? 0)],
	['label' => 'Business categories', 'value' => (string)(int)($currentPlan['max_categories'] ?? 0)],
	['label' => 'SEO keywords', 'value' => (string)(int)($currentPlan['max_keywords'] ?? 0)],
	['label' => 'Brands / products', 'value' => (string)(int)($currentPlan['max_brands'] ?? 0)],
	['label' => 'Priority search ranking', 'value' => !empty($currentPlan['has_priority_ranking']) ? 'Enabled' : 'Not included'],
	['label' => 'Verified business mark', 'value' => !empty($currentPlan['has_verified_mark']) ? 'Enabled' : 'Not included'],
	['label' => 'Dedicated services section', 'value' => !empty($currentPlan['has_services_section']) ? 'Enabled' : 'Not included'],
];

$premiumFeatureFlags = [
	['label' => 'ISO certifications and accolades', 'enabled' => !empty($currentPlan['has_iso_accolades'])],
	['label' => 'Green product badge', 'enabled' => !empty($currentPlan['has_green_badge'])],
	['label' => 'Verified business mark', 'enabled' => !empty($currentPlan['has_verified_mark'])],
	['label' => 'Priority search ranking', 'enabled' => !empty($currentPlan['has_priority_ranking'])],
	['label' => 'Video banner', 'enabled' => !empty($currentPlan['has_video_banner'])],
	['label' => 'Theme customisation', 'enabled' => !empty($currentPlan['has_theme_custom'])],
	['label' => 'Dedicated services section', 'enabled' => !empty($currentPlan['has_services_section'])],
];

$pageTitle = 'My Account - HAIPULSE';
$pageDescription = 'Manage your HAIPULSE account, saved searches, listings, and subscription capabilities from one professional profile workspace.';
$bodyClass = 'page-account-profile';
include __DIR__ . '/../includes/layout/header.php';
?>

<main class="account-shell">
	<div class="container">
		<section class="account-hero">
			<div class="account-hero-body">
				<div class="account-breadcrumb">
					<a href="<?= url('/') ?>">Home</a> / <span>My Account</span>
				</div>

				<div class="account-identity">
					<div class="account-user">
						<div class="account-avatar"><?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?></div>
						<div>
							<h1 class="account-name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></h1>
							<div class="account-subline">
								<span><i class="fa fa-envelope me-1"></i><?= htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8') ?></span>
								<?php if (!empty($user['mobile'])): ?>
									<span><i class="fa fa-phone me-1"></i><?= htmlspecialchars((string)$user['mobile'], ENT_QUOTES, 'UTF-8') ?></span>
								<?php endif; ?>
								<span><i class="fa fa-calendar me-1"></i>Member since <?= htmlspecialchars(dd_((string)($user['created_at'] ?? 'now'), 'd M Y'), ENT_QUOTES, 'UTF-8') ?></span>
							</div>
							<div class="account-badge-row">
								<span class="account-badge"><i class="fa fa-shield"></i><?= !empty($user['email_verified']) ? 'Verified email' : 'Email verification pending' ?></span>
								<span class="account-badge"><i class="fa fa-layer-group"></i><?= htmlspecialchars($tierLabels[$userTier] ?? 'Registered', ENT_QUOTES, 'UTF-8') ?> access</span>
								<?php if ($primaryCompany): ?>
									<span class="account-badge"><i class="fa fa-building"></i><?= htmlspecialchars((string)($currentPlan['plan_name'] ?? 'Free'), ENT_QUOTES, 'UTF-8') ?> listing plan</span>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<div class="account-actions">
						<a href="<?= url('/account/settings') ?>" class="btn btn-light">
							<i class="fa fa-sliders me-1"></i>Settings
						</a>
						<a href="<?= url('/listings') ?>" class="btn btn-outline-light">
							<i class="fa fa-search me-1"></i>Browse Listings
						</a>
						<a href="<?= url('/logout') ?>" class="btn btn-warning">
							<i class="fa fa-sign-out me-1"></i>Logout
						</a>
					</div>
				</div>

				<div class="account-intro">
					<div class="account-panel">
						<h2 class="account-title-md">Your workspace at a glance</h2>
						<p><?= htmlspecialchars($tierDescriptions[$userTier] ?? $tierDescriptions[SubscriptionTier::TIER_REGISTERED], ENT_QUOTES, 'UTF-8') ?></p>
						<div class="mini-kpis">
							<div class="mini-kpi">
								<strong><?= (int)$favoritesCount ?></strong>
								<span>Saved companies</span>
							</div>
							<div class="mini-kpi">
								<strong><?= (int)$savedSearchCount ?></strong>
								<span>Saved searches</span>
							</div>
							<div class="mini-kpi">
								<strong><?= (int)$listingSummary['live'] ?></strong>
								<span>Live listings</span>
							</div>
						</div>
					</div>

					<div class="account-panel">
						<h3 class="account-title-sm">What you can do now</h3>
						<ul class="capability-list">
							<li>
								<span>Run richer searches with up to <?= number_format((int)($tierFeatures['results_per_search'] ?? 0)) ?> results at a time.</span>
								<span class="feature-state">Search</span>
							</li>
							<li>
								<span>Save up to <?= (int)($tierFeatures['saved_searches'] ?? 0) >= 9999 ? 'unlimited' : (int)($tierFeatures['saved_searches'] ?? 0) ?> search setups.</span>
								<span class="feature-state">Organise</span>
							</li>
							<li>
								<span><?= $primaryCompany ? 'Manage your company growth tools from your active listing plan.' : 'Create your first business listing to unlock plan-based visibility tools.' ?></span>
								<span class="feature-state"><?= $primaryCompany ? 'Listings' : 'Next step' ?></span>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</section>

		<section class="workspace-tabs">
			<?php if (!empty($flashSuccess)): ?>
				<div class="alert-inline alert-ok"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
			<?php endif; ?>
			<?php if (!empty($flashErrors)): ?>
				<div class="alert-inline alert-err">
					<?php foreach ($flashErrors as $errorItem): ?>
						<div><?= htmlspecialchars((string)$errorItem, ENT_QUOTES, 'UTF-8') ?></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<nav class="account-nav" aria-label="Logged in account navigation">
				<a href="<?= url('/account/profile?tab=overview') ?>" id="overview-tab" class="account-nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>" aria-current="<?= $activeTab === 'overview' ? 'page' : 'false' ?>">
					<span><i class="fa fa-gauge-high me-1"></i>Overview</span>
					<span class="account-nav-count"><?= (int)$listingSummary['total'] ?></span>
				</a>
				<a href="<?= url('/account/profile?tab=search') ?>" id="search-tab" class="account-nav-link <?= $activeTab === 'search' ? 'active' : '' ?>" aria-current="<?= $activeTab === 'search' ? 'page' : 'false' ?>">
					<span><i class="fa fa-magnifying-glass me-1"></i>Search Tools</span>
					<span class="account-nav-count"><?= (int)$savedSearchCount ?></span>
				</a>
				<a href="<?= url('/account/profile?tab=listing') ?>" id="listing-tab" class="account-nav-link <?= $activeTab === 'listing' ? 'active' : '' ?>" aria-current="<?= $activeTab === 'listing' ? 'page' : 'false' ?>">
					<span><i class="fa fa-building me-1"></i>Listing Plan</span>
					<span class="account-nav-count"><?= (int)$listingSummary['live'] ?></span>
				</a>
				<a href="<?= url('/account/profile?tab=account') ?>" id="account-tab" class="account-nav-link <?= $activeTab === 'account' ? 'active' : '' ?>" aria-current="<?= $activeTab === 'account' ? 'page' : 'false' ?>">
					<span><i class="fa fa-user-gear me-1"></i>Account</span>
				</a>
				<a href="<?= url('/my-favorites') ?>" class="account-nav-link utility-link">
					<span><i class="fa fa-heart me-1"></i>Favorites</span>
					<span class="account-nav-count"><?= (int)$favoritesCount ?></span>
				</a>
				<a href="<?= url('/my-searches') ?>" class="account-nav-link utility-link">
					<span><i class="fa fa-bookmark me-1"></i>Saved</span>
					<span class="account-nav-count"><?= (int)$savedSearchCount ?></span>
				</a>
				<a href="<?= url('/search-history') ?>" class="account-nav-link utility-link">
					<span><i class="fa fa-clock-rotate-left me-1"></i>History</span>
				</a>
				<a href="<?= url('/add-business') ?>" class="account-nav-link utility-link">
					<span><i class="fa fa-plus me-1"></i>Add Listing</span>
				</a>
			</nav>

			<div class="tab-content" id="accountWorkspaceContent">
				<div class="tab-pane fade <?= $activeTab === 'overview' ? 'show active' : '' ?>" id="overview-pane" role="tabpanel" aria-labelledby="overview-tab">
					<div class="row g-4">
						<div class="col-12">
							<div class="workspace-card workspace-card-soft">
								<div class="workspace-title">
									<div>
										<h3 class="account-title-lg">Performance snapshot</h3>
										<p>The most important numbers for your activity on HAIPULSE.</p>
									</div>
								</div>
								<div class="stat-grid">
									<div class="stat-card">
										<strong><?= (int)$listingSummary['total'] ?></strong>
										<span>Total listings</span>
									</div>
									<div class="stat-card">
										<strong><?= (int)$listingSummary['active'] ?></strong>
										<span>Active listings</span>
									</div>
									<div class="stat-card">
										<strong><?= (int)$savedSearchCount ?></strong>
										<span>Saved searches</span>
									</div>
									<div class="stat-card">
										<strong><?= (int)$recentSearchCount ?></strong>
										<span>Searches in 30 days</span>
									</div>
								</div>
							</div>
						</div>

						<div class="col-12">
							<div class="workspace-card h-100">
								<div class="workspace-title">
									<div>
										<h3 class="account-title-md">Recent listings</h3>
										<p><?= $primaryCompany ? 'Your latest company records and publication status.' : 'Create a business listing to start building your presence.' ?></p>
									</div>
									<a href="<?= url('/add-business') ?>">Add listing</a>
								</div>

								<?php if (empty($ownedCompanies)): ?>
									<div class="empty-state">
										<h4 class="account-title-xs">No listings yet</h4>
										<p class="muted-note">Start with a free company profile, then upgrade when you want more keywords, categories, banners, and ranking tools.</p>
										<a href="<?= url('/add-business') ?>" class="btn btn-primary mt-2">Create your first listing</a>
									</div>
								<?php else: ?>
									<ul class="activity-list">
										<?php foreach ($ownedCompanies as $company): ?>
											<?php
											$companyStatusClass = (!empty($company['is_active']) && !empty($company['publish'])) ? 'status-pill-live' : 'status-pill-draft';
											$companyStatusText = (!empty($company['is_active']) && !empty($company['publish'])) ? 'Live' : 'Needs attention';
											$companyLocation = trim((string)(($company['city'] ?? '') . ', ' . ($company['state'] ?? '')), ', ');
											?>
											<li class="activity-item">
												<div>
													<a href="<?= url('/company/' . rawurlencode((string)$company['slug'])) ?>"><?= htmlspecialchars((string)$company['company_name'], ENT_QUOTES, 'UTF-8') ?></a>
													<div class="activity-meta">
															<?= $companyLocation !== '' ? htmlspecialchars($companyLocation, ENT_QUOTES, 'UTF-8') . ' Â· ' : '' ?>Updated <?= htmlspecialchars(dd_((string)$company['updated_at'], 'd M Y'), ENT_QUOTES, 'UTF-8') ?>
													</div>
												</div>
												<span class="status-pill <?= $companyStatusClass ?>"><?= $companyStatusText ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

				<div class="tab-pane fade <?= $activeTab === 'search' ? 'show active' : '' ?>" id="search-pane" role="tabpanel" aria-labelledby="search-tab">
					<div class="row g-4">
						<div class="col-xl-5">
							<div class="workspace-card h-100">
								<div class="workspace-title">
									<div>
										<h3 class="account-title-md">Your directory access</h3>
										<p><?= htmlspecialchars($tierLabels[$userTier] ?? 'Registered', ENT_QUOTES, 'UTF-8') ?> tier capabilities for research, outreach, and exports.</p>
									</div>
									<span class="status-pill status-pill-live"><?= htmlspecialchars($tierLabels[$userTier] ?? 'Registered', ENT_QUOTES, 'UTF-8') ?></span>
								</div>

								<ul class="feature-list">
									<?php foreach ($searchCapabilityRows as $capability): ?>
										<?php $isUpgrade = stripos((string)$capability['value'], 'upgrade') !== false || (string)$capability['value'] === '0'; ?>
										<li>
											<span><?= htmlspecialchars((string)$capability['label'], ENT_QUOTES, 'UTF-8') ?></span>
											<span class="feature-state<?= $isUpgrade ? ' is-off' : '' ?>"><?= htmlspecialchars((string)$capability['value'], ENT_QUOTES, 'UTF-8') ?></span>
										</li>
									<?php endforeach; ?>
								</ul>

								<div class="d-flex flex-wrap gap-2 mt-4">
									<a href="<?= url('/listings') ?>" class="btn btn-primary">Search directory</a>
									<a href="<?= url('/pricing') ?>" class="btn btn-outline-secondary">Upgrade access</a>
								</div>
							</div>
						</div>

						<div class="col-xl-7">
							<div class="workspace-card h-100">
								<div class="workspace-title">
									<div>
										<h3 class="account-title-md">Search habits and saved work</h3>
										<p>Your repeated searches and frequently visited topics help you move faster.</p>
									</div>
								</div>

								<div class="capability-grid">
									<div class="capability-card">
										<strong><?= (int)$savedSearchCount ?></strong>
										<span>Saved search setups</span>
									</div>
									<div class="capability-card">
										<strong><?= (int)$recentSearchCount ?></strong>
										<span>Searches in last 30 days</span>
									</div>
									<div class="capability-card">
										<strong><?= (int)$favoritesCount ?></strong>
										<span>Saved company profiles</span>
									</div>
									<div class="capability-card">
										<strong><?= !empty($tierFeatures['csv_export_rows_per_month']) ? ((int)$tierFeatures['csv_export_rows_per_month'] >= 9999 ? 'âˆž' : number_format((int)$tierFeatures['csv_export_rows_per_month'])) : '0' ?></strong>
										<span>Export rows / month</span>
									</div>
								</div>

								<div class="row g-4 mt-1">
									<div class="col-md-6">
										<h4 class="account-title-xs">Recent searches</h4>
										<?php if (empty($recentSearches)): ?>
											<div class="empty-state">
												<p class="muted-note">Your recent search activity will appear here once you start exploring the directory.</p>
											</div>
										<?php else: ?>
											<ul class="activity-list">
												<?php foreach ($recentSearches as $search): ?>
													<li class="activity-item">
														<div>
															<a href="<?= url('/listings?keyword=' . rawurlencode((string)$search['search_query'])) ?>"><?= htmlspecialchars((string)$search['search_query'], ENT_QUOTES, 'UTF-8') ?></a>
															<div class="activity-meta"><?= (int)($search['result_count'] ?? 0) ?> results Â· <?= htmlspecialchars(dd_((string)$search['created_at'], 'd M Y'), ENT_QUOTES, 'UTF-8') ?></div>
														</div>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</div>

									<div class="col-md-6">
										<h4 class="account-title-xs">Most frequent topics</h4>
										<?php if (empty($frequentSearches)): ?>
											<div class="empty-state">
												<p class="muted-note">Once you build a history, repeated topics will be grouped here for quick reruns.</p>
											</div>
										<?php else: ?>
											<ul class="activity-list">
												<?php foreach ($frequentSearches as $search): ?>
													<li class="activity-item">
														<div>
															<a href="<?= url('/listings?keyword=' . rawurlencode((string)$search['search_query'])) ?>"><?= htmlspecialchars((string)$search['search_query'], ENT_QUOTES, 'UTF-8') ?></a>
															<div class="activity-meta"><?= (int)($search['search_count'] ?? 0) ?> runs Â· last used <?= htmlspecialchars(dd_((string)$search['last_searched'], 'd M Y'), ENT_QUOTES, 'UTF-8') ?></div>
														</div>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="tab-pane fade <?= $activeTab === 'listing' ? 'show active' : '' ?>" id="listing-pane" role="tabpanel" aria-labelledby="listing-tab">
					<div class="row g-4">
						<div class="col-12">
							<div class="workspace-card workspace-card-soft">
								<div class="workspace-title">
									<div>
										<h3 class="account-title-lg">Business listing capabilities</h3>
										<p>These tools are based on your pricing plan and control how much visibility your company profile can unlock.</p>
									</div>
									<a href="<?= url('/pricing') ?>">View all plans</a>
								</div>

								<?php if (!$primaryCompany): ?>
									<div class="empty-state">
										<h4 class="account-title-xs">No company linked yet</h4>
										<p class="muted-note">Create a listing first. After that, you can manage banners, keywords, services, trust badges, and upgrade paths from this area.</p>
										<div class="d-flex flex-wrap gap-2 mt-3">
											<a href="<?= url('/add-business') ?>" class="btn btn-primary">Create listing</a>
											<a href="<?= url('/pricing') ?>" class="btn btn-outline-secondary">Review pricing</a>
										</div>
									</div>
								<?php else: ?>
									<div class="plan-summary">
										<div class="plan-hero-card">
											<div class="status-pill status-pill-on plan-status-pill">
												<?= htmlspecialchars((string)($currentPlan['plan_name'] ?? 'Free'), ENT_QUOTES, 'UTF-8') ?> Plan
											</div>
											<h4 class="account-title-xl"><?= htmlspecialchars((string)$primaryCompany['company_name'], ENT_QUOTES, 'UTF-8') ?></h4>
											<p class="plan-tagline"><?= htmlspecialchars((string)($currentPlan['tagline'] ?? 'Visibility tools for your business listing.'), ENT_QUOTES, 'UTF-8') ?></p>
											<div class="price"><?= !empty($currentPlan['annual_price']) ? 'AED ' . number_format((float)$currentPlan['annual_price']) . ' / year' : 'Free forever' ?></div>
											<p class="plan-status-note">
												<?= !empty($currentSubscription['status']) ? 'Subscription status: ' . htmlspecialchars(ucfirst((string)$currentSubscription['status']), ENT_QUOTES, 'UTF-8') . '.' : 'You are currently on the free listing level.' ?>
											</p>
										</div>

										<div class="workspace-card workspace-card-compact">
											<h4 class="account-title-xs">Included tools</h4>
											<ul class="feature-list">
												<?php foreach ($listingFeatureRows as $feature): ?>
													<?php $isOff = in_array((string)$feature['value'], ['0', 'Not included'], true); ?>
													<li>
														<span><?= htmlspecialchars((string)$feature['label'], ENT_QUOTES, 'UTF-8') ?></span>
														<span class="feature-state<?= $isOff ? ' is-off' : '' ?>"><?= htmlspecialchars((string)$feature['value'], ENT_QUOTES, 'UTF-8') ?></span>
													</li>
												<?php endforeach; ?>
											</ul>
										</div>
									</div>

									<div class="plan-limit-grid mt-4">
										<div class="plan-limit-card">
											<strong><?= (int)($currentPlan['max_categories'] ?? 0) ?></strong>
											<span>Categories</span>
										</div>
										<div class="plan-limit-card">
											<strong><?= (int)($currentPlan['max_keywords'] ?? 0) ?></strong>
											<span>SEO keywords</span>
										</div>
										<div class="plan-limit-card">
											<strong><?= (int)($currentPlan['max_banners'] ?? 0) ?></strong>
											<span>Promotional banners</span>
										</div>
										<div class="plan-limit-card">
											<strong><?= (int)($currentPlan['max_brands'] ?? 0) ?></strong>
											<span>Brands / products</span>
										</div>
									</div>

									<div class="row g-4 mt-1">
										<div class="col-lg-6">
											<div class="workspace-card h-100">
												<div class="workspace-title">
													<div>
														<h4 class="account-title-xs">Premium visibility checklist</h4>
														<p>Features taken from your current pricing plan.</p>
													</div>
												</div>
												<ul class="feature-list">
													<?php foreach ($premiumFeatureFlags as $feature): ?>
														<li>
															<span><?= htmlspecialchars((string)$feature['label'], ENT_QUOTES, 'UTF-8') ?></span>
															<span class="status-pill <?= $feature['enabled'] ? 'status-pill-on' : 'status-pill-off' ?>"><?= $feature['enabled'] ? 'Included' : 'Upgrade needed' ?></span>
														</li>
													<?php endforeach; ?>
												</ul>
											</div>
										</div>

										<div class="col-lg-6">
											<div class="workspace-card h-100">
												<div class="workspace-title">
													<div>
														<h4 class="account-title-xs">Next best upgrades</h4>
														<p>Recommended plans based on the features above.</p>
													</div>
												</div>

												<?php if (empty($upgradePlans)): ?>
													<div class="empty-state">
														<p class="muted-note">You are already on the highest published business plan.</p>
													</div>
												<?php else: ?>
													<div class="upgrade-grid">
														<?php foreach ($upgradePlans as $plan): ?>
															<div class="upgrade-card">
																<a href="<?= url('/pricing#plan-' . rawurlencode((string)$plan['plan_slug'])) ?>"><?= htmlspecialchars((string)$plan['plan_name'], ENT_QUOTES, 'UTF-8') ?></a>
																<p><?= htmlspecialchars((string)($plan['tagline'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
																<div class="activity-meta">AED <?= number_format((float)($plan['annual_price'] ?? 0)) ?> / year</div>
															</div>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>

												<div class="d-flex flex-wrap gap-2 mt-4">
													<a href="<?= url('/pricing') ?>" class="btn btn-primary">Compare plans</a>
													<a href="<?= url('/add-business') ?>" class="btn btn-outline-secondary">Manage listing</a>
												</div>
											</div>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

				<div class="tab-pane fade <?= $activeTab === 'account' ? 'show active' : '' ?>" id="account-pane" role="tabpanel" aria-labelledby="account-tab">
					<div class="row g-4">
						<div class="col-lg-5">
							<div class="workspace-card h-100">
								<div class="workspace-title">
									<div>
										<h3 class="account-title-md">Profile and security</h3>
										<p>Keep your account details accurate and secure.</p>
									</div>
								</div>

								<ul class="feature-list">
									<li>
										<span>Full name</span>
										<span class="feature-state"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
									</li>
									<li>
										<span>Email status</span>
										<span class="feature-state<?= empty($user['email_verified']) ? ' is-off' : '' ?>"><?= !empty($user['email_verified']) ? 'Verified' : 'Pending verification' ?></span>
									</li>
									<li>
										<span>Mobile</span>
										<span class="feature-state<?= empty($user['mobile']) ? ' is-off' : '' ?>"><?= !empty($user['mobile']) ? htmlspecialchars((string)$user['mobile'], ENT_QUOTES, 'UTF-8') : 'Not added' ?></span>
									</li>
									<li>
										<span>Primary access tier</span>
										<span class="feature-state"><?= htmlspecialchars($tierLabels[$userTier] ?? 'Registered', ENT_QUOTES, 'UTF-8') ?></span>
									</li>
								</ul>

								<div class="d-flex flex-wrap gap-2 mt-4">
									<a href="<?= url('/account/settings') ?>" class="btn btn-primary">Open settings</a>
									<a href="<?= url('/email-preferences') ?>" class="btn btn-outline-secondary">Email preferences</a>
								</div>
							</div>
						</div>

						<div class="col-lg-7">
							<div class="workspace-card h-100">
								<div class="workspace-title">
									<div>
										<h3 class="account-title-md">Account shortcuts</h3>
										<p>Everything relevant to your public-facing account area in one place.</p>
									</div>
								</div>

								<div class="account-link-grid">
									<a href="<?= url('/account/profile?tab=overview') ?>">
										Account overview
										<small>Open your main profile workspace and listing summaries.</small>
									</a>
									<a href="<?= url('/my-favorites') ?>">
										Favorites
										<small>Review shortlisted companies and keep notes on each one.</small>
									</a>
									<a href="<?= url('/my-searches') ?>">
										Saved searches
										<small>Reuse search setups and email alert preferences quickly.</small>
									</a>
									<a href="<?= url('/search-history') ?>">
										Search history
										<small>Repeat proven searches and inspect recent discovery work.</small>
									</a>
									<a href="<?= url('/pricing') ?>">
										Pricing plans
										<small>See which features are available at Silver, Gold, and Platinum.</small>
									</a>
									<a href="<?= url('/logout') ?>">
										Logout
										<small>End this session safely on the public website.</small>
									</a>
								</div>

								<div class="workspace-card workspace-card-compact workspace-card-compact-soft mt-4">
									<h4 class="account-title-xs">Change password</h4>
									<form method="post" class="profile-form">
										<?= csrf_field() ?>
										<input type="hidden" name="profile_action" value="change_password">
										<div class="row g-3">
											<div class="col-md-4">
												<label for="profile-current-password">Current password</label>
												<input id="profile-current-password" class="form-control" type="password" name="current_password" required>
											</div>
											<div class="col-md-4">
												<label for="profile-new-password">New password</label>
												<input id="profile-new-password" class="form-control" type="password" name="new_password" minlength="8" required>
											</div>
											<div class="col-md-4">
												<label for="profile-confirm-password">Confirm password</label>
												<input id="profile-confirm-password" class="form-control" type="password" name="confirm_password" minlength="8" required>
											</div>
										</div>
										<button class="btn btn-outline-dark mt-3" type="submit">Update password</button>
									</form>
								</div>

								<div class="workspace-card workspace-card-compact workspace-card-compact-soft mt-4">
									<h4 class="account-title-xs">Saved companies preview</h4>
									<?php if (empty($recentFavorites)): ?>
										<p class="muted-note">You have not saved any companies yet. Use the favorites heart on listing cards to build a shortlist.</p>
									<?php else: ?>
										<ul class="activity-list">
											<?php foreach ($recentFavorites as $favorite): ?>
												<li class="activity-item">
													<div>
														<a href="<?= url('/company/' . rawurlencode((string)$favorite['slug'])) ?>"><?= htmlspecialchars((string)$favorite['company_name'], ENT_QUOTES, 'UTF-8') ?></a>
														<div class="activity-meta"><?= htmlspecialchars((string)($favorite['category_name'] ?? 'Business'), ENT_QUOTES, 'UTF-8') ?></div>
													</div>
													<span class="feature-state"><?= htmlspecialchars(dd_((string)$favorite['saved_at'], 'd M Y'), ENT_QUOTES, 'UTF-8') ?></span>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>


<?php
/**
 * Page: User Dashboard (NEW DESIGN)
 * Route: /dashboard
 * Description: User account dashboard with listings and statistics
 * Author: Development Team
 * Created: February 28, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/Favorites.php';
require_once __DIR__ . '/../classes/frontend/SavedSearches.php';
require_once __DIR__ . '/../classes/frontend/UserSearchHistory.php';

// Legacy public route hardening: never expose /dashboard to frontend users.
header('Location: ' . url('/account/profile'), true, 301);
exit;

// ============================================
// SECTION 2: CHECK AUTHENTICATION
// ============================================
startFrontendSession();
if (!isset($_SESSION['frontend_user_id'])) {
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/login'));
    exit;
}

$userId = $_SESSION['frontend_user_id'];

// ============================================
// SECTION 3: GET USER INFO
// ============================================
$userQuery = "SELECT first_name, last_name, email FROM `" . DB::FRONTEND_USERS . "` WHERE id = ? LIMIT 1";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    session_destroy();
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/login'));
    exit;
}

$userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

// ============================================
// SECTION 4: GET DASHBOARD STATISTICS
// ============================================

// Count active listings
$listingsQuery = "SELECT COUNT(*) as count FROM `" . DB::COMPANIES . "` WHERE created_by = ? AND is_active = 1";
$listingsStmt = $conn->prepare($listingsQuery);
$listingsStmt->bind_param('i', $userId);
$listingsStmt->execute();
$listingsResult = $listingsStmt->get_result();
$listingsCount = $listingsResult->fetch_assoc()['count'] ?? 0;
$listingsStmt->close();

// Count pending listings
$pendingQuery = "SELECT COUNT(*) as count FROM `" . DB::COMPANIES . "` WHERE created_by = ? AND is_active = 0";
$pendingStmt = $conn->prepare($pendingQuery);
$pendingStmt->bind_param('i', $userId);
$pendingStmt->execute();
$pendingResult = $pendingStmt->get_result();
$pendingCount = $pendingResult->fetch_assoc()['count'] ?? 0;
$pendingStmt->close();

// Get user's companies
$companiesQuery = "
    SELECT id, company_name, slug, is_active, publish, updated_at 
    FROM `" . DB::COMPANIES . "` 
    WHERE created_by = ? 
    ORDER BY updated_at DESC 
    LIMIT 5
";
$companiesStmt = $conn->prepare($companiesQuery);
$companiesStmt->bind_param('i', $userId);
$companiesStmt->execute();
$companiesResult = $companiesStmt->get_result();
$companies = [];
while ($row = $companiesResult->fetch_assoc()) {
    $companies[] = $row;
}
$companiesStmt->close();

// Count recent inquiries (last 30 days)
$inquiriesQuery = "
    SELECT COUNT(*) as count 
    FROM `" . DB::INQUIRIES . "` 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$inquiriesResult = $conn->query($inquiriesQuery);
$inquiriesCount = $inquiriesResult->fetch_assoc()['count'] ?? 0;

// ============================================
// SECTION 5: GET USER FAVORITES
// ============================================
$favorites = new Favorites($conn);
$userFavorites = $favorites->getUserFavorites($userId, ['limit' => 5]);
$totalFavorites = count($userFavorites);

// ============================================
// SECTION 6: GET SAVED SEARCHES
// ============================================
$savedSearches = new SavedSearches($conn);
$userSavedSearches = $savedSearches->getUserSearches($userId, ['limit' => 5]);
$totalSavedSearches = $savedSearches->getUserSearchCount($userId);

// ============================================
// SECTION 7: GET SEARCH HISTORY
// ============================================
$searchHistory = new UserSearchHistory($conn);
$recentSearches = $searchHistory->getUserHistory($userId, ['limit' => 5, 'days' => 30]);
$totalSearches = $searchHistory->getTotalSearchCount($userId, 30);

$pageTitle = 'Dashboard - UAE Business Directory';
$pageDescription = 'Manage your business listings and view statistics.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow">
      <div class="section-head">
        <h1 class="dash-title">Dashboard</h1>
        <span class="muted">Welcome back, <?php echo htmlspecialchars($userName ?: 'User', ENT_QUOTES, 'UTF-8'); ?></span>
      </div>

      <section class="kpi-grid dash-gap-bottom">
        <article class="card-ui kpi">
          <span class="value"><?php echo $listingsCount; ?></span>
          <span class="label">Active Listings</span>
        </article>
        <article class="card-ui kpi">
          <span class="value"><?php echo $pendingCount; ?></span>
          <span class="label">Pending Review</span>
        </article>
        <article class="card-ui kpi">
          <span class="value"><?php echo $inquiriesCount; ?></span>
          <span class="label">Inquiries (30d)</span>
        </article>
        <article class="card-ui kpi">
          <span class="value"><?php echo $totalFavorites; ?></span>
          <span class="label">Favorites</span>
        </article>
        <article class="card-ui kpi">
          <span class="value"><?php echo $totalSavedSearches; ?></span>
          <span class="label">Saved Searches</span>
        </article>
        <article class="card-ui kpi">
          <span class="value"><?php echo $totalSearches; ?></span>
          <span class="label">Recent Searches</span>
        </article>
      </section>

      <div class="list-layout">
        <aside>
          <article class="card-ui dashboard-box dash-gap-bottom">
            <h3 class="dash-h3">Quick Actions</h3>
            <p><a class="btn-ui btn-primary-ui dash-btn-full" href="<?php echo htmlspecialchars(url('/add-business'), ENT_QUOTES, 'UTF-8'); ?>">Create Listing</a></p>
            <p><a class="btn-ui btn-light-ui dash-btn-full" href="<?php echo htmlspecialchars(url('/my-favorites'), ENT_QUOTES, 'UTF-8'); ?>">My Favorites</a></p>
            <p><a class="btn-ui btn-light-ui dash-btn-full" href="<?php echo htmlspecialchars(url('/my-searches'), ENT_QUOTES, 'UTF-8'); ?>">Saved Searches</a></p>
            <p><a class="btn-ui btn-light-ui dash-btn-full" href="<?php echo htmlspecialchars(url('/search-history'), ENT_QUOTES, 'UTF-8'); ?>">Search History</a></p>
            <p><a class="btn-ui btn-light-ui dash-btn-full" href="<?php echo htmlspecialchars(url('/account/settings'), ENT_QUOTES, 'UTF-8'); ?>">User Settings</a></p>
            <p><a class="btn-ui btn-light-ui dash-btn-full" href="<?php echo htmlspecialchars(url('/contact'), ENT_QUOTES, 'UTF-8'); ?>">Contact Support</a></p>
            <p><a class="btn-ui btn-light-ui dash-btn-full" href="<?php echo htmlspecialchars(url('/logout'), ENT_QUOTES, 'UTF-8'); ?>">Logout</a></p>
          </article>

          <article class="card-ui dashboard-box">
            <h3 class="dash-h3">Account Info</h3>
            <p class="meta-line">
              Email:
              <?php if (!empty($user['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></a>
              <?php else: ?>
                N/A
              <?php endif; ?>
            </p>
            <p class="meta-line">Name: <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></p>
          </article>
        </aside>

        <section>
          <article class="card-ui dashboard-box dash-gap-bottom">
            <h3 class="dash-h3">Your Listings</h3>
            <?php if (empty($companies)): ?>
              <p class="muted">You haven't added any business listings yet.</p>
              <p class="dash-top-sm"><a class="btn-ui btn-primary-ui" href="<?php echo htmlspecialchars(url('/add-business'), ENT_QUOTES, 'UTF-8'); ?>">Add Your First Listing</a></p>
            <?php else: ?>
              <?php foreach ($companies as $company): ?>
                <div class="dash-item-row">
                  <p class="dash-zero">
                    <strong><?php echo htmlspecialchars($company['company_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if ($company['is_active'] && $company['publish']): ?>
                      <span class="pill dash-pill-active">Active</span>
                    <?php elseif ($company['is_active']): ?>
                      <span class="pill dash-pill-unpublished">Unpublished</span>
                    <?php else: ?>
                      <span class="pill dash-pill-inactive">Inactive</span>
                    <?php endif; ?>
                  </p>
                  <p class="muted dash-meta-sm">
                    Last updated <?php echo timeAgo($company['updated_at']); ?>
                    · <a href="<?php echo htmlspecialchars(url('/company/' . rawurlencode((string)$company['slug'])), ENT_QUOTES, 'UTF-8'); ?>">View</a>
                  </p>
                </div>
              <?php endforeach; ?>
              <?php if (($listingsCount + $pendingCount) > 5): ?>
                <p class="dash-top-md"><a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">View All Listings</a></p>
              <?php endif; ?>
            <?php endif; ?>
          </article>

          <article class="card-ui dashboard-box">
            <h3 class="dash-h3">Your Favorites (<?php echo $totalFavorites; ?>)</h3>
            <?php if (empty($userFavorites)): ?>
              <p class="muted">You haven't saved any favorites yet.</p>
              <p class="dash-top-sm"><a class="btn-ui btn-primary-ui" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Browse Companies</a></p>
            <?php else: ?>
              <?php foreach ($userFavorites as $fav): ?>
                <div class="dash-item-row">
                  <p class="dash-zero">
                    <strong><?php echo htmlspecialchars($fav['company_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if (!empty($fav['verified'])): ?>
                      <span class="pill dash-pill-verified">✓ Verified</span>
                    <?php endif; ?>
                  </p>
                  <p class="muted dash-meta-sm">
                    <?php echo htmlspecialchars($fav['city'] ?? $fav['state'] ?? 'UAE', ENT_QUOTES, 'UTF-8'); ?>
                    · <a href="<?php echo htmlspecialchars(url('/company/' . rawurlencode((string)$fav['slug'])), ENT_QUOTES, 'UTF-8'); ?>">View</a>
                  </p>
                </div>
              <?php endforeach; ?>
              <?php if ($totalFavorites > 5): ?>
                <p class="dash-top-md"><a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars(url('/my-favorites'), ENT_QUOTES, 'UTF-8'); ?>">View All Favorites</a></p>
              <?php endif; ?>
            <?php endif; ?>
          </article>

          <article class="card-ui dashboard-box dash-gap-top">
            <h3 class="dash-h3">Saved Searches (<?php echo $totalSavedSearches; ?>)</h3>
            <?php if (empty($userSavedSearches)): ?>
              <p class="muted">You haven't saved any searches yet.</p>
              <p class="dash-top-sm">
                <a class="btn-ui btn-primary-ui" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Start Searching</a>
              </p>
            <?php else: ?>
              <?php foreach ($userSavedSearches as $search): ?>
                <div class="dash-item-row">
                  <p class="dash-zero">
                    <strong><?php echo htmlspecialchars($search['search_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if ($search['email_alerts']): ?>
                      <span class="pill dash-pill-alerts">📧 Alerts</span>
                    <?php endif; ?>
                  </p>
                  <p class="muted dash-meta-sm">
                    Query: <code class="dash-code"><?php echo htmlspecialchars(substr($search['search_query'], 0, 40), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen($search['search_query']) > 40 ? '...' : ''; ?></code>
                  </p>
                </div>
              <?php endforeach; ?>
              <?php if ($totalSavedSearches > 5): ?>
                <p class="dash-top-md"><a class="btn-ui btn-light-ui" href="<?php echo htmlspecialchars(url('/my-searches'), ENT_QUOTES, 'UTF-8'); ?>">View All Saved Searches</a></p>
              <?php endif; ?>
            <?php endif; ?>
          </article>

          <article class="card-ui dashboard-box dash-gap-top">
            <h3 class="dash-h3">Getting Started</h3>
            <ul class="dash-list">
              <li>Add your first business listing to reach thousands of customers</li>
              <li>Save companies to your favorites for quick access</li>
              <li>Create saved searches with email alerts for new matches</li>
              <li>Complete all business details for better visibility</li>
              <li>Respond to customer inquiries promptly</li>
            </ul>
          </article>
        </section>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>

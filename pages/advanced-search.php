<?php
require_once __DIR__ . '/../config/session.php';
startFrontendSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/SubscriptionTier.php';
require_once __DIR__ . '/../classes/SearchLimiter.php';
require_once __DIR__ . '/../classes/frontend/Search.php';
require_once __DIR__ . '/../classes/frontend/CompanyCategories.php';

$searchModel = new Search($conn);
$categoriesModel = new CompanyCategories($conn);

$keyword = trim((string)($_GET['q'] ?? ''));
$categoryId = (int)($_GET['category_id'] ?? 0);
$emirate = trim((string)($_GET['emirate'] ?? ''));
$minRating = (float)($_GET['min_rating'] ?? 0);
$verifiedOnly = isset($_GET['verified_only']) ? 1 : 0;
$sortBy = (string)($_GET['sort_by'] ?? 'recommended');
$page = max(1, (int)($_GET['page'] ?? 1));
$userId = isset($_SESSION['frontend_user_id']) ? (int)$_SESSION['frontend_user_id'] : 0;
$userTier = SubscriptionTier::getUserTier($userId, $conn);
$resultLimit = SearchLimiter::getResultLimit($userTier);
$perPage = min(12, max(1, (int)$resultLimit));
$offset = ($page - 1) * $perPage;

$success = '';
$errors = [];

if (isset($_GET['load']) && isset($_SESSION['frontend_user_id'])) {
  $loadId = (int)$_GET['load'];
  if ($loadId > 0) {
    $loadStmt = $conn->prepare("SELECT search_filters FROM `" . DB::SEARCHES . "` WHERE id = ? AND user_id = ? AND search_type = 'saved' AND is_active = 1 LIMIT 1");
    if ($loadStmt) {
      $userId = (int)$_SESSION['frontend_user_id'];
      $loadStmt->bind_param('ii', $loadId, $userId);
      $loadStmt->execute();
      $savedRow = $loadStmt->get_result()->fetch_assoc();
      $loadStmt->close();

      if (!empty($savedRow['search_filters'])) {
        $loadedFilters = json_decode((string)$savedRow['search_filters'], true) ?: [];
        $keyword = trim((string)($loadedFilters['keyword'] ?? $keyword));
        $categoryId = (int)($loadedFilters['category_id'] ?? $categoryId);
        $emirate = trim((string)($loadedFilters['emirate'] ?? $emirate));
        $minRating = (float)($loadedFilters['min_rating'] ?? $minRating);
        $verifiedOnly = !empty($loadedFilters['verified_only']) ? 1 : $verifiedOnly;
        $sortBy = (string)($loadedFilters['sort_by'] ?? $sortBy);
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_search') {
    if (!isset($_SESSION['frontend_user_id'])) {
        $errors[] = 'Please sign in to save searches.';
    } elseif (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed.';
    } else {
        $saved = $searchModel->saveSearch((int)$_SESSION['frontend_user_id'], [
            'keyword' => $keyword,
            'category_id' => $categoryId,
        'emirate' => $emirate,
        'min_rating' => $minRating,
        'verified_only' => $verifiedOnly,
        'sort_by' => $sortBy,
        'search_query' => $keyword !== '' ? $keyword : 'Saved Search'
        ], 1);
        $success = $saved ? 'Search saved successfully.' : 'Could not save search at the moment.';
    }
}

$filters = [
    'keyword' => $keyword,
    'category_id' => $categoryId,
    'emirate' => $emirate,
    'min_rating' => $minRating,
    'verified_only' => $verifiedOnly,
    'sort_by' => $sortBy
];

$totalRaw = $searchModel->countAdvancedSearch($filters);
$total = min($totalRaw, max(1, (int)$resultLimit));
$totalPages = max(1, (int)ceil($total / $perPage));
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;
$results = $searchModel->advancedSearch($filters, $perPage, $offset);


$hasActiveFilters = ($keyword !== '' || $categoryId > 0 || $emirate !== '' || $minRating > 0 || $verifiedOnly === 1 || $sortBy !== 'recommended');
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $hasActiveFilters && $page === 1) {
  $logUserId = isset($_SESSION['frontend_user_id']) ? (int)$_SESSION['frontend_user_id'] : 0;
  $searchModel->logSearchHistory($logUserId, $filters, (int)$total);
}

$categories = $categoriesModel->getAll(['order_by' => 'name ASC']);
foreach ($categories as &$cat) {
    if (!isset($cat['name']) && isset($cat['category'])) {
        $cat['name'] = $cat['category'];
    }
}
unset($cat);

$emirates = ['abu dhabi', 'dubai', 'sharjah', 'ajman', 'umm al quwain', 'ras al khaimah', 'fujairah'];
$pageTitle = 'Advanced Search - UAE Business Directory';
$pageDescription = 'Advanced business search with filters and saved searches.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>
<main class="section">
  <div class="container-narrow">
    <div class="section-head"><h1 class="advsearch-title">Advanced Search</h1><span class="muted"><?php echo number_format($total); ?> matches</span></div>

    <?php if ($total < $totalRaw): ?>
      <?php
      $nextTierLabel = 'Platinum';
      $nextTierLimit = 100000;
      if ($userTier === SubscriptionTier::TIER_FREE) {
        $nextTierLabel = 'a registered account';
        $nextTierLimit = 1000;
      } elseif ($userTier === SubscriptionTier::TIER_REGISTERED) {
        $nextTierLabel = 'Silver';
        $nextTierLimit = 5000;
      } elseif ($userTier === SubscriptionTier::TIER_SILVER) {
        $nextTierLabel = 'Gold';
        $nextTierLimit = 25000;
      } elseif ($userTier === SubscriptionTier::TIER_GOLD) {
        $nextTierLabel = 'Platinum';
        $nextTierLimit = 100000;
      }
      ?>
      <div class="listings-upgrade-cta">
        <h4>See All <?php echo number_format($totalRaw); ?> Matches</h4>
        <p class="listings-upgrade-copy">You're viewing limited results (<?php echo number_format($total); ?>/search).
        <?php if ($userTier === SubscriptionTier::TIER_FREE): ?>
          <a href="<?php echo url('/register'); ?>" class="listings-upgrade-link">Create a free account</a>
          to see up to <?php echo number_format($nextTierLimit); ?> results, or
        <?php endif; ?>
        <a href="<?php echo url('/pricing'); ?>" class="listings-upgrade-link-strong">upgrade to <?php echo htmlspecialchars($nextTierLabel, ENT_QUOTES, 'UTF-8'); ?></a>
        for up to <?php echo number_format($nextTierLimit); ?> results.</p>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e) { echo '<div>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</div>'; } ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <form method="get" class="card-ui advsearch-form">
      <div class="search-grid advsearch-grid">
        <input class="field" name="q" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Keyword">
        <select class="select" name="category_id">
          <option value="">All categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>" <?php echo $categoryId === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name'] ?? 'Category', ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
        <select class="select" name="emirate">
          <option value="">All emirates</option>
          <?php foreach ($emirates as $em): ?>
            <option value="<?php echo htmlspecialchars($em, ENT_QUOTES, 'UTF-8'); ?>" <?php echo strtolower($emirate) === strtolower($em) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucwords($em), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
        <select class="select" name="sort_by">
          <option value="recommended" <?php echo $sortBy === 'recommended' ? 'selected' : ''; ?>>Recommended</option>
          <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>Rating</option>
          <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest</option>
          <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name</option>
        </select>
        <button class="btn-ui btn-primary-ui" type="submit">Apply</button>
      </div>
      <div class="advsearch-row">
        <label><input type="checkbox" name="verified_only" value="1" <?php echo $verifiedOnly ? 'checked' : ''; ?>> Verified only</label>
        <label>Min rating: <input type="number" min="0" max="5" step="0.5" name="min_rating" value="<?php echo htmlspecialchars((string)$minRating, ENT_QUOTES, 'UTF-8'); ?>" class="advsearch-rating"></label>
        <a href="<?php echo htmlspecialchars(url('/search/advanced'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui">Clear</a>
      </div>
    </form>

    <?php if (isset($_SESSION['frontend_user_id'])): ?>
      <form method="post" class="advsearch-save">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save_search">
        <button class="btn-ui btn-light-ui" type="submit">Save This Search</button>
      </form>
    <?php endif; ?>

    <?php if (empty($results)): ?>
      <article class="card-ui"><p class="muted advsearch-empty">No results found with selected filters.</p></article>
    <?php else: ?>
      <?php foreach ($results as $r): ?>
        <article class="card-ui business-card advsearch-result">
          <h3 class="advsearch-h3"><a href="company/<?php echo urlencode($r['slug']); ?>"><?php echo htmlspecialchars($r['company_name'], ENT_QUOTES, 'UTF-8'); ?></a></h3>
          <p class="meta-line"><?php echo htmlspecialchars($r['category_name'] ?: 'Business', ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars(trim(($r['city'] ?? '') . ' ' . ($r['state'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="advsearch-ratingline">⭐ <?php echo number_format((float)($r['avg_rating'] ?? 0), 1); ?> · <?php echo number_format((int)($r['review_count'] ?? 0)); ?> reviews</p>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
      <div class="advsearch-pagination">
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <?php if ($i === $page): ?><span class="btn-ui btn-primary-ui"><?php echo $i; ?></span>
          <?php else: ?><a class="btn-ui btn-light-ui" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a><?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>

<?php
/**
 * Page: User Dashboard - My Favorites
 * Route: /dashboard-favorites
 * Description: Manage and view all user's saved favorite companies
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/Favorites.php';

// Legacy public route hardening: never expose /dashboard-favorites to frontend users.
header('Location: ' . url('/my-favorites'), true, 301);
exit;

// ============================================
// SECTION 2: AUTHENTICATION
// ============================================
startFrontendSession();
if (!isset($_SESSION['frontend_user_id'])) {
    header('Location: ' . (($GLOBALS['basePath'] ?? '') . '/login'));
    exit;
}

$userId = $_SESSION['frontend_user_id'];

// ============================================
// SECTION 3: HANDLE ACTIONS
// ============================================
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
    $message = 'Security validation failed. Please refresh the page and try again.';
    $messageType = 'error';
  } else {
  $action = $_POST['action'] ?? '';
  $companyId = (int)($_POST['company_id'] ?? 0);
    
    if ($action === 'remove' && $companyId > 0) {
        $favorites = new Favorites($conn);
        if ($favorites->removeFavorite($userId, $companyId)) {
            $message = 'Company removed from favorites.';
            $messageType = 'success';
        } else {
            $message = 'Failed to remove favorite. Please try again.';
            $messageType = 'error';
        }
    }
        }
}

// ============================================
// SECTION 4: LOAD FAVORITES DATA
// ============================================
$favorites = new Favorites($conn);
$userFavorites = $favorites->getUserFavorites($userId);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$totalFavorites = count($userFavorites);
$totalPages = ceil($totalFavorites / $perPage);
$page = min($page, $totalPages ?: 1);

$start = ($page - 1) * $perPage;
$paginatedFavorites = array_slice($userFavorites, $start, $perPage);

$pageTitle = 'My Favorites - UAE Business Directory';
$pageDescription = 'View and manage all your saved favorite companies.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<main id="main-content" class="section">
  <div class="container-narrow">
    <div class="section-head">
      <h1 class="dash-title">My Favorites</h1>
      <span class="muted"><?php echo $totalFavorites; ?> company<?php echo $totalFavorites !== 1 ? 'ies' : ''; ?> saved</span>
    </div>

    <?php if ($message): ?>
      <div class="dash-message <?php echo $messageType === 'success' ? 'dash-message--success' : 'dash-message--error'; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php if (empty($userFavorites)): ?>
      <div class="dash-empty-state">
        <h2 class="dash-empty-title">No Favorites Yet</h2>
        <p class="muted dash-empty-copy">Start saving companies to quickly access your favorites.</p>
        <a class="btn-ui btn-primary-ui dash-inline-btn" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Browse Companies</a>
      </div>
    <?php else: ?>
      <!-- Filters -->
      <div class="dash-filter-wrap">
        <form method="GET" class="dash-filter-form">
          <input type="text" name="emirate" placeholder="Filter by emirate..." class="dash-filter-input">
          <button type="submit" class="btn-ui btn-primary-ui">Filter</button>
          <a href="?page=1" class="btn-ui btn-light-ui">Reset</a>
        </form>
      </div>

      <!-- Favorites Grid -->
      <div class="dash-card-grid">
        <?php foreach ($paginatedFavorites as $company): ?>
          <div class="card-ui dash-fav-card">
            <div class="dash-fav-main">
              <h3 class="dash-card-title">
                <a href="<?php echo htmlspecialchars(url('/company/' . rawurlencode((string)$company['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="dash-card-link">
                  <?php echo htmlspecialchars($company['company_name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </h3>
              
              <?php if (!empty($company['verified'])): ?>
                <span class="pill dash-pill-verified dash-pill-stack">✓ Verified</span>
              <?php endif; ?>
              
              <?php if (!empty($company['category_name'])): ?>
                <p class="dash-field-line">
                  <strong>Category:</strong> <?php echo htmlspecialchars($company['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
              <?php endif; ?>
              
              <?php if (!empty($company['city']) || !empty($company['state'])): ?>
                <p class="dash-field-line">
                  <strong>Location:</strong> 
                  <?php 
                    $location = [];
                    if (!empty($company['city'])) $location[] = htmlspecialchars($company['city'], ENT_QUOTES, 'UTF-8');
                    if (!empty($company['state'])) $location[] = htmlspecialchars($company['state'], ENT_QUOTES, 'UTF-8');
                    echo implode(', ', $location);
                  ?>
                </p>
              <?php endif; ?>
              
              <?php if (!empty($company['phone'])): ?>
                <p class="dash-field-line">
                  <strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </p>
              <?php endif; ?>
              
              <?php if (!empty($company['user_notes'])): ?>
                <p class="dash-note-box">
                  <strong>Note:</strong> <?php echo htmlspecialchars($company['user_notes'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
              <?php endif; ?>
            </div>

            <div class="dash-card-actions">
              <a href="<?php echo htmlspecialchars(url('/company/' . rawurlencode((string)$company['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui dash-btn-flex dash-btn-sm">View</a>
              
              <form method="POST" class="dash-btn-flex">
                <?php echo csrf_field_frontend(); ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="company_id" value="<?php echo $company['company_id']; ?>">
                <button type="submit" class="btn-ui btn-light-ui dash-btn-full dash-btn-sm" onclick="return confirm('Remove from favorites?')">Remove</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="dash-pagination">
          <div class="dash-pagination-wrap">
            <?php if ($page > 1): ?>
              <a href="?page=1" class="btn-ui btn-light-ui dash-btn-xs">First</a>
              <a href="?page=<?php echo $page - 1; ?>" class="btn-ui btn-light-ui dash-btn-xs">Prev</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <?php if ($i === $page): ?>
                <span class="btn-ui dash-page-current"><?php echo $i; ?></span>
              <?php else: ?>
                <a href="?page=<?php echo $i; ?>" class="btn-ui btn-light-ui dash-btn-xs"><?php echo $i; ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
              <a href="?page=<?php echo $page + 1; ?>" class="btn-ui btn-light-ui dash-btn-xs">Next</a>
              <a href="?page=<?php echo $totalPages; ?>" class="btn-ui btn-light-ui dash-btn-xs">Last</a>
            <?php endif; ?>
          </div>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

    <!-- Back to Dashboard -->
    <p class="dash-back-link-wrap">
      <a href="<?php echo htmlspecialchars(url('/account/profile'), ENT_QUOTES, 'UTF-8'); ?>" class="link">← Back to Dashboard</a>
    </p>
  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

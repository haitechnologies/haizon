<?php
/**
 * Page: User Dashboard - Search History
 * Route: /dashboard-history
 * Description: View and manage search history
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/UserSearchHistory.php';
require_once __DIR__ . '/../classes/frontend/SavedSearches.php';

// Legacy public route hardening: never expose /dashboard-history to frontend users.
header('Location: ' . url('/search-history'), true, 301);
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
  $searchHistory = new UserSearchHistory($conn);
  $savedSearches = new SavedSearches($conn);
    
    if ($action === 'delete_record') {
        $recordId = (int)($_POST['record_id'] ?? 0);
        if ($searchHistory->deleteRecord($recordId, $userId)) {
            $message = 'Search removed from history.';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete search. Please try again.';
            $messageType = 'error';
        }
    } elseif ($action === 'clear_all') {
        $days = (int)($_POST['clear_days'] ?? 0);
        if ($days > 0) {
            if ($searchHistory->clearHistory($userId, $days)) {
                $message = "Searches older than {$days} days deleted.";
                $messageType = 'success';
            }
        } else {
            if ($searchHistory->clearHistory($userId)) {
                $message = 'All search history cleared.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'save_search') {
        $query = trim($_POST['search_query'] ?? '');
        $searchName = trim($_POST['search_name'] ?? '');
        
        if (!empty($query)) {
            if ($savedSearches->saveSearch($userId, $query, $searchName, ['email_alerts' => false])) {
            $message = 'Search saved successfully. View in <a href="' . htmlspecialchars(url('/my-searches'), ENT_QUOTES, 'UTF-8') . '">Saved Searches</a>';
                $messageType = 'success';
            }
        }
    }
        }
}

// ============================================
// SECTION 4: LOAD SEARCH HISTORY
// ============================================
$searchHistory = new UserSearchHistory($conn);
$allHistory = $searchHistory->getUserHistory($userId, ['limit' => 10000, 'days' => 90]);
$frequentSearches = $searchHistory->getFrequentSearches($userId, ['limit' => 5, 'days' => 90]);
$totalSearches = $searchHistory->getTotalSearchCount($userId, 90);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$totalPages = ceil(count($allHistory) / $perPage);
$page = min($page, $totalPages ?: 1);

$start = ($page - 1) * $perPage;
$paginatedHistory = array_slice($allHistory, $start, $perPage);

$pageTitle = 'Search History - UAE Business Directory';
$pageDescription = 'View your recent search history from the last 90 days.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<main id="main-content" class="section">
  <div class="container-narrow">
    <div class="section-head">
      <h1 class="dash-title">Search History</h1>
      <span class="muted"><?php echo $totalSearches; ?> search<?php echo $totalSearches !== 1 ? 'es' : ''; ?> in last 90 days</span>
    </div>

    <?php if ($message): ?>
      <div class="dash-message <?php echo $messageType === 'success' ? 'dash-message--success' : 'dash-message--error'; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <div class="dash-stats-grid">
      <!-- Statistics -->
      <div class="card-ui dash-stats-card">
        <h3 class="dash-h3 dash-text-white">Search Statistics (90 Days)</h3>
        <p class="dash-stats-value"><?php echo $totalSearches; ?></p>
        <p class="dash-stats-copy">Total searches</p>
      </div>

      <!-- Most Frequent -->
      <div class="card-ui dash-pad-lg">
        <h3 class="dash-h3">Most Frequent Searches</h3>
        <?php if (empty($frequentSearches)): ?>
          <p class="muted">No search data available.</p>
        <?php else: ?>
          <ul class="dash-list">
            <?php foreach ($frequentSearches as $idx => $search): ?>
              <li class="dash-list-item">
                <strong><?php echo $search['search_count']; ?>x</strong>
                <code class="dash-code dash-code-md">
                  <?php echo htmlspecialchars(substr($search['search_query'], 0, 40), ENT_QUOTES, 'UTF-8'); ?>
                  <?php echo strlen($search['search_query']) > 40 ? '...' : ''; ?>
                </code>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Clear History Option -->
    <div class="card-ui dash-warning-card">
      <h3 class="dash-h3">Manage Search History</h3>
      <form method="POST">
        <div class="dash-manage-grid">
          <?php echo csrf_field_frontend(); ?>
          <input type="hidden" name="action" value="clear_all">
          <div>
            <label for="clear-days" class="dash-label-block">
              Delete searches older than:
            </label>
            <select id="clear-days" name="clear_days" class="dash-input-full">
              <option value="7">7 days</option>
              <option value="30">30 days</option>
              <option value="90">90 days</option>
              <option value="0">Everything</option>
            </select>
          </div>
          <button type="submit" class="btn-ui btn-light-ui" onclick="return confirm('This action cannot be undone. Continue?')">Delete</button>
        </div>
      </form>
    </div>

    <!-- Search History List -->
    <?php if (empty($allHistory)): ?>
      <div class="dash-empty-state dash-empty-state--tight">
        <h2 class="dash-empty-title">No Search History</h2>
        <p class="muted">Your searches will appear here as you browse.</p>
      </div>
    <?php else: ?>
      <div class="dash-recent-head">
        <h3 class="dash-recent-title">Recent Searches</h3>
        <p class="muted dash-recent-copy">Click any search to re-run it, or save it for quick access.</p>
      </div>

      <table class="dash-table">
        <thead class="dash-table-head">
          <tr>
            <th class="dash-th dash-th-left">Search Query</th>
            <th class="dash-th dash-th-center">Results</th>
            <th class="dash-th dash-th-center">Date</th>
            <th class="dash-th dash-th-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paginatedHistory as $record): ?>
            <tr class="dash-tr">
              <td class="dash-td">
                <code class="dash-code dash-code-lg">
                  <?php echo htmlspecialchars($record['search_query'], ENT_QUOTES, 'UTF-8'); ?>
                </code>
              </td>
              <td class="dash-td dash-td-center dash-text-soft">
                <?php echo $record['result_count'] ?? '—'; ?>
              </td>
              <td class="dash-td dash-td-center dash-submeta">
                <?php echo timeAgo($record['created_at']); ?>
              </td>
              <td class="dash-td dash-td-center">
                <div class="dash-action-wrap">
                  <a href="<?php echo htmlspecialchars(url('/listings') . '?keyword=' . urlencode((string)$record['search_query']), ENT_QUOTES, 'UTF-8'); ?>" 
                     class="btn-ui btn-primary-ui dash-btn-xxs dash-no-underline">Run</a>
                  
                  <form method="POST" class="dash-inline-form">
                    <?php echo csrf_field_frontend(); ?>
                    <input type="hidden" name="action" value="save_search">
                    <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($record['search_query'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="search_name" value="">
                    <button type="submit" class="btn-ui btn-light-ui dash-btn-xxs">Save</button>
                  </form>
                  
                  <form method="POST" class="dash-inline-form">
                    <?php echo csrf_field_frontend(); ?>
                    <input type="hidden" name="action" value="delete_record">
                    <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                    <button type="submit" class="btn-ui btn-light-ui dash-btn-xxs" onclick="return confirm('Remove from history?')">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

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

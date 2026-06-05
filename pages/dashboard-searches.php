<?php
/**
 * Page: User Dashboard - Saved Searches
 * Route: /dashboard-searches
 * Description: Manage saved searches and email alert preferences
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/SavedSearches.php';

// Legacy public route hardening: never expose /dashboard-searches to frontend users.
header('Location: ' . url('/my-searches'), true, 301);
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
  $searches = new SavedSearches($conn);
    
    if ($action === 'delete') {
        $searchId = (int)($_POST['search_id'] ?? 0);
        if ($searches->deleteSearch($searchId, $userId)) {
            $message = 'Search deleted successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete search. Please try again.';
            $messageType = 'error';
        }
    } elseif ($action === 'update') {
        $searchId = (int)($_POST['search_id'] ?? 0);
        $searchName = trim($_POST['search_name'] ?? '');
        $emailAlerts = isset($_POST['email_alerts']) ? 1 : 0;
        $alertFrequency = $_POST['alert_frequency'] ?? 'daily';
        
        if ($searchId > 0) {
            if ($searches->updateSearch($searchId, $userId, [
                'search_name' => $searchName,
                'email_alerts' => $emailAlerts,
                'alert_frequency' => $alertFrequency
            ])) {
                $message = 'Search preferences updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to update search. Please try again.';
                $messageType = 'error';
            }
        }
    }
        }
}

// ============================================
// SECTION 4: LOAD SAVED SEARCHES
// ============================================
$searches = new SavedSearches($conn);
$userSearches = $searches->getUserSearches($userId);
$totalSearches = count($userSearches);

$pageTitle = 'My Saved Searches - UAE Business Directory';
$pageDescription = 'Manage your saved searches and email alerts.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<main id="main-content" class="section">
  <div class="container-narrow">
    <div class="section-head">
      <h1 class="dash-title">Saved Searches</h1>
      <span class="muted"><?php echo $totalSearches; ?> saved search<?php echo $totalSearches !== 1 ? 'es' : ''; ?></span>
    </div>

    <?php if ($message): ?>
      <div class="dash-message <?php echo $messageType === 'success' ? 'dash-message--success' : 'dash-message--error'; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php if (empty($userSearches)): ?>
      <div class="dash-empty-state">
        <h2 class="dash-empty-title">No Saved Searches</h2>
        <p class="muted dash-empty-copy">Save searches from the search results page to revisit them anytime and get email alerts for new matches.</p>
        <a class="btn-ui btn-primary-ui dash-inline-btn" href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>">Start Searching</a>
      </div>
    <?php else: ?>
      <div class="dash-tip-wrap">
        <p class="muted"><strong>Tip:</strong> Enable email alerts to get notified when new companies match your saved search.</p>
      </div>

      <!-- Saved Searches List -->
      <div class="dash-search-list">
        <?php foreach ($userSearches as $search): ?>
          <div class="card-ui dash-search-card">
            <div class="dash-search-head">
              <div>
                <h3 class="dash-card-title dash-card-title-dark">
                  <?php echo htmlspecialchars($search['search_name'], ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                <p class="dash-field-line dash-zero">
                  <strong>Query:</strong> <code class="dash-code dash-code-lg">
                    <?php echo htmlspecialchars($search['search_query'], ENT_QUOTES, 'UTF-8'); ?>
                  </code>
                </p>
              </div>
              <div class="dash-right">
                <?php if ($search['email_alerts']): ?>
                  <span class="pill dash-pill-alerts">
                    📧 <?php echo ucfirst($search['alert_frequency']); ?> Alerts
                  </span>
                <?php else: ?>
                  <span class="pill dash-pill-muted">No Alerts</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="dash-submeta">
              Saved <?php echo timeAgo($search['created_at']); ?>
              <?php if ($search['last_searched_at']): ?>
                · Last searched <?php echo timeAgo($search['last_searched_at']); ?>
              <?php endif; ?>
            </div>

            <!-- Edit Form -->
            <form method="POST" class="dash-edit-form">
              <?php echo csrf_field_frontend(); ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="search_id" value="<?php echo $search['id']; ?>">

              <div class="dash-field-group">
                <label for="name-<?php echo $search['id']; ?>" class="dash-label-block">
                  <strong>Search Name</strong>
                </label>
                <input type="text" id="name-<?php echo $search['id']; ?>" name="search_name" 
                       value="<?php echo htmlspecialchars($search['search_name'], ENT_QUOTES, 'UTF-8'); ?>"
                       class="dash-input-full">
              </div>

              <div class="dash-two-col">
                <div>
                  <label for="alerts-<?php echo $search['id']; ?>" class="dash-inline-check">
                    <input type="checkbox" id="alerts-<?php echo $search['id']; ?>" name="email_alerts" 
                           <?php echo $search['email_alerts'] ? 'checked' : ''; ?> class="dash-check">
                    <span>Email Alerts</span>
                  </label>
                </div>
                <div>
                  <label for="frequency-<?php echo $search['id']; ?>" class="dash-label-block">
                    <strong>Frequency</strong>
                  </label>
                  <select id="frequency-<?php echo $search['id']; ?>" name="alert_frequency" 
                          class="dash-select-full">
                    <option value="daily" <?php echo $search['alert_frequency'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo $search['alert_frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="never" <?php echo $search['alert_frequency'] === 'never' ? 'selected' : ''; ?>>Never</option>
                  </select>
                </div>
              </div>

              <div class="dash-btn-row">
                <button type="submit" class="btn-ui btn-primary-ui dash-btn-sm dash-btn-pad">Update</button>
                <a href="?search=<?php echo urlencode($search['search_query']); ?>" class="btn-ui btn-light-ui dash-btn-sm dash-btn-pad dash-no-underline">Run Search</a>
              </div>
            </form>

            <!-- Delete Button -->
            <form method="POST" class="dash-inline-form">
              <?php echo csrf_field_frontend(); ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="search_id" value="<?php echo $search['id']; ?>">
              <button type="submit" class="btn-ui btn-light-ui dash-btn-sm dash-btn-pad" 
                      onclick="return confirm('Delete this saved search?')">Delete Search</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

    <!-- Back to Dashboard -->
    <p class="dash-back-link-wrap">
      <a href="<?php echo htmlspecialchars(url('/account/profile'), ENT_QUOTES, 'UTF-8'); ?>" class="link">← Back to Dashboard</a>
    </p>
  </div>
</main>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

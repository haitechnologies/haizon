<?php
/**
 * Page: My Saved Searches
 * Route: /my-searches or /pages/my-searches.php
 * Description: View and manage saved searches with email alert preferences
 * Author: Development Team
 * Created: February 28, 2026
 */

// ============================================
// SECTION 1: DEPENDENCIES & INITIALIZATION
// ============================================
require_once __DIR__ . '/../config/session.php';
startFrontendSession();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/SubscriptionTier.php';

// ============================================
// SECTION 2: AUTH CHECK
// ============================================
if (!isset($_SESSION['frontend_user_id'])) {
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  $basePath = rtrim(preg_replace('#/pages$#', '', $scriptDir), '/');
  $loginUrl = ($basePath !== '' ? $basePath : '') . '/login';
  header('Location: ' . $loginUrl . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = (int)$_SESSION['frontend_user_id'];
$userTier = SubscriptionTier::getUserTier($userId, $conn);
$savedSearchLimit = max(1, (int)(SubscriptionTier::getFeatureValue($userTier, 'saved_searches') ?? 5));
$visibleSavedSearchLimit = min(200, $savedSearchLimit);

$savedSearchesTotal = 0;
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM `" . DB::SEARCHES . "` WHERE user_id = ? AND search_type = 'saved' AND is_active = 1");
$countStmt->bind_param('i', $userId);
$countStmt->execute();
$countRow = $countStmt->get_result()->fetch_assoc();
$savedSearchesTotal = (int)($countRow['total'] ?? 0);
$countStmt->close();
$csrfToken = csrf_token_frontend();

// ============================================
// SECTION 3: HANDLE ACTIONS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validate_csrf_token_frontend($_POST['csrf_token'] ?? '')) {
    $message = 'Security validation failed. Please refresh the page and try again.';
  } else {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_search') {
      $searchId = (int)($_POST['search_id'] ?? 0);
      if ($searchId > 0) {
        // Soft delete: set is_active = 0
        $stmt = $conn->prepare("UPDATE `" . DB::SEARCHES . "` SET is_active = 0 WHERE id = ? AND user_id = ? AND search_type = 'saved'");
        $stmt->bind_param('ii', $searchId, $userId);
        $stmt->execute();
        $stmt->close();
        $message = "Search deleted successfully.";
      }
    } elseif ($action === 'toggle_alert') {
      $searchId = (int)($_POST['search_id'] ?? 0);
      $enabled = isset($_POST['enabled']) ? 1 : 0;
      if ($searchId > 0) {
        $stmt = $conn->prepare("UPDATE `" . DB::SEARCHES . "` SET alert_enabled = ? WHERE id = ? AND user_id = ? AND search_type = 'saved'");
        $stmt->bind_param('iii', $enabled, $searchId, $userId);
        $stmt->execute();
        $stmt->close();
        $message = $enabled ? "Email alerts enabled." : "Email alerts disabled.";
      }
    }
        }
}

// ============================================
// SECTION 4: FETCH SAVED SEARCHES
// ============================================

$query = "
  SELECT 
    id,
    search_query,
    search_filters,
    alert_enabled,
    created_at
  FROM `" . DB::SEARCHES . "`
  WHERE user_id = ? AND search_type = 'saved' AND is_active = 1
  ORDER BY created_at DESC
  LIMIT ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $userId, $visibleSavedSearchLimit);
$stmt->execute();
$result = $stmt->get_result();
$savedSearches = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================
// SECTION 5: PAGE METADATA
// ============================================
$pageTitle = 'My Saved Searches - UAE Business Directory';
$pageDescription = 'Manage your saved searches and set up email alerts for new matching companies.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main id="main-content" class="section">
    <div class="container-narrow">
      <!-- Page Header -->
      <div class="mysearch-head">
        <h1 class="mysearch-title">💾 My Saved Searches</h1>
        <p class="muted mysearch-subtitle">Manage your saved searches and email alert preferences (showing <?php echo number_format(count($savedSearches)); ?> of <?php echo number_format(min($savedSearchesTotal, $savedSearchLimit)); ?> visible by your plan)</p>
      </div>

      <?php if ($savedSearchesTotal > $savedSearchLimit): ?>
        <div class="card-ui mysearch-msg">
          <p>
            Your plan currently shows <?php echo number_format($savedSearchLimit); ?> saved searches.
            <a href="<?php echo htmlspecialchars(url('/pricing'), ENT_QUOTES, 'UTF-8'); ?>">Upgrade your plan</a>
            to manage all <?php echo number_format($savedSearchesTotal); ?> saved searches.
          </p>
        </div>
      <?php endif; ?>

      <?php if (!empty($message)): ?>
        <div class="card-ui mysearch-msg">
          <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      <?php endif; ?>

      <?php if (empty($savedSearches)): ?>
        <div class="card-ui detail-box mysearch-empty">
          <h3>No Saved Searches Yet</h3>
          <p class="muted">Start by creating a saved search to get alerts when new companies match your criteria.</p>
          <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">Create Saved Search</a>
        </div>
      <?php else: ?>
        <!-- Saved Searches List -->
        <div class="mysearch-grid">
          <?php foreach ($savedSearches as $search): ?>
            <?php 
            $filters = json_decode($search['search_filters'] ?? '{}', true) ?? [];
            $listingQuery = [];
            if (!empty($filters['keyword'])) { $listingQuery['keyword'] = trim((string)$filters['keyword']); }
            if (!empty($filters['category'])) { $listingQuery['category'] = trim((string)$filters['category']); }
            if (!empty($filters['category_id'])) { $listingQuery['category_id'] = (int)$filters['category_id']; }
            if (!empty($filters['emirate'])) { $listingQuery['emirate'] = trim((string)$filters['emirate']); }
            elseif (!empty($filters['state'])) { $listingQuery['emirate'] = trim((string)$filters['state']); }
            elseif (!empty($filters['city'])) { $listingQuery['emirate'] = trim((string)$filters['city']); }
            if (!empty($filters['sort_by'])) { $listingQuery['sort_by'] = trim((string)$filters['sort_by']); }
            $useSearchUrl = url('/listings') . (!empty($listingQuery) ? '?' . http_build_query($listingQuery) : '');
            $criteriaText = [];
            if (!empty($filters['keyword'])) $criteriaText[] = "Keywords: " . htmlspecialchars($filters['keyword'], ENT_QUOTES, 'UTF-8');
            if (!empty($filters['category'])) $criteriaText[] = "Category: " . htmlspecialchars($filters['category'], ENT_QUOTES, 'UTF-8');
            if (!empty($filters['city'])) $criteriaText[] = "City: " . htmlspecialchars($filters['city'], ENT_QUOTES, 'UTF-8');
            if (!empty($filters['state'])) $criteriaText[] = "State: " . htmlspecialchars($filters['state'], ENT_QUOTES, 'UTF-8');
            if (empty($criteriaText)) $criteriaText[] = "All companies";
            ?>
            <div class="card-ui mysearch-card">
              <div class="mysearch-row">
                <div class="mysearch-main">
                  <h3 class="mysearch-card-title">
                    <?php echo htmlspecialchars($search['search_query'], ENT_QUOTES, 'UTF-8'); ?>
                  </h3>
                  <p class="muted mysearch-card-date">
                    Saved on <?php echo dd_($search['created_at'], 'd M Y'); ?>
                  </p>
                </div>
                <div class="mysearch-actions">
                  <a href="<?php echo htmlspecialchars($useSearchUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui mysearch-mini-btn">Use Search</a>
                  <button class="btn-ui btn-light-ui mysearch-mini-btn mysearch-delete-btn" onclick="deleteSearch(<?php echo $search['id']; ?>)">Delete</button>
                </div>
              </div>

              <!-- Search Criteria -->
              <div class="mysearch-criteria">
                <p>
                  <?php echo empty($criteriaText) ? 'All companies' : implode(' • ', $criteriaText); ?>
                </p>
              </div>

              <!-- Alert Settings -->
              <div class="mysearch-alerts">
                <!-- Email Alerts Toggle -->
                <div>
                  <label class="mysearch-alert-label">
                    <input type="checkbox" id="alert-<?php echo $search['id']; ?>" <?php echo $search['alert_enabled'] ? 'checked' : ''; ?> onchange="toggleAlert(<?php echo $search['id']; ?>)" class="mysearch-alert-check">
                    <span class="mysearch-alert-title">Email Alerts</span>
                  </label>
                  <p class="muted mysearch-alert-copy">Get notified of new matches</p>
                </div>

                <!-- Search Status -->
                <div>
                  <p class="mysearch-status-title">Status</p>
                  <p class="mysearch-status-value">
                    <?php echo $search['alert_enabled'] ? '✓ Alerts Enabled' : '○ Alerts Disabled'; ?>
                  </p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Info Box -->
        <div class="card-ui mysearch-tip">
          <p>
            💡 <strong>Tip:</strong> Enable email alerts to get notified when new companies match your search criteria. You can change the frequency anytime.
          </p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    function deleteSearch(searchId) {
      if (confirm('Are you sure you want to delete this saved search?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        const csrfToken = <?php echo json_encode($csrfToken); ?>;
        form.innerHTML = '<input type="hidden" name="action" value="delete_search"><input type="hidden" name="search_id" value="' + searchId + '"><input type="hidden" name="csrf_token" value="' + csrfToken + '">';
        document.body.appendChild(form);
        form.submit();
      }
    }

    function toggleAlert(searchId) {
      const checkbox = document.getElementById('alert-' + searchId);
      const form = document.createElement('form');
      form.method = 'POST';
      const csrfToken = <?php echo json_encode($csrfToken); ?>;
      form.innerHTML = '<input type="hidden" name="action" value="toggle_alert"><input type="hidden" name="search_id" value="' + searchId + '"><input type="hidden" name="csrf_token" value="' + csrfToken + '">';
      if (checkbox.checked) {
        form.innerHTML += '<input type="hidden" name="enabled" value="1">';
      }
      document.body.appendChild(form);
      form.submit();
    }
  </script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>

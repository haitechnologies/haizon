<?php
/**
 * Page: Search History & Presets
 * Route: /search-history or /pages/search-history.php
 * Description: View search history and manage search presets
 * Author: Development Team
 * Created: February 28, 2026
 */

require_once __DIR__ . '/../config/session.php';
startFrontendSession();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/DB.php';

// Auth check
if (!isset($_SESSION['frontend_user_id'])) {
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  $basePath = rtrim(preg_replace('#/pages$#', '', $scriptDir), '/');
  $loginUrl = ($basePath !== '' ? $basePath : '') . '/login';
  header('Location: ' . $loginUrl . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = (int)$_SESSION['frontend_user_id'];
$message = '';
$activeTab = $_GET['tab'] ?? 'history';

// ============================================
// HANDLE ACTIONS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $message = 'Security validation failed. Please try again.';
  } else {
    $action = $_POST['action'] ?? '';
        
    if ($action === 'save_preset') {
      $presetName = trim($_POST['preset_name'] ?? '');
      $filters = json_decode($_POST['filters'] ?? '{}', true);
            
      if (!empty($presetName) && !empty($filters)) {
        $stmt = $conn->prepare("INSERT INTO `" . DB::SEARCHES . "` (user_id, search_name, search_filters, search_type, is_active, created_at, updated_at) VALUES (?, ?, ?, 'preset', 1, NOW(), NOW())");
        $filtersJson = json_encode($filters);
        $stmt->bind_param('iss', $userId, $presetName, $filtersJson);
                
        if ($stmt->execute()) {
          $message = "Search preset saved successfully.";
        } else {
          $message = "Error saving preset: " . $conn->error;
        }
        $stmt->close();
      }
    } elseif ($action === 'delete_preset') {
      $presetId = (int)($_POST['preset_id'] ?? 0);
      if ($presetId > 0) {
        $stmt = $conn->prepare("UPDATE `" . DB::SEARCHES . "` SET is_active = 0 WHERE id = ? AND user_id = ? AND search_type = 'preset'");
        $stmt->bind_param('ii', $presetId, $userId);
        $stmt->execute();
        $stmt->close();
        $message = "Preset deleted.";
      }
    } elseif ($action === 'clear_history') {
      $stmt = $conn->prepare("DELETE FROM `" . DB::SEARCHES . "` WHERE user_id = ? AND search_type = 'manual'");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $stmt->close();
      $message = "Search history cleared.";
    }
  }
}

// ============================================
// FETCH SEARCH HISTORY
// ============================================
$historyQuery = "
  SELECT 
    id,
    search_query,
    search_filters,
    result_count,
    created_at as searched_at
  FROM `" . DB::SEARCHES . "`
  WHERE user_id = ? AND search_type = 'manual'
  ORDER BY created_at DESC
  LIMIT 50
";

$stmt = $conn->prepare($historyQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$searchHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================
// FETCH SEARCH PRESETS
// ============================================
$presetsQuery = "
  SELECT 
    id,
    search_name as preset_name,
    search_filters,
    created_at
  FROM `" . DB::SEARCHES . "`
  WHERE user_id = ? AND search_type = 'preset' AND is_active = 1
  ORDER BY created_at DESC
";

$stmt = $conn->prepare($presetsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$searchPresets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Search History & Presets - UAE Business Directory';
$pageDescription = 'View your search history and manage saved search presets.';
$bodyClass = 'page-search-history';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

  <main class="section">
    <div class="container-narrow">
      <h1 class="searchhist-title">🔍 Search History & Presets</h1>
      <p class="muted searchhist-subtitle">View your search history and manage saved search presets</p>

      <?php if (!empty($message)): ?>
        <div class="card-ui searchhist-alert">
          <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      <?php endif; ?>

      <!-- Tabs Navigation -->
      <div class="nav-tabs">
        <button class="nav-link <?php echo $activeTab === 'history' ? 'active' : ''; ?>" onclick="switchTab(event, 'history')">
          📋 Search History (<?php echo count($searchHistory); ?>)
        </button>
        <button class="nav-link <?php echo $activeTab === 'presets' ? 'active' : ''; ?>" onclick="switchTab(event, 'presets')">
          ⭐ Saved Presets (<?php echo count($searchPresets); ?>)
        </button>
      </div>

      <!-- Search History Tab -->
      <div id="history-tab" class="tab-content <?php echo $activeTab === 'history' ? 'active' : ''; ?>">
        <?php if (empty($searchHistory)): ?>
          <div class="card-ui searchhist-empty">
            <p class="muted">No search history yet. Start searching to build your history!</p>
            <a href="<?php echo htmlspecialchars(url('/listings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-primary-ui">Go to Listings Search</a>
          </div>
        <?php else: ?>
          <div class="card-ui">
            <div class="searchhist-head">
              <h3 class="searchhist-h3">Recent Searches</h3>
              <form method="post" class="searchhist-inline-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="clear_history">
                <button type="submit" class="btn-ui btn-light-ui searchhist-mini-btn" onclick="return confirm('Clear all search history?')">Clear History</button>
              </form>
            </div>

            <?php foreach ($searchHistory as $item): ?>
              <?php $filters = json_decode($item['search_filters'] ?? '{}', true) ?? []; ?>
              <div class="history-item">
                <div class="searchhist-item-main">
                  <h4 class="searchhist-item-title">
                    <?php echo htmlspecialchars($item['search_query'], ENT_QUOTES, 'UTF-8'); ?>
                  </h4>
                  <p class="muted searchhist-item-meta">
                    📅 <?php echo dd_($item['searched_at'], 'd M Y g:ia'); ?> 
                    • 📊 <?php echo number_format($item['result_count']); ?> results
                  </p>
                </div>
                <div class="searchhist-row-actions">
                  <a href="<?php echo htmlspecialchars(url('/listings') . '?keyword=' . urlencode((string)$item['search_query']), ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui searchhist-mini-btn">Repeat</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Search Presets Tab -->
      <div id="presets-tab" class="tab-content <?php echo $activeTab === 'presets' ? 'active' : ''; ?>">
        <div class="searchhist-grid">
          <!-- Create New Preset -->
          <div class="card-ui searchhist-card-pad">
            <h3 class="searchhist-card-title">💾 Create New Preset</h3>
            <form method="post">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="save_preset">
              <div class="searchhist-field-wrap">
                <label class="searchhist-label">Preset Name</label>
                <input type="text" name="preset_name" placeholder="e.g., Tech Companies in Dubai" required class="searchhist-input">
              </div>
              <div class="searchhist-field-wrap">
                <label class="searchhist-label">Search Filters (JSON)</label>
                <textarea name="filters" placeholder='{"keyword":"tech","category":"1","city":"Dubai"}' required class="searchhist-textarea"></textarea>
              </div>
              <button type="submit" class="btn-ui btn-primary-ui">Save Preset</button>
            </form>
          </div>

          <!-- Existing Presets -->
          <?php if (empty($searchPresets)): ?>
            <div class="card-ui searchhist-empty">
              <p class="muted">No saved presets yet. Create one to quickly apply your favorite filters!</p>
            </div>
          <?php else: ?>
            <div class="card-ui">
              <h3 class="searchhist-card-title">Your Presets</h3>
              <?php foreach ($searchPresets as $preset): ?>
                <?php $filterData = json_decode($preset['search_filters'] ?? '{}', true) ?? []; ?>
                <?php
                  $presetQuery = [];
                  if (!empty($filterData['keyword'])) { $presetQuery['keyword'] = $filterData['keyword']; }
                  if (!empty($filterData['category'])) { $presetQuery['category'] = $filterData['category']; }
                  if (!empty($filterData['category_id'])) { $presetQuery['category_id'] = (int)$filterData['category_id']; }
                  if (!empty($filterData['emirate'])) { $presetQuery['emirate'] = $filterData['emirate']; }
                  if (!empty($filterData['sort_by'])) { $presetQuery['sort_by'] = $filterData['sort_by']; }
                  $presetUrl = url('/listings') . (!empty($presetQuery) ? '?' . http_build_query($presetQuery) : '');
                ?>
                <div class="searchhist-preset-row">
                  <div class="searchhist-preset-main">
                    <h4 class="searchhist-preset-title">
                      <?php echo htmlspecialchars($preset['preset_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </h4>
                    <p class="muted searchhist-preset-meta">
                      Created <?php echo dd_($preset['created_at'], 'd M Y'); ?>
                    </p>
                    <details class="searchhist-details">
                      <summary>View Filters</summary>
                      <pre class="searchhist-pre">
<?php echo htmlspecialchars(json_encode($filterData, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8'); ?>
                      </pre>
                    </details>
                  </div>
                  <div class="searchhist-row-actions">
                    <a href="<?php echo htmlspecialchars($presetUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-ui btn-light-ui searchhist-mini-btn">Use Preset</a>
                    <form method="post" class="searchhist-inline-form">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="delete_preset">
                      <input type="hidden" name="preset_id" value="<?php echo $preset['id']; ?>">
                      <button type="submit" class="btn-ui btn-light-ui searchhist-mini-btn searchhist-delete-btn" onclick="return confirm('Delete this preset?')">Delete</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script>
    function switchTab(event, tab) {
      event.preventDefault();
      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
      
      document.getElementById(tab + '-tab').classList.add('active');
      event.target.classList.add('active');
      
      // Update URL
      window.history.replaceState(null, null, '?tab=' + tab);
    }
  </script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>

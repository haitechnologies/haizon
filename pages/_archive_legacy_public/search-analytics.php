<?php
require_once __DIR__ . '/../config/session.php';
startFrontendSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

if (!isset($_SESSION['frontend_user_id'])) {
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  $basePath = rtrim(preg_replace('#/pages$#', '', $scriptDir), '/');
  $loginUrl = ($basePath !== '' ? $basePath : '') . '/login';
  header('Location: ' . $loginUrl . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = (int)$_SESSION['frontend_user_id'];

$totalSearches = 0;
$searchesLast30 = 0;
$avgResults30 = 0;
$topQueries = [];
$dailyActivity = [];
$topEmirates = [];
$topCategories = [];

$totalStmt = $conn->prepare("SELECT COUNT(*) AS total FROM `" . DB::SEARCHES . "` WHERE user_id = ? AND search_type = 'manual'");
if ($totalStmt) {
    $totalStmt->bind_param('i', $userId);
    $totalStmt->execute();
    $totalRow = $totalStmt->get_result()->fetch_assoc();
    $totalSearches = (int)($totalRow['total'] ?? 0);
    $totalStmt->close();
}

$kpiStmt = $conn->prepare("SELECT COUNT(*) AS total_30, AVG(result_count) AS avg_results_30 FROM `" . DB::SEARCHES . "` WHERE user_id = ? AND search_type = 'manual' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if ($kpiStmt) {
    $kpiStmt->bind_param('i', $userId);
    $kpiStmt->execute();
    $kpiRow = $kpiStmt->get_result()->fetch_assoc();
    $searchesLast30 = (int)($kpiRow['total_30'] ?? 0);
    $avgResults30 = (float)($kpiRow['avg_results_30'] ?? 0);
    $kpiStmt->close();
}

$queryStmt = $conn->prepare("SELECT search_query, COUNT(*) AS cnt, AVG(result_count) AS avg_results FROM `" . DB::SEARCHES . "` WHERE user_id = ? AND search_type = 'manual' GROUP BY search_query ORDER BY cnt DESC, search_query ASC LIMIT 10");
if ($queryStmt) {
    $queryStmt->bind_param('i', $userId);
    $queryStmt->execute();
    $topQueries = $queryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $queryStmt->close();
}

$activityStmt = $conn->prepare("SELECT DATE(created_at) AS day_key, COUNT(*) AS cnt FROM `" . DB::SEARCHES . "` WHERE user_id = ? AND search_type = 'manual' AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) GROUP BY DATE(created_at) ORDER BY day_key ASC");
if ($activityStmt) {
    $activityStmt->bind_param('i', $userId);
    $activityStmt->execute();
    $activityRows = $activityStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $activityStmt->close();

    $activityMap = [];
    foreach ($activityRows as $row) {
        $activityMap[$row['day_key']] = (int)$row['cnt'];
    }

    for ($i = 13; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $dailyActivity[] = [
            'label' => date('d M Y', strtotime($date)),
            'count' => (int)($activityMap[$date] ?? 0)
        ];
    }
}

$emirateStmt = $conn->prepare("SELECT JSON_UNQUOTE(JSON_EXTRACT(search_filters, '$.emirate')) AS emirate, COUNT(*) AS cnt FROM `" . DB::SEARCHES . "` WHERE user_id = ? AND search_type = 'manual' AND JSON_EXTRACT(search_filters, '$.emirate') IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(search_filters, '$.emirate')) <> '' GROUP BY emirate ORDER BY cnt DESC, emirate ASC LIMIT 10");
if ($emirateStmt) {
    $emirateStmt->bind_param('i', $userId);
    $emirateStmt->execute();
    $topEmirates = $emirateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $emirateStmt->close();
}

$categoryUsage = [];
$categoryStmt = $conn->prepare("SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(search_filters, '$.category_id')) AS UNSIGNED) AS category_id, COUNT(*) AS cnt FROM `" . DB::SEARCHES . "` WHERE user_id = ? AND search_type = 'manual' AND JSON_EXTRACT(search_filters, '$.category_id') IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(search_filters, '$.category_id')) <> '0' GROUP BY category_id ORDER BY cnt DESC LIMIT 10");
if ($categoryStmt) {
    $categoryStmt->bind_param('i', $userId);
    $categoryStmt->execute();
    $categoryUsage = $categoryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $categoryStmt->close();
}

if (!empty($categoryUsage)) {
    $categoryIds = [];
    foreach ($categoryUsage as $row) {
        $id = (int)($row['category_id'] ?? 0);
        if ($id > 0) {
            $categoryIds[] = $id;
        }
    }

    if (!empty($categoryIds)) {
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $types = str_repeat('i', count($categoryIds));
        $catSql = "SELECT id, name FROM `" . DB::CATEGORIES . "` WHERE id IN ({$placeholders})";
        $catStmt = $conn->prepare($catSql);
        if ($catStmt) {
            $catStmt->bind_param($types, ...$categoryIds);
            $catStmt->execute();
            $catRows = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $catStmt->close();

            $catMap = [];
            foreach ($catRows as $catRow) {
                $catMap[(int)$catRow['id']] = $catRow['name'];
            }

            foreach ($categoryUsage as $row) {
                $cid = (int)($row['category_id'] ?? 0);
                if ($cid > 0) {
                    $topCategories[] = [
                        'name' => $catMap[$cid] ?? ('Category #' . $cid),
                        'count' => (int)($row['cnt'] ?? 0)
                    ];
                }
            }
        }
    }
}

$pageTitle = 'Search Analytics - UAE Business Directory';
$maxDaily = 1;
foreach ($dailyActivity as $activity) {
    if ($activity['count'] > $maxDaily) {
        $maxDaily = $activity['count'];
    }
}
$pageTitle = 'Search Analytics - UAE Business Directory';
$pageDescription = 'Track your search behavior and trends.';
?>
<?php include __DIR__ . '/../includes/layout/header.php'; ?>

<main class="section">
  <div class="container-narrow">
    <div class="section-head">
      <h1 class="searchana-title">Search Analytics</h1>
      <span class="muted">Your last 30 days performance</span>
    </div>

    <div class="searchana-kpi-grid">
      <article class="card-ui searchana-card-pad">
        <p class="muted searchana-kpi-label">Total Searches</p>
        <h3 class="searchana-kpi-value"><?php echo number_format($totalSearches); ?></h3>
      </article>
      <article class="card-ui searchana-card-pad">
        <p class="muted searchana-kpi-label">Searches (30 Days)</p>
        <h3 class="searchana-kpi-value"><?php echo number_format($searchesLast30); ?></h3>
      </article>
      <article class="card-ui searchana-card-pad">
        <p class="muted searchana-kpi-label">Avg Results / Search (30d)</p>
        <h3 class="searchana-kpi-value"><?php echo number_format($avgResults30, 1); ?></h3>
      </article>
    </div>

    <div class="card-ui searchana-card-pad searchana-activity-card">
      <h2 class="searchana-h2">Daily Search Activity (Last 14 Days)</h2>
      <?php if (empty($dailyActivity)): ?>
        <p class="muted searchana-muted-zero">No activity yet.</p>
      <?php else: ?>
        <div class="searchana-activity-list">
          <?php foreach ($dailyActivity as $item): ?>
            <?php $width = (int)round(($item['count'] / $maxDaily) * 100); ?>
            <div class="searchana-activity-row">
              <span class="muted searchana-day-label"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
              <div class="searchana-bar-track">
                <div class="searchana-bar-fill" data-width="<?php echo $width; ?>"></div>
              </div>
              <strong><?php echo (int)$item['count']; ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="searchana-panels">
      <article class="card-ui searchana-card-pad">
        <h2 class="searchana-h2">Top Queries</h2>
        <?php if (empty($topQueries)): ?>
          <p class="muted searchana-muted-zero">No query data yet.</p>
        <?php else: ?>
          <div class="searchana-grid-list">
            <?php foreach ($topQueries as $row): ?>
              <div class="searchana-list-row">
                <span><?php echo htmlspecialchars($row['search_query'] ?: 'All Businesses', ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="muted"><?php echo (int)$row['cnt']; ?>x</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>

      <article class="card-ui searchana-card-pad">
        <h2 class="searchana-h2">Top Emirates</h2>
        <?php if (empty($topEmirates)): ?>
          <p class="muted searchana-muted-zero">No emirate filter usage yet.</p>
        <?php else: ?>
          <div class="searchana-grid-list">
            <?php foreach ($topEmirates as $row): ?>
              <div class="searchana-list-row">
                <span><?php echo htmlspecialchars(ucwords((string)$row['emirate']), ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="muted"><?php echo (int)$row['cnt']; ?>x</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>

      <article class="card-ui searchana-card-pad searchana-wide-panel">
        <h2 class="searchana-h2">Top Categories Used in Filters</h2>
        <?php if (empty($topCategories)): ?>
          <p class="muted searchana-muted-zero">No category filters used yet.</p>
        <?php else: ?>
          <div class="searchana-cats-grid">
            <?php foreach ($topCategories as $row): ?>
              <div class="searchana-list-row">
                <span><?php echo htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="muted"><?php echo (int)$row['count']; ?>x</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
    </div>
  </div>
</main>

<script>
  document.querySelectorAll('.searchana-bar-fill').forEach(function (bar) {
    var width = parseInt(bar.getAttribute('data-width') || '0', 10);
    if (isNaN(width) || width < 0) {
      width = 0;
    }
    if (width > 100) {
      width = 100;
    }
    bar.style.width = width + '%';
  });
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</html>

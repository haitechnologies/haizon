<?php

use App\Core\DB;
use App\Security\Roles;
/**
 * Coverage Sweep Runner
 *
 * Admin utility to trigger unobserved backend inventory entrypoints
 * while authenticated, so runtime coverage can be registered quickly.
 */

include('admin_elements/admin_header.php');

if (!Roles::hasFullAccess($session_role_id)) {
    echo "<div class='alert alert-danger text-center mt-5'><h3>Access Denied</h3><p>This page is restricted to System and Super Administrators only.</p></div>";
    include('admin_elements/admin_footer.php');
    exit;
}

if (!function_exists('coverage_table_exists')) {
    function coverage_table_exists($mysqli, $tableName)
    {
        $sql = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $tableName);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }
}

function normalizeCoverageSweepPath($pagePath)
{
    $path = trim((string)$pagePath);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    if ($path[0] === '/') {
        return $path;
    }

    if (strpos($path, 'api/') === 0) {
        return '../' . $path;
    }

    return $path;
}

function coverageSweepResolveModuleSlug($pageName)
{
    $base = strtolower((string)pathinfo((string)$pageName, PATHINFO_FILENAME));

    if (strpos($base, 'listing_') === 0) {
        return substr($base, 8) ?: 'unknown';
    }

    if (strpos($base, 'dashboard_') === 0) {
        return substr($base, 10) ?: 'dashboard';
    }

    if ($base !== '' && $base !== 'index') {
        return $base;
    }

    return 'dashboard';
}

$sourceFilter = isset($_GET['source']) ? trim((string)$_GET['source']) : 'dashboard_runtime';
if ($sourceFilter === '') {
    $sourceFilter = 'dashboard_runtime';
}

$seedSummary = ['inserted' => 0, 'existing' => 0, 'skipped' => 0, 'failed' => 0];
$seedNotice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'seed_inventory') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $seedNotice = 'CSRF token validation failed.';
    } elseif (!coverage_table_exists($mysqli, DB::BACKEND_LOG_COVERAGE)) {
        $seedNotice = 'Coverage table is not available. Run migrations first.';
    } else {
        $insertSql = "INSERT IGNORE INTO `" . DB::BACKEND_LOG_COVERAGE . "` (
            module_slug, page_name, page_path, entrypoint_type, source_channel,
            bootstrap_included, first_seen_at, last_seen_at, last_seen_error_at, seen_count
        ) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW(), NULL, 0)";
        $insertStmt = $mysqli->prepare($insertSql);

        if (!$insertStmt) {
            $seedNotice = 'Failed to prepare inventory seed query.';
        } else {
            $skipNames = ['login.php', 'logout.php', 'datatables_dispatcher.php'];
            $dashboardFiles = glob(__DIR__ . '/*.php') ?: [];
            $cronFiles = glob(__DIR__ . '/cron/*.php') ?: [];

            foreach ($dashboardFiles as $filePath) {
                $pageName = basename((string)$filePath);

                if (in_array($pageName, $skipNames, true)) {
                    $seedSummary['skipped']++;
                    continue;
                }

                $moduleSlug = coverageSweepResolveModuleSlug($pageName);
                $pagePath = $pageName;
                $entrypointType = 'page';
                $sourceChannel = 'dashboard_runtime';

                $insertStmt->bind_param('sssss', $moduleSlug, $pageName, $pagePath, $entrypointType, $sourceChannel);
                if (!$insertStmt->execute()) {
                    $seedSummary['failed']++;
                    continue;
                }

                if ($insertStmt->affected_rows > 0) {
                    $seedSummary['inserted']++;
                } else {
                    $seedSummary['existing']++;
                }
            }

            foreach ($cronFiles as $filePath) {
                $pageName = basename((string)$filePath);
                $moduleSlug = coverageSweepResolveModuleSlug($pageName);
                $pagePath = 'cron/' . $pageName;
                $entrypointType = 'cron';
                $sourceChannel = 'cron_runtime';

                $insertStmt->bind_param('sssss', $moduleSlug, $pageName, $pagePath, $entrypointType, $sourceChannel);
                if (!$insertStmt->execute()) {
                    $seedSummary['failed']++;
                    continue;
                }

                if ($insertStmt->affected_rows > 0) {
                    $seedSummary['inserted']++;
                } else {
                    $seedSummary['existing']++;
                }
            }

            $insertStmt->close();

            $seedNotice = 'Inventory seed completed. Inserted: ' . (int)$seedSummary['inserted']
                . ', Existing: ' . (int)$seedSummary['existing']
                . ', Skipped: ' . (int)$seedSummary['skipped']
                . ', Failed: ' . (int)$seedSummary['failed'] . '.';
        }
    }
}

$pendingRows = [];
if (coverage_table_exists($mysqli, DB::BACKEND_LOG_COVERAGE)) {
    $sql = "SELECT module_slug, page_name, page_path, source_channel, entrypoint_type, seen_count
            FROM `" . DB::BACKEND_LOG_COVERAGE . "`
            WHERE seen_count = 0";

    $params = [];
    $types = '';
    if (strcasecmp($sourceFilter, 'all') !== 0) {
        $sql .= " AND source_channel = ?";
        $params[] = $sourceFilter;
        $types .= 's';
    }

    $sql .= " ORDER BY CASE WHEN module_slug = '404' THEN 0 WHEN module_slug = '500' THEN 1 ELSE 2 END, page_path ASC";

    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result ? $result->fetch_assoc() : null) {
            $rawPath = (string)($row['page_path'] ?? '');
            $requestPath = normalizeCoverageSweepPath($rawPath);
            if ($requestPath === '') {
                continue;
            }
            $row['request_path'] = $requestPath;
            $pendingRows[] = $row;
        }
        $stmt->close();
    }
}

$pendingCount = count($pendingRows);

// Group pending rows by module_slug
$groupedRows = [];
foreach ($pendingRows as $row) {
    $mod = trim((string)($row['module_slug'] ?? ''));
    if ($mod === '') {
        $mod = 'unknown';
    }
    $groupedRows[$mod][] = $row;
}
// Sort: error modules first, then alphabetical
uksort($groupedRows, function ($a, $b) {
    $priority = ['404' => 0, '500' => 1, 'unknown' => 99];
    $ap = $priority[$a] ?? 50;
    $bp = $priority[$b] ?? 50;
    if ($ap !== $bp) return $ap - $bp;
    return strcmp($a, $b);
});

// Fetch overall coverage stats for stat cards
$coverageStats = ['total' => 0, 'observed' => 0, 'unobserved' => 0, 'with_errors' => 0, 'total_errors' => 0];
if (coverage_table_exists($mysqli, DB::BACKEND_LOG_COVERAGE)) {
    $r = $mysqli->query("SELECT COUNT(*) AS total,
        SUM(CASE WHEN seen_count > 0 THEN 1 ELSE 0 END) AS observed,
        SUM(CASE WHEN seen_count = 0 THEN 1 ELSE 0 END) AS unobserved,
        SUM(CASE WHEN last_seen_error_at IS NOT NULL THEN 1 ELSE 0 END) AS with_errors
        FROM `" . DB::BACKEND_LOG_COVERAGE . "`");
    if ($r) {
        $cr = $r->fetch_assoc();
        $coverageStats['total']       = (int)($cr['total'] ?? 0);
        $coverageStats['observed']    = (int)($cr['observed'] ?? 0);
        $coverageStats['unobserved']  = (int)($cr['unobserved'] ?? 0);
        $coverageStats['with_errors'] = (int)($cr['with_errors'] ?? 0);
        $r->free();
    }
}
if (coverage_table_exists($mysqli, DB::BACKEND_ERROR_LOGS)) {
    $r2 = $mysqli->query("SELECT COUNT(*) AS cnt FROM `" . DB::BACKEND_ERROR_LOGS . "`");
    if ($r2) {
        $er = $r2->fetch_assoc();
        $coverageStats['total_errors'] = (int)($er['cnt'] ?? 0);
        $r2->free();
    }
}
$moduleCount = count($groupedRows);
$coveragePct = $coverageStats['total'] > 0 ? round(($coverageStats['observed'] / $coverageStats['total']) * 100, 1) : 0;
?>

<style>
    .coverage-sweep-wrap .stat-card {
        border-radius: 10px;
        padding: 14px 18px;
        border: 1px solid #e3e8f0;
        background: #fff;
        min-width: 130px;
    }
    .coverage-sweep-wrap .stat-card .stat-val {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1.1;
    }
    .coverage-sweep-wrap .stat-card .stat-lbl {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c7a8d;
        margin-top: 2px;
    }
    .coverage-sweep-wrap .sweep-table-card {
        border: 1px solid #dee2e9;
        border-radius: 8px;
        overflow: hidden;
    }
    .coverage-sweep-wrap .sweep-table {
        font-size: 0.84rem;
    }
    .coverage-sweep-wrap .sweep-table th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c7a8d;
        background: #f7f9fc;
        white-space: nowrap;
    }
    .coverage-sweep-wrap .sweep-table td {
        vertical-align: middle;
    }
    .coverage-sweep-wrap .module-row {
        background: #eef3fb;
    }
    .coverage-sweep-wrap .module-row td {
        font-weight: 600;
        color: #33435d;
        border-top: 1px solid #d8e2f0;
    }
    .coverage-sweep-wrap .module-row .module-count {
        font-size: 0.75rem;
        color: #5f6d82;
        font-weight: 500;
        margin-left: 8px;
    }
    .coverage-sweep-wrap .progress-bar-coverage {
        height: 6px;
        border-radius: 4px;
        background: #e3e8f0;
        overflow: hidden;
    }
    .coverage-sweep-wrap .progress-bar-coverage .bar-fill {
        height: 100%;
        border-radius: 4px;
        background: #28a745;
        transition: width 0.4s;
    }
</style>

<div class="content-wrapper coverage-sweep-wrap">
    <div class="content-inner">
        <div class="content">

            <!-- Header -->
            <div class="page-header page-header-light shadow carriers-page-header">
                <div class="page-header-content d-lg-flex carriers-page-header-content">
                    <div class="d-flex align-items-center">
                        <h4 class="page-title mb-0">
                            Coverage Sweep Runner
                            <small class="ms-2 text-muted">Auto-hit unobserved backend entrypoints</small>
                        </h4>
                    </div>
                    <div class="ms-lg-auto mt-2 mt-lg-0 d-flex flex-wrap gap-2">
                        <a href="view_backend_error_logs.php" class="btn btn-light btn-sm">
                            <i class="ph-arrow-left me-1"></i> Back to Logs
                        </a>
                        <a href="run_coverage_sweep.php?source=dashboard_runtime" class="btn btn-outline-primary btn-sm <?php echo $sourceFilter === 'dashboard_runtime' ? 'active' : ''; ?>">Runtime Only</a>
                        <a href="run_coverage_sweep.php?source=all" class="btn btn-outline-secondary btn-sm <?php echo $sourceFilter === 'all' ? 'active' : ''; ?>">All Sources</a>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="d-flex flex-wrap gap-3 mb-4 mt-3">
                <div class="stat-card">
                    <div class="stat-val text-primary"><?php echo number_format($coverageStats['total']); ?></div>
                    <div class="stat-lbl">Total Inventory</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val text-success"><?php echo number_format($coverageStats['observed']); ?></div>
                    <div class="stat-lbl">Observed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val text-warning"><?php echo number_format($coverageStats['unobserved']); ?></div>
                    <div class="stat-lbl">Unobserved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val text-danger"><?php echo number_format($coverageStats['with_errors']); ?></div>
                    <div class="stat-lbl">Pages w/ Errors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val text-danger"><?php echo number_format($coverageStats['total_errors']); ?></div>
                    <div class="stat-lbl">Error Log Entries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val <?php echo $pendingCount > 0 ? 'text-warning' : 'text-success'; ?>"><?php echo number_format($pendingCount); ?></div>
                    <div class="stat-lbl">Pending Sweep</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val text-secondary"><?php echo number_format($moduleCount); ?></div>
                    <div class="stat-lbl">Modules</div>
                </div>
                <div class="stat-card" style="min-width:180px;">
                    <div class="stat-val <?php echo $coveragePct >= 80 ? 'text-success' : ($coveragePct >= 50 ? 'text-warning' : 'text-danger'); ?>"><?php echo $coveragePct; ?>%</div>
                    <div class="stat-lbl">Coverage</div>
                    <div class="progress-bar-coverage mt-1">
                        <div class="bar-fill" style="width:<?php echo $coveragePct; ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card mb-3">
                <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-secondary">Source: <?php echo htmlspecialchars($sourceFilter, ENT_QUOTES); ?></span>
                    <form method="post" class="d-inline-block m-0">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="seed_inventory">
                        <button type="submit" class="btn btn-sm btn-outline-dark">
                            <i class="ph-database me-1"></i> Seed Inventory
                        </button>
                    </form>
                    <button type="button" id="selectAllBtn" class="btn btn-sm btn-light"><i class="ph-check-square me-1"></i>Select All</button>
                    <button type="button" id="clearAllBtn" class="btn btn-sm btn-light"><i class="ph-square me-1"></i>Clear All</button>
                    <button type="button" id="runSelectedBtn" class="btn btn-sm btn-success ms-auto">
                        <i class="ph-play me-1"></i> Run Selected Sweep
                    </button>
                </div>
            </div>

            <?php if ($seedNotice !== ''): ?>
                <div class="alert <?php echo $seedSummary['failed'] > 0 ? 'alert-warning' : 'alert-success'; ?> mb-3">
                    <?php echo htmlspecialchars($seedNotice, ENT_QUOTES); ?>
                </div>
            <?php endif; ?>

            <div id="sweepSummary" class="alert alert-info mb-3" style="display:none;"></div>

            <?php if ($pendingCount === 0): ?>
                <div class="alert alert-success"><i class="ph-check-circle me-2"></i>No pending unobserved entrypoints for this source filter. All pages are observed!</div>
            <?php else: ?>

                <div class="sweep-table-card">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 sweep-table">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th style="width:170px;">Module</th>
                                    <th>Page Path</th>
                                    <th>Request URL</th>
                                    <th style="width:120px;">Source</th>
                                    <th style="width:130px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groupedRows as $moduleName => $moduleRows): ?>
                                    <tr class="module-row">
                                        <td colspan="6">
                                            <i class="ph-folder me-1"></i>
                                            <?php echo htmlspecialchars(ucfirst(str_replace(['_', '-'], ' ', $moduleName)), ENT_QUOTES); ?>
                                            <span class="module-count"><?php echo count($moduleRows); ?> pages</span>
                                        </td>
                                    </tr>
                                    <?php foreach ($moduleRows as $row): ?>
                                        <?php
                                        $pagePath    = (string)($row['page_path'] ?? ($row['page_name'] ?? 'unknown'));
                                        $requestPath = (string)($row['request_path'] ?? $pagePath);
                                        $source      = (string)($row['source_channel'] ?? 'unknown');
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input sweep-check" checked
                                                    data-url="<?php echo htmlspecialchars($requestPath, ENT_QUOTES); ?>"
                                                    data-module="<?php echo htmlspecialchars($moduleName, ENT_QUOTES); ?>">
                                            </td>
                                            <td><span class="text-muted"><?php echo htmlspecialchars($moduleName, ENT_QUOTES); ?></span></td>
                                            <td><a href="<?php echo htmlspecialchars($requestPath, ENT_QUOTES); ?>" target="_blank" rel="noopener"><code><?php echo htmlspecialchars($pagePath, ENT_QUOTES); ?></code></a></td>
                                            <td><code class="text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($requestPath, ENT_QUOTES); ?></code></td>
                                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($source, ENT_QUOTES); ?></span></td>
                                            <td><span class="badge bg-secondary status-pill">Pending</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function toggleModuleCard(moduleId) {
    var card = document.getElementById('card-' + moduleId);
    var body = document.getElementById('body-' + moduleId);
    if (!card || !body) return;
    var collapsed = card.classList.contains('collapsed');
    if (collapsed) {
        body.style.display = '';
        card.classList.remove('collapsed');
    } else {
        body.style.display = 'none';
        card.classList.add('collapsed');
    }
}

(function () {
    var selectAllBtn   = document.getElementById('selectAllBtn');
    var clearAllBtn    = document.getElementById('clearAllBtn');
    var expandAllBtn   = document.getElementById('expandAllBtn');
    var collapseAllBtn = document.getElementById('collapseAllBtn');
    var runBtn         = document.getElementById('runSelectedBtn');
    var summaryBox     = document.getElementById('sweepSummary');

    function getChecks() {
        return Array.prototype.slice.call(document.querySelectorAll('.sweep-check'));
    }

    function setStatus(check, label, cls) {
        var row = check.closest('tr');
        if (!row) return;
        var pill = row.querySelector('.status-pill');
        if (!pill) return;
        pill.className = 'badge status-pill ' + cls;
        pill.textContent = label;
    }

    // Module-level select-all checkboxes
    document.querySelectorAll('.module-select-all').forEach(function (modCb) {
        modCb.addEventListener('change', function () {
            var mod = modCb.getAttribute('data-module');
            document.querySelectorAll('.sweep-check[data-module="' + mod + '"]').forEach(function (cb) {
                cb.checked = modCb.checked;
            });
        });
    });

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            getChecks().forEach(function (cb) { cb.checked = true; });
            document.querySelectorAll('.module-select-all').forEach(function (cb) { cb.checked = true; });
        });
    }

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function () {
            getChecks().forEach(function (cb) { cb.checked = false; });
            document.querySelectorAll('.module-select-all').forEach(function (cb) { cb.checked = false; });
        });
    }

    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function () {
            document.querySelectorAll('.module-card').forEach(function (card) {
                var bodyId = card.id.replace('card-', 'body-');
                var body = document.getElementById(bodyId);
                if (body) body.style.display = '';
                card.classList.remove('collapsed');
            });
        });
    }

    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function () {
            document.querySelectorAll('.module-card').forEach(function (card) {
                var bodyId = card.id.replace('card-', 'body-');
                var body = document.getElementById(bodyId);
                if (body) body.style.display = 'none';
                card.classList.add('collapsed');
            });
        });
    }

    async function runSweep() {
        var checks = getChecks().filter(function (cb) { return cb.checked; });
        if (!checks.length) {
            alert('Select at least one entrypoint to run.');
            return;
        }

        checks.sort(function (a, b) {
            var am = (a.getAttribute('data-module') || '').toLowerCase();
            var bm = (b.getAttribute('data-module') || '').toLowerCase();
            var ap = am === '404' ? 0 : (am === '500' ? 1 : 2);
            var bp = bm === '404' ? 0 : (bm === '500' ? 1 : 2);
            if (ap !== bp) return ap - bp;
            return 0;
        });

        runBtn.disabled = true;
        var ok = 0, fail = 0;

        for (var i = 0; i < checks.length; i++) {
            var cb = checks[i];
            var url = cb.getAttribute('data-url') || '';
            if (!url) {
                setStatus(cb, 'Skipped', 'bg-warning');
                fail++;
                continue;
            }
            setStatus(cb, 'Running…', 'bg-primary');
            try {
                var response = await fetch(url, {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store',
                    redirect: 'follow'
                });
                if (response.ok) {
                    setStatus(cb, 'OK ' + response.status, 'bg-success');
                    ok++;
                } else {
                    setStatus(cb, 'HTTP ' + response.status, 'bg-danger');
                    fail++;
                }
            } catch (err) {
                setStatus(cb, 'Failed', 'bg-danger');
                fail++;
            }
            await new Promise(function (resolve) { setTimeout(resolve, 250); });
        }

        runBtn.disabled = false;
        if (summaryBox) {
            summaryBox.style.display = 'block';
            summaryBox.innerHTML = '<strong>Sweep finished.</strong> Success: <strong>' + ok + '</strong> | Failed: <strong>' + fail + '</strong>' +
                ' &mdash; <a href="view_backend_error_logs.php">Refresh backend logs</a> to verify coverage changes.';
        }
    }

    if (runBtn) {
        runBtn.addEventListener('click', runSweep);
    }
})();
</script>

    <?php include('admin_elements/copyright.php'); ?>
</div>
<?php include('admin_elements/admin_footer.php'); ?>

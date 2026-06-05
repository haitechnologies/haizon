<?php
$dbStatus = 'Online';
$dbOnline = true;

if (!isset($mysqli) || !$mysqli->ping()) {
    $dbStatus = 'Offline';
    $dbOnline = false;
}

$dbHost = $_ENV['DB_HOSTNAME'] ?? 'localhost';
$phpVersion = phpversion();
$maxExecution = ini_get('max_execution_time');
$executionDisplay = $maxExecution == 0 ? 'Unlimited' : $maxExecution . 's';
$serverTime = date('d M Y g:ia');
?>

<div class="hai-health-panel" aria-label="System health summary">
    <div class="hai-health-header">
        <h3>System Health</h3>
        <span class="hai-health-status <?php echo $dbOnline ? 'ok' : 'down'; ?>">
            <span class="dot"></span>
            <?php echo $dbOnline ? 'Live' : 'Check Required'; ?>
        </span>
    </div>

    <div class="hai-health-grid">
        <div class="hai-health-item">
            <span class="label">Database</span>
            <strong><?php echo e($dbStatus); ?></strong>
        </div>
        <div class="hai-health-item">
            <span class="label">Host</span>
            <strong><?php echo e($dbHost); ?></strong>
        </div>
        <div class="hai-health-item">
            <span class="label">PHP</span>
            <strong><?php echo htmlspecialchars($phpVersion); ?></strong>
        </div>
        <div class="hai-health-item">
            <span class="label">Max Exec</span>
            <strong><?php echo htmlspecialchars($executionDisplay); ?></strong>
        </div>
        <div class="hai-health-item">
            <span class="label">Server Time</span>
            <strong><?php echo htmlspecialchars($serverTime); ?></strong>
        </div>
    </div>
</div>

<style>
.hai-health-panel {
    margin-bottom: 12px;
    background: #fff;
    border: 1px solid #dde5f0;
    border-radius: 10px;
    padding: 10px 12px;
}

.hai-health-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
}

.hai-health-header h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: #25334a;
}

.hai-health-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.hai-health-status .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
}

.hai-health-status.ok { color: #1f7a61; }
.hai-health-status.ok .dot { background: #1f9d7c; }
.hai-health-status.down { color: #b33b35; }
.hai-health-status.down .dot { background: #d9534f; }

.hai-health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 8px;
}

.hai-health-item {
    background: #f9fbfe;
    border: 1px solid #e6edf7;
    border-radius: 8px;
    padding: 7px 8px;
    min-height: 50px;
}

.hai-health-item .label {
    display: block;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #637893;
    margin-bottom: 4px;
}

.hai-health-item strong {
    font-size: 12px;
    color: #1e2f48;
    font-weight: 600;
    word-break: break-word;
}
</style>

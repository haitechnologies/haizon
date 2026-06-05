<?php
include('admin_elements/admin_header.php');

$module = 'dashboard';
$module_caption = 'Dashboard';
$error_message = '';
$success_message = '';

function dashboardCount(mysqli $mysqli, $sql)
{
    $res = $mysqli->query($sql);
    if (!$res) {
        return 0;
    }

    $row = $res->fetch_assoc();
    return (int)($row['cnt'] ?? 0);
}

function dashboardTableExists(mysqli $mysqli, string $table): bool
{
    $stmt = $mysqli->prepare(
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $table);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function dashboardColumnExists(mysqli $mysqli, string $table, string $column): bool
{
    $stmt = $mysqli->prepare(
        "SELECT 1
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function dashboardTailLines($filePath, $maxLines = 60)
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return [];
    }

    $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines) || empty($lines)) {
        return [];
    }

    return array_slice($lines, -$maxLines);
}

function dashboardRecentErrorEntries($filePath, $limit = 3)
{
    $tailLines = dashboardTailLines($filePath, 80);
    if (empty($tailLines)) {
        return [];
    }

    $errorPattern = '/\b(PHP|Fatal|Error|Exception|Warning|Notice|Deprecated|CRITICAL|FATAL|WARNING)\b/i';
    $filtered = [];
    foreach ($tailLines as $line) {
        $clean = trim((string)$line);
        if ($clean === '') {
            continue;
        }
        if (preg_match($errorPattern, $clean)) {
            $filtered[] = $clean;
        }
    }

    if (empty($filtered)) {
        $filtered = $tailLines;
    }

    return array_slice($filtered, -$limit);
}

function dashboardLogSeverity($line)
{
    $text = (string)$line;

    if (preg_match('/\b(FATAL|Fatal error|PHP Fatal|CRITICAL)\b/i', $text)) {
        return 'fatal';
    }

    if (preg_match('/\b(ERROR|Exception|Type Error|Parse error|Syntax Error)\b/i', $text)) {
        return 'error';
    }

    if (preg_match('/\b(WARNING|Notice|Deprecated|Strict)\b/i', $text)) {
        return 'warning';
    }

    return 'info';
}

$companiesTableExists = dashboardTableExists($mysqli, DB::COMPANIES);
$favoritesTableExists = dashboardTableExists($mysqli, DB::FRONTEND_USER_FAVORITES);

$total_companies = $companiesTableExists
    ? dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::COMPANIES . "`")
    : 0;
$new_companies_7d = $companiesTableExists
    ? dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::COMPANIES . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
    : 0;

$recentCompaniesLimit = 5;
$recentCompanies = [];
$recentCompaniesResult = false;
if ($companiesTableExists) {
    $companyNameExpr = "CONCAT('Company #', c.id)";
    if (dashboardColumnExists($mysqli, DB::COMPANIES, 'company_name')) {
        $companyNameExpr = "COALESCE(NULLIF(c.company_name, ''), CONCAT('Company #', c.id))";
    } elseif (dashboardColumnExists($mysqli, DB::COMPANIES, 'name')) {
        $companyNameExpr = "COALESCE(NULLIF(c.name, ''), CONCAT('Company #', c.id))";
    } elseif (dashboardColumnExists($mysqli, DB::COMPANIES, 'display_name')) {
        $companyNameExpr = "COALESCE(NULLIF(c.display_name, ''), CONCAT('Company #', c.id))";
    }

    $companyEmailExpr = "'-'";
    if (dashboardColumnExists($mysqli, DB::COMPANIES, 'email')) {
        $companyEmailExpr = "COALESCE(NULLIF(c.email, ''), '-')";
    }

    $recentCompaniesResult = $mysqli->query(
        "SELECT c.id,
                " . $companyNameExpr . " AS company_name,
                c.created_at,
                " . $companyEmailExpr . " AS added_by_email
         FROM `" . DB::COMPANIES . "` c
         ORDER BY c.created_at DESC
         LIMIT " . (int)$recentCompaniesLimit
    );
    if ($recentCompaniesResult) {
        while ($companyRow = $recentCompaniesResult->fetch_assoc()) {
            $recentCompanies[] = $companyRow;
        }
    }
}

$recentFrontendUsersLimit = 5;
$recentFrontendUsers = [];
$recentFrontendUsersResult = $mysqli->query(
    "SELECT id,
            COALESCE(NULLIF(full_name, ''), NULLIF(email, ''), CONCAT('User #', id)) AS user_name,
            COALESCE(NULLIF(email, ''), '-') AS user_email,
            created_at
     FROM `" . DB::FRONTEND_USERS . "`
     ORDER BY created_at DESC
     LIMIT " . (int)$recentFrontendUsersLimit
);
if ($recentFrontendUsersResult) {
    while ($userRow = $recentFrontendUsersResult->fetch_assoc()) {
        $recentFrontendUsers[] = $userRow;
    }
}

$latestSearchesLimit = 5;
$latestSearches = [];
$latestSearchesResult = $mysqli->query(
    "SELECT id,
            COALESCE(NULLIF(search_query, ''), '(empty search)') AS search_query,
            COALESCE(NULLIF(search_type, ''), 'general') AS search_type,
            created_at
     FROM `" . DB::SEARCHES . "`
     ORDER BY created_at DESC
     LIMIT " . (int)$latestSearchesLimit
);
if ($latestSearchesResult) {
    while ($searchRow = $latestSearchesResult->fetch_assoc()) {
        $latestSearches[] = $searchRow;
    }
}

$recentEmailsLimit = 5;
$recentDashboardEmails = [];
$recentWebsiteEmails = [];

$recentDashboardEmailsResult = $mysqli->query(
    "SELECT eh.recipient_email, eh.subject, COALESCE(eh.sent_at, eh.created_at) AS sent_time
     FROM `" . DB::EMAIL_HISTORY . "` eh
     LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
     WHERE eh.status = 'sent'
       AND (du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0))
     ORDER BY COALESCE(eh.sent_at, eh.created_at) DESC
     LIMIT " . (int)$recentEmailsLimit
);
if ($recentDashboardEmailsResult) {
    while ($emailRow = $recentDashboardEmailsResult->fetch_assoc()) {
        $recentDashboardEmails[] = $emailRow;
    }
}

$recentWebsiteEmailsResult = $mysqli->query(
    "SELECT eh.recipient_email, eh.subject, COALESCE(eh.sent_at, eh.created_at) AS sent_time
     FROM `" . DB::EMAIL_HISTORY . "` eh
     LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
     WHERE eh.status = 'sent'
       AND NOT (du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0))
     ORDER BY COALESCE(eh.sent_at, eh.created_at) DESC
     LIMIT " . (int)$recentEmailsLimit
);
if ($recentWebsiteEmailsResult) {
    while ($emailRow = $recentWebsiteEmailsResult->fetch_assoc()) {
        $recentWebsiteEmails[] = $emailRow;
    }
}

// Companies trend for last 30 days (including today) used in mini bars under the Companies card.
$companies30d = [];
$companies30dMap = [];
$companies30dResult = false;
if ($companiesTableExists) {
    $companies30dResult = $mysqli->query(
        "SELECT DATE(created_at) AS day_key, COUNT(*) AS cnt
         FROM `" . DB::COMPANIES . "`
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY DATE(created_at)"
    );
    if ($companies30dResult) {
        while ($trendRow = $companies30dResult->fetch_assoc()) {
            $key = (string)($trendRow['day_key'] ?? '');
            if ($key !== '') {
                $companies30dMap[$key] = (int)($trendRow['cnt'] ?? 0);
            }
        }
    }
}

for ($i = 29; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime('-' . $i . ' day'));
    $companies30d[] = [
        'label' => date('d M', strtotime($dateKey)),
        'count' => (int)($companies30dMap[$dateKey] ?? 0),
    ];
}

$companies30dMax = 1;
foreach ($companies30d as $point) {
    if ((int)$point['count'] > $companies30dMax) {
        $companies30dMax = (int)$point['count'];
    }
}

// Searches trend for last 30 days.
$searches30d = [];
$searches30dMap = [];
$searches30dResult = $mysqli->query(
    "SELECT DATE(created_at) AS day_key, COUNT(*) AS cnt
     FROM `" . DB::SEARCHES . "`
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(created_at)"
);
if ($searches30dResult) {
    while ($trendRow = $searches30dResult->fetch_assoc()) {
        $key = (string)($trendRow['day_key'] ?? '');
        if ($key !== '') {
            $searches30dMap[$key] = (int)($trendRow['cnt'] ?? 0);
        }
    }
}
for ($i = 29; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime('-' . $i . ' day'));
    $searches30d[] = [
        'label' => date('d M', strtotime($dateKey)),
        'count' => (int)($searches30dMap[$dateKey] ?? 0),
    ];
}
$searches30dMax = 1;
foreach ($searches30d as $point) {
    if ((int)$point['count'] > $searches30dMax) {
        $searches30dMax = (int)$point['count'];
    }
}

// Enquiries trend for last 30 days.
$inquiries30d = [];
$inquiries30dMap = [];
$inquiries30dResult = $mysqli->query(
    "SELECT DATE(created_at) AS day_key, COUNT(*) AS cnt
     FROM `" . DB::INQUIRIES . "`
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(created_at)"
);
if ($inquiries30dResult) {
    while ($trendRow = $inquiries30dResult->fetch_assoc()) {
        $key = (string)($trendRow['day_key'] ?? '');
        if ($key !== '') {
            $inquiries30dMap[$key] = (int)($trendRow['cnt'] ?? 0);
        }
    }
}
for ($i = 29; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime('-' . $i . ' day'));
    $inquiries30d[] = [
        'label' => date('d M', strtotime($dateKey)),
        'count' => (int)($inquiries30dMap[$dateKey] ?? 0),
    ];
}
$inquiries30dMax = 1;
foreach ($inquiries30d as $point) {
    if ((int)$point['count'] > $inquiries30dMax) {
        $inquiries30dMax = (int)$point['count'];
    }
}

// Frontend users trend for last 30 days.
$frontendUsers30d = [];
$frontendUsers30dMap = [];
$frontendUsers30dResult = $mysqli->query(
    "SELECT DATE(created_at) AS day_key, COUNT(*) AS cnt
     FROM `" . DB::FRONTEND_USERS . "`
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(created_at)"
);
if ($frontendUsers30dResult) {
    while ($trendRow = $frontendUsers30dResult->fetch_assoc()) {
        $key = (string)($trendRow['day_key'] ?? '');
        if ($key !== '') {
            $frontendUsers30dMap[$key] = (int)($trendRow['cnt'] ?? 0);
        }
    }
}
for ($i = 29; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime('-' . $i . ' day'));
    $frontendUsers30d[] = [
        'label' => date('d M', strtotime($dateKey)),
        'count' => (int)($frontendUsers30dMap[$dateKey] ?? 0),
    ];
}
$frontendUsers30dMax = 1;
foreach ($frontendUsers30d as $point) {
    if ((int)$point['count'] > $frontendUsers30dMax) {
        $frontendUsers30dMax = (int)$point['count'];
    }
}

$today_searches = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::SEARCHES . "` WHERE DATE(created_at) = CURDATE()");
$today_inquiries = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::INQUIRIES . "` WHERE DATE(created_at) = CURDATE()");
$week_searches = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::SEARCHES . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$week_inquiries = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::INQUIRIES . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");



$total_frontend_users = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::FRONTEND_USERS . "`");
$new_frontend_users_7d = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::FRONTEND_USERS . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
// Engagement rate: percent of users with favorites (legacy metric, still used in dashboard)
$users_with_favorites = $favoritesTableExists
    ? dashboardCount($mysqli, "SELECT COUNT(DISTINCT user_id) AS cnt FROM `" . DB::FRONTEND_USER_FAVORITES . "`")
    : 0;
$engagement_rate = $total_frontend_users > 0 ? round(($users_with_favorites / $total_frontend_users) * 100, 1) : 0;
// $users_with_favorites and $engagement_rate removed (User Favorites block removed)

$total_blogs = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::BLOGS . "` WHERE is_active=1");
$total_hs_codes = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::HS_CODES . "`");
$emails_sent_total = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_HISTORY . "` WHERE status = 'sent'");
$emails_sent_24h = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_HISTORY . "` WHERE status = 'sent' AND DATE(COALESCE(sent_at, created_at)) = CURDATE()");
$emails_sent_7d = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_HISTORY . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$emails_sent_dashboard_total = dashboardCount(
        $mysqli,
        "SELECT COUNT(*) AS cnt
         FROM `" . DB::EMAIL_HISTORY . "` eh
         LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
         WHERE eh.status = 'sent'
             AND (du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0))"
);
$emails_sent_dashboard_24h = dashboardCount(
        $mysqli,
        "SELECT COUNT(*) AS cnt
         FROM `" . DB::EMAIL_HISTORY . "` eh
         LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
         WHERE eh.status = 'sent'
             AND DATE(COALESCE(eh.sent_at, eh.created_at)) = CURDATE()
             AND (du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0))"
);
$emails_sent_website_total = max(0, $emails_sent_total - $emails_sent_dashboard_total);
$emails_sent_website_24h = max(0, $emails_sent_24h - $emails_sent_dashboard_24h);
$emails_pending = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_QUEUE . "` WHERE status IN ('pending','queued','retry')");
$email_daily_limit_total = dashboardCount(
    $mysqli,
    "SELECT COALESCE(SUM(CASE WHEN daily_limit > 0 THEN daily_limit ELSE 100 END), 0) AS cnt
     FROM `" . DB::EMAIL_PROVIDERS . "`
     WHERE is_active = 1"
);
$emails_remaining_24h = max(0, $email_daily_limit_total - $emails_sent_24h);
$email_providers_count = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_PROVIDERS . "`");
$disposable_email_domains_count = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::DISPOSABLE_EMAIL_DOMAINS . "`");
$banned_words_count = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::BANNED_WORDS . "`");

$total_customers = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::CUSTOMERS . "`");
$new_customers_7d = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::CUSTOMERS . "` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

$total_dashboard_users = dashboardCount($mysqli, "SELECT COUNT(*) AS cnt FROM `" . DB::USERS . "`");
$active_alerts = 0; // System Alerts block removed

$adminLogPath = __DIR__ . '/CONSOLIDATED_ERROR_LOG.txt';
if (!is_file($adminLogPath)) {
    $adminLogPath = __DIR__ . '/error_log.txt';
}

$frontendLogPath = function_exists('resolveFrontendErrorLogPath')
    ? resolveFrontendErrorLogPath()
    : dirname(__DIR__) . '/logs/FRONTEND_ERROR_LOG.txt';

$adminLogCount = (int)($unread_error_logs_count ?? 0);
$publicLogCount = (int)($frontend_error_logs_count ?? 0);

$adminRecentEntries = dashboardRecentErrorEntries($adminLogPath, 5);
$publicRecentEntries = dashboardRecentErrorEntries($frontendLogPath, 5);

$dashboardView = strtolower((string)($_GET['view'] ?? 'compact'));
if (!in_array($dashboardView, ['compact', 'detailed'], true)) {
    $dashboardView = 'compact';
}
$isDetailedView = ($dashboardView === 'detailed');
?>


<style>
    @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');

    :root {
        --ink: #0f1c2f;
        --ink-soft: #4b5d76;
        --surface: #f5f7fb;
        --panel: #ffffff;
        --line: #dde5f0;
        --sky: #2f80ed;
        --mint: #1e9f80;
        --amber: #c9921a;
        --danger: #d9534f;
    }

    .dashboard-shell {
        font-family: 'IBM Plex Sans', sans-serif;
        color: var(--ink);
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 18px;
    }

    .dashboard-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .dashboard-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }

    .pulse-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 12px;
        color: var(--ink-soft);
    }

    .meta-pill {
        background: #fff;
        border: 1px solid var(--line);
        padding: 5px 9px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .view-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 8px;
        padding: 4px;
    }

    .view-toggle a {
        text-decoration: none;
        color: var(--ink-soft);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        padding: 5px 8px;
        border-radius: 6px;
    }

    .view-toggle a.active {
        background: var(--ink);
        color: #fff;
    }

    .module-badge-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }

    .module-badge-strip.module-badge-strip-right {
        justify-content: flex-end;
    }

    .module-badge-strip a {
        text-decoration: none;
    }

    .module-badge-strip .badge {
        border: 1px solid #d8e0eb;
        font-size: 12px;
        font-weight: 600;
        padding: 7px 10px;
    }

    .detailed-only {
        display: none;
    }

    .dashboard-shell.detailed .detailed-only {
        display: block;
    }

    .priority-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .stat-card {
        background: var(--panel);
        border-radius: 18px;
        box-shadow: 0 2px 16px 0 rgba(44, 62, 80, 0.10);
        padding: 16px 16px 12px 16px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        min-height: 80px;
        position: relative;
        overflow: hidden;
        border: none;
        opacity: 0;
        transform: translateY(30px) scale(0.98);
        animation: statCardFadeIn 0.7s cubic-bezier(.4, 1.4, .6, 1) forwards;
        animation-delay: var(--card-delay, 0s);
        transition: box-shadow 0.22s, transform 0.22s;
    }

    .stat-card:hover {
        box-shadow: 0 8px 32px 0 rgba(44, 62, 80, 0.16);
        transform: translateY(-2px) scale(1.01);
    }

    @keyframes statCardFadeIn {
        0% {
            opacity: 0;
            transform: translateY(30px) scale(0.98);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .stat-accent {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 7px;
        border-radius: 18px 0 0 18px;
        background: linear-gradient(180deg, var(--sky) 0%, var(--mint) 100%);
        box-shadow: 0 0 8px 0 rgba(47, 128, 237, 0.08);
    }

    .stat-content {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .stat-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 2px;
        letter-spacing: 0.01em;
    }

    .stat-title a {
        color: inherit;
        text-decoration: none;
    }

    .stat-title a:hover {
        color: var(--sky);
        text-decoration: underline;
    }

    .stat-metrics {
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin-bottom: 2px;
    }

    .stat-metrics span,
    .stat-metrics strong,
    .stat-metrics small {
        font-size: 15px !important;
        font-weight: 400 !important;
        color: var(--ink-soft);
    }

    .mini-trend-wrap {
        margin-top: 6px;
    }

    .mini-trend-bars {
        display: flex;
        align-items: flex-end;
        gap: 2px;
        height: 34px;
    }

    .mini-trend-bar {
        width: 7px;
        border-radius: 3px 3px 0 0;
        background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%);
        opacity: 0.92;
        transition: opacity .18s ease;
    }

    .mini-trend-bar.today {
        background: linear-gradient(180deg, #34d399 0%, #059669 100%);
    }

    .mini-trend-bar:hover {
        opacity: 1;
    }

    .mini-trend-caption {
        margin-top: 3px;
        font-size: 11px;
        color: var(--ink-soft);
    }

    .company-card {
        border: 1px solid #e3ebf7;
        box-shadow: 0 4px 18px rgba(29, 45, 67, 0.08);
        padding: 14px 14px 12px 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    }

    .company-card .stat-accent {
        display: none;
    }

    .company-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }

    .company-total-wrap {
        text-align: right;
        min-width: 96px;
    }

    .company-total-label {
        display: block;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6a7f99;
        margin-bottom: 1px;
    }

    .company-total-value {
        display: block;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 26px;
        line-height: 1;
        font-weight: 700;
        color: #153a6b;
    }

    .company-kpis {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 8px;
    }

    .company-kpi {
        background: #fff;
        border: 1px solid #e5edf8;
        border-radius: 8px;
        padding: 6px 8px;
    }

    .company-kpi-label {
        display: block;
        font-size: 10px;
        color: #6f8199;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .company-kpi-value {
        display: block;
        font-size: 15px;
        font-weight: 700;
        color: #18365f;
    }

    .email-card {
        border: 1px solid #e3ebf7;
        box-shadow: 0 4px 18px rgba(29, 45, 67, 0.08);
        padding: 14px 14px 12px 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    }

    .email-card .stat-accent {
        display: none;
    }

    .email-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }

    .email-main-wrap {
        text-align: right;
        min-width: 110px;
    }

    .email-main-label {
        display: block;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6a7f99;
        margin-bottom: 1px;
    }

    .email-main-value {
        display: block;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 26px;
        line-height: 1;
        font-weight: 700;
        color: #153a6b;
    }

    .email-kpis {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .email-kpi {
        background: #fff;
        border: 1px solid #e5edf8;
        border-radius: 8px;
        padding: 6px 8px;
    }

    .email-kpi.full {
        grid-column: 1 / -1;
    }

    .email-kpi-label {
        display: block;
        font-size: 10px;
        color: #6f8199;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .email-kpi-value {
        display: block;
        font-size: 15px;
        font-weight: 700;
        color: #18365f;
    }

    .frontend-users-card {
        border: 1px solid #dcefe8;
        box-shadow: 0 4px 18px rgba(20, 90, 70, 0.10);
        padding: 14px 14px 12px 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f3fbf8 100%);
    }

    .frontend-users-card .stat-accent {
        display: none;
    }

    .frontend-users-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }

    .frontend-users-total {
        text-align: right;
        min-width: 96px;
    }

    .frontend-users-total-label {
        display: block;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #5f7f74;
        margin-bottom: 1px;
    }

    .frontend-users-total-value {
        display: block;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 26px;
        line-height: 1;
        font-weight: 700;
        color: #0f5f48;
    }

    .frontend-users-strip {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .frontend-pill {
        flex: 1;
        border-radius: 999px;
        padding: 6px 10px;
        border: 1px solid #d8ece4;
        background: #fff;
        font-size: 12px;
        color: #2b6252;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .frontend-pill strong {
        font-weight: 700;
        color: #114f3d;
    }

    .searches-card .stat-accent,
    .enquiries-card .stat-accent {
        display: none;
    }

    .stat-total,
    .stat-week,
    .stat-today,
    .stat-pending,
    .stat-approved,
    .stat-sent,
    .stat-engaged {
        font-size: 14px !important;
        font-weight: 400 !important;
        color: var(--ink-soft);
    }

    .stat-actions a,
    .stat-foot a {
        font-size: 14px;
        color: var(--sky);
        text-decoration: underline;
        font-weight: 500;
        padding: 0;
        background: none;
        border: none;
        transition: color 0.18s;
    }

    .stat-actions a:hover,
    .stat-foot a:hover {
        color: var(--mint);
    }

    .stat-kicker {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--ink-soft);
    }

    .stat-value {
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(20px, 2.1vw, 28px);
        font-weight: 700;
        margin: 0;
    }

    .stat-foot {
        font-size: 12px;
        color: var(--ink-soft);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }

    .pill {
        padding: 2px 7px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .pill.sky {
        background: rgba(47, 128, 237, 0.14);
        color: #1f63bb;
    }

    .pill.mint {
        background: rgba(30, 159, 128, 0.14);
        color: #1f7963;
    }

    .pill.coral {
        background: rgba(217, 83, 79, 0.14);
        color: #a53532;
    }

    .pill.gold {
        background: rgba(201, 146, 26, 0.15);
        color: #8a6411;
    }

    .signal-row {
        margin-top: 12px;
        display: grid;
        gap: 10px;
        grid-template-columns: 1.2fr 1fr;
    }

    .signal-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .signal-title {
        font-weight: 600;
        font-size: 13px;
    }

    .mini-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .mini-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        padding: 7px 0;
        border-bottom: 1px dashed #e5ecf5;
    }

    .mini-list li:last-child {
        border-bottom: 0;
    }

    .mini-list strong {
        color: var(--ink);
    }

    .recent-company-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .recent-company-list li {
        padding: 6px 0;
        border-bottom: 1px dashed #e5ecf5;
    }

    .recent-company-list li:last-child {
        border-bottom: 0;
    }

    .recent-company-name {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--ink);
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .recent-company-meta {
        display: block;
        font-size: 11px;
        color: var(--ink-soft);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .recent-companies-card {
        border: 1px solid #e4ebf7;
        box-shadow: 0 4px 16px rgba(32, 52, 84, 0.08);
        background: linear-gradient(180deg, #ffffff 0%, #f8faff 100%);
        padding: 12px 12px 10px 12px;
    }

    .recent-companies-card .stat-accent {
        display: none;
    }

    .recent-companies-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }

    .recent-companies-count {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #43648e;
        background: #eef4ff;
        border: 1px solid #d8e4fb;
        border-radius: 999px;
        padding: 3px 8px;
        white-space: nowrap;
    }

    .recent-companies-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .recent-companies-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        padding: 7px 0;
        border-bottom: 1px dashed #e4ecf8;
    }

    .recent-companies-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .recent-companies-main {
        min-width: 0;
        flex: 1;
    }

    .recent-companies-name {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #17385f;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .recent-companies-email {
        display: block;
        font-size: 11px;
        color: #627b99;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .recent-companies-time {
        font-size: 10px;
        color: #7288a3;
        white-space: nowrap;
        padding-top: 1px;
    }

    .recent-emails-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .recent-emails-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        padding: 7px 0;
        border-bottom: 1px dashed #e4ecf8;
    }

    .recent-emails-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .recent-emails-main {
        min-width: 0;
        flex: 1;
    }

    .recent-emails-subject {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #17385f;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .recent-emails-recipient {
        display: block;
        font-size: 11px;
        color: #627b99;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .recent-emails-time {
        font-size: 10px;
        color: #7288a3;
        white-space: nowrap;
        padding-top: 1px;
    }

    .quick-links {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .quick-links a {
        text-decoration: none;
        background: var(--ink);
        color: #fff;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 11px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .quick-links a.secondary {
        background: transparent;
        color: var(--ink);
        border: 1px solid var(--line);
    }

    .logs-overview-grid {
        margin-top: 10px;
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .log-overview-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 10px 12px;
    }

    .log-overview-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .log-overview-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--ink);
        margin: 0;
    }

    .log-overview-count {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .05em;
        background: #f6f9ff;
        border: 1px solid #dbe7fb;
        color: #365f9b;
        border-radius: 999px;
        padding: 3px 8px;
    }

    .log-overview-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .log-overview-list li {
        font-size: 12px;
        color: #425875;
        padding: 4px 0;
        border-bottom: 1px dashed #e4ebf6;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .log-overview-list li.sev-fatal {
        background: #fff1f1;
        border-left: 3px solid #c93531;
        padding-left: 8px;
        color: #7c2623;
    }

    .log-overview-list li.sev-error {
        background: #fff5f2;
        border-left: 3px solid #de5c38;
        padding-left: 8px;
        color: #8a3d26;
    }

    .log-overview-list li.sev-warning {
        background: #fff9ef;
        border-left: 3px solid #d29a2e;
        padding-left: 8px;
        color: #6f551d;
    }

    .log-overview-list li.sev-info {
        background: #f4f8ff;
        border-left: 3px solid #3d75c9;
        padding-left: 8px;
        color: #264f88;
    }

    .log-overview-list li:last-child {
        border-bottom: 0;
    }

    .log-overview-footer {
        margin-top: 8px;
        display: flex;
        justify-content: flex-end;
    }

    .log-overview-footer a {
        font-size: 12px;
        color: #245ea9;
        text-decoration: none;
    }

    .log-overview-empty {
        font-size: 12px;
        color: #6a7f99;
        padding: 4px 0;
    }

    @media (max-width: 768px) {
        .dashboard-shell {
            padding: 12px;
            border-radius: 10px;
        }

        .signal-row {
            grid-template-columns: 1fr;
        }

        .logs-overview-grid {
            grid-template-columns: 1fr;
        }

        .view-toggle {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<div class="content-wrapper">
    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <section class="dashboard-shell <?php echo $isDetailedView ? 'detailed' : 'compact'; ?>">
                <div class="priority-grid">
                    <div class="stat-card company-card" style="--card-delay:0.05s">
                        <div class="stat-content">
                            <div class="company-card-head">
                                <div class="stat-title">Companies</div>
                                <div class="company-total-wrap">
                                    <span class="company-total-label">Total</span>
                                    <span class="company-total-value"><?php echo number_format($total_companies); ?></span>
                                </div>
                            </div>
                            <div class="company-kpis">
                                <div class="company-kpi">
                                    <span class="company-kpi-label">Added Today</span>
                                    <span class="company-kpi-value"><?php echo number_format($new_companies_today ?? 0); ?></span>
                                </div>
                                <div class="company-kpi">
                                    <span class="company-kpi-label">Added In 7 Days</span>
                                    <span class="company-kpi-value"><?php echo number_format($new_companies_7d); ?></span>
                                </div>
                            </div>
                            <div class="mini-trend-wrap" aria-label="Companies trend for last 30 days">
                                <div class="mini-trend-bars">
                                    <?php foreach ($companies30d as $trendIndex => $trendPoint): ?>
                                        <?php
                                        $trendCount = (int)($trendPoint['count'] ?? 0);
                                        $trendLabel = (string)($trendPoint['label'] ?? '');
                                        $barHeight = (int)max(4, round(($trendCount / max(1, $companies30dMax)) * 30));
                                        $isTodayBar = ($trendIndex === count($companies30d) - 1);
                                        ?>
                                        <span
                                            class="mini-trend-bar <?php echo $isTodayBar ? 'today' : ''; ?>"
                                            style="height: <?php echo $barHeight; ?>px;"
                                            title="<?php echo htmlspecialchars($trendLabel, ENT_QUOTES); ?>: <?php echo $trendCount; ?> companies"></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mini-trend-caption">Last 30 days (rightmost is today)</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card searches-card" style="--card-delay:0.12s">
                        <div class="stat-accent"></div>
                        <div class="stat-content">
                            <div class="stat-title"><a href="listing_searches.php">Searches</a></div>
                            <div class="stat-metrics">
                                <span class="stat-today"><strong><?php echo number_format($today_searches); ?></strong> <small>today</small></span>
                                <span class="stat-week"><strong><?php echo number_format($week_searches); ?></strong> <small>this week</small></span>
                                <span class="stat-total">Total: <?php echo number_format($total_searches ?? 0); ?></span>
                                <div class="mini-trend-wrap" aria-label="Searches trend for last 30 days">
                                    <div class="mini-trend-bars">
                                        <?php foreach ($searches30d as $trendIndex => $trendPoint): ?>
                                            <?php
                                            $trendCount = (int)($trendPoint['count'] ?? 0);
                                            $trendLabel = (string)($trendPoint['label'] ?? '');
                                            $barHeight = (int)max(4, round(($trendCount / max(1, $searches30dMax)) * 30));
                                            $isTodayBar = ($trendIndex === count($searches30d) - 1);
                                            ?>
                                            <span
                                                class="mini-trend-bar <?php echo $isTodayBar ? 'today' : ''; ?>"
                                                style="height: <?php echo $barHeight; ?>px;"
                                                title="<?php echo htmlspecialchars($trendLabel, ENT_QUOTES); ?>: <?php echo $trendCount; ?> searches"></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mini-trend-caption">Last 30 days (rightmost is today)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card enquiries-card" style="--card-delay:0.19s">
                        <div class="stat-accent"></div>
                        <div class="stat-content">
                            <div class="stat-title"><a href="listing_inquiries.php">Enquiries</a></div>
                            <div class="stat-metrics">
                                <span class="stat-today"><strong><?php echo number_format($today_inquiries); ?></strong> <small>today</small></span>
                                <span class="stat-week"><strong><?php echo number_format($week_inquiries); ?></strong> <small>this week</small></span>
                                <span class="stat-total">Total: <?php echo number_format($total_inquiries ?? 0); ?></span>
                                <div class="mini-trend-wrap" aria-label="Enquiries trend for last 30 days">
                                    <div class="mini-trend-bars">
                                        <?php foreach ($inquiries30d as $trendIndex => $trendPoint): ?>
                                            <?php
                                            $trendCount = (int)($trendPoint['count'] ?? 0);
                                            $trendLabel = (string)($trendPoint['label'] ?? '');
                                            $barHeight = (int)max(4, round(($trendCount / max(1, $inquiries30dMax)) * 30));
                                            $isTodayBar = ($trendIndex === count($inquiries30d) - 1);
                                            ?>
                                            <span
                                                class="mini-trend-bar <?php echo $isTodayBar ? 'today' : ''; ?>"
                                                style="height: <?php echo $barHeight; ?>px;"
                                                title="<?php echo htmlspecialchars($trendLabel, ENT_QUOTES); ?>: <?php echo $trendCount; ?> enquiries"></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mini-trend-caption">Last 30 days (rightmost is today)</div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="stat-card frontend-users-card" style="--card-delay:0.40s">
                        <div class="stat-content">
                            <div class="frontend-users-head">
                                <div class="stat-title"><a href="listing_frontend_users.php">Frontend Users</a></div>
                                <div class="frontend-users-total">
                                    <span class="frontend-users-total-label">Total Users</span>
                                    <span class="frontend-users-total-value"><?php echo number_format($total_frontend_users); ?></span>
                                </div>
                            </div>
                            <div class="frontend-users-strip">
                                <span class="frontend-pill"><strong>+<?php echo number_format($new_frontend_users_7d); ?></strong> in 7d</span>
                                <span class="frontend-pill"><strong><?php echo $engagement_rate; ?>%</strong> engaged</span>
                            </div>
                            <div class="mini-trend-wrap" aria-label="Frontend users trend for last 30 days">
                                <div class="mini-trend-bars">
                                    <?php foreach ($frontendUsers30d as $trendIndex => $trendPoint): ?>
                                        <?php
                                        $trendCount = (int)($trendPoint['count'] ?? 0);
                                        $trendLabel = (string)($trendPoint['label'] ?? '');
                                        $barHeight = (int)max(4, round(($trendCount / max(1, $frontendUsers30dMax)) * 30));
                                        $isTodayBar = ($trendIndex === count($frontendUsers30d) - 1);
                                        ?>
                                        <span
                                            class="mini-trend-bar <?php echo $isTodayBar ? 'today' : ''; ?>"
                                            style="height: <?php echo $barHeight; ?>px;"
                                            title="<?php echo htmlspecialchars($trendLabel, ENT_QUOTES); ?>: <?php echo $trendCount; ?> users"></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mini-trend-caption">Last 30 days (rightmost is today)</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card email-card" style="--card-delay:0.33s">
                        <div class="stat-content">
                            <div class="email-card-head">
                                <div class="stat-title"><a href="listing_email_queue.php">Emails Sent</a></div>
                                <div class="email-main-wrap">
                                    <span class="email-main-label">Sent In 24h</span>
                                    <span class="email-main-value"><?php echo number_format($emails_sent_24h); ?></span>
                                </div>
                            </div>
                            <div class="email-kpis">
                                <div class="email-kpi">
                                    <span class="email-kpi-label">Dashboard (24h)</span>
                                    <span class="email-kpi-value"><?php echo number_format($emails_sent_dashboard_24h); ?></span>
                                </div>
                                <div class="email-kpi">
                                    <span class="email-kpi-label">Website (24h)</span>
                                    <span class="email-kpi-value"><?php echo number_format($emails_sent_website_24h); ?></span>
                                </div>
                                <div class="email-kpi">
                                    <span class="email-kpi-label">Pending Queue</span>
                                    <span class="email-kpi-value"><?php echo number_format($emails_pending); ?></span>
                                </div>
                                <div class="email-kpi">
                                    <span class="email-kpi-label">Sent Total</span>
                                    <span class="email-kpi-value"><?php echo number_format($emails_sent_total); ?></span>
                                </div>
                                <div class="email-kpi">
                                    <span class="email-kpi-label">Dashboard Total</span>
                                    <span class="email-kpi-value"><?php echo number_format($emails_sent_dashboard_total); ?></span>
                                </div>
                                <div class="email-kpi">
                                    <span class="email-kpi-label">Website Total</span>
                                    <span class="email-kpi-value"><?php echo number_format($emails_sent_website_total); ?></span>
                                </div>
                                <div class="email-kpi">
                                    <span class="email-kpi-label">Daily Limit</span>
                                    <span class="email-kpi-value"><?php echo number_format($email_daily_limit_total); ?></span>
                                </div>
                                <div class="email-kpi">
                                    <span class="email-kpi-label">Remaining 24h</span>
                                    <span class="email-kpi-value"><?php echo number_format($emails_remaining_24h); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="module-badge-strip mt-3" aria-label="Important module counts">
                    <a href="listing_hscodes.php" title="Open HS Codes listing">
                        <span class="badge bg-light text-body">HS Codes: <?php echo number_format($total_hs_codes); ?></span>
                    </a>
                    <a href="listing_blogs.php" title="Open Blogs listing">
                        <span class="badge bg-light text-body">Blogs Published: <?php echo number_format($total_blogs); ?></span>
                    </a>
                    <a href="listing_customers.php" title="Open CRM Customers listing">
                        <span class="badge bg-light text-body">CRM Customers: <?php echo number_format($total_customers); ?></span>
                    </a>
                    <a href="listing_cron_jobs.php" title="Open Cron Jobs listing">
                        <span class="badge bg-light text-body">Cron Jobs</span>
                    </a>
                    <a href="listing_banned_words.php" title="Open Banned Words listing">
                        <span class="badge bg-light text-body">Banned Words: <?php echo number_format($banned_words_count ?? 0); ?></span>
                    </a>
                </div>

                <div class="module-badge-strip module-badge-strip-right mt-2" aria-label="Email module counts">
                    <a href="listing_email_providers.php" title="Open Email Providers listing">
                        <span class="badge bg-light text-body">Email Providers: <?php echo number_format($email_providers_count ?? 0); ?></span>
                    </a>
                    <a href="listing_email_history.php" title="Open Email History listing">
                        <span class="badge bg-light text-body">Email History: <?php echo number_format($emails_sent_total); ?></span>
                    </a>
                    <a href="listing_email_queue.php" title="Open Email Queue listing">
                        <span class="badge bg-light text-body">Email Queue Total: <?php echo number_format($emails_pending ?? 0); ?></span>
                    </a>
                    <a href="listing_disposable_email_domains.php" title="Open Disposable Email Domains listing">
                        <span class="badge bg-light text-body">Disposable Email: <?php echo number_format($disposable_email_domains_count ?? 0); ?></span>
                    </a>
                </div>




                <div class="logs-overview-grid">
                    <div class="log-overview-card">
                        <div class="log-overview-head">
                            <h3 class="log-overview-title">Admin Error Logs</h3>
                            <a href="view_backend_error_logs.php">Open Admin Logs</a>
                        </div>
                        <?php if (!empty($adminRecentEntries)): ?>
                            <ul class="log-overview-list">
                                <?php foreach ($adminRecentEntries as $entry): ?>
                                    <?php $entrySeverity = dashboardLogSeverity($entry); ?>
                                    <li class="sev-<?php echo htmlspecialchars($entrySeverity, ENT_QUOTES); ?>" title="<?php echo htmlspecialchars($entry, ENT_QUOTES); ?>"><?php echo htmlspecialchars($entry); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="log-overview-empty">No recent admin log events.</div>
                        <?php endif; ?>
                    </div>

                    <div class="log-overview-card">
                        <div class="log-overview-head">
                            <h3 class="log-overview-title">Public Error Logs</h3>
                            <a href="view_frontend_error_logs.php">Open Public Logs</a>
                        </div>
                        <?php if (!empty($publicRecentEntries)): ?>
                            <ul class="log-overview-list">
                                <?php foreach ($publicRecentEntries as $entry): ?>
                                    <?php $entrySeverity = dashboardLogSeverity($entry); ?>
                                    <li class="sev-<?php echo htmlspecialchars($entrySeverity, ENT_QUOTES); ?>" title="<?php echo htmlspecialchars($entry, ENT_QUOTES); ?>"><?php echo htmlspecialchars($entry); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="log-overview-empty">No recent public log events.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- New row: 3 summary cards for new companies, users, emails -->
                <div class="row mt-3 mb-2">
                    <div class="col-12 col-md-4 mb-3">
                        <div class="stat-card recent-companies-card" style="min-height:90px;">
                            <div class="stat-accent"></div>
                            <div class="stat-content">
                                <div class="recent-companies-head">
                                    <div class="stat-title">Recent Companies</div>
                                    <span class="recent-companies-count"><?php echo count($recentCompanies); ?> latest</span>
                                </div>
                                <?php if (!empty($recentCompanies)): ?>
                                    <ul class="recent-companies-list">
                                        <?php foreach ($recentCompanies as $companyRow): ?>
                                            <?php
                                            $companyName = trim((string)($companyRow['company_name'] ?? ''));
                                            $companyName = ($companyName !== '') ? $companyName : 'Untitled company';
                                            $addedByEmail = trim((string)($companyRow['added_by_email'] ?? '-'));
                                            $companyCreatedAtRaw = trim((string)($companyRow['created_at'] ?? ''));
                                            $companyCreatedAtTs = ($companyCreatedAtRaw !== '') ? strtotime($companyCreatedAtRaw) : false;
                                            $companyCreatedAtLabel = ($companyCreatedAtTs !== false)
                                                ? date('d M, h:i A', $companyCreatedAtTs)
                                                : '-';
                                            ?>
                                            <li class="recent-companies-item">
                                                <div class="recent-companies-main">
                                                    <span class="recent-companies-name" title="<?php echo htmlspecialchars($companyName, ENT_QUOTES); ?>"><?php echo htmlspecialchars($companyName); ?></span>
                                                    <span class="recent-companies-email" title="<?php echo htmlspecialchars($addedByEmail, ENT_QUOTES); ?>">Added by: <?php echo htmlspecialchars($addedByEmail); ?></span>
                                                </div>
                                                <span class="recent-companies-time" title="<?php echo htmlspecialchars($companyCreatedAtRaw, ENT_QUOTES); ?>"><?php echo htmlspecialchars($companyCreatedAtLabel); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="stat-foot"><span>No recent company entries.</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <div class="stat-card" style="min-height:90px;">
                            <div class="stat-accent"></div>
                            <div class="stat-content">
                                <div class="stat-title"><a href="listing_frontend_users.php">Recent Registered Users</a></div>
                                <?php if (!empty($recentFrontendUsers)): ?>
                                    <ul class="recent-company-list">
                                        <?php foreach ($recentFrontendUsers as $userRow): ?>
                                            <?php
                                            $userName = trim((string)($userRow['user_name'] ?? ''));
                                            $userName = ($userName !== '') ? $userName : 'Unnamed user';
                                            $userEmail = trim((string)($userRow['user_email'] ?? '-'));
                                            ?>
                                            <li>
                                                <span class="recent-company-name" title="<?php echo htmlspecialchars($userName, ENT_QUOTES); ?>"><?php echo htmlspecialchars($userName); ?></span>
                                                <span class="recent-company-meta" title="<?php echo htmlspecialchars($userEmail, ENT_QUOTES); ?>">Email: <?php echo htmlspecialchars($userEmail); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="stat-foot"><span>No recent user registrations.</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <div class="stat-card" style="min-height:90px;">
                            <div class="stat-accent"></div>
                            <div class="stat-content">
                                <div class="stat-title"><a href="listing_searches.php">Latest Searches</a></div>
                                <?php if (!empty($latestSearches)): ?>
                                    <ul class="recent-company-list">
                                        <?php foreach ($latestSearches as $searchRow): ?>
                                            <?php
                                            $searchQuery = trim((string)($searchRow['search_query'] ?? ''));
                                            $searchQuery = ($searchQuery !== '') ? $searchQuery : '(empty search)';
                                            $searchType = trim((string)($searchRow['search_type'] ?? 'general'));
                                            ?>
                                            <li>
                                                <span class="recent-company-name" title="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES); ?>"><?php echo htmlspecialchars($searchQuery); ?></span>
                                                <span class="recent-company-meta" title="<?php echo htmlspecialchars($searchType, ENT_QUOTES); ?>">Type: <?php echo htmlspecialchars($searchType); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="stat-foot"><span>No recent searches.</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-12 col-md-6 mb-3">
                        <div class="stat-card" style="min-height:90px;">
                            <div class="stat-accent"></div>
                            <div class="stat-content">
                                <div class="recent-companies-head">
                                    <div class="stat-title"><a href="listing_email_history.php">Emails Sent from Dashboard</a></div>
                                    <span class="recent-companies-count"><?php echo count($recentDashboardEmails); ?> latest</span>
                                </div>
                                <?php if (!empty($recentDashboardEmails)): ?>
                                    <ul class="recent-emails-list">
                                        <?php foreach ($recentDashboardEmails as $emailRow): ?>
                                            <?php
                                            $emailSubject = trim((string)($emailRow['subject'] ?? ''));
                                            $emailSubject = ($emailSubject !== '') ? $emailSubject : '(No Subject)';
                                            $recipientEmail = trim((string)($emailRow['recipient_email'] ?? '-'));
                                            $sentTimeRaw = trim((string)($emailRow['sent_time'] ?? ''));
                                            $sentTimeTs = ($sentTimeRaw !== '') ? strtotime($sentTimeRaw) : false;
                                            $sentTimeLabel = ($sentTimeTs !== false) ? date('d M, h:i A', $sentTimeTs) : '-';
                                            ?>
                                            <li class="recent-emails-item">
                                                <div class="recent-emails-main">
                                                    <span class="recent-emails-subject" title="<?php echo htmlspecialchars($emailSubject, ENT_QUOTES); ?>"><?php echo htmlspecialchars($emailSubject); ?></span>
                                                    <span class="recent-emails-recipient" title="<?php echo htmlspecialchars($recipientEmail, ENT_QUOTES); ?>">To: <?php echo htmlspecialchars($recipientEmail); ?></span>
                                                </div>
                                                <span class="recent-emails-time" title="<?php echo htmlspecialchars($sentTimeRaw, ENT_QUOTES); ?>"><?php echo htmlspecialchars($sentTimeLabel); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="stat-foot"><span>No recent dashboard email sends.</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="stat-card" style="min-height:90px;">
                            <div class="stat-accent"></div>
                            <div class="stat-content">
                                <div class="recent-companies-head">
                                    <div class="stat-title"><a href="listing_email_history.php">Emails Sent from Website</a></div>
                                    <span class="recent-companies-count"><?php echo count($recentWebsiteEmails); ?> latest</span>
                                </div>
                                <?php if (!empty($recentWebsiteEmails)): ?>
                                    <ul class="recent-emails-list">
                                        <?php foreach ($recentWebsiteEmails as $emailRow): ?>
                                            <?php
                                            $emailSubject = trim((string)($emailRow['subject'] ?? ''));
                                            $emailSubject = ($emailSubject !== '') ? $emailSubject : '(No Subject)';
                                            $recipientEmail = trim((string)($emailRow['recipient_email'] ?? '-'));
                                            $sentTimeRaw = trim((string)($emailRow['sent_time'] ?? ''));
                                            $sentTimeTs = ($sentTimeRaw !== '') ? strtotime($sentTimeRaw) : false;
                                            $sentTimeLabel = ($sentTimeTs !== false) ? date('d M, h:i A', $sentTimeTs) : '-';
                                            ?>
                                            <li class="recent-emails-item">
                                                <div class="recent-emails-main">
                                                    <span class="recent-emails-subject" title="<?php echo htmlspecialchars($emailSubject, ENT_QUOTES); ?>"><?php echo htmlspecialchars($emailSubject); ?></span>
                                                    <span class="recent-emails-recipient" title="<?php echo htmlspecialchars($recipientEmail, ENT_QUOTES); ?>">To: <?php echo htmlspecialchars($recipientEmail); ?></span>
                                                </div>
                                                <span class="recent-emails-time" title="<?php echo htmlspecialchars($sentTimeRaw, ENT_QUOTES); ?>"><?php echo htmlspecialchars($sentTimeLabel); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="stat-foot"><span>No recent website email sends.</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================
                 EMAIL TOUCHPOINTS REFERENCE MAP
                 Every file / entry-point that sends email from this system.
                 Update this block whenever a new email trigger is added.
                 ============================================================ -->
                <style>
                    .etp-section {
                        margin-top: 28px;
                    }

                    .etp-section-head {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        flex-wrap: wrap;
                        gap: 10px;
                        padding: 10px 16px 10px 16px;
                        background: linear-gradient(90deg, #1e293b 0%, #0f172a 100%);
                        border-radius: 12px 12px 0 0;
                    }

                    .etp-section-title {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        font-size: 13px;
                        font-weight: 700;
                        color: #f1f5f9;
                        letter-spacing: .01em;
                    }

                    .etp-section-title i {
                        font-size: 17px;
                        color: #60a5fa;
                    }

                    .etp-ref-badge {
                        background: rgba(148, 163, 184, .18);
                        color: #94a3b8;
                        font-size: 10px;
                        font-weight: 600;
                        letter-spacing: .06em;
                        padding: 2px 8px;
                        border-radius: 999px;
                        text-transform: uppercase;
                    }

                    .etp-quick-links {
                        display: flex;
                        gap: 6px;
                        flex-wrap: wrap;
                    }

                    .etp-quick-links a {
                        font-size: 11px;
                        font-weight: 600;
                        padding: 4px 10px;
                        border-radius: 7px;
                        text-decoration: none;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        transition: background .15s, color .15s;
                    }

                    .etp-quick-links a.ql-history {
                        background: #1d4ed8;
                        color: #fff;
                    }

                    .etp-quick-links a.ql-history:hover {
                        background: #2563eb;
                    }

                    .etp-quick-links a.ql-queue {
                        background: #374151;
                        color: #e2e8f0;
                    }

                    .etp-quick-links a.ql-queue:hover {
                        background: #4b5563;
                    }

                    .etp-quick-links a.ql-send {
                        background: #065f46;
                        color: #d1fae5;
                    }

                    .etp-quick-links a.ql-send:hover {
                        background: #047857;
                    }

                    .etp-body {
                        background: #0f172a;
                        border-radius: 0 0 12px 12px;
                        padding: 14px 14px 10px 14px;
                    }

                    .etp-grid {
                        display: grid;
                        gap: 10px;
                        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    }

                    .etp-card {
                        background: #1e293b;
                        border-radius: 9px;
                        overflow: hidden;
                        border: 1px solid rgba(255, 255, 255, .06);
                    }

                    .etp-card-head {
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        padding: 7px 12px;
                        font-size: 11.5px;
                        font-weight: 700;
                        letter-spacing: .01em;
                    }

                    .etp-card-head i {
                        font-size: 14px;
                    }

                    /* Per-category accent colours */
                    .etp-card.etp-blue .etp-card-head {
                        background: rgba(59, 130, 246, .18);
                        color: #93c5fd;
                        border-bottom: 1px solid rgba(59, 130, 246, .25);
                    }

                    .etp-card.etp-blue .etp-card-head i {
                        color: #60a5fa;
                    }

                    .etp-card.etp-green .etp-card-head {
                        background: rgba(16, 185, 129, .18);
                        color: #6ee7b7;
                        border-bottom: 1px solid rgba(16, 185, 129, .25);
                    }

                    .etp-card.etp-green .etp-card-head i {
                        color: #34d399;
                    }

                    .etp-card.etp-amber .etp-card-head {
                        background: rgba(245, 158, 11, .15);
                        color: #fcd34d;
                        border-bottom: 1px solid rgba(245, 158, 11, .22);
                    }

                    .etp-card.etp-amber .etp-card-head i {
                        color: #fbbf24;
                    }

                    .etp-card.etp-violet .etp-card-head {
                        background: rgba(139, 92, 246, .18);
                        color: #c4b5fd;
                        border-bottom: 1px solid rgba(139, 92, 246, .25);
                    }

                    .etp-card.etp-violet .etp-card-head i {
                        color: #a78bfa;
                    }

                    .etp-card.etp-rose .etp-card-head {
                        background: rgba(244, 63, 94, .15);
                        color: #fda4af;
                        border-bottom: 1px solid rgba(244, 63, 94, .22);
                    }

                    .etp-card.etp-rose .etp-card-head i {
                        color: #fb7185;
                    }

                    .etp-card.etp-slate .etp-card-head {
                        background: rgba(100, 116, 139, .18);
                        color: #cbd5e1;
                        border-bottom: 1px solid rgba(100, 116, 139, .25);
                    }

                    .etp-card.etp-slate .etp-card-head i {
                        color: #94a3b8;
                    }

                    .etp-table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 11px;
                    }

                    .etp-table th {
                        padding: 5px 10px 4px;
                        color: #475569;
                        font-size: 9.5px;
                        text-transform: uppercase;
                        letter-spacing: .07em;
                        font-weight: 700;
                        white-space: nowrap;
                        border-bottom: 1px solid rgba(255, 255, 255, .05);
                    }

                    .etp-table td {
                        padding: 6px 10px;
                        color: #cbd5e1;
                        border-bottom: 1px solid rgba(255, 255, 255, .04);
                        vertical-align: middle;
                    }

                    .etp-table tr:last-child td {
                        border-bottom: none;
                    }

                    .etp-table tr:hover td {
                        background: rgba(255, 255, 255, .04);
                    }

                    .etp-table code {
                        background: rgba(148, 163, 184, .1);
                        color: #7dd3fc;
                        font-size: 10px;
                        padding: 1px 5px;
                        border-radius: 4px;
                        white-space: nowrap;
                        font-family: 'JetBrains Mono', monospace;
                    }

                    /* Method pill badges */
                    .etp-pill {
                        display: inline-block;
                        font-size: 9.5px;
                        font-weight: 700;
                        padding: 2px 7px;
                        border-radius: 999px;
                        letter-spacing: .04em;
                        white-space: nowrap;
                    }

                    .etp-pill.smtp {
                        background: rgba(59, 130, 246, .2);
                        color: #93c5fd;
                    }

                    .etp-pill.queue {
                        background: rgba(16, 185, 129, .18);
                        color: #6ee7b7;
                    }

                    .etp-pill.both {
                        background: rgba(245, 158, 11, .18);
                        color: #fcd34d;
                    }

                    .etp-footer {
                        margin-top: 10px;
                        padding: 7px 4px 0;
                        font-size: 10.5px;
                        color: #475569;
                        border-top: 1px solid rgba(255, 255, 255, .06);
                        line-height: 1.6;
                    }

                    .etp-footer code {
                        color: #7dd3fc;
                        background: rgba(148, 163, 184, .1);
                        padding: 1px 5px;
                        border-radius: 4px;
                    }
                </style>

                <section class="etp-section">
                    <div class="etp-section-head">
                        <div class="etp-section-title">
                            <i class="ph-paper-plane-tilt"></i>
                            System Email Touchpoints
                            <span class="etp-ref-badge">Reference Map</span>
                        </div>
                        <div class="etp-quick-links">
                            <a href="listing_email_history.php" class="ql-history"><i class="ph-clock-counter-clockwise"></i> Email History</a>
                            <a href="listing_email_queue.php" class="ql-queue"><i class="ph-stack"></i> Queue</a>
                            <a href="send_email.php" class="ql-send"><i class="ph-paper-plane"></i> Send Email</a>
                        </div>
                    </div>

                    <div class="etp-body">
                        <div class="etp-grid">

                            <!-- Frontend / Public Pages -->
                            <div class="etp-card etp-blue">
                                <div class="etp-card-head"><i class="ph-globe"></i> Frontend / Public Pages</div>
                                <table class="etp-table">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Trigger / Recipient</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>pages/register.php</code></td>
                                            <td>Admin notification on new registration</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>pages/contact.php</code></td>
                                            <td>Support team + auto-reply to sender</td>
                                            <td><span class="etp-pill both">SMTP + Queue</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- User Authentication -->
                            <div class="etp-card etp-green">
                                <div class="etp-card-head"><i class="ph-lock-key"></i> User Authentication</div>
                                <table class="etp-table">
                                    <thead>
                                        <tr>
                                            <th>Class / Method</th>
                                            <th>Trigger / Recipient</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>FrontendUsers::sendVerificationEmail</code></td>
                                            <td>Email verification link to new user</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>FrontendUsers::sendPasswordResetEmail</code></td>
                                            <td>Password reset link to user</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Admin / Dashboard Actions -->
                            <div class="etp-card etp-amber">
                                <div class="etp-card-head"><i class="ph-gauge"></i> Admin / Dashboard Actions</div>
                                <table class="etp-table">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Trigger / Recipient</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>dashboard/send_email.php</code></td>
                                            <td>Manual bulk / targeted send by admin</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>Companies module (decommissioned)</code></td>
                                            <td>Direct company email flow removed with listing decommission</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>email_test.php / ajax_send_test_email.php</code></td>
                                            <td>SMTP provider test send</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Cron Jobs -->
                            <div class="etp-card etp-violet">
                                <div class="etp-card-head"><i class="ph-clock"></i> Cron Jobs (Scheduled)</div>
                                <table class="etp-table">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Trigger / Recipient</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>cron/email_alerts.php</code></td>
                                            <td>Error / system alerts → admin</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>cron/email_digest.php</code></td>
                                            <td>Weekly digest → registered users</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Email Automation -->
                            <div class="etp-card etp-rose">
                                <div class="etp-card-head"><i class="ph-robot"></i> Email Automation (EmailTriggers)</div>
                                <table class="etp-table">
                                    <thead>
                                        <tr>
                                            <th>Function</th>
                                            <th>Trigger</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>triggerWelcomeSequence()</code></td>
                                            <td>Welcome series on signup</td>
                                            <td><span class="etp-pill queue">queueEmail</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>triggerProfileReminderSequence()</code></td>
                                            <td>Profile completion reminders</td>
                                            <td><span class="etp-pill queue">queueEmail</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>triggerVerificationEarnedEmail()</code></td>
                                            <td>Verification badge awarded</td>
                                            <td><span class="etp-pill queue">queueEmail</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>triggerReferralIncentiveEmail()</code></td>
                                            <td>Referral reward earned</td>
                                            <td><span class="etp-pill queue">queueEmail</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>triggerWeeklySummaryEmail()</code></td>
                                            <td>Weekly activity summary</td>
                                            <td><span class="etp-pill queue">queueEmail</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- System / Config Layer -->
                            <div class="etp-card etp-slate">
                                <div class="etp-card-head"><i class="ph-gear-six"></i> System / Config Layer</div>
                                <table class="etp-table">
                                    <thead>
                                        <tr>
                                            <th>File / Function</th>
                                            <th>Purpose</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>config/error_alerting.php</code></td>
                                            <td>High-priority error alerts → admin</td>
                                            <td><span class="etp-pill queue">EmailQueue</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>globals.php::queueEmail()</code></td>
                                            <td>Core helper — queues any email via template</td>
                                            <td><span class="etp-pill queue">EmailQueue</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>globals.php::sendEmailDirect()</code></td>
                                            <td>Immediate send without queueing</td>
                                            <td><span class="etp-pill smtp">SMTPMailer</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div><!-- /etp-grid -->

                        <div class="etp-footer">
                            <i class="ph-info me-1"></i>
                            All paths use <strong style="color:#7dd3fc;">SMTPMailer</strong> (database-configured provider) or <strong style="color:#6ee7b7;">EmailQueue</strong> (async via <code>hai_email_queue</code>).
                            Update this block whenever new email-sending code is added.
                        </div>
                    </div>
                </section>
                <!-- /EMAIL TOUCHPOINTS REFERENCE MAP -->

            </section>
        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
<?php
/**
 * Cron Job: Email Alert System - Phase 3B.5
 * 
 * Sends email alerts for saved searches with new matching companies.
 * Schedule: Daily at 6 AM UTC+3 / Weekly Mondays 6 AM UTC+3
 * 
 * Linux: 0 6 * * * /usr/bin/php /home/user/public_html/cron/email_alerts.php
 * Windows Task Scheduler: Schedule php.exe to run this file daily at 6 AM
 */

// ============================================
// SECTION 1: DEPENDENCIES
// ============================================
define('CRON_SCRIPT', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/SavedSearches.php';
require_once __DIR__ . '/../classes/frontend/Companies.php';
require_once __DIR__ . '/../classes/SMTPMailer.php';

function tableExists(mysqli $conn, string $table): bool {
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
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

// Logging setup
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/email_alerts_errors.log');

$logFile = __DIR__ . '/email_alerts.log';
$timestamp = date('Y-m-d H:i:s');

function logMsg($msg) {
    global $logFile, $timestamp;
    $line = "[{$timestamp}] {$msg}\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

logMsg("====== Email Alerts Cron Started ======");

if (!tableExists($conn, DB::COMPANIES)) {
    logMsg("[SKIP] " . DB::COMPANIES . " table not found. Email alerts disabled for decommissioned company module.");
    $conn->close();
    exit(0);
}

// ============================================
// SECTION 2: DETERMINE ALERT FREQUENCIES TO PROCESS
// ============================================
$hour = (int)date('H', time());
$dayOfWeek = (int)date('N'); // 1=Monday
$frequencies = ['daily']; // Always process daily

if ($hour === 6 && $dayOfWeek === 1) {
    $frequencies[] = 'weekly';
    logMsg("Monday 6 AM detected - processing weekly alerts");
}

// ============================================
// SECTION 3: PROCESS EACH SAVED SEARCH
// ============================================
try {
    $savedSearches = new SavedSearches($conn);
    $companies = new Companies($conn);
    
    $totalSent = 0;
    $totalFailed = 0;
    
    foreach ($frequencies as $frequency) {
        logMsg("Processing {$frequency} searches...");
        $searches = $savedSearches->getSearchesWithAlerts($frequency);
        logMsg("Found " . count($searches) . " searches");
        
        foreach ($searches as $search) {
            $searchId = $search['id'];
            $userId = $search['user_id'];
            $query = $search['search_query'];
            $email = $search['email'];
            $fullName = $search['full_name'];
            
            // Skip unverified emails
            if (!$search['email_verified']) {
                logMsg("âŠ˜ Skipping - email unverified: {$email}");
                continue;
            }
            
            // Search for matching companies
            $results = $companies->search($query, 10, 0);
            
            if (!empty($results)) {
                if (sendAlert($email, $fullName, $search['search_name'], $query, $results)) {
                    logMsg("âœ“ Sent to {$email}");
                    
                    // Update timestamp
                    $sql = "UPDATE `" . DB::SEARCHES . "` SET last_email_sent_at = NOW() WHERE id = ? AND user_id = ? AND search_type = 'saved' AND is_active = 1";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ii', $searchId, $userId);
                    $stmt->execute();
                    $stmt->close();
                    $totalSent++;
                } else {
                    logMsg("âœ— Failed: {$email}");
                    $totalFailed++;
                }
            }
        }
    }
    
    logMsg("====== Complete: {$totalSent} sent, {$totalFailed} failed ======\n");
    
} catch (Exception $e) {
    logMsg("ERROR: " . $e->getMessage());
    exit(1);
}

$conn->close();
exit(0);

// ============================================
// SECTION 4: SEND EMAIL FUNCTION
// ============================================
function sendAlert($email, $fullName, $searchName, $query, $results) {
    try {
        $mailer = new SMTPMailer();
        $subject = "New companies: {$searchName}";
        $htmlBody = buildAlertHTML($fullName, $searchName, $query, $results);
        return $mailer->send($email, $subject, $htmlBody, [
            'from_name' => 'UAE Business',
        ]);
    } catch (\Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

// ============================================
// SECTION 5: EMAIL TEMPLATE
// ============================================
function buildAlertHTML($fullName, $searchName, $query, $results) {
    $siteUrl = 'http://127.0.0.1/haipulse';
    $resultsList = '';
    
    foreach (array_slice($results, 0, 10) as $company) {
        $name = htmlspecialchars($company['company_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $location = htmlspecialchars(($company['city'] ?? '') . ', ' . ($company['state'] ?? ''), ENT_QUOTES, 'UTF-8');
        $slug = urlencode($company['slug'] ?? '');
        $companyUrl = "{$siteUrl}/company/{$slug}";
        
        $resultsList .= "
            <div style=\"margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;\">
                <h3 style=\"margin: 0 0 8px; color: #667eea;\">
                    <a href=\"{$companyUrl}\" style=\"color: #667eea; text-decoration: none;\">{$name}</a>
                </h3>
                <p style=\"margin: 4px 0; font-size: 13px;\">ðŸ“ {$location}</p>
                <a href=\"{$companyUrl}\" style=\"color: #667eea; text-decoration: none; font-weight: bold;\">View Profile â†’</a>
            </div>
        ";
    }
    
    return "
<!DOCTYPE html>
<html>
<head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"></head>
<body style=\"font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;\">
    <div style=\"max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden;\">
        <div style=\"background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;\">
            <h1 style=\"margin: 0; font-size: 24px;\">ðŸ“¬ New Companies Found!</h1>
        </div>
        <div style=\"padding: 30px 20px;\">
            <p>Hi {$fullName},</p>
            <p>We found <strong>" . count($results) . " new companies</strong> matching your search <strong>\"{$searchName}\"</strong>.</p>
            
            <div style=\"background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #667eea;\">
                <p style=\"margin: 0;\"><strong>Search:</strong> {$searchName}</p>
                <p style=\"margin: 4px 0;\"><strong>Query:</strong> {$query}</p>
            </div>
            
            <h3>New Matching Companies:</h3>
            {$resultsList}
            
            <p style=\"text-align: center;\">
                <a href=\"{$siteUrl}/my-searches\" style=\"background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;\">View All Saved Searches</a>
            </p>
        </div>
        <div style=\"background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #e0e0e0;\">
            <p>Manage alerts on your <a href=\"{$siteUrl}/my-searches\" style=\"color: #667eea;\">Saved Searches</a> page.</p>
        </div>
    </div>
</body>
</html>
    ";
}


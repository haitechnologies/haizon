<?php
/**
 * Cron Job: Email Digest System
 * 
 * Sends weekly/monthly digests of new companies to users with active searches
 * Schedule: Run weekly (Sunday) or monthly via cron:
 * 0 9 * * 0 php /path/to/haipulse/cron/email_digest.php weekly
 * 0 9 1 * * php /path/to/haipulse/cron/email_digest.php monthly
 */

define('CRON_SCRIPT', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

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

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/cron_email_digest.log');

$digestType = $argv[1] ?? 'weekly';
$dateRangeDays = $digestType === 'monthly' ? 30 : 7;

echo "[" . date('Y-m-d H:i:s') . "] Starting {$digestType} digest job...\n";

if (!tableExists($conn, DB::COMPANIES)) {
    echo "[" . date('Y-m-d H:i:s') . "] Skipping digest: companies table decommissioned.\n";
    $conn->close();
    exit(0);
}

// ============================================
// FETCH USERS WITH ACTIVE SEARCHES
// ============================================
$userQuery = "
    SELECT DISTINCT
        fu.id,
        fu.email,
        fu.full_name AS first_name,
        COUNT(ss.id) as search_count
    FROM `" . DB::FRONTEND_USERS . "` fu
    JOIN `" . DB::SEARCHES . "` ss ON fu.id = ss.user_id AND ss.search_type = 'saved' AND ss.is_active = 1
    WHERE fu.email_verified = 1 AND ss.alert_enabled = 1
    GROUP BY fu.id
    HAVING search_count > 0
";

$result = $conn->query($userQuery);
$digestCount = 0;

while ($user = $result->fetch_assoc()) {
    // Get user's searches
    $searchQuery = "
        SELECT id, search_query, search_filters 
        FROM `" . DB::SEARCHES . "` 
        WHERE user_id = ? AND alert_enabled = 1 AND search_type = 'saved' AND is_active = 1
    ";
    
    $stmt = $conn->prepare($searchQuery);
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $searches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Collect all matching companies from all searches
    $allCompanies = [];
    
    foreach ($searches as $search) {
        $filters = json_decode($search['search_filters'] ?? '{}', true) ?? [];
        
        // Build query
        $whereConditions = ['c.publish = 1', 'c.is_active = 1'];
        $params = [];
        $types = '';
        
        if (!empty($filters['keyword'])) {
            $whereConditions[] = '(c.company_name LIKE ? OR c.company_profile LIKE ?)';
            $like = '%' . $filters['keyword'] . '%';
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
        
        if (!empty($filters['category_id'])) {
            $whereConditions[] = 'c.primary_category_id = ?';
            $params[] = (int)$filters['category_id'];
            $types .= 'i';
        }

        if (!empty($filters['emirate'])) {
            $whereConditions[] = '(LOWER(c.city) LIKE LOWER(?) OR LOWER(c.state) LIKE LOWER(?) OR LOWER(c.location) LIKE LOWER(?))';
            $emirateLike = '%' . $filters['emirate'] . '%';
            $params[] = $emirateLike;
            $params[] = $emirateLike;
            $params[] = $emirateLike;
            $types .= 'sss';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $companyQuery = "
            SELECT 
                c.id,
                c.company_name,
                c.slug,
                c.email,
                c.city,
                c.country,
                c.website,
                c.primary_category_id,
                IFNULL(cat.name, 'Business') AS category_name,
                c.created_at
            FROM `" . DB::COMPANIES . "` c
            LEFT JOIN `" . DB::CATEGORIES . "` cat ON c.primary_category_id = cat.id
            WHERE {$whereClause} AND DATE(c.created_at) >= DATE_SUB(NOW(), INTERVAL {$dateRangeDays} DAY)
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($companyQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        $companies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Add to collection with search context
        foreach ($companies as $company) {
            $company['search_name'] = $search['search_query'];
            $allCompanies[] = $company;
        }
    }
    
    // Remove duplicates
    $uniqueCompanies = [];
    $seen = [];
    foreach ($allCompanies as $company) {
        if (!isset($seen[$company['id']])) {
            $uniqueCompanies[] = $company;
            $seen[$company['id']] = true;
        }
    }
    
    // Send digest if companies found
    if (!empty($uniqueCompanies)) {
        if (sendDigestEmail($user, $uniqueCompanies, $digestType)) {
            $digestCount++;
            echo "[OK] Digest sent to {$user['email']}\n";
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Digest job completed. Sent {$digestCount} digests.\n";

// ============================================
// SEND DIGEST EMAIL FUNCTION
// ============================================
function sendDigestEmail($user, $companies, $digestType) {
    global $httpHost;
    
    $typeLabel = $digestType === 'monthly' ? 'Monthly' : 'Weekly';
    $subject = "ðŸ“‹ Your {$typeLabel} Business Digest - " . number_format(count($companies)) . " New Companies";
    
    $html = "
        <h2>Hi " . htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8') . ",</h2>
        <p>Here's your <strong>{$typeLabel} Digest</strong> of new companies matching your saved searches:</p>
        
        <h3>ðŸ†• " . number_format(count($companies)) . " New Companies</h3>
        <table style='width:100%; border-collapse:collapse;'>
    ";
    
    foreach ($companies as $company) {
        $url = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/haipulse/company-detail.php?slug=" . urlencode($company['slug']);
        $html .= "
            <tr style='border-bottom:1px solid #eee;'>
                <td style='padding:12px 0;'>
                    <div style='font-weight:bold;'>
                        <a href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "' style='color:#0f4ad8; text-decoration:none;'>
                            " . htmlspecialchars($company['company_name'], ENT_QUOTES, 'UTF-8') . "
                        </a>
                    </div>
                    <div style='font-size:12px; color:#666;'>
                        " . htmlspecialchars($company['category_name'], ENT_QUOTES, 'UTF-8') . " â€¢ " . htmlspecialchars($company['city'] ?? 'UAE', ENT_QUOTES, 'UTF-8') . "
                    </div>
                    <div style='font-size:11px; color:#999; margin-top:4px;'>
                        Matched: " . htmlspecialchars($company['search_name'], ENT_QUOTES, 'UTF-8') . "
                    </div>
                </td>
            </tr>
        ";
    }
    
    $html .= "
        </table>
        
        <div style='margin-top:20px; text-align:center;'>
            <a href='http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/haipulse/my-searches' style='display:inline-block; padding:10px 20px; background:#0f4ad8; color:white; text-decoration:none; border-radius:4px;'>View All Companies</a>
        </div>
        
        <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>
        <p style='color:#999; font-size:12px;'>
            You received this digest because you have saved searches with alerts enabled. 
            <a href='http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/haipulse/my-searches'>Manage your searches</a>
        </p>
    ";
    
    return sendEmailViaPHPMailer($user['email'], $subject, $html);
}

/**
 * Send email via PHPMailer
 */
function sendEmailViaPHPMailer($email, $subject, $html) {
    require_once __DIR__ . '/../classes/SMTPMailer.php';
    
    try {
        $mailer = new SMTPMailer();
        return $mailer->send($email, $subject, $html, [
            'from_name' => 'UAE Business Directory'
        ]);
    } catch (\Exception $e) {
        error_log("Digest email send failed for {$email}: " . $e->getMessage());
        return false;
    }
}

$conn->close();


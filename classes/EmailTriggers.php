<?php
/**
 * Email Trigger Automation System
 * 
 * Handles automated email sequences for user lifecycle events:
 * - Day 0 (Signup): Welcome email
 * - Day 3: Profile completion reminder
 * - When Verified: Verification earned email
 * - When Referred: Referral incentive email
 * - Every Monday: Weekly summary
 * 
 * Add to config/globals.php for global usage
 */

/**
 * Schedule user email for specific event
 * 
 * @param int $company_id Company ID
 * @param int $user_id User ID (frontend user)
 * @param string $event_type Event that triggered email
 * @param array $variables Custom template variables
 * @param int $delay_days Days to delay sending (0 = immediate)
 * @return array ['success' => bool, 'template' => string, 'scheduled_at' => datetime]
 */
function scheduleUserEmail($company_id, $user_id, $event_type, $variables = [], $delay_days = 0) {
    global $conn;
    
    // Map event types to templates
    $emailMap = [
        'signup'              => 'welcome_day0',
        'profile_incomplete'  => 'follow_up_day3',
        'verification_earned' => 'verification_earned',
        'referral_qualified'  => 'referral_incentive',
        'weekly_summary'      => 'weekly_summary'
    ];
    
    if (!isset($emailMap[$event_type])) {
        return ['success' => false, 'error' => 'Unknown event type: ' . $event_type];
    }
    
    $template = $emailMap[$event_type];
    
    // Get user email address
    $userQuery = "SELECT email FROM " . DB::FRONTEND_USERS . " WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userRow = $result->fetch_assoc();
    
    if (!$userRow) {
        return ['success' => false, 'error' => 'User not found'];
    }
    
    $to_email = $userRow['email'];
    
    // Calculate scheduled time
    $scheduled_at = date('Y-m-d H:i:s', strtotime("+$delay_days days"));
    
    // Queue the email
    $queueResult = queueEmail(
        $to_email,
        $template,
        $variables,
        $company_id,
        $user_id,
        $scheduled_at
    );
    
    if ($queueResult['success']) {
        return [
            'success' => true,
            'template' => $template,
            'scheduled_at' => $scheduled_at,
            'email_id' => $queueResult['email_id']
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to queue email'];
    }
}

/**
 * Schedule welcome email sequence for new user
 * 
 * Called when: New user signs up
 * Sends: Welcome email immediately
 * 
 * @param int $company_id Company ID
 * @param int $user_id User ID
 * @param string $first_name User's first name
 * @param string $company_name Company name
 * @return bool Success
 */
function triggerWelcomeSequence($company_id, $user_id, $first_name, $company_name) {
    global $conn;
    
    // Get available companies count for user (growth stat)
    $countQuery = "SELECT COUNT(*) as total FROM " . DB::COMPANIES . " WHERE publish = 1";
    $countResult = $conn->query($countQuery);
    $countRow = $countResult->fetch_assoc();
    $totalCompanies = $countRow['total'] ?? 0;
    
    // Get user dashboard URL
    $profileUrl = '/dashboard/'; // Adjust based on frontend dashboard path
    
    // Day 0: Welcome email
    $result = scheduleUserEmail(
        $company_id,
        $user_id,
        'signup',
        [
            'first_name' => $first_name,
            'company_name' => $company_name,
            'company_count' => number_format($totalCompanies),
            'profile_url' => $profileUrl
        ],
        0  // Send immediately
    );
    
    return $result['success'] ?? false;
}

/**
 * Schedule profile completion reminder
 * 
 * Called when: User has not completed profile after 3 days
 * Sends: Day 3 follow-up email
 * 
 * @param int $company_id Company ID
 * @param int $user_id User ID
 * @param string $first_name User's first name
 * @return bool Success
 */
function triggerProfileReminderSequence($company_id, $user_id, $first_name) {
    global $conn;
    
    // Get user's company info
    $companyQuery = "SELECT company_name, views FROM " . DB::COMPANIES . " WHERE id = ?";
    $stmt = $conn->prepare($companyQuery);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $companyResult = $stmt->get_result();
    $company = $companyResult->fetch_assoc();
    
    // Calculate average views (if there are any stats)
    $avgViewsPerDay = $company['views'] > 0 ? ceil($company['views'] / 3) : 0;
    
    // Day 3: Profile reminder email
    $result = scheduleUserEmail(
        $company_id,
        $user_id,
        'profile_incomplete',
        [
            'first_name' => $first_name,
            'company_name' => $company['company_name'] ?? 'your company',
            'completion_multiplier' => '3x',  // 3x visibility boost example
            'avg_views_day' => $avgViewsPerDay,
            'profile_url' => '/dashboard/'
        ],
        0  // Send immediately (scheduler will handle day 3 delay)
    );
    
    return $result['success'] ?? false;
}

/**
 * Schedule verification earned email
 * 
 * Called when: Admin approves company verification
 * Sends: Congratulations email with referral intro
 * 
 * @param int $company_id Company ID
 * @param int $user_id User ID
 * @param string $first_name User's first name
 * @return bool Success
 */
function triggerVerificationEarnedEmail($company_id, $user_id, $first_name) {
    global $conn;
    
    // Get referral code for this user
    $refQuery = "SELECT referral_code, total_earnings FROM " . DB::REFERRAL_CODES . " 
                 WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($refQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $refResult = $stmt->get_result();
    $refCode = $refResult->fetch_assoc();
    
    $referralCode = $refCode['referral_code'] ?? 'NEW' . substr(md5($user_id), 0, 6);
    
    // Send verification email
    $result = scheduleUserEmail(
        $company_id,
        $user_id,
        'verification_earned',
        [
            'first_name' => $first_name,
            'verified_boost' => '10x',  // 10x visibility boost
            'referral_code' => $referralCode,
            'reward_per_referral' => '75',  // AED per referral
            'referral_dashboard_url' => '/referral?code=' . $referralCode
        ],
        0  // Send immediately
    );
    
    return $result['success'] ?? false;
}

/**
 * Schedule referral incentive email
 * 
 * Called when: User becomes eligible for referral (verified + has code)
 * Sends: Referral program details and earnings potential
 * 
 * @param int $company_id Company ID
 * @param int $user_id User ID
 * @param string $first_name User's first name
 * @return bool Success
 */
function triggerReferralIncentiveEmail($company_id, $user_id, $first_name) {
    global $conn;
    
    // Get referral stats
    $refQuery = "SELECT referral_code, total_clicks, total_conversions, total_earnings 
                 FROM " . DB::REFERRAL_CODES . " WHERE user_id = ?";
    $stmt = $conn->prepare($refQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $refResult = $stmt->get_result();
    $refStats = $refResult->fetch_assoc();
    
    $result = scheduleUserEmail(
        $company_id,
        $user_id,
        'referral_qualified',
        [
            'first_name' => $first_name,
            'referral_code' => $refStats['referral_code'] ?? '',
            'reward_per_referral' => '75',
            'referral_clicks' => $refStats['total_clicks'] ?? 0,
            'referral_conversions' => $refStats['total_conversions'] ?? 0,
            'referral_earnings' => number_format($refStats['total_earnings'] ?? 0, 2),
            'referral_dashboard_url' => '/my-referrals'
        ],
        0
    );
    
    return $result['success'] ?? false;
}

/**
 * Schedule weekly summary email
 * 
 * Called when: Weekly email cycle runs (every Monday)
 * Sends: Activity summary with stats and tips
 * 
 * @param int $company_id Company ID
 * @param int $user_id User ID
 * @param string $first_name User's first name
 * @return bool Success
 */
function triggerWeeklySummaryEmail($company_id, $user_id, $first_name) {
    global $conn;
    
    // Get company info
    $companyQuery = "SELECT company_name, views FROM " . DB::COMPANIES . " WHERE id = ?";
    $stmt = $conn->prepare($companyQuery);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $companyResult = $stmt->get_result();
    $company = $companyResult->fetch_assoc();
    
    // Get this week's views (inquiries would come from inquiry table)
    $weekQuery = "SELECT COUNT(*) as views FROM " . DB::COMPANIES . " 
                  WHERE id = ? AND last_visit >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt = $conn->prepare($weekQuery);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $weekResult = $stmt->get_result();
    $weekRow = $weekResult->fetch_assoc();
    $weeklyViews = $weekRow['views'] ?? 0;
    
    // Category rank (simplified)
    $rankQuery = "SELECT category FROM " . DB::COMPANIES . " WHERE id = ?";
    $stmt = $conn->prepare($rankQuery);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $rankResult = $stmt->get_result();
    $rankRow = $rankResult->fetch_assoc();
    
    // Get category rank
    $categoryRankQuery = "SELECT COUNT(*) as rank FROM " . DB::COMPANIES . " 
                         WHERE category = ? AND views > (SELECT views FROM " . DB::COMPANIES . " WHERE id = ?)";
    $stmt = $conn->prepare($categoryRankQuery);
    $stmt->bind_param("si", $rankRow['category'], $company_id);
    $stmt->execute();
    $categoryRankResult = $stmt->get_result();
    $categoryRankRow = $categoryRankResult->fetch_assoc();
    $categoryRank = ($categoryRankRow['rank'] ?? 0) + 1;
    
    $result = scheduleUserEmail(
        $company_id,
        $user_id,
        'weekly_summary',
        [
            'first_name' => $first_name,
            'company_name' => $company['company_name'] ?? 'Your Company',
            'weekly_views' => $weeklyViews,
            'weekly_inquiries' => 0,  // Would come from inquiry table
            'weekly_referral_clicks' => 0,  // Would come from referral tracking
            'category_rank' => $categoryRank,
            'category' => $rankRow['category'] ?? 'General',
            'improvement_suggestion_1' => 'Add more photos to your profile',
            'improvement_suggestion_2' => 'Update your business description',
            'dashboard_url' => '/dashboard/'
        ],
        0  // Send Monday
    );
    
    return $result['success'] ?? false;
}

/**
 * Batch trigger weekly emails for all active users
 * 
 * Called by: Cron job every Monday at 9 AM
 * Processes: All users with active companies
 * 
 * @return array ['processed' => int, 'sent' => int, 'errors' => int]
 */
function triggerWeeklyEmailBatch() {
    global $conn;
    
    $processed = 0;
    $sent = 0;
    $errors = 0;
    
    // Get all active users with verified companies (optional: only verified)
    $userQuery = "SELECT DISTINCT fu.id, fu.first_name, hc.id as company_id 
                  FROM " . DB::FRONTEND_USERS . " fu
                  LEFT JOIN " . DB::COMPANIES . " hc ON fu.company_id = hc.id
                  WHERE fu.active = 1 AND hc.publish = 1";
    
    $result = $conn->query($userQuery);
    
    if ($result) {
        while ($user = $result->fetch_assoc()) {
            $processed++;
            
            if (triggerWeeklySummaryEmail($user['company_id'], $user['id'], $user['first_name'])) {
                $sent++;
            } else {
                $errors++;
            }
        }
    }
    
    return [
        'processed' => $processed,
        'sent' => $sent,
        'errors' => $errors
    ];
}

?>


<?php
/**
 * Email Click Tracking Endpoint
 * 
 * Logs click events and redirects to original URL
 */

require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../config/database.php';

$mysqli = $GLOBALS['DB']['MSQLI'];

$trackingId = $_GET['t'] ?? '';
$url = $_GET['url'] ?? '';

if (empty($trackingId) || empty($url)) {
    http_response_code(400);
    die('Invalid request');
}

try {
    // Decode URL if needed
    $url = urldecode($url);
    
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        die('Invalid URL');
    }

    $trackingIdEscaped = $mysqli->real_escape_string($trackingId);
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Update email history - mark as clicked
    $mysqli->query(
        "UPDATE `" . tbl_email_history . "` SET 
        clicked_at = NOW(),
        updated_at = NOW()
        WHERE tracking_id = '$trackingIdEscaped' AND clicked_at IS NULL
        LIMIT 1"
    );

    // Log click event with URL
    $mysqli->query(
        "INSERT INTO `" . tbl_email_events . "` 
        (tracking_id, event_type, url, ip, user_agent)
        VALUES (
            '$trackingIdEscaped',
            'click',
            '" . $mysqli->real_escape_string($url) . "',
            '" . $mysqli->real_escape_string($ip) . "',
            '" . $mysqli->real_escape_string($userAgent) . "'
        )"
    );

    // Update campaign click count
    $campaignResult = $mysqli->query(
        "SELECT campaign_id FROM `" . tbl_email_history . "` 
        WHERE tracking_id = '$trackingIdEscaped' 
        LIMIT 1"
    );

    if ($campaignResult && $campaignRow = $campaignResult->fetch_array(MYSQLI_ASSOC)) {
        $campaignId = $campaignRow['campaign_id'];
        if (!empty($campaignId)) {
            $mysqli->query(
                "UPDATE `" . tbl_email_campaigns . "` SET 
                click_count = click_count + 1,
                updated_at = NOW()
                WHERE id = $campaignId"
            );
        }
    }

    // Redirect to original URL
    header('Location: ' . $url, true, 301);
    exit;

} catch (Exception $e) {
    error_log('Email click tracking error: ' . $e->getMessage());
    http_response_code(500);
    die('Error processing click');
}

<?php
/**
 * Email Tracking Endpoints
 * 
 * Handles:
 * - Email open tracking (pixel)
 * - Link click tracking (redirect)
 * - Event logging
 */

header('Content-Type: image/gif');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../config/database.php';

$mysqli = $GLOBALS['DB']['MSQLI'];

$trackingId = $_GET['id'] ?? '';

if (empty($trackingId)) {
    // Return 1x1 transparent GIF
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

// Log open event
try {
    $trackingIdEscaped = $mysqli->real_escape_string($trackingId);
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Update email history - mark as opened
    $mysqli->query(
        "UPDATE `" . tbl_email_history . "` SET 
        opened_at = NOW(),
        updated_at = NOW()
        WHERE tracking_id = '$trackingIdEscaped' AND opened_at IS NULL 
        LIMIT 1"
    );

    // Log event
    $mysqli->query(
        "INSERT INTO `" . tbl_email_events . "` 
        (tracking_id, event_type, ip, user_agent)
        VALUES (
            '$trackingIdEscaped',
            'open',
            '" . $mysqli->real_escape_string($ip) . "',
            '" . $mysqli->real_escape_string($userAgent) . "'
        )"
    );

    // Update campaign open count
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
                open_count = open_count + 1,
                updated_at = NOW()
                WHERE id = $campaignId"
            );
        }
    }
} catch (Exception $e) {
    error_log('Email tracking error: ' . $e->getMessage());
}

// Return 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

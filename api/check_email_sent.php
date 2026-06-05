<?php
// api/check_email_sent.php
// Returns { sent: true } if any email has ever been sent to the given address (from email_queue or sent log)

require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$emailQueueTable = (class_exists('DB') && defined('DB::EMAIL_QUEUE')) ? DB::EMAIL_QUEUE : 'erp_email_queue';
$emailSentLogTable = (class_exists('DB') && defined('DB::EMAIL_SENT_LOG')) ? constant('DB::EMAIL_SENT_LOG') : 'erp_email_sent_log';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['sent' => false]);
    exit;
}

// Check in email queue (sent or delivered)
$stmt = $conn->prepare("SELECT COUNT(*) FROM `" . $emailQueueTable . "` WHERE recipient_email = ? AND (status = 'sent' OR status = 'delivered')");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo json_encode(['sent' => true]);
    exit;
}

// Optionally, check in email sent log
$sentLogExistsResult = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($emailSentLogTable) . "'");
if ($sentLogExistsResult && $sentLogExistsResult->num_rows > 0) {
    $stmt2 = $conn->prepare("SELECT COUNT(*) FROM `" . $emailSentLogTable . "` WHERE recipient_email = ?");
    $stmt2->bind_param('s', $email);
    $stmt2->execute();
    $stmt2->bind_result($count2);
    $stmt2->fetch();
    $stmt2->close();
    if ($count2 > 0) {
        echo json_encode(['sent' => true]);
        exit;
    }
}

echo json_encode(['sent' => false]);

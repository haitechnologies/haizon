<?php
require_once __DIR__ . '/../classes/DB.php';
/**
 * AJAX Handler: Send Test Email
 * Sends a test email immediately using SMTP provider settings (no queue/cron)
 */

// Include bootstrap (handles session, security, database)
require_once __DIR__ . '/bootstrap.php';

// Security header for JSON response
header('Content-Type: application/json');

// Check if user is logged in (using proper session structure)
if (!isset($session_user_id) || empty($session_user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

// Include required files
require_once __DIR__ . '/../classes/EmailProviderManager.php';
require_once __DIR__ . '/../classes/SMTPMailer.php';

// CSRF Token validation
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get POST data
$providerId = (int)($_POST['provider_id'] ?? 0);
$recipient = trim($_POST['recipient'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate inputs
if (empty($providerId) || empty($recipient) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Get provider details
$stmt = $mysqli->prepare("SELECT id, provider_name, email, smtp_host, smtp_port, email_encryption, smtp_username, smtp_password, smtp_password_encrypted, is_active FROM `" . DB::EMAIL_PROVIDERS . "` WHERE id = ?");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$result = $stmt->get_result();
$provider = $result->fetch_assoc();
$stmt->close();

if (!$provider) {
    echo json_encode(['success' => false, 'message' => 'Email provider not found']);
    exit;
}

if ($provider['is_active'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Email provider is inactive. Please activate it first.']);
    exit;
}

// Prepare email body with provider information
$htmlBody = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #6c757d; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; }
        .label { font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>âœ‰ï¸ Test Email from HAIPULSE</h2>
        </div>
        <div class="content">
            <p>' . nl2br(htmlspecialchars($message)) . '</p>
            
            <div class="info-box">
                <p><span class="label">Provider:</span> ' . htmlspecialchars($provider['provider_name']) . '</p>
                <p><span class="label">From:</span> ' . htmlspecialchars($provider['email']) . '</p>
                <p><span class="label">SMTP Host:</span> ' . htmlspecialchars($provider['smtp_host']) . ':' . htmlspecialchars($provider['smtp_port']) . '</p>
                <p><span class="label">Encryption:</span> ' . strtoupper(htmlspecialchars($provider['email_encryption'])) . '</p>
                <p><span class="label">Sent by:</span> ' . htmlspecialchars($session_full_name ?: 'Admin') . '</p>
                <p><span class="label">Sent at:</span> ' . date('Y-m-d H:i:s') . '</p>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated test email from HAIPULSE Admin Panel.</p>
            <p>Â© ' . date('Y') . ' HAIPULSE. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';


// Send test email immediately and log to hai_email_history
$sendSuccess = false;
$errorMsg = '';

// Try SMTPMailer if available, else fallback to mail()
$smtp_debug_log = '';
try {
    // Always use SMTPMailer (never fallback to mail())
    // Capture error_log output to a temp file for debug
    $tmpLog = tempnam(sys_get_temp_dir(), 'smtp_debug_');
    ini_set('log_errors', 1);
    $oldLog = ini_set('error_log', $tmpLog);
    $mailer = new SMTPMailer();
    $sendSuccess = $mailer->send($recipient, $subject, $htmlBody, [
        'from' => $provider['email'],
        'from_name' => $provider['provider_name'],
        'provider_id' => $providerId,
        'skip_history_log' => 1
    ]);
    ini_set('error_log', $oldLog);
    $smtp_debug_log = @file_get_contents($tmpLog);
    @unlink($tmpLog);
} catch (Exception $ex) {
    $sendSuccess = false;
    $errorMsg = $ex->getMessage();
    $smtp_debug_log .= "\nException: " . $errorMsg;
}

// Log to hai_email_history
$status = $sendSuccess ? 'sent' : 'failed';
$historyStmt = $mysqli->prepare("INSERT INTO " . DB::EMAIL_HISTORY . " (recipient_email, subject, body, status, sent_at, campaign_id, user_id) VALUES (?, ?, ?, ?, NOW(), NULL, ?)");
if ($historyStmt) {
    $historyStmt->bind_param("ssssi", $recipient, $subject, $htmlBody, $status, $session_user_id);
    $historyStmt->execute();
    $historyId = $mysqli->insert_id;
    $historyStmt->close();
} else {
    $historyId = null;
}

// Audit log (best effort)
try {
    $userId = $session_user_id;
    $logMessage = "Test email sent via provider #{$providerId} ({$provider['provider_name']}) to {$recipient} [status: $status]";
    $auditTableName = DB::EMAIL_PROVIDERS;
    $logStmt = $mysqli->prepare("INSERT INTO " . DB::AUDIT_LOG . " (user_id, action, table_name, record_id, details, created_at) VALUES (?, 'test_email', ?, ?, ?, NOW())");
    if ($logStmt) {
        $logStmt->bind_param("isis", $userId, $auditTableName, $providerId, $logMessage);
        $logStmt->execute();
        $logStmt->close();
    }
} catch (Exception $auditEx) {
    error_log("Test email audit log error: " . $auditEx->getMessage());
}

if ($sendSuccess) {
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent directly via SMTP successfully!',
        'send_mode' => 'direct_smtp',
        'history_id' => $historyId,
        'provider' => $provider['provider_name'],
        'recipient' => $recipient,
        'smtp_debug_log' => $smtp_debug_log
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send test email.' . ($errorMsg ? ' Error: ' . $errorMsg : ''),
        'send_mode' => 'direct_smtp',
        'history_id' => $historyId,
        'provider' => $provider['provider_name'],
        'recipient' => $recipient,
        'smtp_debug_log' => $smtp_debug_log
    ]);
}


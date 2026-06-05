<?php
// Email Test Page (Standalone)
include('admin_elements/admin_header.php');
$module = 'email_providers';
$module_caption = 'Send Test Email';
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();
require_once __DIR__ . '/../classes/EmailProviderManager.php';
require_once __DIR__ . '/../classes/SMTPMailer.php';

// Fetch providers for dropdown (must be before POST handler)

$providers = [];
if (!empty($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
    $res = $GLOBALS['conn']->query('SELECT id, provider_name, email FROM ' . DB::EMAIL_PROVIDERS . ' WHERE is_active = 1 ORDER BY provider_name');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $providers[] = $row;
        }
    }
}

$error_message = '';
$success_message = '';
$smtp_debug_message = '';
$history_debug_message = '';

$selected_provider_id = (int)($_POST['provider_id'] ?? $_GET['provider_id'] ?? 0);
$recipient_value = trim((string)($_POST['recipient'] ?? ''));
$subject_value = trim((string)($_POST['subject'] ?? 'Test Email from HAIPULSE'));
$message_value = trim((string)($_POST['message'] ?? 'This is a test email from HAIPULSE.'));

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && granted_('view', $module)) {
    $providerId = (int)($_POST['provider_id'] ?? 0);
    $recipient = trim($_POST['recipient'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Test Email from HAIPULSE');
    $message = trim($_POST['message'] ?? 'This is a test email from HAIPULSE.');
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $error_message = 'Invalid CSRF token.';
    } elseif (!$providerId || !$recipient || !$subject || !$message) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid recipient email address.';
    } else {
        $providerStmt = $GLOBALS['conn']->prepare('SELECT * FROM ' . DB::EMAIL_PROVIDERS . ' WHERE id = ? AND is_active = 1 LIMIT 1');
        if (!$providerStmt) {
            $error_message = 'Failed to prepare provider query.';
        } else {
            $providerStmt->bind_param('i', $providerId);
            $providerStmt->execute();
            $providerResult = $providerStmt->get_result();
            $providerRow = $providerResult ? $providerResult->fetch_assoc() : null;
            $providerStmt->close();

            if (!$providerRow) {
                $error_message = 'Email provider not found or inactive.';
            } else {
                $htmlBody = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
                $mailer = new SMTPMailer();
                $sendSuccess = $mailer->send($recipient, $subject, $htmlBody, [
                    'provider_id' => $providerId,
                    'from' => (string)$providerRow['email'],
                    'from_name' => (string)$providerRow['provider_name'],
                    'Reply-To' => (string)$providerRow['email'],
                ]);

                // Always keep test-send attempts in email history for listing_email_history.php.
                $status = $sendSuccess ? 'sent' : 'failed';
                $sentAt = $sendSuccess ? date('Y-m-d H:i:s') : null;
                $errorText = '';
                if (!$sendSuccess) {
                    $errorText = (string)$mailer->getLastError();
                    if ($errorText === '') {
                        $errorText = 'Unknown SMTP error';
                    }
                }

                $historyStmt = $GLOBALS['conn']->prepare(
                    'INSERT INTO ' . DB::EMAIL_HISTORY . ' (user_id, campaign_id, recipient_email, company_id, provider_id, status, error_message, sent_at, subject, body, from_name, from_email) VALUES (?, NULL, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                if ($historyStmt) {
                    $historyUserId = (int)($_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0);
                    $historyFromName = (string)($providerRow['provider_name'] ?? '');
                    $historyFromEmail = (string)($providerRow['email'] ?? '');
                    $historyStmt->bind_param(
                        'isisssssss',
                        $historyUserId,
                        $recipient,
                        $providerId,
                        $status,
                        $errorText,
                        $sentAt,
                        $subject,
                        $htmlBody,
                        $historyFromName,
                        $historyFromEmail
                    );
                    $historyStmt->execute();
                    $historyId = (int)$GLOBALS['conn']->insert_id;
                    $history_debug_message = 'Saved in Email History (ID: ' . $historyId . ').';
                    $historyStmt->close();
                } else {
                    log_error('Email test history insert prepare failed', 'ERROR', __FILE__, __LINE__, [
                        'provider_id' => $providerId,
                        'recipient' => $recipient,
                        'db_error' => $GLOBALS['conn']->error,
                    ]);
                }

                if ($sendSuccess) {
                    $smtpCode = $mailer->getLastSMTPCode();
                    $smtpResponse = $mailer->getLastSMTPResponse();
                    $success_message = 'Email sent directly to SMTP provider for delivery to ' . htmlspecialchars($recipient, ENT_QUOTES, 'UTF-8') . '.';
                    if (!empty($smtpCode) || !empty($smtpResponse)) {
                        $smtpDebug = trim((string)$smtpResponse);
                        $smtpDebug = preg_replace('/\s*queued as\s+\S+/i', '', $smtpDebug);
                        if ($smtpDebug === '' && $smtpCode !== '') {
                            $smtpDebug = $smtpCode;
                        }
                        $smtp_debug_message = 'SMTP provider response: ' . htmlspecialchars($smtpDebug, ENT_QUOTES, 'UTF-8');
                    }
                } else {
                    $sendError = $mailer->getLastError();
                    $smtpCode = $mailer->getLastSMTPCode();
                    $smtpResponse = $mailer->getLastSMTPResponse();
                    log_error('Email test send failed', 'ERROR', __FILE__, __LINE__, [
                        'provider_id' => $providerId,
                        'recipient' => $recipient,
                        'error' => $sendError,
                        'smtp_code' => $smtpCode,
                        'smtp_response' => $smtpResponse,
                    ]);
                    $error_message = 'Failed to send test email: ' . htmlspecialchars($sendError ?: 'Unknown SMTP error', ENT_QUOTES, 'UTF-8');
                    if (!empty($smtpCode) || !empty($smtpResponse)) {
                        $smtpDebug = trim((string)$smtpResponse);
                        $smtpDebug = preg_replace('/\s*queued as\s+\S+/i', '', $smtpDebug);
                        if ($smtpDebug === '' && $smtpCode !== '') {
                            $smtpDebug = $smtpCode;
                        }
                        $smtp_debug_message = 'SMTP provider response: ' . htmlspecialchars($smtpDebug, ENT_QUOTES, 'UTF-8');
                    }
                }
            }
        }
    }

    $selected_provider_id = $providerId;
    $recipient_value = $recipient;
    $subject_value = $subject;
    $message_value = $message;
}
?>
<div class="content-wrapper">
    <?php if (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar')): ?>
        <?php renderEmailQuickbar($visibleEmailLinks, $current_page); ?>
    <?php endif; ?>

    <div class="page-header page-header-light shadow mb-3">
        <div class="page-header-content d-flex">
            <div class="page-title">
                <h4><i class="ph-envelope-simple me-2"></i><span class="fw-semibold">Send Test Email</span></h4>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="card col-md-8 mx-auto mt-4 mb-5">
            <div class="card-header bg-light">
                <strong>Send Test Email via Provider</strong>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php elseif ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if ($smtp_debug_message): ?>
                    <div class="alert alert-secondary small"><?php echo $smtp_debug_message; ?></div>
                <?php endif; ?>
                <?php if ($history_debug_message): ?>
                    <div class="alert alert-info small mb-3"><?php echo htmlspecialchars($history_debug_message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form method="post" action="">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label for="provider_id" class="form-label">Email Provider</label>
                        <select name="provider_id" id="provider_id" class="form-select" required>
                            <option value="">-- Select Provider --</option>
                            <?php foreach ($providers as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>" <?php if ($selected_provider_id === (int)$p['id']) echo 'selected'; ?>>
                                    <?php echo e($p['provider_name'] . ' (' . $p['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="recipient" class="form-label">Recipient Email</label>
                        <input type="email" name="recipient" id="recipient" class="form-control" value="<?php echo e($recipient_value); ?>" required />
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" value="<?php echo e($subject_value); ?>" required />
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea name="message" id="message" class="form-control" rows="5" required><?php echo e($message_value); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Test Email</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include('admin_elements/admin_footer.php'); ?>


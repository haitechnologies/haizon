<?php


use App\Core\DB;
use App\Core\Session;
include('admin_elements/admin_header.php');
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/TOTPAuthenticator.php';
use Endroid\QrCode\QrCode;

$module_caption = 'Security - Two-Factor Authentication';
$tbl_name = DB::USERS;
$userId = (int)(Session::userId() ?? 0);
$isLiveServer = function_exists('isRemote') ? isRemote() : false;

$error_message = '';
$success_message = '';
$recovery_codes_to_show = [];

if ($userId <= 0) {
    header('location:login.php?message=Session expired');
    exit;
}

if (!isset($_SESSION[$project_pre]['DASHBOARD']['mfa_setup_secret'])) {
    $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_secret'] = '';
}

if (!isset($_SESSION[$project_pre]['DASHBOARD']['mfa_setup_started_at'])) {
    $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_started_at'] = 0;
}

if (!isset($_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes'])) {
    $_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes'] = [];
}

$action = (string)($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in mfa_settings.php', 'WARNING', __FILE__, __LINE__, ['user_id' => $userId]);
    }
}

$stmt = $mysqli->prepare("SELECT id, email, password, mfa_totp_enabled, mfa_totp_secret, mfa_recovery_codes FROM `" . $tbl_name . "` WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user) {
    header('location:logout.php');
    exit;
}

$mfaEnabled = !empty($user['mfa_totp_enabled']) && !empty($user['mfa_totp_secret']);

if (isset($_GET['enforce']) && $_GET['enforce'] == '1' && !$mfaEnabled && empty($error_message) && empty($success_message)) {
    $success_message = 'Your role requires two-factor authentication. Please complete setup to continue using the dashboard.';
}

if ($action === 'start_setup' && empty($error_message)) {
    if ($mfaEnabled) {
        $error_message = 'Two-factor authentication is already enabled for your account.';
    } else {
        $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_secret'] = TOTPAuthenticator::generateSecret(32);
        $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_started_at'] = time();
        $success_message = 'Scan the QR code, then enter your 6-digit code to activate two-factor authentication.';
    }
}

if ($action === 'enable_mfa' && empty($error_message)) {
    if ($mfaEnabled) {
        $error_message = 'Two-factor authentication is already enabled for your account.';
    } else {
        $setupSecret = (string)($_SESSION[$project_pre]['DASHBOARD']['mfa_setup_secret'] ?? '');
        $setupStartedAt = (int)($_SESSION[$project_pre]['DASHBOARD']['mfa_setup_started_at'] ?? 0);
        $otpCode = preg_replace('/\D+/', '', (string)($_POST['otp_code'] ?? ''));

        if ($setupSecret === '' || $setupStartedAt <= 0 || (time() - $setupStartedAt) > 900) {
            $error_message = 'Setup session expired. Start setup again.';
            $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_secret'] = '';
            $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_started_at'] = 0;
        } else if (!TOTPAuthenticator::verifyCode($setupSecret, $otpCode, 1)) {
            $error_message = 'Invalid authenticator code. Please try again.';
        } else {
            $recoverySet = TOTPAuthenticator::generateRecoveryCodes(8);
            $encryptedSecret = TOTPAuthenticator::encryptSecret($setupSecret);
            $recoveryCodesJson = json_encode($recoverySet['hashed']);

            $updateStmt = $mysqli->prepare(
                "UPDATE `" . $tbl_name . "`
                 SET mfa_totp_enabled = 1,
                     mfa_totp_secret = ?,
                     mfa_recovery_codes = ?,
                     mfa_enabled_at = NOW()
                 WHERE id = ?
                 LIMIT 1"
            );

            if ($updateStmt) {
                $updateStmt->bind_param('ssi', $encryptedSecret, $recoveryCodesJson, $userId);
                $ok = $updateStmt->execute();
                $updateStmt->close();

                if ($ok) {
                    $success_message = 'Two-factor authentication enabled successfully. Save your recovery codes now.';
                    $_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes'] = $recoverySet['plain'];
                    $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_secret'] = '';
                    $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_started_at'] = 0;
                    $mfaEnabled = true;
                } else {
                    $error_message = 'Failed to enable two-factor authentication. Please try again.';
                }
            } else {
                $error_message = 'Failed to prepare security update. Please try again.';
            }
        }
    }
}

if ($action === 'regenerate_recovery_codes' && empty($error_message)) {
    if (!$mfaEnabled) {
        $error_message = 'Enable two-factor authentication first.';
    } else {
        $otpCode = preg_replace('/\D+/', '', (string)($_POST['otp_code'] ?? ''));
        $storedSecret = (string)$user['mfa_totp_secret'];
        $secret = TOTPAuthenticator::decryptSecret($storedSecret);
        if ($secret === '' && strpos($storedSecret, 'enc:') !== 0) {
            $secret = $storedSecret;
        }

        if ($secret === '' || !TOTPAuthenticator::verifyCode($secret, $otpCode, 1)) {
            $error_message = 'Invalid authenticator code for recovery code regeneration.';
        } else {
            $recoverySet = TOTPAuthenticator::generateRecoveryCodes(8);
            $recoveryCodesJson = json_encode($recoverySet['hashed']);

            $updateStmt = $mysqli->prepare("UPDATE `" . $tbl_name . "` SET mfa_recovery_codes = ? WHERE id = ? LIMIT 1");
            if ($updateStmt) {
                $updateStmt->bind_param('si', $recoveryCodesJson, $userId);
                $ok = $updateStmt->execute();
                $updateStmt->close();

                if ($ok) {
                    $success_message = 'Recovery codes regenerated successfully. Save the new codes now.';
                    $_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes'] = $recoverySet['plain'];
                } else {
                    $error_message = 'Failed to regenerate recovery codes.';
                }
            } else {
                $error_message = 'Failed to prepare update for recovery codes.';
            }
        }
    }
}

if ($action === 'disable_mfa' && empty($error_message)) {
    if (!$mfaEnabled) {
        $error_message = 'Two-factor authentication is already disabled.';
    } else {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        if ($currentPassword === '' || !password_verify($currentPassword, (string)$user['password'])) {
            $error_message = 'Invalid password. MFA was not disabled.';
        } else {
            $updateStmt = $mysqli->prepare(
                "UPDATE `" . $tbl_name . "`
                 SET mfa_totp_enabled = 0,
                     mfa_totp_secret = NULL,
                     mfa_recovery_codes = NULL,
                     mfa_enabled_at = NULL
                 WHERE id = ?
                 LIMIT 1"
            );

            if ($updateStmt) {
                $updateStmt->bind_param('i', $userId);
                $ok = $updateStmt->execute();
                $updateStmt->close();

                if ($ok) {
                    $success_message = 'Two-factor authentication has been disabled.';
                    $mfaEnabled = false;
                    $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_secret'] = '';
                    $_SESSION[$project_pre]['DASHBOARD']['mfa_setup_started_at'] = 0;
                    $_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes'] = [];
                } else {
                    $error_message = 'Failed to disable two-factor authentication.';
                }
            } else {
                $error_message = 'Failed to prepare disable request.';
            }
        }
    }
}

$setupSecret = (string)($_SESSION[$project_pre]['DASHBOARD']['mfa_setup_secret'] ?? '');
$accountEmail = (string)$user['email'];
$issuer = (string)($_ENV['MFA_TOTP_ISSUER'] ?? getenv('MFA_TOTP_ISSUER') ?: 'HAIZON Dashboard');
$otpauthUri = '';
$qrImageUrl = '';

if (!$mfaEnabled && $setupSecret !== '') {
    $otpauthUri = TOTPAuthenticator::getProvisioningUri($issuer, $accountEmail, $setupSecret);

    try {
        $qrCode = new QrCode($otpauthUri);
        if (method_exists($qrCode, 'setSize')) {
            $qrCode->setSize(240);
        }
        $qrImageUrl = 'data:image/png;base64,' . base64_encode($qrCode->writeString());
    } catch (Throwable $e) {
        // Fallback to remote QR API only if local generation fails.
        $qrImageUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=240x240&chl=' . rawurlencode($otpauthUri);
        log_error('Local MFA QR generation failed, using fallback', 'WARNING', __FILE__, __LINE__, [
            'user_id' => $userId,
            'error' => $e->getMessage(),
        ]);
    }
}

if (!empty($_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes']) && is_array($_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes'])) {
    $recovery_codes_to_show = $_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes'];
}

?>

<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content d-lg-flex border-top carriers-page-header-content py-2 px-3">
            <div class="d-flex">
                <div class="breadcrumb py-2">
                    <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                    <a href="profile.php" class="breadcrumb-item">Profile</a>
                    <span class="breadcrumb-item active">Two-Factor Authentication</span>
                </div>
            </div>
        </div>
    </div>

    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <?php if (!empty($error_message)) { ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php } ?>

            <?php if (!empty($success_message)) { ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php } ?>

            <?php if (!$isLiveServer) { ?>
                <div class="alert alert-info">
                    Local environment detected: 2FA setup is available for testing, but login enforcement is disabled on local server.
                </div>
            <?php } ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Google Authenticator / TOTP Security</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Status:
                                <?php if ($mfaEnabled) { ?>
                                    <span class="badge bg-success">Enabled</span>
                                <?php } else { ?>
                                    <span class="badge bg-warning text-dark">Disabled</span>
                                <?php } ?>
                            </p>

                            <?php if (!$mfaEnabled && $setupSecret === '') { ?>
                                <form method="post" action="mfa_settings.php" class="mb-0">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="start_setup">
                                    <button type="submit" class="btn btn-primary">Start 2FA Setup</button>
                                </form>
                            <?php } ?>

                            <?php if (!$mfaEnabled && $setupSecret !== '') { ?>
                                <div class="border rounded p-3 mb-3">
                                    <p class="mb-2">1) Scan this QR code with Google Authenticator (or any TOTP app).</p>
                                    <img src="<?php echo htmlspecialchars($qrImageUrl); ?>" alt="MFA QR" class="img-thumbnail mb-3" width="240" height="240">
                                    <p class="mb-2">2) If QR scan fails, enter this secret manually:</p>
                                    <div class="alert alert-light border mb-3"><strong><?php echo htmlspecialchars($setupSecret); ?></strong></div>

                                    <form method="post" action="mfa_settings.php" class="mb-0">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="enable_mfa">
                                        <div class="mb-3">
                                            <label class="form-label">Enter 6-digit code from your app</label>
                                            <input type="text" name="otp_code" class="form-control" maxlength="6" pattern="[0-9]{6}" required>
                                        </div>
                                        <button type="submit" class="btn btn-success">Enable 2FA</button>
                                    </form>
                                </div>
                            <?php } ?>

                            <?php if ($mfaEnabled) { ?>
                                <div class="border rounded p-3 mb-3">
                                    <h6>Regenerate Recovery Codes</h6>
                                    <p class="text-muted mb-2">This invalidates all previous recovery codes.</p>
                                    <form method="post" action="mfa_settings.php" class="mb-0">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="regenerate_recovery_codes">
                                        <div class="mb-3">
                                            <label class="form-label">Authenticator code</label>
                                            <input type="text" name="otp_code" class="form-control" maxlength="6" pattern="[0-9]{6}" required>
                                        </div>
                                        <button type="submit" class="btn btn-outline-primary">Regenerate Codes</button>
                                    </form>
                                </div>

                                <div class="border rounded p-3">
                                    <h6 class="text-danger">Disable Two-Factor Authentication</h6>
                                    <p class="text-muted mb-2">Enter your password to disable MFA.</p>
                                    <form method="post" action="mfa_settings.php" class="mb-0">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="disable_mfa">
                                        <div class="mb-3">
                                            <label class="form-label">Current password</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Disable 2FA for your account?');">Disable 2FA</button>
                                    </form>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recovery Codes</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Use a recovery code when your authenticator app is unavailable. Each code works only once.</p>

                            <?php if (!empty($recovery_codes_to_show)) { ?>
                                <div class="alert alert-warning">Copy these now. They will not be shown again.</div>
                                <ul class="list-group list-group-flush mb-3">
                                    <?php foreach ($recovery_codes_to_show as $code) { ?>
                                        <li class="list-group-item"><code><?php echo htmlspecialchars($code); ?></code></li>
                                    <?php } ?>
                                </ul>
                                <?php $_SESSION[$project_pre]['DASHBOARD']['mfa_last_recovery_codes'] = []; ?>
                            <?php } else { ?>
                                <div class="alert alert-info mb-0">No new recovery codes generated in this session.</div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>


<?php

require_once __DIR__ . '/../config/session.php';

header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: post-check=0, pre-check=0", false);
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
session_cache_limiter("must-revalidate");
ob_start();
startDashboardSession();
include('../config/globals.php');
include('../config/database.php');
include('admin_elements/error_logger.php');

$isLiveServer = function_exists('isRemote') ? isRemote() : false;

// Initialize Rate Limiter for login protection
require_once __DIR__ . '/../classes/RateLimiter.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/TOTPAuthenticator.php';
RateLimiter::init($mysqli);

$module = 'users';
$module_caption = 'User3';

$message = '';
if (isset($_REQUEST['message']) && !empty($_REQUEST['message'])) {
	$message = e_s__($_REQUEST['message']);
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get client IP address
function get_client_ip() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		return $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
	} else {
		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}
}

function complete_dashboard_login($row, $client_ip, $project_pre) {
	// Regenerate session ID to prevent session fixation
	session_regenerate_id(true);

	// Set session variables
	$_SESSION[$project_pre]['DASHBOARD']['role_id'] = $row['role_id'];
	$_SESSION[$project_pre]['DASHBOARD']['user_id'] = $row['id'];
	$_SESSION[$project_pre]['DASHBOARD']['full_name'] = $row['full_name'];
	$_SESSION[$project_pre]['DASHBOARD']['email'] = $row['email'];
	$_SESSION[$project_pre]['DASHBOARD']['login_time'] = time();
	$_SESSION[$project_pre]['DASHBOARD']['last_activity'] = time();
	$_SESSION[$project_pre]['DASHBOARD']['ip_address'] = $client_ip;
	$_SESSION[$project_pre]['DASHBOARD']['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
	$_SESSION[$project_pre]['DASHBOARD']['mfa_verified'] = 1;
	$_SESSION[$project_pre]['DASHBOARD']['mfa_verified_at'] = time();

	// Regenerate CSRF token after login
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$client_ip = get_client_ip();


/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

// ---------------------------
// SUCCESS LOGIN -> DASHBOARD
// ---------------------------
if (isset($_SESSION[$project_pre]['email']) && !empty($_SESSION[$project_pre]['DASHBOARD']['email'])) {
	header('location:index.php');
}

/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/

$action 	= '';
$email 		= '';
$password 	= '';
$otp_code    = '';
$recovery_code = '';
$csrf_token = '';

if (isset($_POST['action']) 	&& !empty($_POST['action']))
	$action 	=  e_s__($_POST['action']);

if (isset($_POST['email']) && !empty($_POST['email']))
	$email 		= trim(e_s__($_POST['email']));

if (isset($_POST['password']) && !empty($_POST['password']))
	$password 	= $_POST['password']; // Don't sanitize password (may contain special chars)

if (isset($_POST['otp_code']) && !empty($_POST['otp_code']))
	$otp_code 	= preg_replace('/\D+/', '', $_POST['otp_code']);

if (isset($_POST['recovery_code']) && !empty($_POST['recovery_code']))
	$recovery_code 	= strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['recovery_code']));

if (isset($_POST['csrf_token']) && !empty($_POST['csrf_token']))
	$csrf_token = $_POST['csrf_token'];



/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

if ($action == 'login' && empty($email)) {
	$message = 'Please enter your Email.';
	$email = ''; // Clear email on error
} else if ($action == 'login' && empty($password)) {
	$message = 'Please enter your Password.';
} else if ($action == 'login' && !empty($csrf_token) && $csrf_token !== $_SESSION['csrf_token']) {
	$message = 'Invalid security token. Please refresh the page and try again.';
	log_error('CSRF token mismatch on login attempt', 'WARNING', __FILE__, __LINE__, ['email' => $email, 'ip' => $client_ip]);
	$email = '';
} else if ($action == 'login' && !empty($email) && !empty($password)) {
	$rateLimitEnabled = $isLiveServer;

	// RATE LIMITING: Check if IP is rate limited
	$rateLimitCheck = $rateLimitEnabled
		? RateLimiter::check($client_ip, 'login', 5, 900)
		: ['allowed' => true, 'reason' => '', 'retryAfter' => 0]; // Disabled on local
	
	if (!$rateLimitCheck['allowed']) {
		$message = $rateLimitCheck['reason'];
		log_error('Rate limit exceeded for login', 'WARNING', __FILE__, __LINE__, [
			'ip' => $client_ip,
			'email' => $email,
			'retry_after' => $rateLimitCheck['retryAfter']
		]);
		$email = '';
	} else {

		/* ----------------------------------
	| CHECK IF ACCOUNT EXISTS AND ACTIVE
	| Using prepared statements to prevent SQL injection
	------------------------------------- 
	*/
		$stmt = $mysqli->prepare("SELECT * FROM `" . tbl_users . "` WHERE email = ? AND can_access_system = '1' AND is_active = '1' LIMIT 1");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows == 0) {
			// RATE LIMITING: Record failed attempt
			if ($rateLimitEnabled) {
				RateLimiter::recordAttempt($client_ip, 'login', 5, 1800); // Ban for 30 minutes after 5 attempts
			}
			
			log_user_block($email);
			log_error('Failed login attempt - invalid credentials', 'WARNING', __FILE__, __LINE__, ['email' => $email, 'ip' => $client_ip]);
			$message = 'Invalid Email / Password.';
			$email = ''; // Clear email on failed attempt
			$stmt->close();
		} else {

			$row = $result->fetch_assoc();
			$hash = $row['password'];
			$stmt->close();


			if (password_verify($password, $hash)) {
				// Upgrade hash cost/algorithm if needed after valid password.
				if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
					$rehash = password_hash($password, PASSWORD_DEFAULT);
					$rehashStmt = $mysqli->prepare("UPDATE `" . tbl_users . "` SET password = ? WHERE id = ? LIMIT 1");
					if ($rehashStmt) {
						$userId = (int)$row['id'];
						$rehashStmt->bind_param('si', $rehash, $userId);
						$rehashStmt->execute();
						$rehashStmt->close();
					}
				}

				$mfaEnabled = !empty($row['mfa_totp_enabled']) && !empty($row['mfa_totp_secret']);
				$mfaPassed = true;

				// Enforce MFA only on live server. Local can still display MFA fields/details.
				if ($isLiveServer && $mfaEnabled) {
					$mfaPassed = false;
					$storedSecret = (string)$row['mfa_totp_secret'];
					$totpSecret = TOTPAuthenticator::decryptSecret($storedSecret);
					if ($totpSecret === '' && strpos($storedSecret, 'enc:') !== 0) {
						$totpSecret = $storedSecret;
					}

					if ($totpSecret === '') {
						$message = 'Two-factor authentication is enabled but not configured correctly. Please contact an administrator.';
						log_error('MFA secret decrypt failed during login', 'ERROR', __FILE__, __LINE__, ['email' => $email, 'ip' => $client_ip]);
					} else if ($otp_code !== '') {
						$mfaPassed = TOTPAuthenticator::verifyCode($totpSecret, $otp_code, 1);
						if (!$mfaPassed) {
							$message = 'Invalid authenticator code.';
						}
					} else if ($recovery_code !== '') {
						$storedRecoveryCodes = json_decode((string)($row['mfa_recovery_codes'] ?? ''), true);
						if (!is_array($storedRecoveryCodes)) {
							$storedRecoveryCodes = [];
						}

						$consume = TOTPAuthenticator::consumeRecoveryCode($recovery_code, $storedRecoveryCodes);
						if (!empty($consume['valid'])) {
							$mfaPassed = true;
							$remainingCodesJson = json_encode($consume['remaining']);
							$updateRecoveryStmt = $mysqli->prepare("UPDATE `" . tbl_users . "` SET mfa_recovery_codes = ? WHERE id = ? LIMIT 1");
							if ($updateRecoveryStmt) {
								$userId = (int)$row['id'];
								$updateRecoveryStmt->bind_param('si', $remainingCodesJson, $userId);
								$updateRecoveryStmt->execute();
								$updateRecoveryStmt->close();
							}
						} else {
							$message = 'Invalid recovery code.';
						}
					} else {
						$message = 'Enter your authenticator code or a recovery code to continue.';
					}
				}

				if ($mfaPassed) {
					// RATE LIMITING: Reset attempts only after complete auth succeeds
					if ($rateLimitEnabled) {
						RateLimiter::reset($client_ip, 'login');
					}

					complete_dashboard_login($row, $client_ip, $project_pre);

				log_user_login($email);

				// Update last login using prepared statement
				$stmt = $mysqli->prepare("UPDATE `" . tbl_users . "` SET last_login = NOW() WHERE email = ?");
				$stmt->bind_param('s', $email);
				$stmt->execute();
				$stmt->close();

				/* --------------------------
					| SUCCESS LOGIN -> DASHBOARD
					----------------------------- */

					header('location:index.php');
				} else {
					if ($rateLimitEnabled) {
						RateLimiter::recordAttempt($client_ip, 'login', 5, 1800);
					}
					log_error('Failed login attempt - MFA check failed', 'WARNING', __FILE__, __LINE__, ['email' => $email, 'ip' => $client_ip]);
					$email = '';
				}
			} else {
				// Invalid password - RATE LIMITING: Record failed attempt
				if ($rateLimitEnabled) {
					RateLimiter::recordAttempt($client_ip, 'login', 5, 1800); // Ban for 30 minutes after 5 attempts
				}
				
				log_user_block($email);
				log_error('Failed login attempt - incorrect password', 'WARNING', __FILE__, __LINE__, ['email' => $email, 'ip' => $client_ip]);
				$message = 'Invalid Email / Password.';
				$email = ''; // Clear email on failed attempt
				
				// Add small delay to prevent timing attacks
				usleep(500000); // 0.5 second delay
			}
		}
	}
}

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Admin Dashboard - Login</title>
	<meta name="robots" content="noindex">

	<link rel="shortcut icon" href="../<?php echo getSystemFavicon(); ?>" type="image/x-icon" />
	<!-- Global stylesheets -->
	<link href="assets/fonts/inter/inter.css" rel="stylesheet" type="text/css">
	<link href="assets/icons/phosphor/styles.min.css" rel="stylesheet" type="text/css">
	<link href="assets/assets_custom/css/all.min.css" id="stylesheet" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->

	<!-- Core JS files -->
	<script src="assets/assets_custom/js/ui-preferences.js"></script>
	<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script src="assets/assets_custom/js/app.js"></script>
	<!-- /theme JS files -->

</head>

<?php
// Get login colors for the page
$loginColors = getLoginPageColors();
?>
<body style="background-color: <?php echo $loginColors['form_bg']; ?>;">

	<!-- Main navbar -->
	<div class="navbar navbar-static py-2" style="background-color: <?php echo $loginColors['header_bg']; ?>; color: <?php echo $loginColors['header_text']; ?>"><!-- #FF6600-->
		<div class="container-fluid">
			<div class="navbar-brand">
							<a href="login.php" class="d-inline-flex align-items-center" style="color: <?php echo $loginColors['header_text']; ?>;">
					<!-- <img src="assets/images/logo_text_light.svg" class="d-none d-sm-inline-block h-16px ms-3" alt=""> -->
					<!-- <img src="../images/logo.png" alt=""> -->

					<?php
					// ---------------------------------- LOGO ---------------------------------- 
				// Use login-specific logo if available, otherwise fallback to main logo
				$login_logo = getSystemSetting('login_logo', '');
				$logo = getSystemSetting('logo', '');
				$software_name = getSystemSetting('software_name', 'Admin Dashboard');

				// First priority: login_logo, second: logo, fallback: default
				$logo_to_use = !empty($login_logo) ? $login_logo : $logo;

				if (!empty($logo_to_use) && file_exists('../uploads/system_settings/' . $logo_to_use)) {
					$display_logo = '../uploads/system_settings/' . htmlspecialchars($logo_to_use);
					} else {
						$display_logo = '../images/default_logo.png';
					}
					// ----------------------------------------------------------------------------- 
					?>
				<img src="<?php echo $display_logo; ?>" alt="Logo"> &nbsp; 
				<?php echo htmlspecialchars($software_name); ?>

				</a>
			</div>
		</div>
	</div>
	<!-- /main navbar -->


	<!-- Page content -->
	<div class="page-content">

		<!-- Main content -->
		<div class="content-wrapper">

			<!-- Inner content -->
			<div class="content-inner">

				<!-- Content area -->
				<div class="content d-flex justify-content-center align-items-center">

					<!-- Login form -->
					<form class="login-form" id="form_login" action="login.php" method="post" autocomplete="off" style="width: 100%; max-width: 400px;">
						<input type="hidden" name="action" id="action" value="login" />
						<input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />


						<?php
						//echo getTableAttr('company_name', tbl_global_settings, 1); 
						// $software_name		= getTableAttrv('setting_value', tbl_system_settings, 'setting_slug ="software_name"');
						// echo s__($software_name);
						?>


						<div class="card">
							<div class="card-body">
								<div class="text-center mb-3">
									<div class="card-img-actions d-inline-block mb-3">
										<?php
										// ---------------------------------- LOGO ---------------------------------- 
										// Use login-specific logo if available, otherwise fallback to main logo
										$login_logo = getSystemSetting('login_logo', '');
										$logo = getSystemSetting('logo', '');

										// First priority: login_logo, second: logo
										$logo_to_use = !empty($login_logo) ? $login_logo : $logo;

										if (!empty($logo_to_use) && file_exists('../uploads/system_settings/' . $logo_to_use)) {
											$display_logo = '../uploads/system_settings/' . htmlspecialchars($logo_to_use);
										} else {
											// Try to find any available logo in uploads
											$logo_files = @glob('../uploads/system_settings/*logo*');
											if (!empty($logo_files) && file_exists($logo_files[0])) {
												$display_logo = $logo_files[0];
											} else {
												// Use dashboard default
												$display_logo = 'assets/images/logo_icon.svg';
											}
										}
										// ----------------------------------------------------------------------------- 
										?>
										<img class="rounded-circle" src="<?php echo $display_logo; ?>" width="160" height="160" alt="Logo" style="object-fit: cover; border: 3px solid #f0f0f0;">
									</div>

									<h5 class="mb-0">Login to your account</h5>
									<span class="d-block text-muted">Enter your credentials below</span>
								</div>

								<div class="mb-3">
									<label class="form-label">Email <span class="text-danger">*</span></label>
									<div class="form-control-feedback form-control-feedback-start">
										<input required type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="john@system.com" autocomplete="email" maxlength="255">
										<div class="form-control-feedback-icon">
											<i class="ph-user-circle text-muted"></i>
										</div>
									</div>
								</div>

								<div class="mb-3">
									<label class="form-label">Password <span class="text-danger">*</span></label>
									<div class="form-control-feedback form-control-feedback-start">
										<input required type="password" class="form-control" name="password" id="password" placeholder="**********" autocomplete="current-password" maxlength="255">
										<div class="form-control-feedback-icon">
											<i class="ph-lock text-muted"></i>
										</div>
									</div>
								</div>

								<div class="mb-3">
									<label class="form-label">Authenticator Code</label>
									<div class="form-control-feedback form-control-feedback-start">
										<input type="text" class="form-control" name="otp_code" id="otp_code" value="<?php echo htmlspecialchars($otp_code); ?>" placeholder="6-digit code" autocomplete="one-time-code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}">
										<div class="form-control-feedback-icon">
											<i class="ph-shield-check text-muted"></i>
										</div>
									</div>
									<small class="text-muted">Required if 2FA is enabled for your account.</small>
								</div>

								<div class="mb-3">
									<label class="form-label">Recovery Code (optional)</label>
									<div class="form-control-feedback form-control-feedback-start">
										<input type="text" class="form-control" name="recovery_code" id="recovery_code" value="<?php echo htmlspecialchars($recovery_code); ?>" placeholder="Use if authenticator unavailable" autocomplete="off" maxlength="32">
										<div class="form-control-feedback-icon">
											<i class="ph-key text-muted"></i>
										</div>
									</div>
								</div>

								<div class="mb-3">
									<button type="submit" class="btn w-100" style="background-color: <?php echo $loginColors['button_bg']; ?>; color: <?php echo $loginColors['button_text']; ?>; border: none;" onmouseover="this.style.backgroundColor='<?php echo $loginColors['button_hover']; ?>'" onmouseout="this.style.backgroundColor='<?php echo $loginColors['button_bg']; ?>'">Sign in</button>
								</div>

								<div class="text-center">
									<a href="forgot_password.php">Forgot password?</a>
								</div>

							</div>
							<!-- /card-body -->

						</div>
						<!-- /card -->

					<!-- Messages Section - Below Form -->
					<?php if (!empty($message)): ?>
					<div class="alert alert-warning alert-dismissible fade show mt-3 mb-0" role="alert">
						<i class="ph-warning-circle"></i> <?php echo $message; ?>
						<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
					</div>
					<?php endif; ?>

					<?php if (isset($_GET['session_expired'])): ?>
					<div class="alert alert-warning alert-dismissible fade show mt-3 mb-0" role="alert">
						<i class="ph-warning-circle"></i> Your session has expired. Please login again.
						<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
					</div>
					<?php endif; ?>
					</form>
					<!-- /login form -->

				</div>
				<!-- /content area -->


				<!-- Footer -->
				<div class="navbar navbar-sm navbar-footer border-top">
					<div class="container-fluid">
						<span>&copy; <?php echo date('Y'); ?></span>

						<!-- <ul class="nav">
							<li class="nav-item ms-md-1">
								<a href="#" class="navbar-nav-link navbar-nav-link-icon rounded" target="_blank">
									<div class="d-flex align-items-center mx-md-1">
										<i class="ph-file-text"></i>
										<span class="d-none d-md-inline-block ms-2">All Rights Reserved.</span>
									</div>
								</a>
							</li>
						</ul> -->
					</div>
				</div>
				<!-- /footer -->

			</div>
			<!-- /inner content -->

		</div>
		<!-- /main content -->

	</div>
	<!-- /page content -->

</body>

</html>
<?php
$mysqli->close();
ob_flush();
?>
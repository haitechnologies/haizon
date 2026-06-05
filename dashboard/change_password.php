<?php

include('admin_elements/admin_header.php');

$module 				= 'users';
$module_caption 		= 'My Profile';
$tbl_name = $tbl_prefix . $module;

$error_message = '';
$success_message = '';

// print_r($_REQUEST);

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

$id			= $session_user_id;
$email 		= $session_email;

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in change_password.php', 'WARNING', __FILE__, __LINE__);
    }
}

/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "change_password") {
	$current_password		= e_s__($_POST['current_password']);
	$new_password			= e_s__($_POST['new_password']);
	$confirm_new_password	= e_s__($_POST['confirm_new_password']);
} else {
	$current_password		= '';
	$new_password			= '';
	$confirm_new_password	= '';
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == 'change_password') {


	$result 		= $mysqli->query("SELECT password FROM `$tbl_name` WHERE email='" . $email . "' AND is_active=1 LIMIT 1");
	$row 			= $result->fetch_array();
	$hash 			= $row['password'];

	// echo $hash; echo '<br />';
	// echo $current_password;

	if (empty($new_password)) {
		$error_message = 'New Password is required!';
	} else if (strlen($new_password) <= 5) {
		$error_message = 'New Password lenght must be between 6 - 20 chars';
	} else if ($new_password != $confirm_new_password) {
		$error_message = 'New Password and Confirm New Password Mismatch!';
	} else {

		if (password_verify($current_password, $hash)) {

			//Update Query
			$update_row = $mysqli->query("UPDATE `$tbl_name` SET password='" . password_hash($new_password, PASSWORD_DEFAULT) . "' WHERE id='" . $id . "'");

			################################## START SEND EMAIL #################################
			// $to = $_SESSION['email'];
			// $subject = 'New Password Information';
			// $body = 'Your new password is: "'.$new_password.'"';
			// try {
			// 	$mail=new PHPMailer(true); $mail->IsSMTP(); $mail->SMTPAuth=true;
			// 	//**********************************************
			// 	$mail->Port=$GLOBALS['SITE']['SMTP_PORT'];
			// 	$mail->Host=$GLOBALS['SITE']['SMTP_HOST'];
			// 	$mail->Username=$GLOBALS['SITE']['SMTP_USERNAME'];
			// 	$mail->Password=$GLOBALS['SITE']['SMTP_PASSWORD'];
			// 	$mail->IsSendmail();
			// 	$reply_to = $GLOBALS['SITE']['EMAIL_REPLYTO'];
			// 	$reply_to_name = $GLOBALS['SITE']['EMAIL_REPLYTONAME'];
			// 	$from = $GLOBALS['SITE']['EMAIL_FROMEMAIL'];
			// 	$from_name = $GLOBALS['SITE']['EMAIL_FROMNAME'];
			// 	//**********************************************
			// 	$mail->AddAddress($to); $mail->Subject=$subject; $mail->AltBody=""; $mail->WordWrap=80; $mail->From = $from; $mail->FromName = $from_name; $mail->MsgHTML($body); $mail->IsHTML(true); $mail->Send();
			// 	//echo 'Mail Sentzzz';
			// } catch (phpmailerException $e) { echo $e->errorMessage(); }//end try catch
			################################## END SEND EMAIL ###################################
			$success_message = 'Congratulations! Your password is changed successfully.';
			sleep(2);
			header("Location:logout.php");
		} else {
			// redirect to the homepage
			$error_message = 'Incorrect Current Password!';
		}
	}
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>

<div class="content-wrapper">

	<form method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="change_password.php" autocomplete="off" novalidate>
		<input type="hidden" name="action" id="action" value="change_password" />
		<?php echo csrf_field(); ?>

		<!-- Page header -->
		<div class="page-header page-header-light shadow">
			<div class="page-header-content d-lg-flex border-top">
				<div class="d-flex">
					<div class="breadcrumb py-2">
						<a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
						<a href="index.php" class="breadcrumb-item">Home</a>
						<a href="#" class="breadcrumb-item"><?php echo htmlspecialchars($session_email); ?></a>
						<span class="breadcrumb-item active">Update Password</span>
					</div>

					<a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
						<i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
					</a>
				</div>

				<div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
					<div class="d-lg-flex mb-2 mb-lg-0">
						<button type="submit" class="btn btn-info my-1 me-2">Update Password </button>
					</div>
				</div>

			</div>
		</div>
		<!-- /page header -->

		<div class="content-inner">
			<div class="content">

				<?php include('admin_elements/breadcrumb.php'); ?>

				<div class="row">
					<div class="col-lg-8">

						<div class="card">

							<div class="card-body">

								<div class="row">
									<div class="col-md-12">
										<div class="mb-3">
											<p class="mb-12"><label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label></p>
											<div style="position: relative;">
												<input required type="password" class="form-control maxlength-badge-position pe-5" maxlength="20" name="current_password" id="current_password" aria-required="true">
												<div class="invalid-feedback">
													Current password is required
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-12">
										<div class="mb-3">
											<p class="mb-12"><label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label></p>
											<div style="position: relative;">
												<input required type="password" class="form-control maxlength-badge-position pe-5 password-input" data-strength-target="#new_password" maxlength="20" name="new_password" id="new_password" aria-required="true">
												<div class="invalid-feedback">
													New password is required (6-20 characters)
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-12">
										<div class="mb-3">
											<p class="mb-12"><label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label></p>
											<div style="position: relative;">
												<input required type="password" class="form-control maxlength-badge-position pe-5" maxlength="20" name="confirm_new_password" id="confirm_new_password" aria-required="true">
												<div class="invalid-feedback">
													Please confirm your new password
												</div>
											</div>
										</div>
									</div>
								</div>

							</div>

						</div>

						<div class="alert bg-info text-white">
							System will <span class="fw-semibold">logout </span> after Successfull Password Change.
							<i class="ph-warning-circle float-end ms-2"></i>
						</div>

					</div>

				</div>


			</div>

			<?php include('admin_elements/copyright.php'); ?>
		</div>
	</form>

</div>
<?php include('admin_elements/admin_footer.php'); ?>
<?php

require_once __DIR__ . '/../config/session.php';

header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: post-check=0, pre-check=0", false);
session_cache_limiter("must-revalidate");
ob_start();
startDashboardSession();
include('../config/globals.php');
include('../config/database.php');
include('admin_elements/grab_vars.php');

// Initialize CSRF token for authentication page
csrf_token();

$module = 'users';
$module_caption = 'User3';
$message = '';


/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

// print_r($_REQUEST);


// -- If already logged in 
if (isset($_SESSION[$project_pre]['email']) && !empty($_SESSION[$project_pre]['DASHBOARD']['email'])) {
    header('location:index.php');
}


/*
|--------------------------------------------------------------------------
| 	VARIFY TOKEN
|--------------------------------------------------------------------------
|
*/

$activation_code = '';


if (isset($_REQUEST['activation_code']) && !empty($_REQUEST['activation_code'])) {
    $activation_code     = e_s__($_REQUEST['activation_code']);
}
if (isset($_POST['activation_code']) && !empty($_POST['activation_code'])) {
    $activation_code     = e_s__($_POST['activation_code']);
}



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "reset_password") {
	// CSRF validation
	if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
		$message = 'Invalid security token. Please refresh the page and try again.';
		log_error('CSRF token validation failed in reset_password.php', 'WARNING', __FILE__, __LINE__);
		$new_password = '';
		$confirm_new_password = '';
	} else {
		$new_password            = e_s__($_POST['new_password']);
		$confirm_new_password    = e_s__($_POST['confirm_new_password']);
	}
} else {
    $new_password            = '';
    $confirm_new_password    = '';
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == 'reset_password') {

    if (empty($new_password)) {
        $message = 'New Password is required!';
    } else if (strlen($new_password) <= 5) {
        $message = 'New Password lenght must be between 6 - 20 chars';
    } else if ($new_password != $confirm_new_password) {
        $message = 'New Password and Confirm New Password Mismatch!';
    } else {

        $email      = getTableAttrV("email", tbl_users, " activation_code = '" . $activation_code . "' ");
        $id         = getTableAttrV("id", tbl_users, " activation_code = '" . $activation_code . "' ");

        $token = hash("sha512", 'bushogai' . $id);

        /*
        |--------------------------------------------------------------------------
        | 	RESET PASSWORD
        |--------------------------------------------------------------------------
        |
        */
        if ($activation_code == $token) {
            $mysqli->query("UPDATE `" . tbl_users . "` SET password = '" . password_hash($new_password, PASSWORD_DEFAULT) . "' WHERE activation_code = '" . $activation_code . "' AND id = '" . $id . "'  AND email = '" . $email . "' ");

            // -- Clear activation code
            $mysqli->query("UPDATE `" . tbl_users . "` SET activation_code = '' WHERE activation_code = '" . rand(111111, 9999999999) . "' AND id = '" . $id . "'  AND email = '" . $email . "' ");
            
            // -- Redirect to index page
            header('location:login.php?message=New Password has been updated.');
        
        } else {
            $message = 'Invalid Token Request.';
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
    <title>Admin Dashboard - Reset Password</title>
    <meta name="robots" content="noindex">

    <link rel="shortcut icon" href="<?php echo $admin_base_url; ?>/favicon.ico" type="image/x-icon" />
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

<body style="background-color: #F6F6EF;">

    <!-- Main navbar -->
    <div class="navbar navbar-static py-2" style="background-color: #182A3E;"><!-- #FF6600-->
        <div class="container-fluid">
            <div class="navbar-brand">
                <a href="login.php" class="d-inline-flex align-items-center" style="color: #fff;">
                    <!-- <img src="assets/images/logo_text_light.svg" class="d-none d-sm-inline-block h-16px ms-3" alt=""> -->
                    <!-- <img src="../images/logo.png" alt=""> -->

                    <?php
                    // ---------------------------------- LOGO ---------------------------------- 
                    $logo        = getTableAttrv('setting_value', tbl_system_settings, 'setting_slug ="logo"');

                    if (!empty($logo) && file_exists('../uploads/global_settings/thumbs/' . $logo)) {
                        $display_logo = '../uploads/global_settings/' . s__($logo);
                    } else {
                        $display_logo = $base_url . '../images/default_logo.png';
                    }
                    // ----------------------------------------------------------------------------- 
                    ?>
                    <img src="<?php echo $display_logo; ?>" alt="Logo">


                    &nbsp;

                    <?php
                    //echo getTableAttr('company_name', tbl_global_settings, 1); 
                    $software_name        = getTableAttrv('setting_value', tbl_system_settings, 'setting_slug ="software_name"');
                    echo s__($software_name);
                    ?>

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

                    <form class="login-form" id="form_reset_password" action="reset_password.php" method="post">
                        <input type="hidden" name="action" id="action" value="reset_password" />
                        <input type="hidden" name="activation_code" id="activation_code" value="<?php echo $activation_code;?>" />
						<?php echo csrf_field(); ?>

                        <?php
                        // echo getTableAttr('company_name', tbl_global_settings, 1);
                        // $software_name		= getTableAttrv('setting_value', tbl_system_settings, 'setting_slug ="software_name"');
                        // echo s__($software_name);
                        ?>

                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="d-inline-flex bg-primary bg-opacity-10 text-primary lh-1 rounded-pill p-3 mb-3 mt-1">
                                        <i class="ph-arrows-counter-clockwise ph-2x"></i>
                                    </div>
                                    <h5 class="mb-0">Set your Password</h5>
                                    <?php if (!empty($message)) { ?><span class="d-block text-warning"> <?php echo $message; ?> </small></span><?php } ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <p class="mb-12"><label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label></p>
                                            <div style="position: relative;">
                                                <input required type="password" class="form-control maxlength-badge-position pe-5 password-input" data-strength-target="#new_password" maxlength="20" name="new_password" id="new_password">
                                            </div>
                                            <div class="form-text text-muted">Password lenght must be between 6 - 20 chars</div>
                                            <div id="new_password-strength-status"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <p class="mb-12"><label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label></p>
                                            <div style="position: relative;">
                                                <input required type="password" class="form-control maxlength-badge-position pe-5" maxlength="20" name="confirm_new_password" id="confirm_new_password">
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ph-arrow-counter-clockwise me-2"></i>
                                    Set password
                                </button>
                            </div>
                        </div>

                    </form>


                </div>
                <!-- /content area -->


                <!-- Footer -->
                <div class="navbar navbar-sm navbar-footer border-top">
                    <div class="container-fluid">
                        <span>&copy; <?php echo date('Y'); ?></span>
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
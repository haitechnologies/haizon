<?php

include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$module                 = 'email_providers';
$module_caption         = 'Email Provider';
$tbl_name               = DB::EMAIL_PROVIDERS;

$error_message = '';
$success_message = '';


/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in email_providers.php', 'WARNING', __FILE__, __LINE__);
    }
}

// print_r($_REQUEST);

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

if (isset($_POST['is_active']))       $is_active = 1;
else $is_active = 0;

if (isset($_POST['is_primary']))    $is_primary     = 1;
else $is_primary = 0;



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $provider_name          = e_s__((string)($_POST['provider_name'] ?? ''));
    $email_encryption       = e_s__((string)($_POST['email_encryption'] ?? 'NONE'));
    $smtp_host              = e_s__((string)($_POST['smtp_host'] ?? ''));
    $smtp_port              = e_s__((string)($_POST['smtp_port'] ?? ''));
    $email                  = e_s__((string)($_POST['email'] ?? ''));
    $smtp_username          = e_s__((string)($_POST['smtp_username'] ?? ''));
    $smtp_password          = e_s__((string)($_POST['smtp_password'] ?? ''));
    $bcc                    = e_s__((string)($_POST['bcc'] ?? ''));
    $daily_limit            = (int)($_POST['daily_limit'] ?? 500);
} else {
    $provider_name          = '';
    $email_encryption       = '';
    $smtp_host              = '';
    $smtp_port              = '';
    $email                  = '';
    $smtp_username          = '';
    $smtp_password          = '';
    $bcc                    = '';
    $daily_limit            = 500;
}



/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    if (Roles::isSuperAdmin(Roles::getCurrentRoleId())) {

        $mysqli->query("DELETE FROM `" . DB::EMAIL_PROVIDERS . "` WHERE id=$id");
    } else {

        $mysqli->query("DELETE FROM `" . DB::EMAIL_PROVIDERS . "` WHERE id=$id AND created_by ='" . $session_user_id . "'");
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($provider_name)) {
        $error_message = 'Provider Name is mandatory.';
    } else if (empty($smtp_host)) {
        $error_message = 'SMTP Host is mandatory.';
    } else if (empty($smtp_port)) {
        $error_message = 'SMTP Port is mandatory.';
    } else if (empty($email)) {
        $error_message = 'Email is mandatory.';
    } else if (empty($smtp_username)) {
        $error_message = 'SMTP Usernam is mandatory.';
    } else if (empty($smtp_password)) {
        $error_message = 'SMTP Password is mandatory.';
    } else {

        // If is_primary is set to 1, unset all other primary email providers
        if ($is_primary == 1) {
            $update_row = $mysqli->query("UPDATE `$tbl_name` SET is_primary = '0' ");
        }

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
                                    UPDATE `$tbl_name` SET                                        provider_name               = '" . $provider_name . "',                                        email_encryption            = '" . $email_encryption . "',
                                    smtp_host						= '" . $smtp_host . "',
                                    smtp_port						= '" . $smtp_port . "',
                                    email					        = '" . $email . "',
                                    smtp_username					= '" . $smtp_username . "',
                                    smtp_password					= '" . $smtp_password . "',
                                    bcc				            = '" . $bcc . "',
                                    daily_limit					= '" . $daily_limit . "',
                                    is_primary				    = '" . $is_primary . "',
                                    is_active		        = '" . $is_active . "'
                                    WHERE id=$id");
        if ($update_row) {

            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($provider_name)) {
        $error_message = 'Provider Name is mandatory.';
    } else if (empty($smtp_host)) {
        $error_message = 'SMTP Host is mandatory.';
    } else if (empty($smtp_port)) {
        $error_message = 'SMTP Port is mandatory.';
    } else if (empty($email)) {
        $error_message = 'Email is mandatory.';
    } else if (empty($smtp_username)) {
        $error_message = 'SMTP Usernam is mandatory.';
    } else if (empty($smtp_password)) {
        $error_message = 'SMTP Password is mandatory.';
    } else {

        // If is_primary is set to 1, unset all other primary email providers
        if ($is_primary == 1) {
            $update_row = $mysqli->query("UPDATE `$tbl_name` SET is_primary = '0' ");
        }

        /* ---------------------- QUERY ---------------------- */
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(provider_name, is_primary, email_encryption, smtp_host, smtp_port, email, smtp_username, smtp_password, bcc, daily_limit, is_active) VALUES ('" . $provider_name . "', '" . $is_primary . "', '" . $email_encryption . "', '" . $smtp_host . "', '" . $smtp_port . "', '" . $email . "', '" . $smtp_username . "', '" . $smtp_password . "', '" . $bcc . "', '" . $daily_limit . "', '" . $is_active . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
            //header("Location:$module.php?error_message=$error_message");
        }
    }
}



/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/

if ($action == "edit_$module" && !empty($id)) {

    $result = $mysqli->query("SELECT * FROM `" . DB::EMAIL_PROVIDERS . "` WHERE id=$id");
    $row = ($result && $result->num_rows > 0) ? $result->fetch_array(MYSQLI_ASSOC) : [];

    $provider_name          = s__((string)($row['provider_name'] ?? ''));
    $is_primary             = s__((string)($row['is_primary'] ?? '0'));
    $email_encryption       = s__((string)($row['email_encryption'] ?? 'NONE'));
    $smtp_host              = s__((string)($row['smtp_host'] ?? ''));
    $smtp_port              = s__((string)($row['smtp_port'] ?? ''));
    $email                  = s__((string)($row['email'] ?? ''));
    $smtp_username          = s__((string)($row['smtp_username'] ?? ''));
    $smtp_password          = s__((string)($row['smtp_password'] ?? ''));
    $bcc                    = s__((string)($row['bcc'] ?? ''));
    $daily_limit            = (int)($row['daily_limit'] ?? 500);
    $is_active              = s__((string)($row['is_active'] ?? '0'));
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">
	<?php if (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar')): ?>
		<?php renderEmailQuickbar($visibleEmailLinks, $current_page); ?>
	<?php endif; ?>

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
        <?php if ($action == "edit_$module" || $action == "update_$module") { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>


        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="d-flex">
                    <div class="breadcrumb py-2">
                        <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                        <a href="index.php" class="breadcrumb-item">Home</a>
                        <a href="listing_customers.php" class="breadcrumb-item">Global Settings</a>
                        <span class="breadcrumb-item active">Email Providers</span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="p-3 rounded">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_primary" id="is_primary" <?php if ($is_primary == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Is Primary?</label>
                    </div>
                </div>

                <div class="p-3 rounded">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Publish</label>
                    </div>
                </div>

                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <div class="collapse d-lg-block ms-lg-auto mt-1" id="breadcrumb_elements">
                        <div class="d-lg-flex mb-2 mb-lg-0">
                            <button type="submit" class="btn btn-info my-1 me-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Save<?php } ?> <?php echo $module_caption; ?></button>
                            <button type="button" class="btn btn-outline-secondary my-1 ms-auto d-flex align-items-center gap-1" style="min-width:110px" onclick="window.location.href='listing_email_providers.php'" title="Back to Email Providers">
                                 Exit
                            </button>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
        <!-- /page header -->

        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>


                <div class="row">

                    <div class="row">

                        <div class="col-lg-6">
                            <div class="card">

                                <div class="card-header">
                                    <h6 class="mb-0"><?php echo $module_caption; ?></h6>
                                </div>

                                <div class="card-body">


                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Provider Name: <span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <input required name="provider_name" id="provider_name" value="<?php echo $provider_name; ?>" class="form-control" type="text" placeholder="e.g., HAIPULSE - Support">
                                            <div class="form-text text-muted"><small>A friendly name to identify this email provider</small></div>
                                        </div>
                                    </div>




                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Email Encryption: <span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="email_encryption" id="email_encryption">
                                                <option value="NONE">None</option>
                                                <option value="SSL" <?php if ($action == "edit_$module" && $email_encryption == 'SSL') { ?>selected <?php } else if ($email_encryption == 'SSL') { ?>selected <?php } ?>>SSL</option>
                                                <option value="TLS" <?php if ($action == "edit_$module" && $email_encryption == 'TLS') { ?>selected <?php } else if ($email_encryption == 'TLS') { ?>selected <?php } ?>>TLS</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">SMTP Host: <span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <input required name="smtp_host" id="smtp_host" value="<?php echo $smtp_host; ?>" class="form-control" type="text">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">SMTP Port:<span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <input name="smtp_port" id="smtp_port" value="<?php echo $smtp_port; ?>" class="form-control" type="text">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Email:<span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <input required name="email" id="email" value="<?php echo $email; ?>" class="form-control" type="email">
                                        </div>
                                    </div>


                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">SMTP Username: <span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <input name="smtp_username" id="smtp_username" value="<?php echo $smtp_username; ?>" class="form-control" type="text">
                                            <div class="form-text text-muted"><small>+971 50 1234567</small></div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">SMTP Password: <span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <?php 
                                            // Always show password field in plaintext with generate/copy buttons
                                            ?>
                                            <div class="input-group">
                                                <input name="smtp_password" id="smtp_password" value="<?php echo $smtp_password; ?>" class="form-control" type="text" placeholder="Enter SMTP password" autocomplete="off" minlength="8" required pattern="^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$">
                                                <button type="button" class="btn btn-outline-secondary" id="generateSmtpPassword" tabindex="-1">Generate</button>
                                                <button type="button" class="btn btn-outline-secondary" id="copySmtpPassword" tabindex="-1">Copy</button>
                                            </div>
                                            <div id="smtpPasswordHelp" class="form-text text-danger" style="display:none;"></div>
                                            <div class="form-text text-muted"><small>Password should be a minimum 8 characters. Must contain letters, numbers, and symbols. <span class="text-danger">Do not share this password.</span></small></div>
                                            <script>
                                            // Password generator
                                            function generateStrongPassword(length = 18) {
                                                const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*()-_=+[]{}';
                                                let password = '';
                                                for (let i = 0, n = charset.length; i < length; ++i) {
                                                    password += charset.charAt(Math.floor(Math.random() * n));
                                                }
                                                return password;
                                            }
                                            function validateSmtpPassword(pwd) {
                                                const minLen = 8;
                                                const hasLetter = /[A-Za-z]/.test(pwd);
                                                const hasNumber = /\d/.test(pwd);
                                                const hasSymbol = /[^A-Za-z\d]/.test(pwd);
                                                if (pwd.length < minLen) {
                                                    return 'Password should be a minimum 8 characters.';
                                                }
                                                if (!hasLetter || !hasNumber || !hasSymbol) {
                                                    return 'Must contain letters, numbers, and symbols.';
                                                }
                                                return '';
                                            }
                                            document.addEventListener('DOMContentLoaded', function() {
                                                const genBtn = document.getElementById('generateSmtpPassword');
                                                const copyBtn = document.getElementById('copySmtpPassword');
                                                const pwdInput = document.getElementById('smtp_password');
                                                const helpText = document.getElementById('smtpPasswordHelp');
                                                if (genBtn && pwdInput) {
                                                    genBtn.addEventListener('click', function() {
                                                        pwdInput.value = generateStrongPassword();
                                                        pwdInput.dispatchEvent(new Event('input'));
                                                    });
                                                }
                                                if (copyBtn && pwdInput) {
                                                    copyBtn.addEventListener('click', function() {
                                                        pwdInput.select();
                                                        document.execCommand('copy');
                                                    });
                                                }
                                                if (pwdInput && helpText) {
                                                    pwdInput.addEventListener('input', function() {
                                                        const msg = validateSmtpPassword(pwdInput.value);
                                                        if (msg) {
                                                            helpText.textContent = msg;
                                                            helpText.style.display = 'block';
                                                        } else {
                                                            helpText.textContent = '';
                                                            helpText.style.display = 'none';
                                                        }
                                                    });
                                                    // Initial validation
                                                    pwdInput.dispatchEvent(new Event('input'));
                                                }
                                            });
                                            </script>
                                        <?php 
                                            ?>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Email Charset: </label>
                                        <div class="col-lg-9">
                                            <input name="email_charset" id="email_charset" value="UTF-8" class="form-control" type="text" readonly>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">&nbsp;</h6>
                                </div>

                                <div class="card-body">


                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">BCC All Emails To:</label>
                                        <div class="col-lg-9">
                                            <input name="bcc" id="bcc" value="<?php echo $bcc; ?>" class="form-control" type="email">
                                            <div class="form-text text-muted"><small>info@example.com</small></div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Daily Limit (24h):</label>
                                        <div class="col-lg-9">
                                            <input name="daily_limit" id="daily_limit" value="<?php echo htmlspecialchars($daily_limit, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" type="number" min="1" max="10000" placeholder="500" required>
                                            <div class="form-text text-muted"><small>Maximum emails allowed to send per 24 hours (default: 500).</small></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Domain Hour Limit:</label>
                                        <div class="col-lg-9">
                                            <input name="domain_hour_limit" id="domain_hour_limit" value="<?php echo isset($row['domain_hour_limit']) ? (int)$row['domain_hour_limit'] : ''; ?>" class="form-control" type="number" min="1" max="100000" placeholder="1000">
                                            <div class="form-text text-muted"><small>Max emails allowed per hour for the domain (Titan default: 1000).</small></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Domain Day Limit:</label>
                                        <div class="col-lg-9">
                                            <input name="domain_day_limit" id="domain_day_limit" value="<?php echo isset($row['domain_day_limit']) ? (int)$row['domain_day_limit'] : ''; ?>" class="form-control" type="number" min="1" max="100000" placeholder="2000">
                                            <div class="form-text text-muted"><small>Max emails allowed per day for the domain (Titan default: 2000).</small></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Mailbox Hour Limit:</label>
                                        <div class="col-lg-9">
                                            <input name="mailbox_hour_limit" id="mailbox_hour_limit" value="<?php echo isset($row['mailbox_hour_limit']) ? (int)$row['mailbox_hour_limit'] : ''; ?>" class="form-control" type="number" min="1" max="100000" placeholder="50">
                                            <div class="form-text text-muted"><small>Max emails allowed per hour for this mailbox (Titan default: 50).</small></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Mailbox Day Limit:</label>
                                        <div class="col-lg-9">
                                            <input name="mailbox_day_limit" id="mailbox_day_limit" value="<?php echo isset($row['mailbox_day_limit']) ? (int)$row['mailbox_day_limit'] : ''; ?>" class="form-control" type="number" min="1" max="100000" placeholder="100">
                                            <div class="form-text text-muted"><small>Max emails allowed per day for this mailbox (Titan default: 100).</small></div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>


                </div>
            </div>


            <?php include('admin_elements/copyright.php'); ?>
        </div>
    </form>

</div>


<!-- 
    // ---------------------------------------------------------
    // ENABLE VIEW ONLY MODE FOR FORM ELEMENTS
    // ---------------------------------------------------------
-->
<?php if (isset($module_id) && granted('view', $module_id) && !granted('create', $module_id) && !granted('edit', $module_id)) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>

<?php include('admin_elements/admin_footer.php'); ?>


<?php

include('admin_elements/admin_header.php');

$module             = 'users';
$module_caption     = 'Employee';
$tbl_name = $tbl_prefix . $module;

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
        log_error('CSRF token validation failed in users.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

$role_id = getTableAttr('role_id', tbl_users, $id);

if (Roles::hasFullAccess($role_id)) {
    header("Location:listing_users.php?error_message=Access Denied to edit/update Super Admin Users. Super Admin can update Information from My Profile Link.");
}


if (isset($_POST['can_access_system']))                     $can_access_system     = 1;
else $can_access_system = 0;

if (isset($_POST['is_active']))                               $is_active     = 1;
else $is_active = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {

    $role_id                = e_s__($_POST['role_id']);

    $full_name              = e_s__($_POST['full_name']);
    $email                  = e_s__($_POST['email']);
    $password               = e_s__($_POST['password']);
    $contact1               = e_s__($_POST['contact1']);
    $contact2               = e_s__($_POST['contact2']);
    $address                = e_s__($_POST['address']);
    $dob                    = e_s__($_POST['dob']);
} else {

    $role_id                = '';

    $full_name              = '';
    $email                  = '';
    $password               = '';
    $contact1               = '';
    $contact2               = '';
    $address                = '';
    $dob                    = '';
    $can_access_system      = 1;
}

if (empty($dob)) $dob = '01-01-1970';


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($role_id) || $role_id == 'Please select') {
        $error_message = 'Please select role.';
    } else if (empty($full_name)) {
        $error_message = 'Full name is mandatory.';
    } else if (empty($email)) {
        $error_message = 'Email is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'email', $email) && $email != getTableAttr('email', $tbl_name, $id)) {
        $error_message = 'Duplicate Email. Please enter different.';
    } else if (!empty($password) && strlen($password) <= 5) {
        $error_message = 'Password lenght must be between 6 - 20 chars.';
    } else if (empty($contact1)) {
        $error_message = 'Contact 1 is mandatory.';
    } else {

        $dob = processDateDtoY($dob);

        if (!empty($password))
            $mysqli->query("UPDATE `$tbl_name` SET password = '" . password_hash($password, PASSWORD_DEFAULT) . "' WHERE id=$id");

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET

                                            role_id				        = '" . $role_id . "',

											full_name				    = '" . $full_name . "',
											email						= '" . $email . "',
											contact1					= '" . $contact1 . "',
											contact2					= '" . $contact2 . "',
											address						= '" . $address . "',
											dob							= '" . $dob . "',
											can_access_system 		    = '" . $can_access_system . "',
											is_active 					= '" . $is_active . "'
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
} else if ($action == "add_$module") {

    if (empty($role_id) || $role_id == 'Please select') {
        $error_message = 'Please select role.';
    } else if (empty($full_name)) {
        $error_message = 'Full name is mandatory.';
    } else if (empty($email)) {
        $error_message = 'Email is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'email', $email) && $email != getTableAttr('email', $tbl_name, $id)) {
        $error_message = 'Duplicate Email. Please enter different.';
    } else if (!empty($password) && strlen($password) <= 5) {
        $error_message = 'Password lenght must be between 6 - 20 chars.';
    } else if (empty($contact1)) {
        $error_message = 'Contact 1 is mandatory.';
    } else {

        $dob = processDateDtoY($dob);

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(role_id, full_name, email, contact1, contact2, address, dob, can_access_system) VALUES ('" . $role_id . "', '" . $full_name . "', '" . $email . "', '" . $contact1 . "', '" . $contact2 . "', '" . $address . "', '" . $dob . "', '" . $can_access_system . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);

            if (!empty($password))
                $mysqli->query("UPDATE `$tbl_name` SET password = '" . password_hash($password, PASSWORD_DEFAULT) . "' WHERE id=$id");

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
if (!empty($id)) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $role_id                = s__($row['role_id']);

    $full_name              = s__($row['full_name']);
    $email                  = s__($row['email']);
    $password               = s__($row['password']);
    $contact1               = s__($row['contact1']);
    $contact2               = s__($row['contact2']);
    $address                = s__($row['address']);

    $dob                    = s__($row['dob']);
    $dob                    = processDateYtoD($dob);

    $can_access_system      = s__($row['can_access_system']);
    $is_active              = s__($row['is_active']);
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>
<div class="content-wrapper">

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h1 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h1>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>


                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="can_access_system" id="can_access_system" <?php if ($can_access_system == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Can Access System?</label>
                    </div>
                </div>

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Is Active?</label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

                            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                <button type="submit" class="btn btn-primary btn-sm me-2">Save</button>
                            <?php } ?>

                            <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->

        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">System Role:*</span></label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="role_id" id="role_id">
                                            <option value='0'>Please select</option>
                                            <?php
                                            $result_roles = $mysqli->query("SELECT * FROM `" . tbl_roles  . "` WHERE is_active=1 AND id > 2 ORDER BY role_name ASC");
                                            while ($rows_roles = $result_roles->fetch_array()) {
                                                // $role        = s__($rows_roles['role
                                            ?>
                                                <option value="<?php echo $rows_roles['id']; ?>" <?php if ($action == "edit_$module" && $rows_roles['id'] == $role_id) { ?>selected <?php } else if ($rows_roles['id'] == $role_id) { ?>selected <?php } ?>>
                                                    <?php echo $rows_roles['role_name']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>
                                        <div class="form-text text-muted">System Access: Roles & Permissions</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label" for="full_name"><span class="text-danger">Full Name:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="full_name" id="full_name" value="<?php echo $full_name; ?>" class="form-control" aria-required="true" aria-label="Full name (required)">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label" for="email"><span class="text-danger">Email:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="email" name="email" id="email" value="<?php echo $email; ?>" class="form-control" aria-required="true" aria-label="Email address (required)">
                                    </div>
                                </div>


                                <?php if (has_full_access()) { ?>
                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">
                                            Password:
                                            <?php if (!empty($password)) { ?>
                                                <div class="text-end mt-1">
                                                    <span class="badge bg-indigo">Password Generated</span>
                                                </div>
                                            <?php } ?>

                                        </label>
                                        <div class="col-lg-9">
                                            <input type="password" name="password" id="password" class="form-control password-input" data-strength-target="#password">
                                            <div class="form-text text-muted">Password lenght must be between 6 - 20 chars</div>
                                            <div id="password-strength-status"></div>

                                        </div>
                                    </div>
                                <?php } ?>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Contact 1:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="contact1" id="contact1" value="<?php echo $contact1; ?>" class="form-control">
                                        <div class="form-text text-muted">050 1234574</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Contact 2:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="contact2" id="contact2" value="<?php echo $contact2; ?>" class="form-control">
                                        <div class="form-text text-muted">050 1234574</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Address:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="address" id="address" value="<?php echo $address; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Date of Birth:</label>
                                    <div class="col-lg-9">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ph-calendar"></i>
                                            </span>
                                            <input type="text" class="form-control datepicker-basic datepicker-input in-edit" name="dob" id="dob" value="<?php ($dob == '01-01-1970') ? '' : print($dob); ?>" placeholder="Date of Birth">
                                        </div>
                                    </div>
                                </div>


                            </div>

                        </div>

                    </div>



                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-header">
                                <h2 class="mb-0">Documents</h2>
                            </div>

                            <div class="card-body">




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
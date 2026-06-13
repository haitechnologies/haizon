<?php


use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in modules.php', 'WARNING', __FILE__, __LINE__);
    }
}

$module             = 'modules';
$module_caption     = 'Module';
$tbl_name             = $tbl_prefix . $module;
$error_message         = '';
$success_message     = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/


// print_r($_REQUEST);



// ---------------------- Tag Types Array -----------------------------
$systems_arr          = array();
$posted_systems_arr   = array();
$systems_string       = '';
$system_name               = '';


if (isset($_POST['systems'])) {

    $posted_systems = $_POST['systems'];

    foreach ($posted_systems as $system_name) {
        $systems_string .= $system_name . ', ';
    }
    if (strlen($systems_string) > 2) {
        $systems_string = substr($systems_string, 0, -2);
    }
    // echo $systems_string;

    $posted_systems_arr = explode(',', $systems_string);
}



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $slug               = e_s__($_POST['slug']);
    $module_name        = e_s__($_POST['module_name']);
} else {
    $slug               = '';
    $module_name        = '';
}

/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {


    if (empty($slug)) {
        $error_message = 'Slug is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'slug', $slug) && $slug != getTableAttr('slug', $tbl_name, $id)) {
        $error_message = 'Duplicate Slug. Please enter different.';
    } else if (empty($module_name)) {
        $error_message = 'Module name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'module_name', $module_name) && $module_name != getTableAttr('module_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Module Name. Please enter different.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
											slug			    = '" . $slug . "',
                                            systems	            = '" . $systems_string . ",',
											module_name         = '" . $module_name . "'
										WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_modules.php?success_message=$success_message");
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

    if (empty($slug)) {
        $error_message = 'Slug is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'slug', $slug)) {
        $error_message = 'Duplicate Slug. Please enter different.';
    } else if (empty($module_name)) {
        $error_message = 'Module name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'module_name', $module_name)) {
        $error_message = 'Duplicate Module name. Please enter different.';
    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(slug, module_name, systems) VALUES ('" . $slug . "', '" . $module_name . "', '" . $systems_string . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_modules.php?success_message=$success_message");
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

    $slug               = s__($row['slug']);
    $module_name        = s__($row['module_name']);

    // -- Dnamic Modules
    $systems                   = s__($row['systems']);
    $systems_arr               = array();
    if ($systems != NULL) {
        $systems_arr           = explode(',', $systems);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module" || $action == "change_password") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" <?php if ($publish == '1') { ?>checked="checked" <?php } ?> form="frmmodules">
                    <label class="form-check-label" for="publish">Publish</label>
                </div>
            </div>
            <div class="my-1">
                <?php if (empty($id) || (isset($module_id) && granted('create', $module_id)) || (isset($module_id) && granted('edit', $module_id)) || $file === 'profile.php' || $file === 'change_password.php') { ?>
                    <button type="submit" form="frmmodules" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" autocomplete="off" action="<?php echo $module; ?>.php">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <!-- Page header -->


                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Slug: <span class="text-danger">*</span> (DB Table Name) </label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="slug" id="slug" value="<?php echo $slug; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Module Name: <span class="text-danger">*</span> </label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="module_name" id="module_name" value="<?php echo $module_name; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Modules: </label>
                                    <div class="col-lg-9">

                                        <select name="systems[]" id="systems[]" class="form-control select" multiple="multiple" data-tags="true">
                                            <?php
                                            // -------------------------------------------------------------------------------------------------
                                            $result_systems       = $mysqli->query("SELECT * FROM `" . $tbl_name . "` WHERE module_type='system' AND is_active=1 ORDER BY module_name");
                                            while ($rows_systems  = $result_systems->fetch_array()) {
                                                // $assigned_to        = s__($rows_tags['full_name']);
                                                // -------------------------------------------------------------------------------------------------
                                            ?>

                                                <option value="<?php echo $rows_systems['id']; ?>" <?php if ($action == "edit_$module" && in_array($rows_systems['id'], $systems_arr)) { ?>selected <?php } else if (in_array($rows_systems['id'], $posted_systems_arr)) { ?>selected <?php } ?>>
                                                    <?php echo $rows_systems['module_name']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>

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
<?php include('admin_elements/admin_footer.php'); ?>
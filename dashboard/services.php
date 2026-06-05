<?php

include('admin_elements/admin_header.php');

$module             = 'services';
$module_caption     = 'Service';
$tbl_name             = $tbl_prefix . $module;
$error_message         = '';
$success_message     = '';


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
        log_error('CSRF token validation failed in services.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

if (isset($_POST['is_active']))       $is_active = 1;
else $is_active = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $service_name           = e_s__($_POST['service_name']);
    $description            = e_s__($_POST['description']);
} else {
    $service_name           = '';
    $description            = '';
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {


    if (empty($service_name)) {
        $error_message = 'Service name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'service_name', $service_name) && $service_name != getTableAttr('service_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Service name. Please enter different.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
											service_name    = '" . $service_name . "',
											description	    = '" . $description . "',
											publish 		= '" . $publish . "'
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

    if (empty($service_name)) {
        $error_message = 'Service name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'service_name', $service_name)) {
        $error_message = 'Service name already exists. Please enter a different one.';
    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(service_name, description, publish) VALUES ('" . $service_name . "', '" . $description . "', '" . $publish . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            fp__($tbl_name, $id);
            $success_message = "The $module_caption has been saved successfully.";
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

    $service_name       = s__($row['service_name']);
    $description        = s__($row['description']);
    $is_active = s__($row['publish']);
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
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
                <div class="d-flex">
                    <div class="breadcrumb py-2">
                        <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                        <a href="index.php" class="breadcrumb-item">Home</a>
                        <a href="listing_<?php echo $module; ?>.php" class="breadcrumb-item">Services</a>
                        <span class="breadcrumb-item active"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Create<?php } ?> </span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="p-3 rounded">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Publish</label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="submit" class="btn btn-info my-1 me-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Save<?php } ?> <?php echo $module_caption; ?> and Exit</button>
                        <button type="button" class="btn btn-outline-dark my-1 me-2 nav-link" data-href="listing_<?php echo $module; ?>.php">Exit</button>
                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->

        <div class="content-inner">

            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Service: <span class="text-danger">*</span></label>
                                    <input required type="text" name="service_name" id="service_name" value="<?php echo $service_name; ?>" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Description: </label>
                                    <textarea class="form-control" name="description" id="description" style="field-sizing: content;"><?php echo $description; ?></textarea>
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
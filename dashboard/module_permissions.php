<?php

include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$module             = 'module_permissions';
$module_caption     = 'Module Permission';
$tbl_name             = $tbl_prefix . $module;
$error_message         = '';
$success_message     = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in module_permissions.php', 'WARNING', __FILE__, __LINE__);
    }
}



/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

$module_id = '';
if (isset($_REQUEST['module_id']) && !empty($_REQUEST['module_id']))        $module_id     = e_s__($_REQUEST['module_id']);
if (isset($_POST['module_id']) && !empty($_POST['module_id']))              $module_id     = e_s__($_POST['module_id']);

/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $slug               = e_s__($_POST['slug']);
    $permission_name    = e_s__($_POST['permission_name']);
} else {
    $slug               = '';
    $permission_name    = '';
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

        // } else if (checkDuplicateRow($tbl_name, 'slug', $slug) && $slug != getTableAttr('slug', $tbl_name, $id)) {
        //     $error_message = 'Duplicate Slug. Please enter different.';

    } else if (empty($permission_name)) {
        $error_message = 'Permission is mandatory.';

        // } else if (checkDuplicateRow($tbl_name, 'permission_name', $module) && $module != getTableAttr('permission_name', $tbl_name, $id)) {
        //     $error_message = 'Duplicate Permission. Please enter different.';

    } else {

        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
											slug			        = '" . $slug . "',
											permission_name			= '" . $permission_name . "'
										WHERE id=$id AND module_id = $module_id");
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
} else if ($action == "add_$module" && !empty($module_id)) {

    if (empty($slug)) {
        $error_message = 'Slug is mandatory.';

        // } else if (checkDuplicateRow($tbl_name, 'slug', $slug)) {
        //     $error_message = 'Duplicate Slug. Please enter different.';

    } else if (empty($permission_name)) {
        $error_message = 'Permission is mandatory.';

        // } else if (checkDuplicateRow($tbl_name, 'permission_name', $permission_name)) {
        //     $error_message = 'Duplicate Permission. Please enter different.';

    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(module_id, slug, permission_name) VALUES ('" . $module_id . "', '" . $slug . "', '" . $permission_name . "'); ");

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
    $permission_name    = s__($row['permission_name']);
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">


    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" autocomplete="off" action="<?php echo $module; ?>.php">
        <input type="hidden" name="module_id" id="module_id" value="<?php echo $module_id; ?>" />
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
                        <a href="listing_<?php echo $module; ?>.php" class="breadcrumb-item">Module Permission</a>
                        <span class="breadcrumb-item active"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Create<?php } ?> </span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="p-3 rounded">
                    <div class="form-check form-check-inline form-switch">
                        <label class="form-check-label" for="sc_r_success"><?php echo ''; ?></label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="submit" class="btn btn-info my-1 me-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Save<?php } ?> <?php echo $module_caption; ?> and Exit</button>
                        <button type="button" class="btn btn-danger my-1 me-2 nav-link" data-href="listing_modules.php?action=delete_module_permissions&id=<?php echo $id; ?>&module_id=<?php echo $module_id; ?>"> Delete</button>
                        <button type="button" class="btn btn-outline-primary my-1 me-2 nav-link" data-href="listing_modules.php">Exit</button>
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

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Slug: <span class="text-danger">*</span></label>
                                            <input required type="text" name="slug" id="slug" value="<?php echo $slug; ?>" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Permission Name: <span class="text-danger">*</span></label>
                                            <input required type="text" name="permission_name" id="permission_name" value="<?php echo $permission_name; ?>" class="form-control">
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
<?php include('admin_elements/admin_footer.php'); ?>
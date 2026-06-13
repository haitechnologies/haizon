<?php

declare(strict_types=1);

use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'units';
$module_caption     = 'Unit';
$tbl_name = DB::UNITS;
$error_message         = '';
$success_message     = '';

$crud = $container->get(\App\Controller\SimpleCrudHandler::class);


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
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


if (isset($_POST['publish']))       $publish     = 1;
else $publish = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $unit        = e_s__($_POST['unit']);
} else {
    $unit        = '';
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($unit)) {
        $error_message = 'Unit is mandatory.';
    } else if ($crud->exists($tbl_name, 'unit', $unit, $id) && $unit !== ($crud->findById($tbl_name, $id)['unit'] ?? '')) {
        $error_message = 'Duplicate Unit. Please enter different.';
    } else {
        $updated = $crud->update($tbl_name, ['unit', 'publish'], [$unit, $publish], $id, (int)$session_user_id);
        if ($updated) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($unit)) {
        $error_message = 'Unit is mandatory.';
    } else if ($crud->exists($tbl_name, 'unit', $unit)) {
        $error_message = 'Unit already exists. Please enter a different one.';
    } else {
        $newId = $crud->create($tbl_name, ['unit', 'publish'], [$unit, $publish], (int)$session_user_id);
        if ($newId) {
            $id = (int)$newId;
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
    $row = $crud->findById($tbl_name, $id);
    if ($row !== null) {
        $unit    = s__($row['unit']);
        $publish = s__($row['is_active']);
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
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" <?php if ($publish == '1') { ?>checked="checked" <?php } ?> form="frm<?php echo $module; ?>">
                    <label class="form-check-label" for="publish">Publish</label>
                </div>
            </div>

            <div class="my-1">
                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php } else { ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php } ?>

                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Unit:*</span></label>

                            <div class="col-lg-9">
                                <input required type="text" name="unit" id="unit" value="<?php echo $unit; ?>" class="form-control">
                            </div>
                        </div>

                    </div>

                </div>
            </form>
        </div>


        <?php include('admin_elements/copyright.php'); ?>
    </div>

</div>
<?php include('admin_elements/admin_footer.php'); ?>
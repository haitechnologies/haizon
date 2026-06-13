<?php

declare(strict_types=1);

use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'setup_sources';
$module_caption     = 'Source';
$tbl_name = DB::TAXONOMIES;
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
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in setup_sources.php', 'WARNING', __FILE__, __LINE__);
    }
}


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
    $source_type   = e_s__($_POST['source_type']);
    $source_name   = e_s__($_POST['source_name']);
} else {
    $source_type   = '';
    $source_name   = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($source_type) || $source_type == 'Please select') {
        $error_message = 'Please select Source type.';
    } else if (empty($source_name)) {
        $error_message = 'Source is mandatory.';
    } else {

        $type = ($source_type === 'leads') ? 'lead_source' : 'customer_source';

        if ($crud->exists($tbl_name, 'value', $source_name, $id, ['type' => $type]) && $source_name !== ($crud->findById($tbl_name, $id)['value'] ?? '')) {
            $error_message = 'Duplicate Source. Please enter different.';
        } else {
            $updated = $crud->update($tbl_name, ['value', 'key', 'type', 'is_active'], [$source_name, slugify($source_name), $type, $publish], $id, (int)$session_user_id);
            if ($updated) {
                $success_message = "The $module_caption has been updated successfully.";
                fp__($tbl_name, $id);
                header("Location:listing_$module.php?success_message=$success_message");
            } else {
                $error_message = "The $module_caption could not be updated. Please try again.";
                //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
            }
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($source_type) || $source_type == 'Please select') {
        $error_message = 'Please select Source type.';
    } else if (empty($source_name)) {
        $error_message = 'Source is mandatory.';
    } else {

        $type = ($source_type === 'leads') ? 'lead_source' : 'customer_source';

        if ($crud->exists($tbl_name, 'value', $source_name, null, ['type' => $type])) {
            $error_message = 'Source already exists. Please enter a different one.';
        } else {
            $newId = $crud->create($tbl_name, ['type', 'value', 'key', 'is_active'], [$type, $source_name, slugify($source_name), $publish], (int)$session_user_id);
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
        $typeVal        = s__($row['type']);
        $source_type    = ($typeVal === 'customer_source') ? 'customers' : (($typeVal === 'lead_source') ? 'leads' : $typeVal);
        $source_name    = s__($row['value']);
        $publish        = s__($row['is_active']);
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
        <?php echo csrf_field(); ?>

        <!-- Page header -->


                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Source Type:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="source_type" id="source_type">
                                    <option value='0'>Please select</option>
                                    <option value="customers" <?php if ($action == "edit_$module" && $source_type == 'customers') { ?>selected <?php } else if ($source_type == 'customers') { ?>selected <?php } ?>>Customers</option>
                                    <option value="leads" <?php if ($action == "edit_$module" && $source_type == 'leads') { ?>selected <?php } else if ($source_type == 'leads') { ?>selected <?php } ?>>Leads</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Source:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="source_name" id="source_name" value="<?php echo $source_name; ?>" class="form-control">
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

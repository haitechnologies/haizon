<?php

declare(strict_types=1);


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'designations';
$module_caption     = 'Designation';
$tbl_name = DB::DESIGNATIONS;
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
| SERVICES & DEPENDENCIES
|--------------------------------------------------------------------------
|
*/
use App\Core\Container;
use App\Core\Database;
use App\Service\DesignationService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

$container = Container::getInstance();
$designationService = $container->get(DesignationService::class);

if ($action == "update_$module" || $action == "add_$module") {
    $designation = e_s__($_POST['designation'] ?? '');
    $publish     = isset($_POST['publish']) ? 1 : 0;
} else {
    $designation = '';
    $publish     = 0;
}


/*
|--------------------------------------------------------------------------
|   UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    try {
        $designationService->update((int)$id, $designation, $publish === 1);
        $success_message = "The $module_caption has been updated successfully.";
        fp__($tbl_name, $id);
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    } catch (\Throwable $e) {
        $error_message = "The $module_caption could not be updated. Please try again.";
    }

    /*
|--------------------------------------------------------------------------
|   ADD
|--------------------------------------------------------------------------
|
*/
} elseif ($action == "add_$module" && granted('create', $module_id)) {
    try {
        $newDesg = $designationService->create(
            $designation,
            $activeOrganizationId,
            (int)($_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0)
        );
        $id = $newDesg->id;
        fp__($tbl_name, $id);
        $success_message = "The $module_caption has been saved successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (\Throwable $e) {
        $error_message = "The $module_caption could not be saved. Please try again.";
    }
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
if (!empty($id) && $action !== "update_$module") {
    try {
        $desg = $designationService->getById((int)$id);
        $designation = s__($desg->designation);
        $publish = $desg->isActive ? 1 : 0;
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    }
}

$formTitle = (($action == "edit_$module" || $action == "update_$module") && !empty($id)) ? 'Edit' : 'New';
?>
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php echo $formTitle . ' ' . $module_caption; ?></h5>
                <div class="form-check form-check-inline form-switch ms-2 my-0">
                    <input type="checkbox" class="form-check-input form-check-input-success"
                           name="publish" id="publish" form="frm<?php echo $module; ?>"
                           <?php if ($publish == '1') :
                                ?>checked="checked"<?php
                           endif; ?>>
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

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>"
                  name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php"
                  enctype="multipart/form-data">
                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php } else { ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php } ?>

                <div class="card col-lg-6">

                    <div class="content clearfix">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">
                                <span class="text-danger">Designation:*</span>
                            </label>

                            <div class="col-lg-9">
                                <input required type="text" name="designation" id="designation"
                                       value="<?php echo $designation; ?>" class="form-control">
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
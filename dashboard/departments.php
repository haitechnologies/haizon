<?php
declare(strict_types=1);


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'departments';
$module_caption     = 'Department';
$tbl_name = DB::DEPARTMENTS;
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
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

use App\Core\Container;
use App\Core\Database;
use App\Service\DepartmentService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

$container   = Container::getInstance();
$db          = $container->get(Database::class);
$deptService = $container->get(DepartmentService::class);

if ($action == "update_$module" || $action == "add_$module") {
    $department        = e_s__($_POST['department']);
} else {
    $department        = '';
}

$publish = 1;

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    try {
        $deptService->update((int)$id, $department, true);
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
} else if ($action == "add_$module" && granted('create', $module_id)) {
    try {
        $newDept = $deptService->create($department, $activeOrganizationId, (int)($_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0));
        $id = $newDept->id;
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

if (!empty($id)) {
    try {
        $dept = $deptService->getById((int)$id);
        $department = s__($dept->department);
        $publish = $dept->isActive ? 1 : 0;
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
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
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                <span class="text-muted small">(<?php if ($publish == '1') { ?>Active<?php } else { ?>InActive<?php } ?>)</span>
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

                    <div class="content clearfix">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Department:*</span></label>

                            <div class="col-lg-9">
                                <input required type="text" name="department" id="department" value="<?php echo $department; ?>" class="form-control">
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
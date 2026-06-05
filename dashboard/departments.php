<?php
declare(strict_types=1);

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

use App\Core\Database;
use App\Repository\DepartmentRepository;
use App\Repository\UserRepository;
use App\Service\DepartmentService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

$db = new Database();
$deptRepo = new DepartmentRepository($db);
$userRepo = new UserRepository($db);
$deptService = new DepartmentService($deptRepo, $userRepo);

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
        $publish = $dept->publish ? 1 : 0;
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

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-2">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
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

            </div>


            <?php include('admin_elements/copyright.php'); ?>
        </div>
    </form>

</div>
<?php include('admin_elements/admin_footer.php'); ?>
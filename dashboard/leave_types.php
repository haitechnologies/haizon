<?php
declare(strict_types=1);

use App\Core\DB;
use App\Core\Container;
use App\Service\LeaveTypeService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

include('admin_elements/admin_header.php');

$module = 'leave_types';
$module_caption = 'Leave Type';
$tbl_name = DB::LEAVE_TYPES;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| RESTRICT ACCESS: Only System Admin, Super Admin, and HR can manage leave types
|--------------------------------------------------------------------------
*/
if (!is_SystemAdmin() && !is_SuperAdmin() && is_role() != 'hr') {
    echo 'Permission Denied.';
    exit();
}

$container = Container::getInstance();
/** @var LeaveTypeService $leaveTypeService */
$leaveTypeService = $container->get(LeaveTypeService::class);

$leave_type = '';
$max_per_year = 0;
$paid = 1;

if ($action == "update_$module" || $action == "add_$module") {
    $leave_type = e_s__($_POST['leave_type'] ?? '');
    $max_per_year = !empty($_POST['max_per_year']) ? (int)e_s__($_POST['max_per_year']) : 0;
    $paid = isset($_POST['paid']) ? 1 : 0;
}

if ($action == "update_$module" && !empty($id) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    try {
        $leaveTypeService->update((int)$id, $leave_type, $max_per_year, $paid === 1, $activeOrganizationId);
        $success_message = "The $module_caption has been updated successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    } catch (\Throwable $e) {
        $error_message = "The $module_caption could not be updated.";
    }
} elseif ($action == "add_$module" && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    try {
        $leaveTypeService->create($leave_type, $max_per_year, $paid === 1, $activeOrganizationId);
        $success_message = "The $module_caption has been saved successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (\Throwable $e) {
        $error_message = "The $module_caption could not be saved.";
    }
}

if (!empty($id)) {
    try {
        $typeDto = $leaveTypeService->getById((int)$id, $activeOrganizationId);
        $leave_type = s__($typeDto->leaveType);
        $max_per_year = s__((string)$typeDto->maxPerYear);
        $paid = s__($typeDto->paid ? '1' : '0');
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    }
}
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php if (!empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1">
                <?php if (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr') { ?>
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

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><?php echo $module_caption; ?></h5></div>
                <div class="card-body">
                    <?php if (!empty($error_message)) { ?>
                        <div class="alert alert-danger"> <?php echo $error_message; ?> </div>
                    <?php } ?>

                    <form method="post" id="frm<?php echo $module; ?>">
                        <input type="hidden" name="action" value="<?php echo !empty($id) ? 'update_'.$module : 'add_'.$module; ?>">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Leave Type Name <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="leave_type" class="form-control" value="<?php echo $leave_type; ?>" required placeholder="e.g., Annual Leave, Sick Leave, etc.">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Max Days Per Year</label>
                            <div class="col-lg-9">
                                <input type="number" step="1" name="max_per_year" class="form-control" value="<?php echo $max_per_year; ?>" placeholder="0 = Unlimited">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Paid Leave</label>
                            <div class="col-lg-9">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="paid" class="form-check-input" <?php if ($paid == 1) echo 'checked'; ?>>
                                    <span class="form-check-label">This is a paid leave type</span>
                                </label>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

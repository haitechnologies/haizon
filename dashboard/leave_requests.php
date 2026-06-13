<?php
declare(strict_types=1);

use App\Core\DB;
use App\Core\Container;
use App\Core\Database;
use App\Service\LeaveRequestService;
use App\Service\LeaveTypeService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

include('admin_elements/admin_header.php');

$module = 'leave_requests';
$module_caption = 'Leave Request';
$tbl_name = DB::LEAVE_REQUESTS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$container = Container::getInstance();
/** @var LeaveRequestService $leaveRequestService */
$leaveRequestService = $container->get(LeaveRequestService::class);
/** @var LeaveTypeService $leaveTypeService */
$leaveTypeService = $container->get(LeaveTypeService::class);
/** @var Database $db */
$db = $container->get(Database::class);

$employee_id = 0;
$leave_type_id = 0;
$start_date = '';
$end_date = '';
$total_days = 0.0;
$reason = '';
$status = 'pending';

if ($action == "update_$module" || $action == "add_$module") {
    $employee_id = (int)($_POST['employee_id'] ?? 0);
    $leave_type_id = (int)($_POST['leave_type_id'] ?? 0);
    $start_date = e_s__($_POST['start_date'] ?? '');
    $end_date = e_s__($_POST['end_date'] ?? '');
    $total_days = !empty($_POST['total_days']) ? (float)e_s__($_POST['total_days']) : 0.0;
    $reason = e_s__($_POST['reason'] ?? '');
    $status = e_s__($_POST['status'] ?? 'pending');
}

if ($action == "update_$module" && !empty($id) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    try {
        $reqData = [
            'employee_id' => $employee_id,
            'leave_type_id' => $leave_type_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_days' => $total_days,
            'reason' => $reason,
            'status' => $status,
        ];
        if ($status === 'approved') {
            $reqData['approved_by'] = (int)($_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0);
        }
        $leaveRequestService->update((int)$id, $reqData, $activeOrganizationId);
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
        $reqData = [
            'employee_id' => $employee_id,
            'leave_type_id' => $leave_type_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_days' => $total_days,
            'reason' => $reason,
            'status' => $status,
        ];
        if ($status === 'approved') {
            $reqData['approved_by'] = (int)($_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0);
        }
        $leaveRequestService->create($reqData, $activeOrganizationId);
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
        $reqDto = $leaveRequestService->getById((int)$id, $activeOrganizationId);
        $employee_id = $reqDto->employeeId;
        $leave_type_id = $reqDto->leaveTypeId;
        $start_date = s__($reqDto->startDate);
        $end_date = s__($reqDto->endDate);
        $total_days = $reqDto->totalDays;
        $reason = s__($reqDto->reason ?? '');
        $status = s__($reqDto->status);
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
                            <label class="col-lg-3 col-form-label">Employee <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <select name="employee_id" class="form-select" required>
                                    <option value="0">Select Employee</option>
                                    <?php
                                    // Show all users regardless of publish status, exclude system/super admins
                                    $res = $db->fetchAll("SELECT u.id, u.full_name, r.role_name FROM `" . DB::USERS . "` u LEFT JOIN `" . DB::ROLES . "` r ON u.role_id = r.id WHERE u.role_id NOT IN (1, 2) ORDER BY u.full_name");
                                    foreach ($res as $u) {
                                    ?>
                                        <option value="<?php echo $u['id']; ?>" <?php if ($employee_id == $u['id']) echo 'selected'; ?>>
                                            <?php echo $u['full_name'] . (!empty($u['role_name']) ? ' (' . ucfirst($u['role_name']) . ')' : ''); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Leave Type <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <select name="leave_type_id" class="form-select" required>
                                    <option value="0">Select</option>
                                    <?php
                                    $typesList = $leaveTypeService->list($activeOrganizationId);
                                    foreach ($typesList as $t) {
                                    ?>
                                        <option value="<?php echo $t->id; ?>" <?php if ($leave_type_id == $t->id) echo 'selected'; ?>><?php echo s__($t->leaveType); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Start Date</label>
                            <div class="col-lg-9">
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">End Date</label>
                            <div class="col-lg-9">
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Total Days</label>
                            <div class="col-lg-9">
                                <input type="number" step="0.5" name="total_days" class="form-control" value="<?php echo $total_days; ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Reason</label>
                            <div class="col-lg-9">
                                <textarea name="reason" class="form-control" rows="3"><?php echo $reason; ?></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-select">
                                    <option value="pending" <?php if ($status == 'pending') echo 'selected'; ?>>Pending</option>
                                    <option value="approved" <?php if ($status == 'approved') echo 'selected'; ?>>Approved</option>
                                    <option value="rejected" <?php if ($status == 'rejected') echo 'selected'; ?>>Rejected</option>
                                </select>
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

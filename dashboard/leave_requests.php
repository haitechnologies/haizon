<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'leave_requests';
$module_caption = 'Leave Request';
$tbl_name = DB::LEAVE_REQUESTS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$employee_id = 0;
$leave_type_id = 0;
$start_date = '';
$end_date = '';
$total_days = 0;
$reason = '';
$status = 'pending';

if ($action == "update_$module" || $action == "add_$module") {
    $employee_id = e_s__($_POST['employee_id'] ?? 0);
    $leave_type_id = e_s__($_POST['leave_type_id'] ?? 0);
    $start_date = e_s__($_POST['start_date'] ?? '');
    $end_date = e_s__($_POST['end_date'] ?? '');
    $total_days = !empty($_POST['total_days']) ? (float)e_s__($_POST['total_days']) : 0;
    $reason = e_s__($_POST['reason'] ?? '');
    $status = e_s__($_POST['status'] ?? 'pending');
}

if ($action == "update_$module" && !empty($id) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($employee_id) || empty($leave_type_id) || empty($start_date) || empty($end_date)) {
        $error_message = 'Employee, Leave Type, Start and End dates are mandatory.';
    } else {
        $update_row = $mysqli->query("UPDATE `$tbl_name` SET employee_id='$employee_id', leave_type_id='$leave_type_id', start_date='$start_date', end_date='$end_date', total_days='$total_days', reason='$reason', status='$status' WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated.";
        }
    }
} elseif ($action == "add_$module" && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($employee_id) || empty($leave_type_id) || empty($start_date) || empty($end_date)) {
        $error_message = 'Employee, Leave Type, Start and End dates are mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(employee_id, leave_type_id, start_date, end_date, total_days, reason, status) VALUES ('$employee_id', '$leave_type_id', '$start_date', '$end_date', '$total_days', '$reason', '$status')");
        if ($insert_row) {
            $success_message = "The $module_caption has been saved successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be saved.";
        }
    }
}

if (!empty($id)) {
    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();
    $employee_id = s__($row['employee_id']);
    $leave_type_id = s__($row['leave_type_id']);
    $start_date = s__($row['start_date']);
    $end_date = s__($row['end_date']);
    $total_days = s__($row['total_days']);
    $reason = s__($row['reason']);
    $status = s__($row['status']);
}
?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>
    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><?php echo $module_caption; ?></h5></div>
                <div class="card-body">
                    <?php if (!empty($error_message)) { ?>
                        <div class="alert alert-danger"> <?php echo $error_message; ?> </div>
                    <?php } ?>

                    <form method="post">
                        <input type="hidden" name="action" value="<?php echo !empty($id) ? 'update_'.$module : 'add_'.$module; ?>">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Employee <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <select name="employee_id" class="form-select" required>
                                    <option value="0">Select Employee</option>
                                    <?php
                                    // Show all users regardless of publish status, exclude system/super admins
                                    $res = $mysqli->query("SELECT u.id, u.full_name, r.role_name FROM `" . DB::USERS . "` u LEFT JOIN `" . DB::ROLES . "` r ON u.role_id = r.id WHERE u.role_id NOT IN (1, 2) ORDER BY u.full_name");
                                    while ($u = $res->fetch_array()) {
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
                                    $res = $mysqli->query("SELECT id, leave_type FROM `" . DB::LEAVE_TYPES . "` ORDER BY leave_type");
                                    while ($t = $res->fetch_array()) {
                                    ?>
                                        <option value="<?php echo $t['id']; ?>" <?php if ($leave_type_id == $t['id']) echo 'selected'; ?>><?php echo $t['leave_type']; ?></option>
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

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a href="listing_<?php echo $module; ?>.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

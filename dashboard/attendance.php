<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'attendance';
$module_caption = 'Attendance';
$tbl_name = DB::ATTENDANCE;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$employee_id = 0;
$work_date = '';
$check_in = '';
$check_out = '';
$total_hours = 0;
$status = 'present';

if ($action == "update_$module" || $action == "add_$module") {
    $employee_id = e_s__($_POST['employee_id'] ?? 0);
    $work_date = e_s__($_POST['work_date'] ?? '');
    $check_in = e_s__($_POST['check_in'] ?? '');
    $check_out = e_s__($_POST['check_out'] ?? '');
    $total_hours = !empty($_POST['total_hours']) ? (float)e_s__($_POST['total_hours']) : 0;
    $status = e_s__($_POST['status'] ?? 'present');
}

if ($action == "update_$module" && !empty($id) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($employee_id) || empty($work_date)) {
        $error_message = 'Employee and Date are mandatory.';
    } else {
        $update_row = $mysqli->query("UPDATE `$tbl_name` SET employee_id='$employee_id', work_date='$work_date', check_in='$check_in', check_out='$check_out', total_hours='$total_hours', status='$status' WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated.";
        }
    }
} elseif ($action == "add_$module" && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($employee_id) || empty($work_date)) {
        $error_message = 'Employee and Date are mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(employee_id, work_date, check_in, check_out, total_hours, status) VALUES ('$employee_id', '$work_date', '$check_in', '$check_out', '$total_hours', '$status')");
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
    $work_date = s__($row['work_date']);
    $check_in = s__($row['check_in']);
    $check_out = s__($row['check_out']);
    $total_hours = s__($row['total_hours']);
    $status = s__($row['status']);
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
                            <label class="col-lg-3 col-form-label">Date</label>
                            <div class="col-lg-9">
                                <input type="date" name="work_date" class="form-control" value="<?php echo $work_date; ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Check In</label>
                            <div class="col-lg-9">
                                <input type="datetime-local" name="check_in" class="form-control" value="<?php echo $check_in; ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Check Out</label>
                            <div class="col-lg-9">
                                <input type="datetime-local" name="check_out" class="form-control" value="<?php echo $check_out; ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Total Hours</label>
                            <div class="col-lg-9">
                                <input type="number" step="0.01" name="total_hours" class="form-control" value="<?php echo $total_hours; ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-select">
                                    <option value="present" <?php if ($status == 'present') echo 'selected'; ?>>Present</option>
                                    <option value="absent" <?php if ($status == 'absent') echo 'selected'; ?>>Absent</option>
                                    <option value="late" <?php if ($status == 'late') echo 'selected'; ?>>Late</option>
                                    <option value="leave" <?php if ($status == 'leave') echo 'selected'; ?>>Leave</option>
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

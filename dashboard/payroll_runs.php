<?php
$module = 'payroll_runs';
$module_caption = 'Payroll Run';
$tbl_name = DB::PAYROLL_RUNS;
$error_message = '';
$success_message = '';

include('admin_elements/admin_header.php');
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$period_start = '';
$period_end = '';
$status = 'draft';

if ($action == "update_$module" || $action == "add_$module") {
    $period_start = e_s__($_POST['period_start'] ?? '');
    $period_end = e_s__($_POST['period_end'] ?? '');
    $status = e_s__($_POST['status'] ?? 'draft');
}

if ($action == "update_$module" && !empty($id) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($period_start) || empty($period_end)) {
        $error_message = 'Period start and end are mandatory.';
    } else {
        $update_row = $mysqli->query("UPDATE `$tbl_name` SET period_start='$period_start', period_end='$period_end', status='$status' WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated.";
        }
    }
} elseif ($action == "add_$module" && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($period_start) || empty($period_end)) {
        $error_message = 'Period start and end are mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(period_start, period_end, status, created_by) VALUES ('$period_start', '$period_end', '$status', '$session_user_id')");
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
    $period_start = s__($row['period_start']);
    $period_end = s__($row['period_end']);
    $status = s__($row['status']);
}
?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>
    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php if (!empty($id)) { ?>
                            <i class="ph-pencil me-2"></i>Edit Payroll Run
                        <?php } else { ?>
                            <i class="ph-plus me-2"></i>Create New Payroll Run
                        <?php } ?>
                    </h5>
                    <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">
                        <i class="ph-list me-2"></i>View All Runs
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)) { ?>
                        <div class="alert alert-danger"> <?php echo $error_message; ?> </div>
                    <?php } ?>

                    <form method="post">
                        <?php if (!empty($id)) { ?>
                            <input type="hidden" name="action" value="update_<?php echo $module; ?>">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <?php } else { ?>
                            <input type="hidden" name="action" value="add_<?php echo $module; ?>">
                        <?php } ?>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Period Start <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="date" name="period_start" class="form-control" value="<?php echo $period_start; ?>" required>
                                <small class="form-text text-muted">Start date of the payroll period</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Period End <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="date" name="period_end" class="form-control" value="<?php echo $period_end; ?>" required>
                                <small class="form-text text-muted">End date of the payroll period</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Status</label>
                            <div class="col-lg-9">
                                <select name="status" class="form-select">
                                    <option value="draft" <?php if ($status == 'draft') echo 'selected'; ?>>Draft</option>
                                    <option value="approved" <?php if ($status == 'approved') echo 'selected'; ?>>Approved</option>
                                    <option value="posted" <?php if ($status == 'posted') echo 'selected'; ?>>Posted</option>
                                </select>
                                <small class="form-text text-muted">Payroll run status (Draft → Approved → Posted)</small>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ph-floppy-disk me-2"></i>Save Payroll Run
                            </button>
                            <a href="listing_<?php echo $module; ?>.php" class="btn btn-light">
                                <i class="ph-x me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

<?php
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

$leave_type = '';
$max_per_year = 0;
$paid = 1;

if ($action == "update_$module" || $action == "add_$module") {
    $leave_type = e_s__($_POST['leave_type'] ?? '');
    $max_per_year = !empty($_POST['max_per_year']) ? (int)e_s__($_POST['max_per_year']) : 0;
    $paid = isset($_POST['paid']) ? 1 : 0;
}

if ($action == "update_$module" && !empty($id) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($leave_type)) {
        $error_message = 'Leave Type name is mandatory.';
    } else {
        $update_row = $mysqli->query("UPDATE `$tbl_name` SET leave_type='$leave_type', max_per_year='$max_per_year', paid='$paid' WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated.";
        }
    }
} elseif ($action == "add_$module" && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($leave_type)) {
        $error_message = 'Leave Type name is mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(leave_type, max_per_year, paid) VALUES ('$leave_type', '$max_per_year', '$paid')");
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
    $leave_type = s__($row['leave_type']);
    $max_per_year = s__($row['max_per_year']);
    $paid = s__($row['paid']);
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

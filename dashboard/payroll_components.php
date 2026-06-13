<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'payroll_components';
$module_caption = 'Payroll Component';
$tbl_name = DB::PAYROLL_COMPONENTS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$component_name = '';
$component_type = 'earning';
$taxable = 1;
$account_id = '';
$is_active = 1;

if ($action == "update_$module" || $action == "add_$module") {
    $component_name = e_s__($_POST['component_name'] ?? '');
    $component_type = e_s__($_POST['component_type'] ?? 'earning');
    $taxable = isset($_POST['taxable']) ? 1 : 0;
    $account_id = e_s__($_POST['account_id'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
}

if ($action == "update_$module" && !empty($id) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($component_name)) {
        $error_message = 'Component name is mandatory.';
    } else {
        $update_row = $mysqli->query("UPDATE `$tbl_name` SET component_name='$component_name', component_type='$component_type', taxable='$taxable', account_id='$account_id', is_active='$is_active' WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated.";
        }
    }
} elseif ($action == "add_$module" && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'hr')) {
    if (empty($component_name)) {
        $error_message = 'Component name is mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(component_name, component_type, taxable, account_id, is_active) VALUES ('$component_name', '$component_type', '$taxable', '$account_id', '$is_active')");
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
    $component_name = s__($row['component_name']);
    $component_type = s__($row['component_type']);
    $taxable = (int)$row['taxable'];
    $account_id = s__($row['account_id']);
    $is_active = (int)$row['is_active'];
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
                            <label class="col-lg-3 col-form-label">Component Name</label>
                            <div class="col-lg-9">
                                <input type="text" name="component_name" class="form-control" value="<?php echo $component_name; ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Type</label>
                            <div class="col-lg-9">
                                <select name="component_type" class="form-select">
                                    <option value="earning" <?php if ($component_type == 'earning') echo 'selected'; ?>>Earning</option>
                                    <option value="deduction" <?php if ($component_type == 'deduction') echo 'selected'; ?>>Deduction</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label">Account ID</label>
                            <div class="col-lg-9">
                                <input type="text" name="account_id" class="form-control" value="<?php echo $account_id; ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-lg-3"></div>
                            <div class="col-lg-9">
                                <label class="form-check">
                                    <input type="checkbox" name="taxable" class="form-check-input" <?php if ($taxable == 1) echo 'checked'; ?> />
                                    <span class="form-check-label">Taxable</span>
                                </label>
                                <label class="form-check mt-2">
                                    <input type="checkbox" name="is_active" class="form-check-input" <?php if ($is_active == 1) echo 'checked'; ?> />
                                    <span class="form-check-label">Active</span>
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

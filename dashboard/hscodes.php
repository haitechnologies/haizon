<?php

include('admin_elements/admin_header.php');

$module             = 'hscodes';
$module_caption     = 'HS Code';
$error_message      = '';
$success_message    = '';

$action             = isset($_GET['action']) ? e_s__($_GET['action']) : '';
$id                 = isset($_GET['id']) ? intval($_GET['id']) : '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
    }
}

if (isset($_POST['is_active']))       $is_active = 1;
else                                   $is_active = 0;

if ($action == "update_$module" || $action == "add_$module") {
    $code               = e_s__($_POST['code'] ?? '');
    $old_code           = e_s__($_POST['old_code'] ?? '');
    $level              = intval($_POST['level'] ?? 0);
    $duty_rate          = e_s__($_POST['duty_rate'] ?? '');
} else {
    $code               = '';
    $old_code           = '';
    $level              = '';
    $duty_rate          = '';
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    if (empty($code)) {
        $error_message = 'HS Code is mandatory.';
    } else {
        $update_row = $mysqli->query("
            UPDATE `" . DB::HS_CODES . "` SET
                code            = '" . $code . "',
                old_code        = '" . $old_code . "',
                level           = '" . $level . "',
                duty_rate       = '" . $duty_rate . "',
                is_active       = '" . $is_active . "',
                updated_at      = NOW()
            WHERE id=$id");
        
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "Could not update. Please try again.";
        }
    }

} else if ($action == "add_$module" && granted('create', $module_id)) {
    if (empty($code)) {
        $error_message = 'HS Code is mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `" . DB::HS_CODES . "`
            (code, old_code, level, duty_rate, is_active) 
            VALUES 
            ('" . $code . "', '" . $old_code . "', '" . $level . "', '" . $duty_rate . "', '" . $is_active . "')");
        
        if ($insert_row) {
            $success_message = "The $module_caption has been saved successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "Could not save. Please try again.";
        }
    }
}

if (!empty($id)) {
    $result = $mysqli->query("SELECT * FROM `" . DB::HS_CODES . "` WHERE id=$id");
    $row = $result ? $result->fetch_array() : null;

    if ($row) {
        $code               = s__($row['code'] ?? '');
        $old_code           = s__($row['old_code'] ?? '');
        $level              = s__($row['level'] ?? '');
        $duty_rate          = s__($row['duty_rate'] ?? '');
        $is_active          = s__($row['is_active'] ?? '');
    }
}

?>

<div class="content-wrapper">
    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php echo (($action == "edit_$module" || $action == "update_$module") && !empty($id)) ? 'Edit' : 'New'; ?> <?php echo $module_caption; ?></h5>
                    </div>
                </div>

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch me-3">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto">
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

        <div class="content-inner">
            <div class="content">
                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="card">
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">HS Code:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="code" id="code" placeholder="e.g., 6204.62.20" value="<?php echo $code; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Old Code:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="old_code" id="old_code" placeholder="Previous version code" value="<?php echo $old_code; ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Classification Level:</label>
                            <div class="col-lg-9">
                                <select class="form-select" name="level" id="level">
                                    <option value="0" <?php if ($level == 0) echo 'selected'; ?>>-- Select Level --</option>
                                    <option value="1" <?php if ($level == 1) echo 'selected'; ?>>Chapter (2-digit)</option>
                                    <option value="2" <?php if ($level == 2) echo 'selected'; ?>>Heading (4-digit)</option>
                                    <option value="3" <?php if ($level == 3) echo 'selected'; ?>>Sub-heading (6-digit)</option>
                                    <option value="4" <?php if ($level == 4) echo 'selected'; ?>>Item (8-digit)</option>
                                    <option value="5" <?php if ($level == 5) echo 'selected'; ?>>Sub-item (10-digit)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Duty Rate (%):</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="duty_rate" id="duty_rate" placeholder="e.g., 5%, 0%" value="<?php echo $duty_rate; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

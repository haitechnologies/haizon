<?php

include('admin_elements/admin_header.php');

$module             = 'alerts';
$module_caption     = 'Alert';
$error_message      = '';
$success_message    = '';

// Get action and ID from query string
$action             = isset($_GET['action']) ? e_s__($_GET['action']) : '';
$id                 = isset($_GET['id']) ? intval($_GET['id']) : '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in ' . __FILE__, 'WARNING', __FILE__, __LINE__);
    }
}

// Handle is_active checkbox
if (isset($_POST['is_active']))       $is_active = 1;
else                                   $is_active = 0;

/*
|--------------------------------------------------------------------------
| GET ALL VARIABLES FOR ADD/UPDATE
|--------------------------------------------------------------------------
*/
if ($action == "update_$module" || $action == "add_$module") {
    $alert_name         = e_s__($_POST['alert_name'] ?? '');
    $alert_message      = e_s__($_POST['alert_message'] ?? '');
    $alert_type         = e_s__($_POST['alert_type'] ?? '');
} else {
    $alert_name         = '';
    $alert_message      = '';
    $alert_type         = '';
}

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    if (empty($alert_name)) {
        $error_message = 'Alert name is mandatory.';
    } else {
        $update_row = $mysqli->query("
            UPDATE `" . DB::ALERTS . "` SET
                alert_name      = '" . $alert_name . "',
                alert_message   = '" . $alert_message . "',
                alert_type      = '" . $alert_type . "',
                is_active       = '" . $is_active . "',
                updated_by      = '" . $session_user_id . "',
                updated_at      = NOW()
            WHERE id=$id");
        
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__(DB::ALERTS, $id);
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
        }
    }

/*
|--------------------------------------------------------------------------
| ADD
|--------------------------------------------------------------------------
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {
    if (empty($alert_name)) {
        $error_message = 'Alert name is mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `" . DB::ALERTS . "`
            (alert_name, alert_message, alert_type, is_active, created_by) 
            VALUES 
            ('" . $alert_name . "', '" . $alert_message . "', '" . $alert_type . "', '" . $is_active . "', '" . $session_user_id . "')");
        
        if ($insert_row) {
            $id = $mysqli->insert_id;
            fp__(DB::ALERTS, $id);
            $success_message = "The $module_caption has been saved successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
*/
if (!empty($id)) {
    $result = $mysqli->query("SELECT * FROM `" . DB::ALERTS . "` WHERE id=$id");
    $row = $result ? $result->fetch_array() : null;

    if ($row) {
        $alert_name         = s__($row['alert_name'] ?? '');
        $alert_message      = s__($row['alert_message'] ?? '');
        $alert_type         = s__($row['alert_type'] ?? '');
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

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch me-3">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
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

        <!-- Inner content -->
        <div class="content-inner">
            <div class="content">
                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="card">
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Alert Name:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="alert_name" id="alert_name" placeholder="Enter alert name" value="<?php echo $alert_name; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Alert Type:</label>
                            <div class="col-lg-9">
                                <select class="form-select" name="alert_type" id="alert_type">
                                    <option value="">-- Select Type --</option>
                                    <option value="info" <?php if ($alert_type == 'info') echo 'selected'; ?>>Info</option>
                                    <option value="warning" <?php if ($alert_type == 'warning') echo 'selected'; ?>>Warning</option>
                                    <option value="error" <?php if ($alert_type == 'error') echo 'selected'; ?>>Error</option>
                                    <option value="success" <?php if ($alert_type == 'success') echo 'selected'; ?>>Success</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Alert Message:</label>
                            <div class="col-lg-9">
                                <textarea class="form-control" name="alert_message" id="alert_message" placeholder="Enter alert message" rows="4"><?php echo $alert_message; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

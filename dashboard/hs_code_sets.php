<?php

include('admin_elements/admin_header.php');

$module             = 'hs_code_sets';
$module_caption     = 'HS Code Set';
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
    $country_code       = e_s__($_POST['country_code'] ?? '');
    $version_label      = e_s__($_POST['version_label'] ?? '');
    $effective_from     = e_s__($_POST['effective_from'] ?? '');
    $effective_to       = e_s__($_POST['effective_to'] ?? '');
} else {
    $country_code       = '';
    $version_label      = '';
    $effective_from     = '';
    $effective_to       = '';
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    if (empty($country_code) || empty($version_label)) {
        $error_message = 'Country code and version label are mandatory.';
    } else {
        $update_row = $mysqli->query("
            UPDATE `" . DB::HS_CODE_SETS . "` SET
                country_code    = '" . $country_code . "',
                version_label   = '" . $version_label . "',
                effective_from  = '" . $effective_from . "',
                effective_to    = '" . $effective_to . "',
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
    if (empty($country_code) || empty($version_label)) {
        $error_message = 'Country code and version label are mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `" . DB::HS_CODE_SETS . "`
            (country_code, version_label, effective_from, effective_to, is_active) 
            VALUES 
            ('" . $country_code . "', '" . $version_label . "', '" . $effective_from . "', '" . $effective_to . "', '" . $is_active . "')");
        
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
    $result = $mysqli->query("SELECT * FROM `" . DB::HS_CODE_SETS . "` WHERE id=$id");
    $row = $result ? $result->fetch_array() : null;

    if ($row) {
        $country_code       = s__($row['country_code'] ?? '');
        $version_label      = s__($row['version_label'] ?? '');
        $effective_from     = s__($row['effective_from'] ?? '');
        $effective_to       = s__($row['effective_to'] ?? '');
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
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Country Code:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="country_code" id="country_code" placeholder="e.g., AE, US, IN" value="<?php echo $country_code; ?>" maxlength="3" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Version Label:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="version_label" id="version_label" placeholder="e.g., 2023, 2024" value="<?php echo $version_label; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Effective From:</label>
                            <div class="col-lg-9">
                                <input type="date" class="form-control" name="effective_from" id="effective_from" value="<?php echo $effective_from; ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Effective To:</label>
                            <div class="col-lg-9">
                                <input type="date" class="form-control" name="effective_to" id="effective_to" value="<?php echo $effective_to; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

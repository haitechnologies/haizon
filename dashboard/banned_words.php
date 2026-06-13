<?php
include('admin_elements/admin_header.php');

$module = 'banned_words';
$module_caption = 'Banned Word';
$tbl_name = $tbl_prefix . $module;
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in banned_words.php', 'WARNING', __FILE__, __LINE__);
    }
}

if (isset($_POST['publish'])) {
    $is_active = 1;
} else {
    $is_active = 0;
}

if ($action == "update_$module" || $action == "add_$module") {
    $banned_word = e_s__($_POST['banned_word']);
} else {
    $banned_word = '';
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($banned_word)) {
        $error_message = 'Banned word is mandatory.';
    } else {
        $update_row = $mysqli->query("UPDATE `$tbl_name` SET
            banned_word = '" . $banned_word . "',
            is_active = '" . $is_active . "',
            updated_by = '" . $session_user_id . "'
            WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
        }
    }

} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($banned_word)) {
        $error_message = 'Banned word is mandatory.';
    } else {
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(banned_word, is_active, created_by) VALUES ('" . $banned_word . "', '" . $is_active . "', '" . $session_user_id . "');");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            fp__($tbl_name, $id);
            $success_message = "The $module_caption has been saved successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
        }
    }
}

if (!empty($id)) {
    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $banned_word = s__($row['banned_word']);
    $is_active = s__($row['is_active']);
}
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?> form="frm<?php echo $module; ?>">
                    <label class="form-check-label" for="is_active">Publish</label>
                </div>
            </div>

            <div class="my-1">
                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
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

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php } else { ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php } ?>
                <?php echo csrf_field(); ?>

                <div class="card col-lg-6">
                    <div class="card-body">
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Banned Word:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="banned_word" id="banned_word" value="<?php echo $banned_word; ?>" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>

</div>
<?php include('admin_elements/admin_footer.php'); ?>

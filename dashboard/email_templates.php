<?php

include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$module = 'email_templates';
$module_caption = 'Email Template';
$tbl_name = $tbl_prefix . $module;
$error_message = '';
$success_message = '';

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
        log_error('CSRF token validation failed in email_templates.php', 'WARNING', __FILE__, __LINE__);
    }
}

if ($action == "update_$module" || $action == "add_$module") {
    $name = e_s__($_POST['name']);
    $subject_default = e_s__($_POST['subject_default']);
    $html_body = $_POST['html_body'] ?? '';
    $text_body = e_s__($_POST['text_body']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $is_system = isset($_POST['is_system']) ? 1 : 0;
} else {
    $name = '';
    $subject_default = '';
    $html_body = '';
    $text_body = '';
    $is_default = 0;
    $is_system = 0;
}

if ($action == "delete_$module" && !empty($id) && granted('delete', $module_id)) {
    $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");

    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    if (empty($name)) {
        $error_message = 'Template name is mandatory.';
    } else {
        if ($is_default == 1) {
            $mysqli->query("UPDATE `$tbl_name` SET is_default = '0'");
        }

        $update_row = $mysqli->query(
            "UPDATE `$tbl_name` SET
                name = '" . $name . "',
                subject_default = '" . $subject_default . "',
                html_body = '" . $mysqli->real_escape_string($html_body) . "',
                text_body = '" . $text_body . "',
                is_default = '" . $is_default . "',
                is_system = '" . $is_system . "',
                updated_by = '" . $session_user_id . "',
                updated_at = CURRENT_TIMESTAMP
            WHERE id=$id"
        );

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
        }
    }
} else if ($action == "add_$module" && granted('create', $module_id)) {
    if (empty($name)) {
        $error_message = 'Template name is mandatory.';
    } else {
        if ($is_default == 1) {
            $mysqli->query("UPDATE `$tbl_name` SET is_default = '0'");
        }

        $insert_row = $mysqli->query(
            "INSERT INTO `$tbl_name`(name, subject_default, html_body, text_body, is_default, is_system, created_by, updated_by)
            VALUES ('" . $name . "', '" . $subject_default . "', '" . $mysqli->real_escape_string($html_body) . "', '" . $text_body . "', '" . $is_default . "', '" . $is_system . "', '" . $session_user_id . "', '" . $session_user_id . "');"
        );

        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
        }
    }
}

if ($action == "edit_$module" && !empty($id)) {
    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = ($result && $result->num_rows > 0) ? $result->fetch_array(MYSQLI_ASSOC) : [];

    $name = s__((string)($row['name'] ?? ''));
    $subject_default = s__((string)($row['subject_default'] ?? ''));
    $html_body = s__((string)($row['html_body'] ?? ''));
    $text_body = s__((string)($row['text_body'] ?? ''));
    $is_default = s__((string)($row['is_default'] ?? '0'));
    $is_system = s__((string)($row['is_system'] ?? '0'));
}

?>
<div class="content-wrapper">
	<?php if (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar')): ?>
		<?php renderEmailQuickbar($visibleEmailLinks, $current_page); ?>
	<?php endif; ?>

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
        <?php if ($action == "edit_$module" || $action == "update_$module") { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="d-flex">
                    <div class="breadcrumb py-2">
                        <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                        <a href="index.php" class="breadcrumb-item">Home</a>
                        <a href="listing_<?php echo $module; ?>.php" class="breadcrumb-item">Email Templates</a>
                        <span class="breadcrumb-item active"><?php if ($action == "edit_$module" || $action == "update_$module") { ?>Update<?php } else { ?>Create<?php } ?></span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="submit" class="btn btn-info my-1 me-2"><?php if ($action == "edit_$module" || $action == "update_$module") { ?>Update<?php } else { ?>Save<?php } ?> <?php echo $module_caption; ?></button>
                        <button type="button" class="btn btn-outline-secondary my-1 ms-auto d-flex align-items-center gap-1" onclick="window.location.href='listing_<?php echo $module; ?>.php'" title="Back to Email Templates">
                            <i class="ph-arrow-left"></i> Exit
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->

        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Template Name: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="name" id="name" value="<?php echo $name; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Subject:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="subject_default" id="subject_default" value="<?php echo $subject_default; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">HTML Body:</label>
                                    <div class="col-lg-9">
                                        <textarea name="html_body" id="html_body" class="form-control" rows="10"><?php echo $html_body; ?></textarea>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Text Body:</label>
                                    <div class="col-lg-9">
                                        <textarea name="text_body" id="text_body" class="form-control" rows="10"><?php echo $text_body; ?></textarea>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label"></label>
                                    <div class="col-lg-9">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="is_default" id="is_default" <?php if ($is_default == 1) { ?>checked<?php } ?>>
                                            <label class="form-check-label" for="is_default">Set as Default Template</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label"></label>
                                    <div class="col-lg-9">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="is_system" id="is_system" <?php if ($is_system == 1) { ?>checked<?php } ?> disabled>
                                            <label class="form-check-label" for="is_system">System Template</label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>

</div>

<?php include('admin_elements/admin_footer.php'); ?>


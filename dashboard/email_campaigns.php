<?php

include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$module = 'email_campaigns';
$module_caption = 'Email Campaign';
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
        log_error('CSRF token validation failed in email_campaigns.php', 'WARNING', __FILE__, __LINE__);
    }
}

if ($action == "update_$module" || $action == "add_$module") {
    $name = e_s__($_POST['name']);
    $status = e_s__($_POST['status']);
    $schedule_at = e_s__($_POST['schedule_at']);
    $template_id = e_s__($_POST['template_id']);
    $subject = e_s__($_POST['subject']);
    $from_name = e_s__($_POST['from_name']);
    $from_email = e_s__($_POST['from_email']);
    $reply_to = e_s__($_POST['reply_to']);
    $segment_id = e_s__($_POST['segment_id']);
} else {
    $name = '';
    $status = 'draft';
    $schedule_at = '';
    $template_id = '';
    $subject = '';
    $from_name = '';
    $from_email = '';
    $reply_to = '';
    $segment_id = '';
}

if ($action == "delete_$module" && !empty($id) && granted('delete', $module_id)) {
    $mysqli->query("DELETE FROM `" . DB::EMAIL_CAMPAIGNS . "` WHERE id=$id");

    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    if (empty($name)) {
        $error_message = 'Campaign name is mandatory.';
    } else {
        $schedule_at_sql = !empty($schedule_at) ? "'" . $schedule_at . "'" : 'NULL';
        $template_id_sql = !empty($template_id) ? (int)$template_id : 'NULL';
        $segment_id_sql = !empty($segment_id) ? (int)$segment_id : 'NULL';

        $update_row = $mysqli->query(
            "UPDATE `$tbl_name` SET
                name = '" . $name . "',
                status = '" . $status . "',
                schedule_at = $schedule_at_sql,
                template_id = $template_id_sql,
                subject = '" . $subject . "',
                from_name = '" . $from_name . "',
                from_email = '" . $from_email . "',
                reply_to = '" . $reply_to . "',
                segment_id = $segment_id_sql,
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
        $error_message = 'Campaign name is mandatory.';
    } else {
        $schedule_at_sql = !empty($schedule_at) ? "'" . $schedule_at . "'" : 'NULL';
        $template_id_sql = !empty($template_id) ? (int)$template_id : 'NULL';
        $segment_id_sql = !empty($segment_id) ? (int)$segment_id : 'NULL';

        $insert_row = $mysqli->query(
            "INSERT INTO `$tbl_name`(name, status, schedule_at, template_id, subject, from_name, from_email, reply_to, segment_id, created_by, updated_by)
            VALUES ('" . $name . "', '" . $status . "', $schedule_at_sql, $template_id_sql, '" . $subject . "', '" . $from_name . "', '" . $from_email . "', '" . $reply_to . "', $segment_id_sql, '" . $session_user_id . "', '" . $session_user_id . "');"
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
    $result = $mysqli->query("SELECT * FROM `" . DB::EMAIL_CAMPAIGNS . "` WHERE id=$id");
    $row = ($result && $result->num_rows > 0) ? $result->fetch_array(MYSQLI_ASSOC) : [];

    $name = s__((string)($row['name'] ?? ''));
    $status = s__((string)($row['status'] ?? 'draft'));
    $schedule_at = s__((string)($row['schedule_at'] ?? ''));
    $template_id = s__((string)($row['template_id'] ?? ''));
    $subject = s__((string)($row['subject'] ?? ''));
    $from_name = s__((string)($row['from_name'] ?? ''));
    $from_email = s__((string)($row['from_email'] ?? ''));
    $reply_to = s__((string)($row['reply_to'] ?? ''));
    $segment_id = s__((string)($row['segment_id'] ?? ''));
}

$templates_result = $mysqli->query("SELECT id, name FROM `" . tbl_email_templates . "` ORDER BY id DESC");
$targets_result = $mysqli->query("SELECT id, name FROM `" . tbl_email_targets . "` ORDER BY id DESC");

?>
<div class="content-wrapper">
	<?php if (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar')): ?>
		<?php renderEmailQuickbar($visibleEmailLinks, $current_page); ?>
	<?php endif; ?>

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off">
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
                        <a href="listing_<?php echo $module; ?>.php" class="breadcrumb-item">Email Marketing</a>
                        <span class="breadcrumb-item active"><?php if ($action == "edit_$module" || $action == "update_$module") { ?>Update<?php } else { ?>Create<?php } ?></span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="submit" class="btn btn-info my-1 me-2"><?php if ($action == "edit_$module" || $action == "update_$module") { ?>Update<?php } else { ?>Save<?php } ?> <?php echo $module_caption; ?></button>
                        <button type="button" class="btn btn-outline-primary my-1 me-2 nav-link" data-href="listing_<?php echo $module; ?>.php">Exit</button>
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
                                    <label class="col-lg-3 col-form-label">Campaign Name: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="name" id="name" value="<?php echo $name; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Status:</label>
                                    <div class="col-lg-9">
                                        <select name="status" id="status" class="form-control">
                                            <?php
                                            $statuses = ['draft', 'scheduled', 'running', 'paused', 'completed'];
                                            foreach ($statuses as $option) {
                                                $selected = ($status === $option) ? 'selected' : '';
                                                echo "<option value=\"$option\" $selected>" . ucfirst($option) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Schedule At:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="schedule_at" id="schedule_at" value="<?php echo $schedule_at; ?>" class="form-control" placeholder="YYYY-MM-DD HH:MM:SS">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Template:</label>
                                    <div class="col-lg-9">
                                        <select name="template_id" id="template_id" class="form-control">
                                            <option value="">-- Select Template --</option>
                                            <?php while ($tpl = $templates_result->fetch_array(MYSQLI_ASSOC)) { ?>
                                                <option value="<?php echo $tpl['id']; ?>" <?php if ($template_id == $tpl['id']) { ?>selected<?php } ?>><?php echo s__($tpl['name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Subject:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="subject" id="subject" value="<?php echo $subject; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">From Name:</label>
                                    <div class="col-lg-9">
                                        <input type="text" name="from_name" id="from_name" value="<?php echo $from_name; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">From Email:</label>
                                    <div class="col-lg-9">
                                        <input type="email" name="from_email" id="from_email" value="<?php echo $from_email; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Reply To:</label>
                                    <div class="col-lg-9">
                                        <input type="email" name="reply_to" id="reply_to" value="<?php echo $reply_to; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-lg-3 col-form-label">Segment:</label>
                                    <div class="col-lg-9">
                                        <select name="segment_id" id="segment_id" class="form-control">
                                            <option value="">-- Select Segment --</option>
                                            <?php while ($seg = $targets_result->fetch_array(MYSQLI_ASSOC)) { ?>
                                                <option value="<?php echo $seg['id']; ?>" <?php if ($segment_id == $seg['id']) { ?>selected<?php } ?>><?php echo s__($seg['name']); ?></option>
                                            <?php } ?>
                                        </select>
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


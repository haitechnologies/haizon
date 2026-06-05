<?php

include('admin_elements/admin_header.php');

$module                     = 'lead_attachments';
$module_caption             = 'Lead Attachment';
$tbl_name = DB::LEAD_ATTACHMENTS;

$attachment_upload_path     = '../uploads/' . $module . '/';
$allowed_file_size          = $GLOBALS['DOCUMENT']['MAX_UPLOAD_SIZE']; //MB Bytes
$allowed_file_formats       = $GLOBALS['DOCUMENT']['FORMATS']; //MB Bytes


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

// print_r($_REQUEST);

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

if (isset($_POST['publish']))       $publish     = 1;
else $publish = 0;


$lead_id = '';
if (isset($_REQUEST['lead_id']))        $lead_id     = e_s__($_REQUEST['lead_id']);
if (isset($_POST['lead_id']))           $lead_id     = e_s__($_POST['lead_id']);



$attachment_id = 0;
if (isset($_REQUEST['attachment_id']))        $attachment_id     = e_s__($_REQUEST['attachment_id']);
if (isset($_POST['attachment_id']))           $attachment_id     = e_s__($_POST['attachment_id']);

/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $attachment_name      = e_s__($_POST['attachment_name']);
} else {
    $attachment_name      = '';
}


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($lead_id)) && granted('delete', $module_id)) {

    if (is_SystemAdmin() || is_SuperAdmin()) {

        $attachment_filename = getTableAttr('attachment_filename', DB::LEAD_ATTACHMENTS, $attachment_id);
        unlink($attachment_upload_path  . $attachment_filename);

        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$attachment_id");

        // Lead Logs
        updateLeadLogs($lead_id, 'attachment', $attachment_id, 'deleted');
    } else {

        $attachment_filename = getTableAttr('attachment_filename', DB::LEAD_ATTACHMENTS, $attachment_id);
        unlink($attachment_upload_path  . $attachment_filename);

        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$attachment_id AND created_by='" . $_SESSION[$project_pre]['DASHBOARD']['user_id'] . "'");


        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$note_id AND created_by ='" . $session_user_id . "'");
        // Lead Logs
        updateLeadLogs($lead_id, 'attachment', $attachment_id, 'deleted');
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        // header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($lead_id) && granted('edit', $module_id)) {

    /* ---------------------- QUERY ---------------------- */
    $update_row = $mysqli->query("
                                UPDATE `$tbl_name` SET
                                    attachment_name           = '" . $attachment_name . "'
                                WHERE id=$attachment_id");
    if ($update_row) {
        $attachment_name = '';

        $success_message = "The $module_caption has been updated successfully.";
        fp__($tbl_name, $attachment_id);

        // Lead Logs
        updateLeadLogs($lead_id, 'attachment', $attachment_id, 'updated');
    } else {
        $error_message = "The $module_caption could not be updated. Please try again.";
        //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {


    // $target_dir = '../uploads/lead_attachments/';
    $target_file = $attachment_upload_path . basename($_FILES["document"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // .DOC, .DOCX, .PDF, .TXT, .RTF, .XLS, .XLSX, .PPT, .PPTX, JPEG, JPG, PNG
    $extensions = array("doc", "docs", "pdf", "txt", "rtf", "xls", "xlsx", "ppt", "pptx", "jpeg", "jpg", "png");

    if (empty($_FILES['document']['tmp_name'])) {
        $error_message = "Document is mandatory.";

        // To check extensions are correct or not 
    } else if (!in_array($imageFileType, $extensions) === true) {
        $error_message = "No file selected or Invalid file extension...";
    } else if ($_FILES["document"]["size"] > 5242880) {
        $error_message = "Sorry, your file is too large.";
    } else {

        $attachment_filename        = rename_file_name($_FILES['document']['name']);
        if (move_uploaded_file($_FILES['document']['tmp_name'], $attachment_upload_path . $attachment_filename)) {

            /* ---------------------- QUERY ---------------------- */
            $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(lead_id, attachment_name, attachment_filename) VALUES ('" . $lead_id . "', '" . $attachment_name . "', '" . $attachment_filename . "'); ");

            $attachment_filename = '';

            $attachment_id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $attachment_id);

            // Lead Logs
            updateLeadLogs($lead_id, 'attachment', $attachment_id, 'added');
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
            //header("Location:$module.php?error_message=$error_message");
        }
    } // endif

}



/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/

if ($action == "edit_$module" && !empty($attachment_id) && !empty($lead_id)) {

    $result     = $mysqli->query("SELECT * FROM `" . DB::LEAD_ATTACHMENTS . "` WHERE id=$attachment_id AND lead_id=$lead_id");
    $row        = $result->fetch_array();

    $attachment_name            = s__($row['attachment_name']);
    $attachment_filename        = s__($row['attachment_filename']);
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">

    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="lead_id" id="lead_id" value="<?php echo $lead_id; ?>" />

        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($lead_id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="attachment_id" id="attachment_id" value="<?php echo $attachment_id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>


        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-2">
                    <div class="col-lg-12">
                        <?php include('admin_elements/lead_navbar.php'); ?>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <div class="collapse d-lg-block ms-lg-auto mt-1" id="breadcrumb_elements">
                        <div class="d-lg-flex mb-2 mb-lg-0 mt-1">
                            <button type="submit" class="btn btn-primary btn-sm my-1 me-2">Save</button>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
        <!-- /page header -->

        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="row">

                    <div class="row">
                        <div class="col-lg-6">

                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($lead_id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h6>
                                </div>

                                <div class="content clearfix">

                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Attachment Name: </label>
                                        <div class="col-lg-9">
                                            <input type="text" name="attachment_name" id="attachment_name" value="<?php echo $attachment_name; ?>" class="form-control">
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>



                        <div class="col-lg-4">

                            <div class="card card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold"><span class="text-danger">Document:*</span></label>

                                        <?php if (!empty($attachment_filename) && file_exists('../uploads/lead_attachments/' . $attachment_filename)) { ?>
                                            <div class="form-group">
                                                <h5>
                                                    <a href="<?php echo $attachment_upload_path; ?><?php echo $attachment_filename; ?>" target="_blank">
                                                        <small><?php echo $attachment_filename; ?></small>
                                                    </a>
                                                </h5>
                                            </div>
                                        <?php } else { ?>
                                            <div class="row mb-3">
                                                <input type="file" name="document" id="document" class="form-control">
                                            </div>

                                            <div class="form-text text-muted"><?php echo $allowed_file_formats; ?> <br /><?php echo $allowed_file_size; ?></div>

                                        <?php } ?>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="">

                        <div class="card">
                            <div class="card-header d-flex">
                                <h5 class="mb-0">
                                    <i class="ph-folder me-2"></i>
                                    Attachments
                                </h5>

                                <div class="ms-auto">
                                    <span class="text-muted">
                                        <?php
                                        // ----------------------------------------------------------------
                                        $result = $mysqli->query("SELECT id FROM `" . DB::LEAD_ATTACHMENTS . "` WHERE lead_id=$lead_id");
                                        echo '(' . $result->num_rows . ')';
                                        // ----------------------------------------------------------------
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <!-- <div class="list-group list-group-borderless py-2"> -->
                            <div class="card-body">

                                <?php
                                // ======================================================
                                $result = $mysqli->query("SELECT * FROM `" . DB::LEAD_ATTACHMENTS . "` WHERE lead_id=$lead_id ORDER BY id DESC");
                                while ($rows = $result->fetch_array()) {
                                    $attachment_id          = $rows['id'];
                                    $attachment_name        = $rows['attachment_name'];
                                    $attachment_filename    = $rows['attachment_filename'];
                                    $created_at             = dd__($rows['created_at']);
                                    // ======================================================
                                ?>
                                    <div class="d-flex mb-3">
                                        <div class="me-3">
                                            <div class="bg-success bg-opacity-10 text-success lh-1 rounded-pill p-2">
                                                <i class="ph-file"></i>
                                            </div>
                                        </div>
                                        <div class="flex-fill">
                                            <a href="<?php echo $attachment_upload_path; ?><?php echo $attachment_filename; ?>">
                                                <?php echo $attachment_name; ?>
                                            </a>


                                            <?php if (isset($module_id) && granted('edit', $module_id)) { ?>
                                                <a href="lead_attachments.php?action=edit_lead_attachments&attachment_id=<?php echo $attachment_id; ?>&lead_id=<?php echo $lead_id; ?>">
                                                    <span class="text-dark opacity-50"><i class="ph-pencil"></i></span>
                                                </a>
                                            <?php } ?>

                                            <?php if (isset($module_id) && granted('delete', $module_id)) { ?>
                                                <a href="lead_attachments.php?action=delete_lead_attachments&attachment_id=<?php echo $attachment_id; ?>&lead_id=<?php echo $lead_id; ?>">
                                                    <span class="text-danger opacity-50"><i class="ph-trash"></i></span>
                                                </a>
                                            <?php } ?>

                                            <a href="<?php echo $attachment_upload_path; ?><?php echo $attachment_filename; ?>">
                                                <small><?php echo $attachment_filename; ?></small>
                                            </a>

                                            <div class="text-muted fs-sm"><?php echo $created_at; ?></div>
                                        </div>
                                    </div>

                                <?php } // while 
                                ?>

                            </div>
                        </div>

                    </div>

                </div>
            </div>


        </div>


        <?php include('admin_elements/copyright.php'); ?>
</div>
</form>

</div>


<!-- 
    // ---------------------------------------------------------
    // ENABLE VIEW ONLY MODE FOR FORM ELEMENTS
    // ---------------------------------------------------------
-->
<?php if (isset($module_id) && granted('view', $module_id) && !granted('create', $module_id) && !granted('edit', $module_id)) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>

<?php include('admin_elements/admin_footer.php'); ?>
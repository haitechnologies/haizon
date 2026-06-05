<?php

include('admin_elements/admin_header.php');

$module = 'customer_attachments';
$module_caption = 'Customer Attachment';
$tbl_name = 'erp_customer_attachments'; // table decommissioned — page will return empty

$file_upload_path           = '../uploads/' . $module . '/';
$documentSettings           = (array)($GLOBALS['DOCUMENT'] ?? []);
$allowed_file_size          = (int)($documentSettings['MAX_UPLOAD_SIZE'] ?? 10); // MB
$allowed_file_formats       = (array)($documentSettings['FORMATS'] ?? ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']);

if (!function_exists('rename_file_name')) {
    function rename_file_name($originalName)
    {
        $base = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$originalName);
        $base = trim((string)$base, '._-');
        if ($base === '') {
            $base = 'upload';
        }

        $ext = strtolower((string)pathinfo($base, PATHINFO_EXTENSION));
        $name = (string)pathinfo($base, PATHINFO_FILENAME);
        $suffix = date('Ymd_His') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);

        if ($ext !== '') {
            return $name . '_' . $suffix . '.' . $ext;
        }

        return $name . '_' . $suffix;
    }
}

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
        log_error('CSRF token validation failed in customer_attachments.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

if (isset($_POST['is_active']))       $is_active = 1;
else $is_active = 0;


$customer_id = '';
if (isset($_REQUEST['customer_id']))        $customer_id     = e_s__($_REQUEST['customer_id']);
if (isset($_POST['customer_id']))           $customer_id     = e_s__($_POST['customer_id']);


//VERIFY IF IS VALID 
$rs_customer_valid  = $mysqli->query("SELECT id FROM `" . tbl_customers . "` WHERE id='" . $customer_id . "'");
if ($rs_customer_valid->num_rows == 0) header("Location:listing_customers.php");


//---------------
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
    $customer_attachment      = e_s__($_POST['customer_attachment']);
} else {
    $customer_attachment      = '';
}


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($customer_id)) && granted('delete', $module_id)) {

    //SUPERADMIN CAN DELETE ANY DATA
    if (Roles::hasFullAccess($session_role_id)) {

        $filename = getTableAttr('filename', 'erp_customer_attachments', $attachment_id);
        $stmt = $mysqli->prepare("DELETE FROM `$tbl_name` WHERE id=?");
        $stmt->bind_param("i", $attachment_id);
        $result = $stmt->execute();
        $stmt->close();
        unlink($file_upload_path  . $filename);

        // Customer Logs
        updateCustomerLogs($customer_id, 'attachment', 'deleted');

        //ADMIN CAN DELETE ONLY HIS/HER DATA
    } else {
        $filename = getTableAttr('filename', 'erp_customer_attachments', $attachment_id);
        $stmt = $mysqli->prepare("DELETE FROM `$tbl_name` WHERE id=? AND created_by=?");
        $stmt->bind_param("ii", $attachment_id, $session_user_id);
        $result = $stmt->execute();
        $stmt->close();
        unlink($file_upload_path  . $filename);

        // Customer Logs
        updateCustomerLogs($customer_id, 'attachment', 'deleted');
    }


    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
        // header("Location:listing_$module.php?page=$page&success_message=$success_message");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
    }
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($customer_id) && granted('edit', $module_id)) {

    /* ---------------------- QUERY ---------------------- */
    $update_row = $mysqli->query("
                                UPDATE `$tbl_name` SET
                                    customer_attachment           = '" . $customer_attachment . "'
                                WHERE id=$attachment_id");
    if ($update_row) {
        $customer_attachment = '';

        $success_message = "The $module_caption has been updated successfully.";
        fp__($tbl_name, $attachment_id);

        // Customer Logs
        updateCustomerLogs($customer_id, 'attachment', 'updated');
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


    // $target_dir = '../uploads/customer_attachments/';
    $target_file = $file_upload_path . basename($_FILES["document"]["name"]);
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

        $filename        = rename_file_name($_FILES['document']['name']);
        if (move_uploaded_file($_FILES['document']['tmp_name'], $file_upload_path . $filename)) {

            /* ---------------------- QUERY ---------------------- */
            $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(customer_id, customer_attachment, filename) VALUES ('" . $customer_id . "', '" . $customer_attachment . "', '" . $filename . "'); ");

            $filename = '';

            $attachment_id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $attachment_id);

            // Customer Logs
            updateCustomerLogs($customer_id, 'attachment', 'added');
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

if ($action == "edit_$module" && !empty($attachment_id) && !empty($customer_id)) {

    $result     = $mysqli->query("SELECT * FROM `erp_customer_attachments` WHERE id=$attachment_id AND customer_id=$customer_id");
    $row        = $result ? $result->fetch_array() : null;

    $customer_attachment        = s__($row['customer_attachment']);
    $filename                   = s__($row['filename']);
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>

<div class="sidebar sidebar-secondary sidebar-expand-lg">

    <!-- Expand button -->
    <button type="button" class="btn btn-sidebar-expand sidebar-control sidebar-secondary-toggle h-100">
        <i class="ph-caret-right"></i>
    </button>
    <!-- /expand button -->


    <!-- Sidebar content -->
    <?php include('admin_elements/sidebar_customer.php'); ?>
    <!-- /sidebar content -->

</div>

<div class="content-wrapper">

    <!-- Inner content -->
    <div class="content-inner">

        <!-- Page header -->
        <?php include('admin_elements/page_header_customer.php'); ?>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="row">

                <div class="col-xl-10">

                    <div class="card">

                        <div class="card-body">

                            <div class="row">

                                <div class="col-lg-12">

                                    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
                                        <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $customer_id; ?>" />

                                        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($customer_id)) { ?>
                                            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                                            <input type="hidden" name="attachment_id" id="attachment_id" value="<?php echo $attachment_id; ?>" />
                                        <?php } else { ?>
                                            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                                        <?php } ?>
                                        <?php echo csrf_field(); ?>

                                        <div class="row">
                                            <div class="col-lg-10">
                                                <div class="row mb-3">
                                                    <label class="col-lg-3 col-form-label">Customer File: </label>
                                                    <div class="col-lg-9">
                                                        <input type="text" name="customer_attachment" id="customer_attachment" value="<?php echo $customer_attachment; ?>" class="form-control">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Document: *</span></label>
                                                    <div class="col-lg-9">
                                                        <?php if (!empty($filename) && file_exists('../uploads/customer_attachments/' . $filename)) { ?>
                                                            <div class="form-group">
                                                                <h5>
                                                                    <a href="<?php echo $file_upload_path; ?><?php echo $filename; ?>" target="_blank">
                                                                        <small><?php echo $filename; ?></small>
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


                                                <div class="d-flex justify-content-end">
                                                    <button type="submit" class="btn btn-light btn-sm my-1">
                                                        Add Attachment
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>


                                    <div class="row">
                                        <div class="col-lg-10">

                                            <span class="small text-muted">
                                                ALL ATTACHMENTS

                                                <span class="badge bg-primary rounded-pill ms-auto">
                                                    <?php
                                                    // ----------------------------------------------------------------
                                                    $result = $mysqli->query("SELECT id FROM `erp_customer_attachments` WHERE customer_id=$customer_id");
                                                    echo $result ? $result->num_rows : 0;
                                                    // ----------------------------------------------------------------
                                                    ?>
                                                </span>
                                            </span>


                                            <div class="comment-timeline p-4">

                                                <?php
                                                // ======================================================
                                                $result = $mysqli->query("SELECT * FROM `erp_customer_attachments` WHERE customer_id=$customer_id ORDER BY id DESC");
                                                while ($result && $rows = $result->fetch_array()) {
                                                    $attachment_id          = $rows['id'];
                                                    $customer_attachment    = $rows['customer_attachment'];
                                                    $filename               = $rows['filename'];
                                                    $created_at             = $rows['created_at'];
                                                    // ======================================================
                                                ?>
                                                    <div class="d-flex mb-4 position-relative">
                                                        <div class="position-absolute border-start h-100" style="left: 15px; top: 30px; z-index: 0; width: 2px; border-color: #e9ecef !important;"></div>

                                                        <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; z-index: 1;">
                                                            <i class="ph-chat-centered-text text-primary"></i>
                                                        </div>

                                                        <div class="ms-3 flex-grow-1">
                                                            <div class="d-flex align-items-center mb-1">
                                                                <span class="fw-bold me-2"><?php echo getTableAttr('full_name', tbl_users, $rows['created_by']) ?></span>
                                                                <span class="text-muted small">• <?php echo dd__($created_at); ?></span>
                                                            </div>
                                                            <div class="bg-light rounded p-3 d-flex justify-content-between align-items-center">
                                                                <span class="text-dark">
                                                                    <a href="<?php echo $file_upload_path; ?><?php echo $filename; ?>" target="_blank">
                                                                        <small><?php echo $filename; ?></small>
                                                                    </a>
                                                                </span>

                                                                <button type="button"
                                                                    class="btn btn-link text-muted p-0 confirm-delete"
                                                                    data-href="customer_attachments.php?action=delete_customer_attachments&attachment_id=<?php echo $attachment_id; ?>&customer_id=<?php echo $customer_id; ?>">
                                                                    <i class="ph-trash"></i>
                                                                </button>

                                                            </div>
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

                    </div>

                </div>
            </div>

        </div>


    </div>
    <!-- /content area -->

    <?php include('admin_elements/copyright.php'); ?>
</div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module                 = 'user_documents';
$module_caption         = 'User Document';
$tbl_name = DB::USER_DOCUMENTS;

$document_upload_path   = '../uploads/' . $module . '/';
$allowed_file_size      = $GLOBALS['DOCUMENT']['MAX_UPLOAD_SIZE']; //MB Bytes
$allowed_file_formats   = $GLOBALS['DOCUMENT']['FORMATS']; //MB Bytes


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
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $user                   = e_s__($_POST['user']);
    $document_category      = e_s__($_POST['document_category']);
    $document_name          = e_s__($_POST['document_name']);
    $issued_date            = e_s__($_POST['issued_date']);
    $expiry_date            = e_s__($_POST['expiry_date']);
    $description            = e_s__($_POST['description']);
} else {
    $user                   = '';
    $document_category      = '';
    $document_name          = '';
    $issued_date            = '';
    $expiry_date            = '';
    $description            = '';
}




/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($lead_id)) && granted('delete', $module_id)) {

    //SUPERADMIN CAN DELETE ANY DATA
    if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1') {

        $user_document_filename = getTableAttr('filename', DB::USER_DOCUMENTS, $document_id);
        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$document_id");
        unlink($document_upload_path  . $document_filename);


        //ADMIN CAN DELETE ONLY HIS/HER DATA
    } else {
        $document_filename = getTableAttr('filename', DB::USER_DOCUMENTS, $document_id);
        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$document_id AND created_by='" . $_SESSION[$project_pre]['DASHBOARD']['user_id'] . "'");
        unlink($document_upload_path  . $document_filename);
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
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($user) || $user == 'Please select') {
        $error_message = 'Please select Employee.';
    } else if (!empty($issued_date) && $issued_date != '1970-01-01' && !empty($expiry_date)  && $expiry_date < $issued_date) {
        $error_message = 'Expiry date should always later than the Issued Date.';
    } else {

        $issued_date        = (empty($issued_date) ? '1970-01-01' : processDateDtoY($issued_date));
        $expiry_date        = (empty($expiry_date) ? '1970-01-01' : processDateDtoY($expiry_date));

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
											attachable_id	        = '" . $user . "',
											document_category       = '" . $document_category . "',
											display_name			= '" . $document_name . "',
											issued_date			    = '" . $issued_date . "',
											expiry_date			    = '" . $expiry_date . "',
											description			    = '" . $description . "'
										WHERE id=$id");
        if ($message) {
            $error_message = $message;
        } else if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }

        // } // END DUPLICATE VEHICLE

    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($user) || $user == 'Please select') {
        $error_message = 'Please select Employee.';
    } else if (!empty($issued_date) && $issued_date != '1970-01-01' && !empty($expiry_date)  && $expiry_date < $issued_date) {
        $error_message = 'Expiry date should always later than the Issued Date.';
    } else {


        // $target_dir = '../uploads/user_documents/';
        $target_file = $document_upload_path . basename($_FILES["document"]["name"]);
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

            $document_filename        = rename_file_name($_FILES['document']['name']);
            if (move_uploaded_file($_FILES['document']['tmp_name'], $document_upload_path . $document_filename)) {

                $issued_date        = (empty($issued_date) ? '1970-01-01' : processDateDtoY($issued_date));
                $expiry_date        = (empty($expiry_date) ? '1970-01-01' : processDateDtoY($expiry_date));

                $document_category  = (empty($document_category) ? 0 : $document_category);


                /* ---------------------- QUERY ---------------------- */
                $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(attachable_type, attachable_id, document_category, display_name, filename, issued_date, expiry_date, description) VALUES ('UserDoc', '" . $user . "', '" . $document_category . "', '" . $document_name . "', '" . $document_filename . "', '" . $issued_date . "', '" . $expiry_date . "', '" . $description . "'); ");

                $document_filename = '';

                $document_id = $mysqli->insert_id;
                $success_message = "The $module_caption has been saved successfully.";
                fp__($tbl_name, $document_id);
                header("Location:listing_$module.php?success_message=$success_message");
            } else {
                $error_message = "The $module_caption could not be saved. Please try again.";
                //header("Location:$module.php?error_message=$error_message");
            }
        } // endif

    }
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
if (!empty($id)) {

    $result = $mysqli->query("SELECT *, attachable_id AS user, display_name AS document_name, filename AS document_filename FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $user               = s__($row['user']);
    $document_category  = s__($row['document_category']);
    $document_name      = s__($row['document_name']);
    $document_filename  = s__($row['document_filename']);
    $issued_date        = s__($row['issued_date']);
    $expiry_date        = s__($row['expiry_date']);
    $description        = s__($row['description']);

    $issued_date        = ($issued_date == '1970-01-01' ? '' : processDateDtoY($issued_date));
    $expiry_date        = ($expiry_date == '1970-01-01' ? '' : processDateDtoY($expiry_date));

    // $issued_date = processDateYtoD($issued_date);
    // $expiry_date = processDateYtoD($expiry_date);
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module" || $action == "change_password") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1">
                <?php if (empty($id) || (isset($module_id) && granted('create', $module_id)) || (isset($module_id) && granted('edit', $module_id)) || $file === 'profile.php' || $file === 'change_password.php') { ?>
                    <button type="submit" form="frmuser_documents" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>

        <!-- Page header -->



                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Employee Name: *</span></label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="user" id="user">
                                            <option value='0'>Please select</option>
                                            <?php
                                            $result = $mysqli->query("SELECT * FROM `" . DB::USERS . "` WHERE is_active=1 ORDER BY full_name");
                                            while ($rows = $result->fetch_array()) {
                                            ?>
                                                <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $user) { ?>selected <?php } else if ($rows['id'] == $user) { ?>selected <?php } ?>>
                                                    <?php echo $rows['full_name']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Document Category: </label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="document_category" id="document_category">
                                            <option value='0'>Please select</option>
                                            <?php
                                            $result = $mysqli->query("SELECT * FROM `" . DB::DOCUMENT_CATEGORIES . "` WHERE is_active=1 AND document_category_type='employees' ORDER BY document_category");
                                            while ($rows = $result->fetch_array()) {
                                            ?>
                                                <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $document_category) { ?>selected <?php } else if ($rows['id'] == $document_category) { ?>selected <?php } ?>>
                                                    <?php echo $rows['document_category']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Document Name: </label>
                                    <div class="col-lg-9">
                                        <input type="text" name="document_name" id="document_name" value="<?php echo $document_name; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Issued Date: </label>
                                    <div class="col-lg-9">
                                        <div class="form-control-feedback form-control-feedback-start">
                                            <input type="text" class="form-control" name="issued_date" id="issued_date" value="<?php echo $issued_date; ?>">
                                            <div class="form-control-feedback-icon">
                                                <i class="ph-calendar"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Expiry Date: </label>
                                    <div class="col-lg-9">
                                        <div class="form-control-feedback form-control-feedback-start">
                                            <input type="text" class="form-control" name="expiry_date" id="expiry_date" value="<?php echo $expiry_date; ?>">
                                            <div class="form-control-feedback-icon">
                                                <i class="ph-calendar"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Description: </label>
                                    <div class="col-lg-9">
                                        <textarea class="form-control" name="description" id="description" style="field-sizing: content;"><?php echo $description; ?></textarea>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>



                    <div class="col-lg-4">

                        <div class="card card-body">
                            <div class="row">
                                <div class="col-md-12">

                                    <?php if (!empty($document_filename) && file_exists('../uploads/user_documents/' . $document_filename)) { ?>
                                        <div class="form-group">
                                            <a href="<?php echo $document_upload_path; ?><?php echo $document_filename; ?>" target="_blank">
                                                <small><?php echo $document_filename; ?></small>
                                            </a>
                                        </div>
                                    <?php } else { ?>
                                        <div class="row mb-3">
                                            <label class="form-label fw-semibold"><span class="text-danger">Document:*</span></label>
                                            <input type="file" name="document" id="document" class="form-control">
                                        </div>

                                        <div class="form-text text-muted"><?php echo $allowed_file_formats; ?> <br /><?php echo $allowed_file_size; ?></div>

                                    <?php } ?>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>


            </div>
        </div>


        </form>
    <?php include('admin_elements/copyright.php'); ?>
</div>

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
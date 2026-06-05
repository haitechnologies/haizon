<?php

include('admin_elements/admin_header.php');

$module                 = 'lead_notes';
$module_caption         = 'Lead notes';
$tbl_name = DB::ENTITY_NOTES;

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



$note_id = 0;
if (isset($_REQUEST['note_id']))        $note_id     = e_s__($_REQUEST['note_id']);
if (isset($_POST['note_id']))           $note_id     = e_s__($_POST['note_id']);




/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $notes      = e_s__($_POST['notes']);
} else {
    $notes      = '';
}


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($lead_id)) && granted('delete', $module_id)) {

    if (is_SystemAdmin() || is_SuperAdmin()) {

        $mysqli->query("DELETE FROM `$tbl_name` WHERE entity_type='lead' AND id=$note_id");
        // Lead Logs
        updateLeadLogs($lead_id, 'note', $note_id, 'deleted');
    } else {

        $mysqli->query("DELETE FROM `$tbl_name` WHERE entity_type='lead' AND id=$note_id AND created_by ='" . $session_user_id . "'");
        // Lead Logs
        updateLeadLogs($lead_id, 'note', $note_id, 'deleted');
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


    if (empty($notes)) {
        $error_message = 'Notes are mandatory.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
                                    UPDATE `$tbl_name` SET
                                        notes           = '" . $notes . "'
                                    WHERE entity_type='lead' AND id=$note_id");
        if ($update_row) {
            $notes = '';

            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $note_id);

            // Lead Logs
            updateLeadLogs($lead_id, 'note', $note_id, 'updated');
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($notes)) {
        $error_message = 'Notes are mandatory.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(entity_type, entity_id, notes) VALUES ('lead', '" . $lead_id . "', '" . $notes . "'); ");

        if ($insert_row) {
            $notes = '';

            $note_id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $note_id);

            // Lead Logs
            updateLeadLogs($lead_id, 'note', $note_id, 'added');
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
            //header("Location:$module.php?error_message=$error_message");
        }
    }
}



/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/

if ($action == "edit_$module" && !empty($note_id) && !empty($lead_id)) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE entity_type='lead' AND id=$note_id AND entity_id=$lead_id");
    $row = $result->fetch_array();
    $notes            = s__($row['notes']);
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
            <input type="hidden" name="note_id" id="note_id" value="<?php echo $note_id; ?>" />
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

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($lead_id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h6>
                            </div>


                            <div class="card-body">

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Notes:*</span> </label>
                                    <div class="col-lg-9">
                                        <textarea class="form-control" name="notes" id="notes" style="field-sizing: content;"><?php echo $notes; ?></textarea>
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
                                    Notes
                                </h5>

                                <div class="ms-auto">
                                    <span class="text-muted">
                                        <?php
                                        // ----------------------------------------------------------------
                                        $result = $mysqli->query("SELECT id FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='lead' AND entity_id=$lead_id");
                                        echo '(' . $result->num_rows . ')';
                                        // ----------------------------------------------------------------
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div class="p-3">

                                <?php

                                // ======================================================
                                $result = $mysqli->query("SELECT * FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='lead' AND entity_id=$lead_id ORDER BY id DESC");
                                while ($rows = $result->fetch_array()) {
                                    $note_id = $rows['id'];
                                    // ======================================================
                                ?>
                                    <div class="mb-3">
                                        <a href="user.php?id=<?php echo $rows['created_by']; ?>"><?php echo getTableAttr('full_name', tbl_users, $rows['created_by']) ?></a> <br />
                                        <small>Note Added: <?php echo date("d F Y h:i", strtotime($rows['created_at'])); ?></span></small> <br /><br />
                                        <?php echo $rows['notes']; ?>

                                        <?php if (isset($module_id) && granted('edit', $module_id)) { ?>
                                            <a href="lead_notes.php?action=edit_lead_notes&note_id=<?php echo $note_id; ?>&lead_id=<?php echo $lead_id; ?>">
                                                <span class="text-dark opacity-50"><i class="ph-pencil"></i></span>
                                            </a>
                                        <?php } // if 
                                        ?>

                                        <?php if (isset($module_id) && granted('delete', $module_id)) { ?>
                                            <a href="lead_notes.php?action=delete_lead_notes&note_id=<?php echo $note_id; ?>&lead_id=<?php echo $lead_id; ?>">
                                                <span class="text-danger opacity-50"><i class="ph-trash"></i></span>
                                            </a>
                                        <?php } // if 
                                        ?>
                                        <hr />
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
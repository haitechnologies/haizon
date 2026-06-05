<?php


use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');

$module = 'customer_comments';
$module_caption = 'Comments';
$tbl_name = DB::ENTITY_NOTES; // erp_customer_comments merged into erp_entity_notes
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
        log_error('CSRF token validation failed in customer_comments.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

if (isset($_POST['is_active']))       $is_active = 1;
else $is_active = 0;


// ------------------ CHECK IF CUSTOMER EXISTS ----------------
$customer_id = '';
if (isset($_REQUEST['customer_id']))        $customer_id     = e_s__($_REQUEST['customer_id']);
if (isset($_POST['customer_id']))           $customer_id     = e_s__($_POST['customer_id']);


if (!empty($customer_id)) {
    $id = $customer_id;
}

//VERIFY IF CUSTOMER IS VALID 
// $rs_customer_valid  = $mysqli->query("SELECT id FROM `" . DB::CUSTOMERS . "` WHERE id=$id");
// if ($rs_customer_valid->num_rows == 0) header("Location:listing_customers.php");


//---------------
if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
    $id     = e_s__($_REQUEST['id']);
}


$comment_id = 0;
if (isset($_REQUEST['comment_id']))        $comment_id     = e_s__($_REQUEST['comment_id']);
if (isset($_POST['comment_id']))           $comment_id     = e_s__($_POST['comment_id']);


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $comments      = e_s__($_POST['comments']);
} else {
    $comments      = '';
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

        $stmt = $mysqli->prepare("DELETE FROM `$tbl_name` WHERE id=?");
        $stmt->bind_param("i", $comment_id);
        $result = $stmt->execute();
        $stmt->close();

        // Customer Logs
        updateCustomerLogs($customer_id, 'comments', 'deleted');


        //ADMIN CAN DELETE ONLY HIS/HER DATA
    } else {
        $stmt = $mysqli->prepare("DELETE FROM `$tbl_name` WHERE id=? AND created_by=?");
        $stmt->bind_param("ii", $comment_id, $session_user_id);
        $result = $stmt->execute();
        $stmt->close();

        // Customer Logs
        updateCustomerLogs($customer_id, 'comments', 'deleted');
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


    if (empty($comments)) {
        $error_message = 'Comments are mandatory.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
                                    UPDATE `$tbl_name` SET
                                        notes    = '" . $comments . "'
                                    WHERE id=$comment_id AND entity_type='customer'");
        if ($update_row) {
            $comments = '';

            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $comment_id);

            // Customer Logs
            updateCustomerLogs($customer_id, 'comment', 'updated');
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

    if (empty($comments)) {
        $error_message = 'Comments are mandatory.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(entity_type, entity_id, notes) VALUES ('customer', '" . $customer_id . "', '" . $comments . "'); ");

        if ($insert_row) {
            $comments = '';

            $comment_id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $comment_id);

            // Customer Logs
            updateCustomerLogs($customer_id, 'comment', 'added');
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

if ($action == "edit_$module" && !empty($comment_id) && !empty($customer_id)) {

    $result     = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$comment_id AND entity_type='customer' AND entity_id=$customer_id");
    $row        = $result->fetch_array();
    $comments   = s__($row['notes'] ?? '');
}



/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>

<aside class="sidebar sidebar-secondary sidebar-expand-lg" aria-label="Secondary Navigation">

    <!-- Expand button -->
    <button type="button" class="btn btn-sidebar-expand sidebar-control sidebar-secondary-toggle h-100">
        <i class="ph-caret-right"></i>
    </button>
    <!-- /expand button -->


    <!-- Sidebar content -->
    <?php include('admin_elements/sidebar_customer.php'); ?>
    <!-- /sidebar content -->

</aside>

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
                                            <input type="hidden" name="comment_id" id="comment_id" value="<?php echo $comment_id; ?>" />
                                        <?php } else { ?>
                                            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                                        <?php } ?>
                                        <?php echo csrf_field(); ?>

                                        <div class="row">
                                            <div class="col-lg-10">
                                                <textarea class="form-control" name="comments" id="comments" rows="3"><?php echo $comments; ?></textarea>

                                                <div class="d-flex justify-content-end">
                                                    <button type="submit" class="btn btn-light btn-sm my-1">
                                                        Add Comments
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>


                                    <div class="row">
                                        <div class="col-lg-10">

                                            <span class="small text-muted">
                                                ALL COMMENTS

                                                <span class="badge bg-primary rounded-pill ms-auto">
                                                    <?php
                                                    // ----------------------------------------------------------------
                                                    $result = $mysqli->query("SELECT id FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='customer' AND entity_id=$customer_id");
                                                    echo $result->num_rows;
                                                    // ----------------------------------------------------------------
                                                    ?>
                                                </span>
                                            </span>


                                            <div class="comment-timeline p-4">

                                                <?php
                                                // ======================================================
                                                $result = $mysqli->query("SELECT * FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='customer' AND entity_id=$customer_id ORDER BY id DESC");
                                                while ($rows = $result->fetch_array()) {
                                                    $comment_id = $rows['id'];
                                                    // ======================================================
                                                ?>
                                                    <div class="d-flex mb-4 position-relative">
                                                        <div class="position-absolute border-start h-100" style="left: 15px; top: 30px; z-index: 0; width: 2px; border-color: #e9ecef !important;"></div>

                                                        <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; z-index: 1;">
                                                            <i class="ph-chat-centered-text text-primary"></i>
                                                        </div>

                                                        <div class="ms-3 flex-grow-1">
                                                            <div class="d-flex align-items-center mb-1">
                                                                <span class="fw-bold me-2"><?php echo getTableAttr('full_name', DB::USERS, $rows['created_by']) ?></span>
                                                                <span class="text-muted small">• <?php echo dd__($created_at); ?></span>
                                                            </div>
                                                            <div class="bg-light rounded p-3 d-flex justify-content-between align-items-center">
                                                                <span class="text-dark"><?php echo $rows['notes']; ?></span>

                                                                <button type="button"
                                                                    class="btn btn-link text-muted p-0 confirm-delete"
                                                                    data-href="customer_comments.php?action=delete_customer_comments&comment_id=<?php echo $comment_id; ?>&customer_id=<?php echo $customer_id; ?>">
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
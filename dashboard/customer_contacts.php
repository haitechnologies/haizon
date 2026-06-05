<?php

include('admin_elements/admin_header.php');

$module = 'customer_contacts';
$module_caption = 'Contact Person';
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
        log_error('CSRF token validation failed in customer_contacts.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

$customer_id = '';
if (isset($_REQUEST['customer_id']))        $customer_id     = e_s__($_REQUEST['customer_id']);
if (isset($_POST['customer_id']))           $customer_id     = e_s__($_POST['customer_id']);


//VERIFY IF IS VALID 
$rs_customer_valid  = $mysqli->query("SELECT id FROM `" . tbl_customers . "` WHERE id='". $customer_id."'");
if ($rs_customer_valid->num_rows == 0) header("Location:listing_customers.php");


//---------------
$contact_id = 0;
if (isset($_REQUEST['contact_id']))        $contact_id     = e_s__($_REQUEST['contact_id']);
if (isset($_POST['contact_id']))           $contact_id     = e_s__($_POST['contact_id']);


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $first_name         = e_s__($_POST['first_name']);
    $last_name          = e_s__($_POST['last_name']);
    $position           = e_s__($_POST['position']);
    $email              = e_s__($_POST['email']);
    $phone              = e_s__($_POST['phone']);
    $notes              = e_s__($_POST['notes']);
} else {
    $first_name        = '';
    $last_name         = '';
    $position          = '';
    $email             = '';
    $phone             = '';
    $notes             = '';
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
        $stmt->bind_param("i", $contact_id);
        $result = $stmt->execute();
        $stmt->close();

        // Customer Logs
        updateCustomerLogs($customer_id, 'contacts', 'deleted');


        //ADMIN CAN DELETE ONLY HIS/HER DATA
    } else {
        $stmt = $mysqli->prepare("DELETE FROM `$tbl_name` WHERE id=? AND created_by=?");
        $stmt->bind_param("ii", $contact_id, $session_user_id);
        $result = $stmt->execute();
        $stmt->close();

        // Customer Logs
        updateCustomerLogs($customer_id, 'contacts', 'deleted');
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


    if (empty($first_name)) {
        $error_message = 'First name is mandatory.';
    } else if (empty($last_name)) {
        $error_message = 'Last name is mandatory.';
    } else if (empty($email)) {
        $error_message = 'Email is mandatory.';
    } else {


        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
                                    UPDATE `$tbl_name` SET
                                        first_name					= '" . $first_name . "',
										last_name					= '" . $last_name . "',
										position					= '" . $position . "',
										email				        = '" . $email . "',
										phone				        = '" . $phone . "',
										notes				        = '" . $notes . "'
                                    WHERE id=$contact_id");
        if ($update_row) {

            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $contact_id);

            // Customer Logs
            updateCustomerLogs($customer_id, 'contact', 'updated');
            // header("Location:listing_$module.php?customer_id=$customer_id&success_message=$success_message");
            header("Location:customer_overview.php?customer_id=$customer_id&success_message=$success_message");
            
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

    if (empty($first_name)) {
        $error_message = 'First name is mandatory.';
    } else if (empty($last_name)) {
        $error_message = 'Last name is mandatory.';
    } else if (empty($email)) {
        $error_message = 'Email is mandatory.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(is_primary, customer_id, first_name, last_name, position, email, phone, notes) VALUES ('1', '" . $customer_id . "', '" . $first_name . "', '" . $last_name . "', '" . $position . "', '" . $email . "', '" . $phone . "', '" . $notes . "'); ");

        if ($insert_row) {



            $contact_id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $contact_id);

            // Customer Logs
            updateCustomerLogs($customer_id, 'contact', 'added');
            // header("Location:listing_$module.php?customer_id=$customer_id&success_message=$success_message");
            header("Location:customer_overview.php?customer_id=$customer_id&success_message=$success_message");
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

if ($action == "edit_$module" && !empty($contact_id) && !empty($customer_id)) {

    $result = $mysqli->query("SELECT * FROM `".tbl_customer_contacts."` WHERE id=$contact_id AND customer_id=$customer_id");
    $row = $result->fetch_array();

    $first_name         = s__($row['first_name']);
    $last_name          = s__($row['last_name']);
    $position           = s__($row['position']);
    $email              = s__($row['email']);
    $phone              = s__($row['phone']);
    $notes              = s__($row['notes']);
    $is_active = s__($row['publish']);
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

                <div class="col-lg-6 col-xl-12">

                    <div class="card">

                        <div class="tab-content card-body">
                            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">

                                <div class="row">

                                    <?php include('admin_elements/sidebar_customer_overview.php'); ?>

                                    <div class="col-lg-8">

                                        <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
                                            <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $customer_id; ?>" />

                                            <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($customer_id)) { ?>
                                                <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                                                <input type="hidden" name="contact_id" id="contact_id" value="<?php echo $contact_id; ?>" />
                                            <?php } else { ?>
                                                <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                                            <?php } ?>
                                            <?php echo csrf_field(); ?>


                                            <span class="fw-semibold"><?php echo $module_caption; ?></span>

                                            <div class="card-body">

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label"><span class="text-danger">First Name:*</span></label>
                                                    <div class="col-lg-9">
                                                        <input required name="first_name" id="first_name" value="<?php echo $first_name; ?>" class="form-control" type="text">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Last Name:*</span></label>
                                                    <div class="col-lg-9">
                                                        <input required name="last_name" id="last_name" value="<?php echo $last_name; ?>" class="form-control" type="text">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Position: </label>
                                                    <div class="col-lg-9">
                                                        <input name="position" id="position" value="<?php echo $position; ?>" class="form-control" type="text">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Email:*</span></label>
                                                    <div class="col-lg-9">
                                                        <input required name="email" id="email" value="<?php echo $email; ?>" class="form-control" type="email">
                                                    </div>
                                                </div>


                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Phone:</label>
                                                    <div class="col-lg-9">
                                                        <input name="phone" id="phone" value="<?php echo $phone; ?>" class="form-control" type="text">
                                                        <div class="form-text text-muted"><small>+971 50 1234567</small></div>
                                                    </div>
                                                </div>


                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Notes: </label>
                                                    <div class="col-lg-9">
                                                        <textarea class="form-control" name="notes" id="notes" style="field-sizing: content;"><?php echo $notes; ?></textarea>
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">&nbsp;</label>
                                                    <div class="col-lg-9">
                                                        <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                                            <button type="submit" class="btn btn-primary btn-sm my-1 me-2">Save</button>
                                                        <?php } ?>
                                                    </div>
                                                </div>



                                            </div>


                                        </form>

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
<?php

include('admin_elements/admin_header.php');

$module = 'customer_addresses';
$module_caption = 'Billing Address';
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
        log_error('CSRF token validation failed in customer_billing_addresses.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

// ------------------ CHECK IF CUSTOMER EXISTS ----------------
$customer_id = 0;
$id = 0;
if (isset($_REQUEST['customer_id'])) {
    $customer_id = (int)$_REQUEST['customer_id'];
}
if (isset($_POST['customer_id'])) {
    $customer_id = (int)$_POST['customer_id'];
}

if (!empty($customer_id)) {
    $id = $customer_id;
}


if ($id <= 0) {
    header("Location:listing_customers.php");
    exit;
}


//VERIFY IF CUSTOMER IS VALID 
$rs_customer_valid  = $mysqli->query("SELECT id FROM `" . tbl_customers . "` WHERE id=" . (int)$id);
if (!$rs_customer_valid || $rs_customer_valid->num_rows == 0) {
    header("Location:listing_customers.php");
    exit;
}


//---------------
if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
    $id     = (int)$_REQUEST['id'];
}



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
if ($action == "update_$module") {

    $billing_attention      = e_s__($_POST['billing_attention']);
    $billing_country        = e_s__($_POST['billing_country']);
    $billing_address_line1  = e_s__($_POST['billing_address_line1']);
    $billing_address_line2  = e_s__($_POST['billing_address_line2']);
    $billing_city           = e_s__($_POST['billing_city']);
    $billing_state          = e_s__($_POST['billing_state']);
    $billing_zipcode        = e_s__($_POST['billing_zipcode']);
    $billing_phone          = e_s__($_POST['billing_phone']);
    $billing_fax            = e_s__($_POST['billing_fax']);
} else {
    $billing_attention      = '';
    $billing_country        = '';
    $billing_address_line1  = '';
    $billing_address_line2  = '';
    $billing_city           = '';
    $billing_state          = '';
    $billing_zipcode        = '';
    $billing_phone          = '';
    $billing_fax            = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($customer_id) && granted('edit', $module_id)) {

    $billing_country = ((empty($billing_country)) ? '0' : $billing_country);
    $rs_billing     = $mysqli->query("SELECT * FROM `$tbl_name` WHERE customer_id=$customer_id AND type='billing' ");

    // IF EXISTS - UPDATE
    /* ---------------------- QUERY ---------------------- */
    if ($rs_billing->num_rows == 0) {

        $query = $mysqli->query("INSERT INTO `$tbl_name` (customer_id, type, attention, country, address_line1, address_line2, city, state, zipcode, phone, fax)  VALUES ('$customer_id', 'billing', '$billing_attention', '$billing_country', '$billing_address_line1', '$billing_address_line2', '$billing_city', '$billing_state', '$billing_zipcode', '$billing_phone', '$billing_fax') ");

        // IF DOES NOT EXIST - INSERT
        /* ---------------------- QUERY ---------------------- */
    } else {

        $query = $mysqli->query("
                UPDATE `$tbl_name` SET
                    attention				= '" . $billing_attention . "',
                    country					= '" . $billing_country . "',
                    address_line1			= '" . $billing_address_line1 . "',
                    address_line2			= '" . $billing_address_line2 . "',
                    city					= '" . $billing_city . "',
                    state					= '" . $billing_state . "',
                    zipcode					= '" . $billing_zipcode . "',
                    phone					= '" . $billing_phone . "',
                    fax					    = '" . $billing_fax . "'
                WHERE customer_id=$customer_id AND type='billing' ");
    }


    if ($query) {
        $id = $mysqli->insert_id;
        $success_message = "The $module_caption has been updated successfully.";
        fp__($tbl_name, $id);

        // header("Location:listing_$module.php?customer_id=$customer_id&success_message=$success_message");
        header("Location:customer_overview.php?customer_id=$customer_id&success_message=$success_message");
    } else {
        $error_message = "The $module_caption could not be updated. Please try again.";
        //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
    }
}

/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/

if ($action == "edit_$module" && !empty($customer_id)) {

    $rs_billing     = $mysqli->query("SELECT * FROM `$tbl_name` WHERE customer_id=$customer_id AND type='billing' ");
    $row_billing    = $rs_billing->fetch_array();

    $billing_attention      = (!empty($row_billing['attention']) ? s__($row_billing['attention']) : '');
    $billing_country        = (!empty($row_billing['country']) ? s__($row_billing['country']) : '');
    $billing_address_line1  = (!empty($row_billing['address_line1']) ? s__($row_billing['address_line1']) : '');
    $billing_address_line2  = (!empty($row_billing['address_line2']) ? s__($row_billing['address_line2']) : '');
    $billing_city           = (!empty($row_billing['city']) ? s__($row_billing['city']) : '');
    $billing_state          = (!empty($row_billing['state']) ? s__($row_billing['state']) : '');
    $billing_zipcode        = (!empty($row_billing['zipcode']) ? s__($row_billing['zipcode']) : '');
    $billing_phone          = (!empty($row_billing['phone']) ? s__($row_billing['phone']) : '');
    $billing_fax            = (!empty($row_billing['fax']) ? s__($row_billing['fax']) : '');
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

                                        <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="customer_billing_addresses.php" autocomplete="off" enctype="multipart/form-data" novalidate>
                                            <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $customer_id; ?>" />
                                            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                                            <!-- <input type="idden" name="contact_id" id="contact_id" value="<?php echo $contact_id; ?>" /> -->
                                            <?php echo csrf_field(); ?>

                                            <span class="fw-semibold"><?php echo $module_caption;?></span>

                                            <div class="card-body">

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Attention:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="billing_attention" id="billing_attention" value="<?php echo $billing_attention; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Country:</label>
                                                    <div class="col-lg-9">
                                                        <select required class="form-select" name="billing_country" id="billing_country" aria-required="true">
                                                            <option value="0">Please select</option>
                                                            <?php echo getUAECountryDropdown($billing_country); ?>
                                                        </select>
                                                        <div class="invalid-feedback">
                                                            Please select a country
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Address Line 1:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="billing_address_line1" id="billing_address_line1" value="<?php echo $billing_address_line1; ?>" required aria-required="true">
                                                        <div class="invalid-feedback">
                                                            Address Line 1 is required
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Address Line 2:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="billing_address_line2" id="billing_address_line2" value="<?php echo $billing_address_line2; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">City:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="billing_city" id="billing_city" value="<?php echo $billing_city; ?>" required aria-required="true">
                                                        <div class="invalid-feedback">
                                                            City is required
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">State:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="billing_state" id="billing_state" value="<?php echo $billing_state; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Zip Code:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="billing_zipcode" id="billing_zipcode" value="<?php echo $billing_zipcode; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Phone:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="billing_phone" id="billing_phone" value="<?php echo $billing_phone; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Fax Number:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="billing_fax" id="billing_fax" value="<?php echo $billing_fax; ?>">
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
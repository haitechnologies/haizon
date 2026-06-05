<?php

include('admin_elements/admin_header.php');

$module             = 'quotations';
$module_caption     = 'Quotation';
$tbl_name = DB::QUOTATIONS;
$error_message         = '';
$success_message     = '';


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
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

$lead_id = '';
if (isset($_REQUEST['lead_id']))        $lead_id     = e_s__($_REQUEST['lead_id']);
if (isset($_POST['lead_id']))           $lead_id     = e_s__($_POST['lead_id']);


// $q_s = getTableAttr('quotation_status', DB::QUOTATIONS, $id);
// if ($q_s == 'booked') header("Location:listing_$module.php?error_message=quotation is already booked.");;


if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 0;



// ---------------------- quotation Items -----------------------------
$item_id_arr                = array();
$service_arr                = array();
$description_arr            = array();
$qty_arr                    = array();
$rate_arr                   = array();
$sub_total_arr              = array();
// $discount_type_arr          = array();
// $discount_type_value_arr    = array();
// $discount_amount_arr        = array();
$tax_arr                    = array();
$tax_amount_arr             = array();
$total_arr                  = array();


if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows            = e_s__($_POST['total_rows']);
    // if ($total_rows == 0 || $total_rows == '') $total_rows = 1;
} else {
    $total_rows            = 1;
}



if ($action == "update_lead_$module" || $action == "add_lead_$module") {

    for ($quotation_item = 1; $quotation_item <= $total_rows; $quotation_item++) {

        $index = $quotation_item;
        $index = $index - 1;

        $post_item_id       = (isset($_POST['item_id'][$index]) && !empty($_POST['item_id'][$index]) ? $_POST['item_id'][$index] :  0);
        $post_service       = (isset($_POST['service'][$index]) && !empty($_POST['service'][$index]) ? $_POST['service'][$index] :  0);
        $post_description   = (isset($_POST['description'][$index]) && !empty($_POST['description'][$index]) ? $_POST['description'][$index] :  '');
        $post_qty           = (isset($_POST['qty'][$index]) && !empty($_POST['qty'][$index]) ? $_POST['qty'][$index] :  1);
        $post_rate          = (isset($_POST['rate'][$index]) && !empty($_POST['rate'][$index]) ? $_POST['rate'][$index] :  0);
        $post_sub_total     = (isset($_POST['sub_total'][$index]) && !empty($_POST['sub_total'][$index]) ? $_POST['sub_total'][$index] :  0);
        $post_tax           = (isset($_POST['tax'][$index]) && !empty($_POST['tax'][$index]) ? $_POST['tax'][$index] :  0);
        $post_tax_amount    = (isset($_POST['tax_amount'][$index]) && !empty($_POST['tax_amount'][$index]) ? $_POST['tax_amount'][$index] :  0);
        $post_total         = (isset($_POST['total'][$index]) && !empty($_POST['total'][$index]) ? $_POST['total'][$index] :  0);


        array_push($item_id_arr,                e_s__($post_item_id));
        array_push($service_arr,                e_s__($post_service));
        array_push($description_arr,            e_s__($post_description));
        array_push($qty_arr,                    e_s__($post_qty));
        array_push($rate_arr,                   e_s__($post_rate));
        array_push($sub_total_arr,              e_s__($post_sub_total));
        // array_push($discount_type_arr,          e_s__($post_discount_type));
        // array_push($discount_type_value_arr,    e_s__($post_discount_type_value));
        // array_push($discount_amount_arr,        e_s__($post_discount_amount));
        array_push($tax_arr,                    e_s__($post_tax));
        array_push($tax_amount_arr,             e_s__($post_tax_amount));
        array_push($total_arr,                  e_s__($post_total));
    } //for 
}


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_lead_$module" || $action == "add_lead_$module") {
    $quotation_date             = e_s__($_POST['quotation_date']);
    $expiry_date                = e_s__($_POST['expiry_date']);
    $quotation_status           = e_s__($_POST['quotation_status']);
    $subject                    = e_s__($_POST['subject']);
    $warehouse_id               = e_s__($_POST['warehouse_id']);

    $customer_notes             = e_s__($_POST['customer_notes']);
    $terms_and_conditions       = e_s__($_POST['terms_and_conditions']);

    $grand_subtotal             = e_s__($_POST['grand_subtotal']);
    $grand_discount_type        = e_s__($_POST['grand_discount_type']);
    $grand_discount_type_value  = e_s__($_POST['grand_discount_type_value']);
    $grand_discount_amount      = e_s__($_POST['grand_discount_amount']);
    $grand_after_discount       = e_s__($_POST['grand_after_discount']);
    $grand_tax                  = e_s__($_POST['grand_tax']);
    $grand_total                = e_s__($_POST['grand_total']);
} else {
    $quotation_date             = date('d-m-Y', time());
    $expiry_date                = '';
    $quotation_status           = '';
    $subject                    = '';
    $warehouse_id               = '';

    $customer_notes             = '';
    $terms_and_conditions       = '';

    $grand_subtotal             = '';
    $grand_discount_type        = '';
    $grand_discount_type_value  = '';
    $grand_discount_amount      = '';
    $grand_after_discount       = '';
    $grand_tax                  = '';
    $grand_total                = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_lead_$module" && !empty($id)) {

    if (empty($lead_id) || $lead_id == 'Please select') {
        $error_message = 'Please select Lead.';
    } else if (empty($quotation_date)) {
        $error_message = 'Please select quotation Date.';
    } else if (empty($warehouse_id) || $warehouse_id == 'Please select') {
        $error_message = 'Please select warehouse.';
    } else {

        if ($grand_subtotal == '')                      $grand_subtotal = '0.00';
        if ($grand_discount_type == '')                 $grand_discount_type = '0.00';
        if ($grand_discount_type_value == '')           $grand_discount_type_value = '0.00';
        if ($grand_discount_amount == '')               $grand_discount_amount = '0.00';
        if ($grand_after_discount == '')                $grand_after_discount = '0.00';
        if ($grand_tax == '')                           $grand_tax = '0.00';
        if ($grand_total == '')                         $grand_total = '0.00';

        $quotation_date     = processDateDtoY($quotation_date);
        $expiry_date        = (empty($expiry_date) ? '1970-01-01' : processDateDtoY($expiry_date));

        // ---------------------------------------------
        // UPDATE quotation
        // ---------------------------------------------
        $update_row = $mysqli->query("
                                        UPDATE `$tbl_name` SET
                                            quotation_date		        = '" . $quotation_date . "',
                                            expiry_date		            = '" . $expiry_date . "',
                                            customer_id					= '" . $lead_id . "',
                                            quotation_status		    = '" . $quotation_status . "',
                                            subject		                = '" . $subject . "',
                                            warehouse_id		        = '" . $warehouse_id . "',
                                            
                                            customer_notes		        = '" . $customer_notes . "',
                                            terms_and_conditions		= '" . $terms_and_conditions . "',
                                            
                                            grand_subtotal		        = '" . $grand_subtotal . "',
                                            grand_discount_type		    = '" . $grand_discount_type . "',
                                            grand_discount_type_value   = '" . $grand_discount_type_value . "',
                                            grand_discount_amount		= '" . $grand_discount_amount . "',
                                            grand_after_discount		= '" . $grand_after_discount . "',
                                            grand_tax		            = '" . $grand_tax . "',
                                            grand_total		            = '" . $grand_total . "',
                                            
                                            publish 					= '" . $publish . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);

            // Lead Logs
            updateLeadLogs($lead_id, 'quotation', $id, 'updated');

            $quotation_id = $id;
            ///////////////////////////////////////////////////////////

            // -- PROCESS quotation ITEMS - ITNS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                for ($quotation_item = 1; $quotation_item <= $total_rows; $quotation_item++) {

                    $index = $quotation_item;
                    $index = $index - 1;

                    $item_id                        = e_s__($_POST['item_id'][$index]);
                    $item_service                   = e_s__($_POST['service'][$index]);
                    $item_description               = e_s__($_POST['description'][$index]);
                    $item_qty                       = e_s__($_POST['qty'][$index]);
                    $item_rate                      = e_s__($_POST['rate'][$index]);
                    $item_sub_total                 = e_s__($_POST['sub_total'][$index]);
                    $item_tax                       = e_s__($_POST['tax'][$index]);
                    $item_tax_amount                = e_s__($_POST['tax_amount'][$index]);
                    $item_total                     = e_s__($_POST['total'][$index]);


                    // ---------------------------------------------
                    // UPDATE quotation ITEMS
                    // ---------------------------------------------

                    $item_qty           = (($item_qty == '') ? 1 : $item_qty);
                    $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                    $item_sub_total     = (($item_sub_total == '') ? 0 : $item_sub_total);
                    $item_tax           = (($item_tax == '') ? 0 : $item_tax);
                    $item_tax_amount    = (($item_tax_amount == '') ? 0 : $item_tax_amount);
                    $item_total         = (($item_total == '') ? 0 : $item_total);

                    // Process Updated quotation Items
                    if (!empty($item_id) && !empty($item_service)) {

                        $update_row = $mysqli->query("UPDATE `" . DB::QUOTATION_ITEMS . "` SET 
                                                            service         = '" . $item_service . "',
                                                            description     = '" . $item_description . "',
                                                            qty             = '" . $item_qty . "',
                                                            rate            = '" . $item_rate . "',
                                                            sub_total       = '" . $item_sub_total . "',
                                                            tax             = '" . $item_tax . "',
                                                            tax_amount      = '" . $item_tax_amount . "',
                                                            total           = '" . $item_total . "' 
                                                        WHERE id=$item_id");

                        if ($update_row) $updated_row++;
                        fp__(DB::QUOTATION_ITEMS, $item_id);

                        // Process New quotation Items
                    } else if (empty($item_id) && !empty($item_service)) {

                        $insert_row = $mysqli->query("INSERT INTO `" . DB::QUOTATION_ITEMS . "`(quotation_id, service, description, qty, rate, sub_total, tax, tax_amount, total) VALUES ('" . $quotation_id . "', '" . $item_service . "', '" . $item_description . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_sub_total . "', '" . $item_tax . "', '" . $item_tax_amount . "', '" . $item_total . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__(DB::QUOTATION_ITEMS, $mysqli->insert_id);


                        // Process Deleted quotation Items
                    } else if (!empty($item_id) && empty($item_service) && empty($item_rate) && empty($item_tax) && empty($item_total)) {

                        $mysqli->query("DELETE FROM `" . DB::QUOTATION_ITEMS . "` WHERE id=$item_id");
                    }
                    // ---------------------------------------------

                } //for 

            }
            ///////////////////////////////////////////////////////////

            // CHECK IF AT LEAST ONE quotation ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $quotation_date = processDateYtoD($quotation_date);
                $expiry_date    = processDateYtoD($expiry_date);
                $error_message = "Please add at least one quotation Item.";
            } else {
                header("Location:listing_lead_$module.php?lead_id=$lead_id&success_message=$success_message");
            }
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }

        // CHECK IF AT LEAST ONE quotation ITEM IS ADDED
        // if ($inserted_row == 0) {
        //     $success_message = '';
        //     $quotation_date = processDateYtoD($quotation_date);
        //     $error_message = "Please add at least one quotation Item.";
        // } else {
        //     header("Location:listing_lead_$module.php?success_message=$success_message");
        // }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_lead_$module") {

    if (empty($lead_id) || $lead_id == 'Please select') {
        $error_message = 'Please select Lead.';
    } else if (empty($quotation_date)) {
        $error_message = 'Please select quotation Date.';
    } else if (empty($warehouse_id) || $warehouse_id == 'Please select') {
        $error_message = 'Please select warehouse.';
    } else {

        ///////////////////////////////////////////////////////////

        // -- PROCESS quotation ITEMS - ITNS
        if ($total_rows > 0) {

            $inserted_row = 0;

            for ($quotation_item = 1; $quotation_item <= $total_rows; $quotation_item++) {

                $index = $quotation_item;
                $index = $index - 1;

                $item_service                   = e_s__($_POST['service'][$index]);
                $item_description               = e_s__($_POST['description'][$index]);
                $item_qty                       = e_s__($_POST['qty'][$index]);
                $item_rate                      = e_s__($_POST['rate'][$index]);
                $item_sub_total                 = e_s__($_POST['sub_total'][$index]);
                $item_tax                       = e_s__($_POST['tax'][$index]);
                $item_tax_amount                = e_s__($_POST['tax_amount'][$index]);
                $item_total                     = e_s__($_POST['total'][$index]);


                if (!empty($item_service)) {

                    // ---------------------------------------------
                    // SAVE quotation
                    // ---------------------------------------------
                    if ($inserted_row == 0) {

                        if ($grand_subtotal == '')                      $grand_subtotal = '0.00';
                        if ($grand_discount_type == '')                 $grand_discount_type = '0.00';
                        if ($grand_discount_type_value == '')           $grand_discount_type_value = '0.00';
                        if ($grand_discount_amount == '')               $grand_discount_amount = '0.00';
                        if ($grand_after_discount == '')                $grand_after_discount = '0.00';
                        if ($grand_tax == '')                           $grand_tax = '0.00';
                        if ($grand_total == '')                         $grand_total = '0.00';


                        $quotation_date = processDateDtoY($quotation_date);
                        $expiry_date        = (empty($expiry_date) ? '1970-01-01' : processDateDtoY($expiry_date));

                        $item_qty           = (($item_qty == '') ? 1 : $item_qty);
                        $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                        $item_sub_total     = (($item_sub_total == '') ? 0 : $item_sub_total);
                        $item_tax           = (($item_tax == '') ? 0 : $item_tax);
                        $item_tax_amount    = (($item_tax_amount == '') ? 0 : $item_tax_amount);



                        // ======================================================
                        // QUOTATION NO Auto Generation System
                        // ======================================================

                        // Build the prefix for this month
                        $prefix = 'FL-QT' . date('ym');

                        // Get the last quotation number for this month
                        $sql = "SELECT quotation_no  FROM `" . DB::QUOTATIONS . "`  WHERE quotation_no LIKE '{$prefix}-%'ORDER BY quotation_no DESC LIMIT 1";
                        $result = $mysqli->query($sql);

                        if ($row = $result->fetch_assoc()) {
                            // Extract the serial part after the dash
                            $last_serial = (int) substr($row['quotation_no'], -4);
                            $new_serial = $last_serial + 1;
                        } else {
                            // First quotation of the month
                            $new_serial = 1;
                        }

                        // Build new quotation number with zero padding
                        $quotation_no = $prefix . '-' . str_pad($new_serial, 4, '0', STR_PAD_LEFT);
                        // ======================================================


                        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(quotation_no, customer_id, quotation_status, quotation_date, expiry_date, subject, warehouse_id, terms_and_conditions, grand_subtotal, grand_discount_type, grand_discount_type_value, grand_discount_amount, grand_after_discount, customer_notes, grand_tax, grand_total, publish) VALUES ('" . $quotation_no . "', '" . $lead_id . "', '" . $quotation_status . "',  '" . $quotation_date . "', '" . $expiry_date . "', '" . $subject . "', '" . $warehouse_id . "',  '" . $terms_and_conditions . "',   '" . $grand_subtotal . "',  '" . $grand_discount_type . "',  '" . $grand_discount_type_value . "',  '" . $grand_discount_amount . "',  '" . $grand_after_discount . "',   '" . $customer_notes . "',  '" . $grand_tax . "', '" . $grand_total . "', '" . $publish . "'); ");

                        $id = $mysqli->insert_id;
                        // if ($insert_row) {
                        fp__($tbl_name, $id);

                        // Lead Logs
                        updateLeadLogs($lead_id, 'quotation', $id, 'created');

                        $success_message = "The $module_caption has been saved successfully.";
                        $quotation_id = $id;
                    }
                    // ---------------------------------------------


                    // ---------------------------------------------
                    // SAVE quotation ITEMS
                    // ---------------------------------------------

                    $item_qty           = (($item_rate == '') ? 1 : $item_qty);
                    $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                    $item_sub_total     = (($item_sub_total == '') ? 0 : $item_sub_total);
                    $item_tax           = (($item_tax == '') ? 0 : $item_tax);
                    $item_tax_amount    = (($item_tax_amount == '') ? 0 : $item_tax_amount);
                    $item_total         = (($item_total == '') ? 0 : $item_total);

                    $insert_row = $mysqli->query("INSERT INTO `" . DB::QUOTATION_ITEMS . "`(quotation_id, service, description, qty, rate, sub_total, tax, tax_amount, total) VALUES ('" . $quotation_id . "', '" . $item_service . "', '" . $item_description . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_sub_total . "', '" . $item_tax . "', '" . $item_tax_amount . "', '" . $item_total . "'); ");

                    if ($insert_row) $inserted_row++;

                    fp__(DB::QUOTATION_ITEMS, $mysqli->insert_id);
                    // ---------------------------------------------

                }
            } //for 


            // CHECK IF AT LEAST ONE quotation ITEM IS ADDED
            if ($inserted_row == 0) {
                $error_message = "Please add at least one quotation Item.";
            } else {
                header("Location:listing_lead_$module.php?lead_id=$lead_id&success_message=$success_message");
            }
        } // if
        ///////////////////////////////////////////////////////////
        // header("Location:listing_lead_$module.php?success_message=$success_message");
        // } else {
        //     $error_message = "The $module_caption could not be saved. Please try again.";
        //     //header("Location:$module.php?error_message=$error_message");
        // }

    }
}


/*
|--------------------------------------------------------------------------
| EDIT - ONLY SUPERADMIN or RELEVANT USER
|--------------------------------------------------------------------------
|
*/
$created_by = getTableAttr('created_by', DB::QUOTATIONS, $id);

if (
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1')
    ||
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['admin_id'] == $created_by)
) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $lead_id                = s__($row['customer_id']);
    $quotation_no           = s__($row['quotation_no']);
    $quotation_status       = s__($row['quotation_status']);
    $quotation_date         = s__($row['quotation_date']);
    $expiry_date            = s__($row['expiry_date']);
    $quotation_status       = s__($row['quotation_status']);
    $subject                = s__($row['subject']);
    $warehouse_id           = s__($row['warehouse_id']);

    $customer_notes         = s__($row['customer_notes']);
    $terms_and_conditions   = s__($row['terms_and_conditions']);

    $grand_subtotal             = s__($row['grand_subtotal']);
    $grand_discount_type        = s__($row['grand_discount_type']);
    $grand_discount_type_value  = s__($row['grand_discount_type_value']);
    $grand_discount_amount      = s__($row['grand_discount_amount']);
    $grand_after_discount       = s__($row['grand_after_discount']);
    $grand_tax                  = s__($row['grand_tax']);
    $grand_total                = s__($row['grand_total']);

    $publish                = s__($row['publish']);

    $quotation_date = processDateYtoD($quotation_date);
    $expiry_date        = ($expiry_date == '1970-01-01' ? '' : processDateDtoY($expiry_date));


    // ------------------ TOTAL quotation ITEMS ------------------
    $result_quotation_items     = $mysqli->query("SELECT * FROM `" . DB::QUOTATION_ITEMS . "` WHERE quotation_id=$id");
    $total_rows                 = $result_quotation_items->num_rows;


    if ($total_rows > 0) {
        while ($row_quotation_items = $result_quotation_items->fetch_array()) {

            array_push($item_id_arr,                $row_quotation_items['id']);
            array_push($service_arr,                $row_quotation_items['service']);
            array_push($description_arr,            $row_quotation_items['description']);
            array_push($qty_arr,                    $row_quotation_items['qty']);
            array_push($rate_arr,                   $row_quotation_items['rate']);
            array_push($sub_total_arr,              $row_quotation_items['sub_total']);
            // array_push($discount_type_arr,          $row_quotation_items['discount_type']);
            // array_push($discount_type_value_arr,    $row_quotation_items['discount_type_value']);
            // array_push($discount_amount_arr,        $row_quotation_items['discount_amount']);
            array_push($tax_arr,                    $row_quotation_items['tax']);
            array_push($tax_amount_arr,             $row_quotation_items['tax_amount']);
            array_push($total_arr,                  $row_quotation_items['total']);
        }
    }
}


if ($total_rows == 0) $total_rows = 1;


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>


<div class="content-wrapper">


    <form class="steps-basic clearfix" method="post" id="frmlead_<?php echo $module; ?>" name="frmlead_<?php echo $module; ?>" action="lead_<?php echo $module; ?>.php" enctype="multipart/form-data">
        <input type="hidden" name="lead_id" id="lead_id" value="<?php echo $lead_id; ?>" />
        <?php if (($action == "edit_lead_$module" || $action == "update_lead_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_lead_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_lead_<?php echo $module; ?>" />
        <?php } ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="d-flex">
                    <div class="breadcrumb py-2">
                        <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                        <a href="index.php" class="breadcrumb-item">Home</a>
                        <a href="listing_lead_<?php echo $module; ?>.php" class="breadcrumb-item">Quotations</a>
                        <span class="breadcrumb-item active"><?php if (($action == "edit_lead_$module" || $action == "update_lead_$module") && !empty($id)) { ?>Update<?php } else { ?>Create<?php } ?> </span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>


                <?php if (($action == "edit_lead_$module" || $action == "update_lead_$module") && !empty($id)) { ?>
                    <div class="p-3 rounded">
                        <div class="form-check form-check-inline form-switch">
                            <label class="form-check-label fw-semibold" for="sc_r_success">Quotation #: <?php echo $quotation_no; ?></label>
                        </div>
                    </div>
                <?php } ?>

                <div class="p-3 rounded">
                    <div class="form-check form-check-inline form-switch">
                        <label class="form-check-label" for="sc_r_success"> <strong><?php echo ((empty($quotation_status) ? 'Not Confirmed' : colorfulquotationstatus($quotation_status))); ?></strong></label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="button" onclick=" this.form.submit();" class="btn btn-info my-1 me-2"><?php if (($action == "edit_lead_$module" || $action == "update_lead_$module") && !empty($id) && !empty($lead_id)) { ?>Update<?php } else { ?>Save<?php } ?> <?php echo $module_caption; ?> and Exit</button>
                        <button type="button" onclick="window.location.href='listing_lead_<?php echo $module; ?>.php?lead_id=<?php echo $lead_id; ?>';" class=" btn btn-outline-dark my-1 me-2">Exit</button>
                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->


        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>


                <div class="col-xl-12">

                    <div class="row p-lg-2">

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lead Name: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Lead Name" name="" id="" value="<?php echo getTableAttr('display_name', DB::LEADS, $lead_id); ?>" readonly>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Quotation Date: <span class="text-danger">*</span></label>
                                <div class="form-control-feedback form-control-feedback-start">
                                    <input type="text" class="form-control" placeholder="Requested Date" name="quotation_date" id="quotation_date" value="<?php echo $quotation_date; ?>">
                                    <div class="form-control-feedback-icon">
                                        <i class="ph-calendar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Expiry Date:</label>

                                <div class="form-control-feedback form-control-feedback-start">
                                    <input type="text" class="form-control" placeholder="Expiry Date" name="expiry_date" id="expiry_date" value="<?php echo $expiry_date; ?>">
                                    <div class="form-control-feedback-icon">
                                        <i class="ph-calendar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Warehouse: <span class="text-danger">*</span><i class="ph-info ms-2" data-bs-popup="tooltip" data-bs-placement="right" data-bs-original-title="List of Warehouses"></i> </label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select">
                                    <option value='0'>Please select</option>
                                    <?php
                                    $result = $mysqli->query("SELECT * FROM `" . tbl_warehouses  . "` WHERE publish=1");
                                    while ($rows = $result->fetch_array()) {
                                        $warehouse_name = $rows["warehouse_name"];
                                    ?>
                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_lead_$module" && $rows['id'] == $warehouse_id) { ?>selected <?php } else if ($rows['id'] == $warehouse_id) { ?>selected <?php } ?>>
                                            <?php echo $warehouse_name; ?>
                                        </option>
                                    <?php } ?>

                                </select>

                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Subject:</label>
                                <input type="text" class="form-control" placeholder="Subject" name="subject" id="subject" value="<?php echo $subject; ?>">
                            </div>
                        </div>

                        <div class="col-lg-1">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status: </label>
                                <select name="quotation_status" id="quotation_status" class="form-select">
                                    <!-- <option value='0'>Please select</option> -->
                                    <option value="draft" <?php if ($quotation_status == 'draft') { ?>selected <?php } ?>>Draft</option>
                                    <option value="sent" <?php if ($quotation_status == 'sent') { ?>selected <?php } ?>>Sent</option>
                                    <option value="open" <?php if ($quotation_status == 'open') { ?>selected <?php } ?>>Open</option>
                                    <option value="revised" <?php if ($quotation_status == 'revised') { ?>selected <?php } ?>>Revised</option>
                                    <option value="declined" <?php if ($quotation_status == 'declined') { ?>selected <?php } ?>>Declined</option>
                                    <option value="accepted" <?php if ($quotation_status == 'accepted') { ?>selected <?php } ?>>Accepted</option>
                                </select>
                            </div>
                        </div>


                    </div>



                    <div class="col-xl-12">

                        <div class="row mb-2">

                            <div class="col-lg-2">
                                <label class="form-label ms-3 ">ITEM DETAILS <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-3">
                                <label class="form-label ms-4 ">DESCRIPTION</label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-3 ">QUANTITY </label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-4 ">RATE </label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-3 ">SUBTOTAL </label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-1 ">TAX </label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label ms-2 ">TOTAL <span class="text-danger">*</span></label>
                            </div>

                        </div>

                        <div class="card">

                            <div class="row card-body">

                                <div class="col-lg-12">

                                    <?php
                                    // ----------------------------------------------------------------------------
                                    for ($quotation_item = 1; $quotation_item <= $total_rows; $quotation_item++) {
                                        $index = $quotation_item;
                                        $index = $index - 1;

                                        // ----------------------------------------------------------------------------
                                    ?>

                                        <div class="mb-2">
                                            <div class="row mb-3 pb-3" id="row_<?php echo $quotation_item; ?>">


                                                <div class="col-lg-12">
                                                    <div class="row">

                                                        <input type="hidden" name="item_id[]" id="item_id<?php echo $quotation_item; ?>" value="<?php echo (!empty($item_id_arr[$index]) ? $item_id_arr[$index] : ''); ?>">

                                                        <div class="col-lg-2">
                                                            <select class="form-select" name="service[]" id="service<?php echo $quotation_item; ?>" onchange="ajax_populate_item_rate(this.value, <?php echo $quotation_item; ?>); ">
                                                                <option value="0">Please select</option>
                                                                <?php
                                                                $result = $mysqli->query("SELECT * FROM `" . tbl_items . "` WHERE publish=1 AND item_type='services' ORDER BY item_name");
                                                                while ($rows = $result->fetch_array()) {
                                                                    $service_id = $rows['id'];
                                                                ?>
                                                                    <option value="<?php echo $service_id; ?>" <?php echo ((!empty($service_arr[$index]) && $service_arr[$index] == $service_id) ? 'selected="selected"' : ''); ?>>
                                                                        <?php echo $rows['item_name']; ?>
                                                                    </option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>

                                                        <div class="col-lg-3">
                                                            <textarea name="description[]" id="description<?php echo $quotation_item; ?>" rows="2" class="form-control" placeholder="Add a description to your item"><?php echo (!empty($description_arr[$index]) ? $description_arr[$index] : ''); ?></textarea>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <div class="input-group">
                                                                <button type="button" class="btn btn-light btn-icon" onclick="this.parentNode.querySelector('input[type=number]').stepDown(); calculateItemAmount('<?php echo $quotation_item; ?>'); ">

                                                                    <i class="ph-minus ph-sm"></i></button>
                                                                <input class="form-control form-control-number text-center" type="number" name="qty[]" id="qty<?php echo $quotation_item; ?>" value="<?php echo (!empty($qty_arr[$index]) ? $qty_arr[$index] : '1'); ?>" min="1" onkeyup="calculateItemAmount('<?php echo $quotation_item; ?>');" onchange="calculateItemAmount('<?php echo $quotation_item; ?>');">

                                                                <button type="button" class="btn btn-light btn-icon" onclick="this.parentNode.querySelector('input[type=number]').stepUp(); calculateItemAmount('<?php echo $quotation_item; ?>'); "><i class="ph-plus ph-sm"></i></button>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" step="1" name="rate[]" id="rate<?php echo $quotation_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($rate_arr[$index]) ? $rate_arr[$index] : '0'); ?>" onkeyup="calculateItemAmount('<?php echo $quotation_item; ?>');" onchange=" calculateItemAmount('<?php echo $quotation_item; ?>');"> <!--  step="0.1" value="0.0" -->
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input readonly type="number" name="sub_total[]" id="sub_total<?php echo $quotation_item; ?>" min="0" class="form-control bg-light bg-opacity-75 text-end" value="<?php echo (!empty($sub_total_arr[$index]) ? $sub_total_arr[$index] : '0'); ?>"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <select name="tax[]" id="tax<?php echo $quotation_item; ?>" class="form-select" onchange="calculateItemAmount(<?php echo $quotation_item; ?>, this.value); ">
                                                                <?php
                                                                // -----------------------
                                                                for ($i = 0; $i <= 100; $i++) {
                                                                    // -----------------------
                                                                ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo ((!empty($tax_arr[$index]) && $tax_arr[$index] == $i) ? 'selected="selected"' : ''); ?>>
                                                                        <?php echo $i; ?>%
                                                                    </option>
                                                                <?php } // for 
                                                                ?>
                                                            </select>



                                                            <div class="text-center mt-1">
                                                                <span class="badge bg-light text-black" style="font-weight: normal;" id="div_tax_amount<?php echo $quotation_item; ?>" style="display: <?php if (!empty($tax_arr[$index])) { ?> block <?php } else { ?> none <?php } ?>;">
                                                                    <span id="span_tax_amount<?php echo $quotation_item; ?>">
                                                                        <?php echo (!empty($tax_amount_arr[$index]) ? $tax_amount_arr[$index] : '0'); ?>
                                                                    </span>
                                                                </span>
                                                            </div>

                                                            <input type="hidden" name="tax_amount[]" id="tax_amount<?php echo $quotation_item; ?>" class="form-control" placeholder="0" value="<?php echo (!empty($tax_amount_arr[$index]) ? $tax_amount_arr[$index] : '0'); ?>">
                                                            <!-- <div class="form-text bg-light border border-top-0 rounded-bottom text-end px-2 py-1 mt-0">15,584</div> -->
                                                        </div>


                                                        <div class="col-lg-1">
                                                            <input readonly type="number" name="total[]" id="total<?php echo $quotation_item; ?>" min="0" class="form-control bg-light bg-opacity-75 text-end" placeholder="0" value="<?php echo (!empty($total_arr[$index]) ? $total_arr[$index] : ''); ?>" onchange="calculateGrand(<?php echo $quotation_item; ?>);" onkeyup="calculateGrand(<?php echo $quotation_item; ?>);"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-2 mt-1">
                                                            <?php if ($quotation_item > 1) { ?>
                                                                <a href="#" onclick="calculateItemAmount('<?php echo $quotation_item; ?>'); clear_row(<?php echo $quotation_item; ?>)"><span class="badge bg-warning"> <i class="ph-x"></i> </span></a>
                                                            <?php } ?>
                                                        </div>

                                                    </div>
                                                </div>


                                            </div>

                                        </div>

                                    <?php
                                        // -------------------------------------------------- 
                                    } // for 
                                    // -------------------------------------------------- 
                                    ?>

                                    <div id="add_row_here"></div>
                                </div>

                                <div class="">
                                    <span id="span_add_item_row<?php echo $quotation_item; ?>"><a href="#" onclick="add_item_row(); "><span class="badge bg-primary"> Add New Row </a></span></span>
                                </div>


                                <!-- </div> -->


                                <script>
                                    function add_item_row() {
                                        var div_add_here = document.getElementById('div_add_here');
                                        var total_rows = document.getElementById('total_rows').value;
                                        total_rows++;

                                        var new_row = "";

                                        new_row += "<div class=\"row mb-3 pb-3\" id=\"row_" + total_rows + "\">";
                                        new_row += "<input type=\"hidden\" name=\"item_id[]\" id=\"item_id" + total_rows + "\">";

                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<select class=\"form-select\" onchange=\"ajax_populate_item_rate(this.value, " + total_rows + "); \" name=\"service[]\" id=\"service" + total_rows + "\">";
                                        new_row += "<option value=\"0\">Please select</option>";
                                        new_row += "</select>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-3\">";
                                        new_row += "<textarea type=\"text\" name=\"description[]\" id=\"description" + total_rows + "\" rows=\"2\" min=\"0\" placeholder=\"Add a description to your item\" class=\"form-control\"></textarea>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<div class=\"input-group\">";
                                        new_row += "<button type=\"button\" class=\"btn btn-light btn-icon\" onclick=\"this.parentNode.querySelector('input[type=number]').stepDown(); calculateItemAmount('" + total_rows + "'); \"><i class=\"ph-minus ph-sm\"></i></button>";
                                        new_row += "<input class=\"form-control form-control-number text-center\" type=\"number\" name=\"qty[]\" id=\"qty" + total_rows + "\" value=\"1\" min=\"1\" onkeyup=\"calculateItemAmount('" + total_rows + "');\" onchange=\"calculateItemAmount('" + total_rows + "');\">";
                                        new_row += "<button type=\"button\" class=\"btn btn-light btn-icon\" onclick=\"this.parentNode.querySelector('input[type=number]').stepUp(); calculateItemAmount('" + total_rows + "'); \"><i class=\"ph-plus ph-sm\"></i></button>";
                                        new_row += "</div>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"rate[]\" id=\"rate" + total_rows + "\" min=\"0\" class=\"form-control text-center\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input readonly type=\"number\" name=\"sub_total[]\" id=\"sub_total" + total_rows + "\" min=\"0\" class=\"form-control bg-light bg-opacity-75 text-end\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<select name=\"tax[]\" id=\"tax" + total_rows + "\" class=\"form-select\" onchange=\"calculateItemAmount(" + total_rows + ", this.value);\">";
                                        // -----------------------
                                        for (i = 0; i <= 100; i++) {
                                            // -----------------------
                                            new_row += "<option value=" + i + ">" + i + "%</option>";
                                        } // for
                                        new_row += "</select>";

                                        new_row += "<div class=\"text-center mt-1\">";
                                        new_row += "<span class=\"badge bg-light text-black\" style=\"font-weight: normal;\" id=\"div_tax_amount" + total_rows + "\" style=\"display:none; \">";
                                        // new_row += "Tax:";
                                        // new_row += "<span id=\"span_tax_amount" + total_rows + "\" > < /span>";
                                        // new_row += "</span>";
                                        new_row += "</div>";

                                        new_row += "<input type=\"hidden\" name=\"tax_amount[]\" id=\"tax_amount" + total_rows + "\" class=\"form-control\" placeholder=\"0\" value=\"0\">";

                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input readonly type=\"number\" name=\"total[]\" id=\"total" + total_rows + "\" min=\"0\" class=\"form-control bg-light bg-opacity-75 text-end\" placeholder=\"0\">";
                                        new_row += "</div>";

                                        // new_row += "</div><div class=\"col-lg-1 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" onclick=\"clear_row(" + total_rows + ")\"></span></div>";

                                        new_row += "<div class=\"col-lg-1 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" onclick=\"clear_row(" + total_rows + ")\"><span class=\"badge bg-warning\"> <i class=\"ph-x\"></i> </span></a></span> </div>";

                                        new_row += "</div>";

                                        // document.getElementById('add_row_here').innerHTML += new_row;

                                        // This is to preserve the values of previously dynamicall created elements
                                        document.getElementById('add_row_here').insertAdjacentHTML("beforebegin", new_row);

                                        document.getElementById('total_rows').value = total_rows;

                                        ajax_populate_services();

                                    }


                                    function clear_row(row_no) {

                                        calculateItemAmount(row_no);

                                        document.getElementById('service' + row_no).value = '0';
                                        document.getElementById('service' + row_no).text = 'Please select';
                                        document.getElementById('description' + row_no).value = '';
                                        document.getElementById('qty' + row_no).value = '';
                                        document.getElementById('rate' + row_no).value = '';
                                        document.getElementById('sub_total' + row_no).value = '';
                                        document.getElementById('tax' + row_no).value = '';
                                        document.getElementById('tax_amount' + row_no).value = '';
                                        document.getElementById('total' + row_no).value = '';

                                        document.getElementById('row_' + row_no).style.display = 'none';

                                    }

                                    function percentage(num, percentage) {
                                        const result = num * (percentage / 100);
                                        return parseFloat(result.toFixed(3));
                                    }
                                    // const percntVal = percentage(1, 5);
                                    // console.log(percntVal);


                                    // -------------------------------------------------------------------------
                                    //  CALCULATE AMOUNT + TAX
                                    // -------------------------------------------------------------------------
                                    function calculateItemAmount(row_no) {

                                        // console.log(row_no);
                                        clearGrandDiscountTypeValue(); // REMOVE GRAND DISCOUNT

                                        let service = document.getElementById('service' + row_no);
                                        let service_value = service.options[service.selectedIndex].value;
                                        // let service_text = service.options[service.selectedIndex].text;
                                        // console.log("Service " + row_no + " text:", service_text);

                                        if (service_value != NaN && service_value != '' && service_value != 'undefined' && service_value != '0') {

                                            // ---  Calculate Item Qty ------------------
                                            var qty = document.getElementById('qty' + row_no).value;
                                            qty = Number(qty);

                                            var rate = document.getElementById('rate' + row_no).value;


                                            // --- Calculate Sub Total
                                            var sub_total = parseFloat(rate * qty).toFixed(2);
                                            document.getElementById('sub_total' + row_no).value = parseFloat(sub_total);

                                            //  ---  Calculate Item Tax ------------------
                                            var tax = document.getElementById('tax' + row_no).value;
                                            let tax_amount = percentage(sub_total, tax).toFixed(2);

                                            if (rate > 0 && tax > 0) {
                                                // console.log('i m in' + tax_amount);

                                                document.getElementById('div_tax_amount' + row_no).style.display = 'block';
                                                // document.getElementById('span_tax_amount' + row_no).style.display = 'block';
                                                document.getElementById('div_tax_amount' + row_no).innerHTML = 'Tax ' + parseFloat(tax_amount);
                                                document.getElementById('tax_amount' + row_no).value = parseFloat(tax_amount); // Hidden Value

                                                document.getElementById('total' + row_no).value = parseFloat(sub_total) + parseFloat(tax_amount);

                                            } else {
                                                // console.log('i m out');
                                                // document.getElementById('span_tax_amount' + row_no).innerHTML = '';
                                                document.getElementById('div_tax_amount' + row_no).style.display = 'none';
                                                document.getElementById('tax_amount' + row_no).value = '0'; // Hidden Value

                                                document.getElementById('total' + row_no).value = parseFloat(sub_total);
                                            }

                                            calculateGrand();

                                        } // if


                                    } // function




                                    // -------------------------------------------------------------------------
                                    //  GRAND CALCULATIONS
                                    // -------------------------------------------------------------------------
                                    function calculateGrand() {

                                        // ------ GRAND CALCULATIONS
                                        var total_rows = document.getElementById('total_rows').value;

                                        // --- Grand Subttotal
                                        var final_total = 0;
                                        for (var i = 1; i <= total_rows; i++) {
                                            var total = document.getElementById('total' + i).value;
                                            final_total += Number(total);
                                        } // for

                                        // --- Grand Subttotal
                                        document.getElementById('grand_subtotal').value = parseFloat(final_total.toFixed(2));

                                        // ---------------------------------------------
                                        //  CALCULATE GRAND DISCOUNT FIXED
                                        // ---------------------------------------------
                                        var apply_discount = false;


                                        var e = document.getElementById('grand_discount_type');
                                        var grand_discount_type = e.value;



                                        var grand_subtotal = document.getElementById('grand_subtotal').value;
                                        grand_subtotal = parseFloat(grand_subtotal);


                                        // ------------- VALIDATE DISCOUNT VALUE -------------
                                        var grand_discount_type_value = document.getElementById('grand_discount_type_value').value;

                                        if (grand_discount_type_value == '' || grand_discount_type_value == 'undefined' || grand_discount_type_value == 'NULL') {
                                            grand_discount_type_value = '0';
                                        } else {
                                            grand_discount_type_value = parseFloat(grand_discount_type_value);
                                        }
                                        // ----------------------------------------------------

                                        if (grand_discount_type == 'fixed') {

                                            if (grand_subtotal == 0) {
                                                // DO NOTHING

                                            } else if (grand_discount_type_value > grand_subtotal) {
                                                alert('Grand Discount cannot be greater than Grand Sub Total.');
                                                document.getElementById('grand_discount_type_value').value = '0';
                                                document.getElementById('grand_total').value = document.getElementById('grand_subtotal').value;


                                            } else if (grand_discount_type_value <= grand_subtotal) {
                                                var recalculated_grand_total = (parseFloat(grand_subtotal) - parseFloat(grand_discount_type_value));

                                                document.getElementById('grand_discount_amount').value = parseFloat(grand_discount_type_value);
                                                apply_discount = true;

                                            }


                                            // ---------------------------------------------
                                            //  CALCULATE DISCOUNT PERCENTAGE
                                            // ---------------------------------------------
                                        } else if (grand_discount_type == 'percent') {



                                            if (grand_discount_type_value > 100) {
                                                // alert('Discount Percentage cannot be greater than 100.');
                                                document.getElementById('grand_discount_type_value').value = 0;


                                            } else if (grand_discount_type_value <= 100) {

                                                var percntVal = percentage(grand_subtotal, grand_discount_type_value); // amount, percent
                                                document.getElementById('grand_discount_amount').value = parseFloat(percntVal.toFixed(2));

                                                var recalculated_total = (parseFloat(grand_subtotal) - parseFloat(grand_discount_type_value));
                                                var grand_after_discount = parseFloat(grand_subtotal.toFixed(2)) - parseFloat(percntVal.toFixed(2));
                                                document.getElementById('grand_total').value = parseFloat(grand_after_discount.toFixed(2));
                                                apply_discount = true;

                                            }


                                            // ---------------------------------------------
                                            //  REMOVE DISCOUNT FROM TOTAL
                                            // ---------------------------------------------

                                        } else {
                                            document.getElementById('grand_discount_type_value').value = '';
                                            var grand_tax = document.getElementById('grand_tax').value;
                                            var grand_subtotal = document.getElementById('grand_subtotal').value;
                                            var grand_total = parseFloat(grand_subtotal) + parseFloat(grand_tax);
                                            document.getElementById('grand_total').value = parseFloat(grand_total.toFixed(2));

                                        }


                                        // APPLY DISCOUNT
                                        if (apply_discount == true) {

                                            var grand_discount_amount = document.getElementById('grand_discount_amount').value;
                                            final_total = parseFloat(final_total) - parseFloat(grand_discount_amount);


                                            // console.log(grand_discount_amount);
                                            document.getElementById('grand_after_discount').value = parseFloat(final_total.toFixed(2));

                                        }


                                        // ---------------------------------------------
                                        // CALCUALTE GRAND TAX
                                        // ---------------------------------------------
                                        // --- Grand Subttotal
                                        var total_tax = 0;
                                        for (var i = 1; i <= total_rows; i++) {
                                            var tax_amount = document.getElementById('tax_amount' + i).value;
                                            total_tax += Number(tax_amount);
                                        } // for


                                        // var percntVal = percentage(final_total, '5'); // amount, percent
                                        // percntVal = parseFloat(percntVal.toFixed(2));
                                        // var total_rows = document.getElementById('total_rows').value;

                                        //  CALCULATE GRAND TOTAL
                                        // var total_tax = percntVal;
                                        document.getElementById('grand_tax').value = parseFloat(total_tax.toFixed(2));

                                        var grand_subtotal = Number(final_total);
                                        var grand_total = parseFloat(grand_subtotal) + parseFloat(total_tax);

                                        document.getElementById('grand_total').value = parseFloat(grand_total.toFixed(2));

                                    }


                                    function clearGrandDiscountTypeValue() {
                                        // document.getElementById('grand_discount_type').value = '';
                                        document.getElementById('grand_discount_type_value').value = '';
                                        document.getElementById('grand_discount_amount').value = '';
                                        document.getElementById('grand_after_discount').value = '';
                                    }
                                </script>

                                <input type="hidden" name="total_rows" id="total_rows" value="<?php echo $total_rows; ?>">


                            </div>
                        </div>
                    </div>


                    <div class="row">

                        <div class="col-lg-4">

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">Customer Notes:</label>
                                        <textarea class="form-control" name="customer_notes" id="customer_notes" style="field-sizing: content;" placeholder=""><?php echo $customer_notes; ?></textarea>
                                        <!-- Looking forward for your business -->
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">Terms & Conditions: </label>
                                        <textarea class="form-control" name="terms_and_conditions" id="terms_and_conditions" style="field-sizing: content;" placeholder=""><?php echo $terms_and_conditions; ?></textarea>
                                        <!-- Enter the terms and conditions of your business to be displayed in your transaction. -->
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-4">
                        </div>

                        <div class="col-lg-4">
                            <div class="card ">

                                <div class="card-body"> <!--  bg-info bg-opacity-10 -->

                                    <div class="row mb-1">
                                        <!-- <label class="col-lg-6 col-form-label fw-semibold">Grand Subtotal: (VAT Excluded)</label> -->
                                        <label class="col-lg-6 col-form-label fw-semibold">Grand Subtotal:</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" placeholder="0" name="grand_subtotal" id="grand_subtotal" value="<?php echo $grand_subtotal; ?>" />
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row mb-1">
                                        <label class="col-lg-3 col-form-label">Discount Type: </label>
                                        <div class="col-lg-3">
                                            <div class="mb-3 mb-sm-0">
                                                <select name="grand_discount_type" id="grand_discount_type" class="form-select" onchange="clearGrandDiscountTypeValue(); calculateGrand();">
                                                    <option value='0'></option>
                                                    <option value="percent" <?php if ($grand_discount_type == 'percent') { ?>selected <?php } ?>>Percent %</option>
                                                    <option value="fixed" <?php if ($grand_discount_type == 'fixed') { ?>selected <?php } ?>>Fixed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <input type="number" min="0" step="any" class="form-control" name="grand_discount_type_value" id="grand_discount_type_value" value="<?php echo $grand_discount_type_value; ?>" placeholder="0" onkeyup="calculateGrand();" onchange="calculateGrand();">
                                        </div>
                                    </div>


                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label">Discount Amount</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control bg-light bg-opacity-50 text-end" name="grand_discount_amount" id="grand_discount_amount" value="<?php echo $grand_discount_amount; ?>" placeholder="0">
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label">Subtotal: (Discounted)</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control bg-light bg-opacity-50 text-end" name="grand_after_discount" id="grand_after_discount" value="<?php echo $grand_after_discount; ?>" placeholder="0">
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label">Total Tax Amount</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control bg-light bg-opacity-50 text-end" name="grand_tax" id="grand_tax" value="<?php echo $grand_tax; ?>" placeholder="0">
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Grand Total</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" name="grand_total" id="grand_total" value="<?php echo $grand_total; ?>" readonly>
                                            </div>
                                        </div>
                                    </div>

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


<?php include('admin_elements/admin_footer.php'); ?>
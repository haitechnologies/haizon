<?php

include('admin_elements/admin_header.php');

$module             = 'shipping_invoices';
$module_caption     = 'Shipping Invoice';
$tbl_name = DB::SHIPPING_INVOICES;
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


// print_r($_REQUEST);


if (isset($_REQUEST['customer_id']) && !empty($_REQUEST['customer_id'])) {
    $customer_id     = e_s__($_REQUEST['customer_id']);
} else {
    $customer_id = 0;
}



if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 0;



// ---------------------- Shipping Invoice Items -----------------------------
$item_id_arr                = array();
$description_arr            = array();
$coo_arr                    = array();
$declaration_no_arr         = array();
$hscode_arr                 = array();
$qty_arr                    = array();
$rate_arr                   = array();
$total_arr                  = array();


if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows            = e_s__($_POST['total_rows']);
    // if ($total_rows == 0 || $total_rows == '') $total_rows = 1;
} else {
    $total_rows            = 1;
}



if ($action == "update_$module" || $action == "add_$module") {

    for ($shipping_invoice_item = 1; $shipping_invoice_item <= $total_rows; $shipping_invoice_item++) {

        $index = $shipping_invoice_item;
        $index = $index - 1;

        $post_item_id           = (isset($_POST['item_id'][$index]) && !empty($_POST['item_id'][$index]) ? $_POST['item_id'][$index] :  0);
        $post_description       = (isset($_POST['description'][$index]) && !empty($_POST['description'][$index]) ? $_POST['description'][$index] :  '');
        $post_coo               = (isset($_POST['coo'][$index]) && !empty($_POST['coo'][$index]) ? $_POST['coo'][$index] :  0);
        $post_declaration_no    = (isset($_POST['declaration_no'][$index]) && !empty($_POST['declaration_no'][$index]) ? $_POST['declaration_no'][$index] :  '');
        $post_hscode            = (isset($_POST['hscode'][$index]) && !empty($_POST['hscode'][$index]) ? $_POST['hscode'][$index] :  '');
        $post_qty               = (isset($_POST['qty'][$index]) && !empty($_POST['qty'][$index]) ? $_POST['qty'][$index] :  1);
        $post_rate              = (isset($_POST['rate'][$index]) && !empty($_POST['rate'][$index]) ? $_POST['rate'][$index] :  0);
        $post_total             = (isset($_POST['total'][$index]) && !empty($_POST['total'][$index]) ? $_POST['total'][$index] :  0);


        array_push($item_id_arr,                e_s__($post_item_id));
        array_push($description_arr,            e_s__($post_description));
        array_push($coo_arr,                    e_s__($post_coo));
        array_push($declaration_no_arr,         e_s__($post_declaration_no));
        array_push($hscode_arr,                 e_s__($post_hscode));
        array_push($qty_arr,                    e_s__($post_qty));
        array_push($rate_arr,                   e_s__($post_rate));
        array_push($total_arr,                  e_s__($post_total));
    } //for 
}


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $invoice_date               = e_s__($_POST['invoice_date']);
    $customer_id                = e_s__($_POST['customer_id']);
    $invoice_status             = e_s__($_POST['invoice_status']);
    $invoice_no                 = e_s__($_POST['invoice_no']);
    $warehouse_id               = e_s__($_POST['warehouse_id']);

    $pkgs                       = e_s__($_POST['pkgs']);
    $weight                     = e_s__($_POST['weight']);
    $awb                        = e_s__($_POST['awb']);

    $grand_total                = e_s__($_POST['grand_total']);
} else {
    $invoice_date               = date('d-m-Y', time());
    $invoice_status             = '';
    $invoice_no                 = '';
    $warehouse_id               = '';

    $pkgs                       = '';
    $weight                     = '';
    $awb                        = '';

    $grand_total                = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {

    if (empty($customer_id) || $customer_id == 'Please select') {
        $error_message = 'Please select Customer.';
    } else if (empty($invoice_date)) {
        $error_message = 'Please select Invoice Date.';
    } else if (empty($warehouse_id) || $warehouse_id == 'Please select') {
        $error_message = 'Please select warehouse.';
    } else if (empty($pkgs)) {
        $error_message = 'PLT/BOX/PKGs is mandatory.';
    } else if (empty($weight)) {
        $error_message = 'Weight is mandatory.';
    } else if (empty($awb)) {
        $error_message = 'AWB is mandatory.';
    } else {

        if ($grand_total == '')                         $grand_total = '0.00';

        $invoice_date     = processDateDtoY($invoice_date);

        // ---------------------------------------------
        // UPDATE SHIPPPING INVOICE
        // ---------------------------------------------
        $update_row = $mysqli->query("
                                        UPDATE `$tbl_name` SET
                                            invoice_date		        = '" . $invoice_date . "',
                                            customer_id					= '" . $customer_id . "',
                                            invoice_status		        = '" . $invoice_status . "',
                                            invoice_no		            = '" . $invoice_no . "',
                                            warehouse_id		        = '" . $warehouse_id . "',
                                            
                                            pkgs		                = '" . $pkgs . "',
                                            weight		                = '" . $weight . "',
                                            awb		                    = '" . $awb . "',
                                            
                                            grand_total		            = '" . $grand_total . "',
                                            
                                            publish 					= '" . $publish . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            $invoice_id = $id;
            ///////////////////////////////////////////////////////////

            // -- PROCESS SHIPPING INVOICE ITEMS - ITNS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                for ($shipping_invoice_item = 1; $shipping_invoice_item <= $total_rows; $shipping_invoice_item++) {

                    $index = $shipping_invoice_item;
                    $index = $index - 1;

                    $item_id                        = e_s__($_POST['item_id'][$index]);
                    $item_description               = e_s__($_POST['description'][$index]);
                    $item_coo                       = e_s__($_POST['coo'][$index]);
                    $item_declaration_no            = e_s__($_POST['declaration_no'][$index]);
                    $item_hscode                    = e_s__($_POST['hscode'][$index]);
                    $item_qty                       = e_s__($_POST['qty'][$index]);
                    $item_rate                      = e_s__($_POST['rate'][$index]);
                    $item_total                     = e_s__($_POST['total'][$index]);


                    // ---------------------------------------------
                    // UPDATE SHIPPING INVOICE ITEMS
                    // ---------------------------------------------

                    $item_qty           = (($item_qty == '') ? 1 : $item_qty);
                    $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                    $item_total         = (($item_total == '') ? 0 : $item_total);

                    // Process Updated Shipping Invoice Items
                    if (!empty($item_id) && !empty($item_description) && !empty($item_coo) && !empty($item_declaration_no) && !empty($item_hscode) && !empty($item_qty) && !empty($item_rate) && !empty($item_total)) {

                        $update_row = $mysqli->query("UPDATE `" . DB::SHIPPING_INVOICE_ITEMS . "` SET 
                                                            description     = '" . $item_description . "',
                                                            coo             = '" . $item_coo . "',
                                                            declaration_no  = '" . $item_declaration_no . "',
                                                            hscode          = '" . $item_hscode . "',
                                                            qty             = '" . $item_qty . "',
                                                            rate            = '" . $item_rate . "',
                                                            total           = '" . $item_total . "' 
                                                        WHERE id=$item_id");

                        if ($update_row) $updated_row++;
                        fp__(DB::SHIPPING_INVOICE_ITEMS, $item_id);

                        // Process New Shipping Invoice Items
                    } else if (empty($item_id) && !empty($item_description) && !empty($item_coo) && !empty($item_declaration_no) && !empty($item_hscode) && !empty($item_qty) && !empty($item_rate) && !empty($item_total)) {

                        $insert_row = $mysqli->query("INSERT INTO `" . DB::SHIPPING_INVOICE_ITEMS . "`(invoice_id, description, coo, declaration_no, hscode, qty, rate, total) VALUES ('" . $invoice_id . "', '" . $item_description . "', '" . $item_coo . "', '" . $item_declaration_no . "', '" . $item_hscode . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_total . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__(DB::SHIPPING_INVOICE_ITEMS, $mysqli->insert_id);

                        // Process Deleted Shipping Invoice Items
                    } else if (!empty($item_id) && empty($item_description) && empty($item_coo) && empty($item_rate) && empty($item_total)) {

                        $mysqli->query("DELETE FROM `" . DB::SHIPPING_INVOICE_ITEMS . "` WHERE id=$item_id");
                    }
                    // ---------------------------------------------

                } //for 

            }
            ///////////////////////////////////////////////////////////

            // CHECK IF AT LEAST ONE SHIPPING INVOICE ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $invoice_date = processDateYtoD($invoice_date);
                $error_message = "Please add at least one Invoice Item.";
            } else {
                header("Location:listing_$module.php?success_message=$success_message");
            }
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }

        // CHECK IF AT LEAST ONE SHIPPING INVOICE ITEM IS ADDED
        // if ($inserted_row == 0) {
        //     $success_message = '';
        //     $invoice_date = processDateYtoD($invoice_date);
        //     $error_message = "Please add at least one Shipping Invoice Item.";
        // } else {
        //     header("Location:listing_$module.php?success_message=$success_message");
        // }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module") {

    if (empty($customer_id) || $customer_id == 'Please select') {
        $error_message = 'Please select Customer.';
    } else if (empty($invoice_date)) {
        $error_message = 'Please select Invoice Date.';
    } else if (empty($warehouse_id) || $warehouse_id == 'Please select') {
        $error_message = 'Please select warehouse.';
    } else if (empty($pkgs)) {
        $error_message = 'PLT/BOX/PKGs is mandatory.';
    } else if (empty($weight)) {
        $error_message = 'Weight is mandatory.';
    } else if (empty($awb)) {
        $error_message = 'AWB is mandatory.';
    } else {

        ///////////////////////////////////////////////////////////

        // -- PROCESS SHIPPING INVOICE ITEMS - ITNS
        if ($total_rows > 0) {

            $inserted_row = 0;

            for ($shipping_invoice_item = 1; $shipping_invoice_item <= $total_rows; $shipping_invoice_item++) {

                $index = $shipping_invoice_item;
                $index = $index - 1;

                $item_description               = e_s__($_POST['description'][$index]);
                $item_coo                       = e_s__($_POST['coo'][$index]);
                $item_declaration_no            = e_s__($_POST['declaration_no'][$index]);
                $item_hscode                    = e_s__($_POST['hscode'][$index]);
                $item_qty                       = e_s__($_POST['qty'][$index]);
                $item_rate                      = e_s__($_POST['rate'][$index]);
                $item_total                     = e_s__($_POST['total'][$index]);


                if (!empty($item_description)) {

                    // ---------------------------------------------
                    // SAVE SHIPPING INVOICE
                    // ---------------------------------------------
                    if ($inserted_row == 0) {

                        if ($grand_total == '')                         $grand_total = '0.00';


                        $invoice_date   = processDateDtoY($invoice_date);

                        $item_qty           = (($item_qty == '') ? 1 : $item_qty);
                        $item_rate          = (($item_rate == '') ? 0 : $item_rate);


                        // ======================================================
                        // INVOICE NO Auto Generation System
                        // ======================================================

                        // Build the prefix for this month
                        $prefix = 'FL-INS' . date('ym');

                        // Get the last invoice number for this month
                        $sql = "SELECT invoice_no  FROM `" . DB::SHIPPING_INVOICES . "`  WHERE invoice_no LIKE '{$prefix}-%'ORDER BY invoice_no DESC LIMIT 1";
                        $result = $mysqli->query($sql);

                        if ($row = $result->fetch_assoc()) {
                            // Extract the serial part after the dash
                            $last_serial = (int) substr($row['invoice_no'], -4);
                            $new_serial = $last_serial + 1;
                        } else {
                            // First invoice of the month
                            $new_serial = 1;
                        }

                        // Build new invoice number with zero padding
                        $invoice_no = $prefix . '-' . str_pad($new_serial, 4, '0', STR_PAD_LEFT);
                        // ======================================================


                        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(customer_id, invoice_status, invoice_date, invoice_no, warehouse_id, weight, awb, pkgs, grand_total, publish) VALUES ('" . $customer_id . "', '" . $invoice_status . "',  '" . $invoice_date . "', '" . $invoice_no . "', '" . $warehouse_id . "',  '" . $weight . "',  '" . $awb . "',  '" . $pkgs . "', '" . $grand_total . "', '" . $publish . "'); ");

                        $id = $mysqli->insert_id;
                        // if ($insert_row) {
                        fp__($tbl_name, $id);
                        $success_message = "The $module_caption has been saved successfully.";
                        $invoice_id = $id;
                    }
                    // ---------------------------------------------


                    // ---------------------------------------------
                    // SAVE invoice ITEMS
                    // ---------------------------------------------

                    $item_declaration_no    = (($item_declaration_no == '') ? 1 : $item_declaration_no);
                    $item_hscode            = (($item_hscode == '') ? 1 : $item_hscode);
                    $item_qty               = (($item_qty == '') ? 1 : $item_qty);
                    $item_rate              = (($item_rate == '') ? 0 : $item_rate);
                    $item_total             = (($item_total == '') ? 0 : $item_total);


                    $insert_row = $mysqli->query("INSERT INTO `" . DB::SHIPPING_INVOICE_ITEMS . "`(invoice_id, description, coo, declaration_no, hscode, qty, rate, total) VALUES ('" . $invoice_id . "', '" . $item_description . "', '" . $item_coo . "', '" . $item_declaration_no . "', '" . $item_hscode . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_total . "'); ");

                    if ($insert_row) $inserted_row++;

                    fp__(DB::SHIPPING_INVOICE_ITEMS, $mysqli->insert_id);
                    // ---------------------------------------------

                }
            } //for 


            // CHECK IF AT LEAST ONE SHIPPING INVOICE ITEM IS ADDED
            if ($inserted_row == 0) {
                $error_message = "Please add at least one Invoice Item.";
            } else {
                header("Location:listing_$module.php?success_message=$success_message");
            }
        } // if
        ///////////////////////////////////////////////////////////
        // header("Location:listing_$module.php?success_message=$success_message");
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
$created_by = getTableAttr('created_by', DB::SHIPPING_INVOICES, $id);

if (
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1')
    ||
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['admin_id'] == $created_by)
) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $customer_id            = s__($row['customer_id']);
    $invoice_no             = s__($row['invoice_no']);
    $invoice_status         = s__($row['invoice_status']);
    $invoice_date           = s__($row['invoice_date']);
    $warehouse_id           = s__($row['warehouse_id']);

    $pkgs                   = s__($row['pkgs']);
    $weight                 = s__($row['weight']);
    $awb                    = s__($row['awb']);

    $grand_total                = s__($row['grand_total']);

    $publish                = s__($row['publish']);

    $invoice_date = processDateYtoD($invoice_date);

    // ------------------ TOTAL SHIPPING INVOICE ITEMS ------------------
    $result_shipping_invoice_items     = $mysqli->query("SELECT * FROM `" . DB::SHIPPING_INVOICE_ITEMS . "` WHERE invoice_id=$id");
    $total_rows                 = $result_shipping_invoice_items->num_rows;


    if ($total_rows > 0) {
        while ($row_shipping_invoice_items = $result_shipping_invoice_items->fetch_array()) {

            array_push($item_id_arr,                $row_shipping_invoice_items['id']);
            array_push($description_arr,            $row_shipping_invoice_items['description']);
            array_push($coo_arr,                    $row_shipping_invoice_items['coo']);
            array_push($declaration_no_arr,         $row_shipping_invoice_items['declaration_no']);
            array_push($hscode_arr,                 $row_shipping_invoice_items['hscode']);
            array_push($qty_arr,                    $row_shipping_invoice_items['qty']);
            array_push($rate_arr,                   $row_shipping_invoice_items['rate']);
            array_push($total_arr,                  $row_shipping_invoice_items['total']);
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


    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="d-flex">
                    <div class="breadcrumb py-2">
                        <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                        <a href="index.php" class="breadcrumb-item">Home</a>
                        <a href="listing_<?php echo $module; ?>.php" class="breadcrumb-item">Invoices</a>
                        <span class="breadcrumb-item active"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Create<?php } ?> </span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>


                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <div class="p-3 rounded">
                        <div class="form-check form-check-inline form-switch">
                            <label class="form-check-label fw-semibold" for="sc_r_success">Invoice #: <?php echo $invoice_no; ?></label>
                        </div>
                    </div>
                <?php } ?>

                <div class="p-3 rounded">
                    <div class="form-check form-check-inline form-switch">
                        <label class="form-check-label" for="sc_r_success"> <strong><?php echo ((empty($invoice_status) ? 'Not Confirmed' : colorfulInvoiceStatus($invoice_status))); ?></strong></label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="button" onclick=" this.form.submit();" class="btn btn-info my-1 me-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Save<?php } ?> <?php echo $module_caption; ?> and Exit</button>
                        <button type="button" onclick="window.location.href='listing_<?php echo $module; ?>.php';"" class=" btn btn-outline-dark my-1 me-2">Exit</button>
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
                                <label class="form-label fw-semibold">Customer Name: <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control select">
                                    <option value='0'>Please select</option>
                                    <?php
                                    // -------------------------------------------------------------------------------------------------
                                    $customer_details = '';
                                    // $result = $mysqli->query("SELECT * FROM `" . tbl_customers  . "` WHERE publish=1 ORDER BY id DESC");
                                    $result = $mysqli->query("SELECT * FROM `" . tbl_customers  . "` ORDER BY id DESC");
                                    while ($rows = $result->fetch_array()) {
                                        $display_name           = $rows["display_name"];
                                        // -------------------------------------------------------------------------------------------------
                                    ?>
                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $customer_id) { ?>selected <?php } else if ($rows['id'] == $customer_id) { ?>selected <?php } ?>>
                                            <?php echo $display_name; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Invoice Date: <span class="text-danger">*</span></label>
                                <div class="form-control-feedback form-control-feedback-start">
                                    <input type="text" class="form-control" placeholder="Requested Date" name="invoice_date" id="invoice_date" value="<?php echo $invoice_date; ?>">
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
                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $warehouse_id) { ?>selected <?php } else if ($rows['id'] == $warehouse_id) { ?>selected <?php } ?>>
                                            <?php echo $warehouse_name; ?>
                                        </option>
                                    <?php } ?>

                                </select>

                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Invoice No:</label>
                                <input type="text" class="form-control" placeholder="Invoice No" name="invoice_no" id="invoice_no" value="<?php echo $invoice_no; ?>">
                            </div>
                        </div>

                        <div class="col-lg-1">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status: </label>
                                <select name="invoice_status" id="invoice_status" class="form-select">
                                    <!-- <option value='0'>Please select</option> -->
                                    <option value="draft" <?php if ($invoice_status == 'draft') { ?>selected <?php } ?>>Draft</option>
                                    <option value="sent" <?php if ($invoice_status == 'sent') { ?>selected <?php } ?>>Sent</option>
                                    <option value="open" <?php if ($invoice_status == 'open') { ?>selected <?php } ?>>Open</option>
                                    <option value="revised" <?php if ($invoice_status == 'revised') { ?>selected <?php } ?>>Revised</option>
                                    <option value="declined" <?php if ($invoice_status == 'declined') { ?>selected <?php } ?>>Declined</option>
                                    <option value="accepted" <?php if ($invoice_status == 'accepted') { ?>selected <?php } ?>>Accepted</option>
                                </select>
                            </div>
                        </div>


                    </div>



                    <div class="col-xl-12">

                        <div class="row mb-2">

                            <div class="col-lg-3">
                                <label class="form-label ms-3 ">Description <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label ms-3 ">Origin <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label">Declaration No <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-3 ">HS Code <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-3 ">Qty</label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-4 ">Unit Price</label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label ms-2 ">Total</label>
                            </div>

                        </div>

                        <div class="card">

                            <div class="row card-body">

                                <div class="col-lg-12">

                                    <?php
                                    // ----------------------------------------------------------------------------
                                    for ($shipping_invoice_item = 1; $shipping_invoice_item <= $total_rows; $shipping_invoice_item++) {
                                        $index = $shipping_invoice_item;
                                        $index = $index - 1;

                                        // ----------------------------------------------------------------------------
                                    ?>

                                        <div class="mb-2">
                                            <div class="row mb-3 pb-3" id="row_<?php echo $shipping_invoice_item; ?>">


                                                <div class="col-lg-12">
                                                    <div class="row">

                                                        <input type="hidden" name="item_id[]" id="item_id<?php echo $shipping_invoice_item; ?>" value="<?php echo (!empty($item_id_arr[$index]) ? $item_id_arr[$index] : ''); ?>">

                                                        <div class="col-lg-3">
                                                            <input class="form-control" name="description[]" id="description<?php echo $shipping_invoice_item; ?>" class="form-control" placeholder="Add a description to your item" value="<?php echo (!empty($description_arr[$index]) ? $description_arr[$index] : ''); ?>">
                                                        </div>

                                                        <div class="col-lg-2">
                                                            <select class="form-select" name="coo[]" id="coo<?php echo $shipping_invoice_item; ?>">
                                                                <option value="0">Please select</option>
                                                                <?php
                                                                $result = $mysqli->query("SELECT * FROM `" . tbl_geo_countries . "` WHERE publish=1 ORDER BY country_name");
                                                                while ($rows = $result->fetch_array()) {
                                                                    $country_id = $rows['id'];
                                                                ?>
                                                                    <option value="<?php echo $country_id; ?>" <?php echo ((!empty($coo_arr[$index]) && $coo_arr[$index] == $country_id) ? 'selected="selected"' : ''); ?>>
                                                                        <?php echo $rows['alpha2_code']; ?> - <?php echo $rows['country_name']; ?>
                                                                    </option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input class="form-control" name="declaration_no[]" id="declaration_no<?php echo $shipping_invoice_item; ?>" class="form-control" placeholder="Declaration no" value="<?php echo (!empty($declaration_no_arr[$index]) ? $declaration_no_arr[$index] : ''); ?>">
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input class="form-control" name="hscode[]" id="hscode<?php echo $shipping_invoice_item; ?>" class="form-control" placeholder="HS Code" value="<?php echo (!empty($hscode_arr[$index]) ? $hscode_arr[$index] : ''); ?>">
                                                        </div>


                                                        <div class="col-lg-1">
                                                            <div class="input-group">
                                                                <button type="button" class="btn btn-light btn-icon" onclick="this.parentNode.querySelector('input[type=number]').stepDown(); calculateItemAmount('<?php echo $shipping_invoice_item; ?>'); ">

                                                                    <i class="ph-minus ph-sm"></i></button>
                                                                <input class="form-control form-control-number text-center" type="number" name="qty[]" id="qty<?php echo $shipping_invoice_item; ?>" value="<?php echo (!empty($qty_arr[$index]) ? $qty_arr[$index] : '1'); ?>" min="1" onkeyup="calculateItemAmount('<?php echo $shipping_invoice_item; ?>');" onchange="calculateItemAmount('<?php echo $shipping_invoice_item; ?>');">

                                                                <button type="button" class="btn btn-light btn-icon" onclick="this.parentNode.querySelector('input[type=number]').stepUp(); calculateItemAmount('<?php echo $shipping_invoice_item; ?>'); "><i class="ph-plus ph-sm"></i></button>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" step="1" name="rate[]" id="rate<?php echo $shipping_invoice_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($rate_arr[$index]) ? $rate_arr[$index] : '0'); ?>" onkeyup="calculateItemAmount('<?php echo $shipping_invoice_item; ?>');" onchange=" calculateItemAmount('<?php echo $shipping_invoice_item; ?>');"> <!--  step="0.1" value="0.0" -->
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" name="total[]" id="total<?php echo $shipping_invoice_item; ?>" min="0" class="form-control text-end" placeholder="0" value="<?php echo (!empty($total_arr[$index]) ? $total_arr[$index] : ''); ?>" onchange="calculateGrand(<?php echo $shipping_invoice_item; ?>);" onkeyup="calculateGrand(<?php echo $shipping_invoice_item; ?>);"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-2 mt-1">
                                                            <?php if ($shipping_invoice_item > 1) { ?>
                                                                <a href="#" onclick="calculateItemAmount('<?php echo $shipping_invoice_item; ?>'); clear_row(<?php echo $shipping_invoice_item; ?>)"><span class="badge bg-warning"> <i class="ph-x"></i> </span></a>
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
                                    <span id="span_add_item_row<?php echo $shipping_invoice_item; ?>"><a href="#" onclick="add_item_row(); "><span class="badge bg-primary"> Add New Row </a></span></span>
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

                                        new_row += "<div class=\"col-lg-3\">";
                                        new_row += "<input class=\"form-control\" name=\"description[]\" id=\"description" + total_rows + "\" placeholder=\"Add a description to your item\" class=\"form-control\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<select class=\"form-select\" name=\"coo[]\" id=\"coo" + total_rows + "\">";
                                        new_row += "<option value=\"0\">Please select</option>";
                                        new_row += "</select>";
                                        new_row += "</div>";


                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input class=\"form-control\" name=\"declaration_no[]\" id=\"declaration_no" + total_rows + "\" class=\"form-control\" placeholder=\"Declaration no\" >";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input class=\"form-control\" name=\"hscode[]\" id=\"hscode" + total_rows + "\" class=\"form-control\" placeholder=\"HS Code\">";
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
                                        new_row += "<input type=\"number\" name=\"total[]\" id=\"total" + total_rows + "\" min=\"0\" class=\"form-control text-end\" placeholder=\"0\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" onclick=\"clear_row(" + total_rows + ")\"><span class=\"badge bg-warning\"> <i class=\"ph-x\"></i> </span></a></span> </div>";

                                        new_row += "</div>";

                                        // This is to preserve the values of previously dynamicall created elements
                                        document.getElementById('add_row_here').insertAdjacentHTML("beforebegin", new_row);
                                        document.getElementById('total_rows').value = total_rows;
                                        ajax_populate_coo();

                                    }


                                    function clear_row(row_no) {

                                        calculateItemAmount(row_no);

                                        document.getElementById('description' + row_no).value = '';
                                        document.getElementById('coo' + row_no).value = '0';
                                        document.getElementById('coo' + row_no).text = 'Please select';
                                        document.getElementById('declaration_no' + row_no).value = '';
                                        document.getElementById('hscode' + row_no).value = '';
                                        document.getElementById('qty' + row_no).value = '';
                                        document.getElementById('rate' + row_no).value = '';
                                        document.getElementById('total' + row_no).value = '';

                                        document.getElementById('row_' + row_no).style.display = 'none';

                                    }


                                    // -------------------------------------------------------------------------
                                    //  CALCULATE AMOUNT
                                    // -------------------------------------------------------------------------
                                    function calculateItemAmount(row_no) {

                                        // console.log(row_no);

                                        // let service = document.getElementById('service' + row_no);
                                        // let service_value = service.options[service.selectedIndex].value;
                                        // let service_text = service.options[service.selectedIndex].text;
                                        // console.log("Service " + row_no + " text:", service_text);

                                        // if (service_value != NaN && service_value != '' && service_value != 'undefined' && service_value != '0') {

                                        // ---  Calculate Item Qty ------------------
                                        var qty = document.getElementById('qty' + row_no).value;
                                        qty = Number(qty);

                                        var rate = document.getElementById('rate' + row_no).value;

                                        // --- Calculate Total
                                        var total = parseFloat(rate * qty).toFixed(2);
                                        document.getElementById('total' + row_no).value = parseFloat(total).toFixed(2);

                                        // document.getElementById('total' + row_no).value = parseFloat(sub_total);

                                        calculateGrand();

                                        // } // if


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


                                        // ---------------------------------------------
                                        // CALCUALTE GRAND
                                        // ---------------------------------------------
                                        document.getElementById('grand_total').value = parseFloat(final_total.toFixed(2));

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
                                        <label class="col-lg-6 col-form-label">PLT/BOX/PKG's:</label>
                                        <input type="text" class="form-control" name="pkgs" id="pkgs" value="<?php echo $pkgs; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">WEIGHT:</label>
                                        <input type="text" class="form-control" name="weight" id="weight" value="<?php echo $weight; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">AWB:</label>
                                        <input type="text" class="form-control" name="awb" id="awb" value="<?php echo $awb; ?>">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-3">
                        </div>

                        <div class="col-lg-3">
                            <div class="card ">

                                <div class="card-body"> <!--  bg-info bg-opacity-10 -->

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Grand Total</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
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
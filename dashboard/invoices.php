<?php

include('admin_elements/admin_header.php');

$module             = 'invoices';
$module_caption     = 'Invoice';
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

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in invoices.php', 'WARNING', __FILE__, __LINE__);
    }
}

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


if (isset($_POST['publish']))                                 $is_active     = 1;
else $is_active = 0;


$save_and_send = 0;
if (isset($_POST['save_and_send']) && $_POST['save_and_send'] == 1)
    $save_and_send  = 1;



// ---------------------- Items -----------------------------
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



if ($action == "update_$module" || $action == "add_$module") {

    for ($invoice_item = 1; $invoice_item <= $total_rows; $invoice_item++) {

        $index = $invoice_item;
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
if ($action == "update_$module" || $action == "add_$module") {
    $invoice_date               = e_s__($_POST['invoice_date']);
    $expiry_date                = e_s__($_POST['expiry_date']);
    $customer_id                = e_s__($_POST['customer_id']);
    $invoice_status             = e_s__($_POST['invoice_status']);
    $reference_no               = e_s__($_POST['reference_no']);
    $warehouse_id               = e_s__($_POST['warehouse_id']);

    $expected_shipment_date     = e_s__($_POST['expected_shipment_date']);
    $payment_term               = e_s__($_POST['payment_term']);

    $shipment_type              = e_s__($_POST['shipment_type']);
    $sales_person               = e_s__($_POST['sales_person']);
    $job_reference_no           = e_s__($_POST['job_reference_no']);
    $master_awb_no              = e_s__($_POST['master_awb_no']);
    $shipper                    = e_s__($_POST['shipper']);
    $consignee                  = e_s__($_POST['consignee']);
    $origin                     = e_s__($_POST['origin']);
    $destination                = e_s__($_POST['destination']);
    $no_of_packs                = e_s__($_POST['no_of_packs']);
    $gross_weight               = e_s__($_POST['gross_weight']);
    $chargeable_weight          = e_s__($_POST['chargeable_weight']);
    $volume                     = e_s__($_POST['volume']);

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
    $invoice_date               = date('d-m-Y', time());
    $expiry_date                = '';
    $reference_no               = '';
    $warehouse_id               = '';

    $expected_shipment_date     = '';
    $payment_term              = '';

    $shipment_type              = '';
    $sales_person               = '';
    $job_reference_no           = '';
    $master_awb_no              = '';
    $shipper                    = '';
    $consignee                  = '';
    $origin                     = '';
    $destination                = '';
    $no_of_packs                = '';
    $gross_weight               = '';
    $chargeable_weight          = '';
    $volume                     = '';

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
if ($action == "update_$module" && !empty($id)) {

    if (empty($customer_id) || $customer_id == 'Please select') {
        $error_message = 'Please select Customer.';
    } else if (empty($invoice_date)) {
    } else {

        if (empty($invoice_status)) {
            $invoice_status = getTableAttr('invoice_status', DB::INVOICES, $id);
        }

        if ($grand_subtotal == '')                      $grand_subtotal = '0.00';
        if ($grand_discount_type == '')                 $grand_discount_type = '0.00';
        if ($grand_discount_type_value == '')           $grand_discount_type_value = '0.00';
        if ($grand_discount_amount == '')               $grand_discount_amount = '0.00';
        if ($grand_after_discount == '')                $grand_after_discount = '0.00';
        if ($grand_tax == '')                           $grand_tax = '0.00';
        if ($grand_total == '')                         $grand_total = '0.00';

        $invoice_date         = processDateDtoY($invoice_date);
        $expiry_date            = (empty($expiry_date) ? '1970-01-01' : processDateDtoY($expiry_date));
        $expected_shipment_date = (empty($expected_shipment_date) ? '1970-01-01' : processDateDtoY($expected_shipment_date));

        $payment_term       = (empty($payment_term) ? '0' : $payment_term);
        $sales_person       = (empty($sales_person) ? '0' : $sales_person);
        $master_awb_no      = (empty($master_awb_no) ? '0' : $master_awb_no);
        $shipper            = (empty($shipper) ? '0' : $shipper);
        $consignee          = (empty($consignee) ? '0' : $consignee);
        $origin             = (empty($origin) ? '0' : $origin);
        $destination        = (empty($destination) ? '0' : $destination);
        $no_of_packs        = (empty($no_of_packs) ? '0' : $no_of_packs);
        $gross_weight       = (empty($gross_weight) ? '0' : $gross_weight);
        $chargeable_weight  = (empty($chargeable_weight) ? '0' : $chargeable_weight);
        $volume             = (empty($volume) ? '0' : $volume);

        // ---------------------------------------------
        // UPDATE 
        // ---------------------------------------------
        $update_row = $mysqli->query("
                                        UPDATE `" . DB::INVOICES . "` SET
                                            invoice_date		        = '" . $invoice_date . "',
                                            expiry_date		            = '" . $expiry_date . "',
                                            customer_id					= '" . $customer_id . "',
                                            invoice_status		        = '" . $invoice_status . "',
                                            reference_no		        = '" . $reference_no . "',
                                            warehouse_id		        = '" . $warehouse_id . "',
                                            
                                            expected_shipment_date		= '" . $expected_shipment_date . "',
                                            payment_term		        = '" . $payment_term . "',
                                            
                                            shipment_type		        = '" . $shipment_type . "',
                                            sales_person		        = '" . $sales_person . "',
                                            job_reference_no		    = '" . $job_reference_no . "',
                                            master_awb_no		        = '" . $master_awb_no . "',
                                            shipper		                = '" . $shipper . "',
                                            consignee		            = '" . $consignee . "',
                                            origin		                = '" . $origin . "',
                                            destination		            = '" . $destination . "',
                                            no_of_packs		            = '" . $no_of_packs . "',
                                            gross_weight		        = '" . $gross_weight . "',
                                            chargeable_weight		    = '" . $chargeable_weight . "',
                                            volume		                = '" . $volume . "',
                                            
                                            customer_notes		        = '" . $customer_notes . "',
                                            terms_and_conditions		= '" . $terms_and_conditions . "',
                                            
                                            grand_subtotal		        = '" . $grand_subtotal . "',
                                            grand_discount_type		    = '" . $grand_discount_type . "',
                                            grand_discount_type_value   = '" . $grand_discount_type_value . "',
                                            grand_discount_amount		= '" . $grand_discount_amount . "',
                                            grand_after_discount		= '" . $grand_after_discount . "',
                                            grand_tax		            = '" . $grand_tax . "',
                                            grand_total		            = '" . $grand_total . "',
                                            
                                            publish 					= '" . $is_active . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__(DB::INVOICES, $id);
            $invoice_id = $id;


            // PROCESS -> JOURNAL ENTRY

            ///////////////////////////////////////////////////////////

            // -- PROCESS ITEMS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                for ($invoice_item = 1; $invoice_item <= $total_rows; $invoice_item++) {

                    $index = $invoice_item;
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
                    // UPDATE ITEMS
                    // ---------------------------------------------

                    $item_qty           = (($item_qty == '') ? 1 : $item_qty);
                    $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                    $item_sub_total     = (($item_sub_total == '') ? 0 : $item_sub_total);
                    $item_tax           = (($item_tax == '') ? 0 : $item_tax);
                    $item_tax_amount    = (($item_tax_amount == '') ? 0 : $item_tax_amount);
                    $item_total         = (($item_total == '') ? 0 : $item_total);

                    // Process Updated Items
                    if (!empty($item_id) && !empty($item_service)) {

                        $update_row = $mysqli->query("UPDATE `" . tbl_invoice_items . "` SET 
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
                        fp__(tbl_invoice_items, $item_id);

                        // Process New Items
                    } else if (empty($item_id) && !empty($item_service)) {

                        $insert_row = $mysqli->query("INSERT INTO `" . tbl_invoice_items . "`(invoice_id, service, description, qty, rate, sub_total, tax, tax_amount, total) VALUES ('" . $invoice_id . "', '" . $item_service . "', '" . $item_description . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_sub_total . "', '" . $item_tax . "', '" . $item_tax_amount . "', '" . $item_total . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__(tbl_invoice_items, $mysqli->insert_id);

                        // Process Deleted Items
                    } else if (!empty($item_id) && empty($item_service) && empty($item_rate) && empty($item_tax) && empty($item_total)) {

                        $mysqli->query("DELETE FROM `" . tbl_invoice_items . "` WHERE id=$item_id");
                    }
                    // ---------------------------------------------

                } //for 

            }
            ///////////////////////////////////////////////////////////

            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $invoice_date   = processDateYtoD($invoice_date);
                $expiry_date    = processDateYtoD($expiry_date);
                $error_message  = "No items added. Please add at least one item..";
            } else {

                if ($save_and_send == 1) {
                    header("Location:send_email.php?current_module=$module&id=$id");
                } else {
                    header("Location:listing_$module.php?success_message=$success_message");
                }
            }
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }

        // CHECK IF AT LEAST ONE ITEM IS ADDED
        // if ($inserted_row == 0) {
        //     $success_message = '';
        //     $invoice_date = processDateYtoD($invoice_date);
        //     $error_message = "No items added. Please add at least one item..";
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
    } else {

        ///////////////////////////////////////////////////////////

        // -- PROCESS ITEMS - ITNS
        if ($total_rows > 0) {

            $inserted_row = 0;

            for ($invoice_item = 1; $invoice_item <= $total_rows; $invoice_item++) {

                $index = $invoice_item;
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
                    // SAVE invoice
                    // ---------------------------------------------
                    if ($inserted_row == 0) {

                        if ($grand_subtotal == '')                      $grand_subtotal = '0.00';
                        if ($grand_discount_type == '')                 $grand_discount_type = '0.00';
                        if ($grand_discount_type_value == '')           $grand_discount_type_value = '0.00';
                        if ($grand_discount_amount == '')               $grand_discount_amount = '0.00';
                        if ($grand_after_discount == '')                $grand_after_discount = '0.00';
                        if ($grand_tax == '')                           $grand_tax = '0.00';
                        if ($grand_total == '')                         $grand_total = '0.00';


                        if (empty($invoice_status)) {
                            $invoice_status = 'draft';
                        }

                        $invoice_date        = processDateDtoY($invoice_date);
                        $expiry_date            = (empty($expiry_date) ? '1970-01-01' : processDateDtoY($expiry_date));
                        $expected_shipment_date = (empty($expected_shipment_date) ? '1970-01-01' : processDateDtoY($expected_shipment_date));

                        $payment_term       = (empty($payment_term) ? '0' : $payment_term);
                        $sales_person       = (empty($sales_person) ? '0' : $sales_person);
                        $master_awb_no      = (empty($master_awb_no) ? '0' : $master_awb_no);
                        $shipper            = (empty($shipper) ? '0' : $shipper);
                        $consignee          = (empty($consignee) ? '0' : $consignee);
                        $origin             = (empty($origin) ? '0' : $origin);
                        $destination        = (empty($destination) ? '0' : $destination);
                        $no_of_packs        = (empty($no_of_packs) ? '0' : $no_of_packs);
                        $gross_weight       = (empty($gross_weight) ? '0' : $gross_weight);
                        $chargeable_weight  = (empty($chargeable_weight) ? '0' : $chargeable_weight);
                        $volume             = (empty($volume) ? '0' : $volume);


                        $item_qty           = (($item_qty == '') ? 1 : $item_qty);
                        $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                        $item_sub_total     = (($item_sub_total == '') ? 0 : $item_sub_total);
                        $item_tax           = (($item_tax == '') ? 0 : $item_tax);
                        $item_tax_amount    = (($item_tax_amount == '') ? 0 : $item_tax_amount);



                        // ======================================================
                        // invoice NO Auto Generation System
                        // ======================================================

                        // Build the prefix for this month
                        $prefix = 'FL-IN' . date('ym');

                        // Get the last invoice number for this month
                        $sql = "SELECT invoice_no  FROM " . DB::INVOICES . "  WHERE invoice_no LIKE '{$prefix}-%'ORDER BY invoice_no DESC LIMIT 1";
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


                        $insert_row = $mysqli->query("INSERT INTO `" . DB::INVOICES . "`(invoice_no, customer_id, invoice_status, invoice_date, expiry_date, reference_no, warehouse_id, expected_shipment_date, payment_term, shipment_type, sales_person, job_reference_no, master_awb_no, shipper, consignee, origin, destination, no_of_packs, gross_weight, chargeable_weight, volume, terms_and_conditions, grand_subtotal, grand_discount_type, grand_discount_type_value, grand_discount_amount, grand_after_discount, customer_notes, grand_tax, grand_total, publish) VALUES ('" . $invoice_no . "', '" . $customer_id . "', '" . $invoice_status . "',  '" . $invoice_date . "', '" . $expiry_date . "', '" . $reference_no . "', '" . $warehouse_id . "', '" . $expected_shipment_date . "', '" . $payment_term . "', '" . $shipment_type . "', '" . $sales_person . "', '" . $job_reference_no . "', '" . $master_awb_no . "', '" . $shipper . "', '" . $consignee . "', '" . $origin . "', '" . $destination . "', '" . $no_of_packs . "', '" . $gross_weight . "', '" . $chargeable_weight . "', '" . $volume . "', '" . $terms_and_conditions . "',   '" . $grand_subtotal . "',  '" . $grand_discount_type . "',  '" . $grand_discount_type_value . "',  '" . $grand_discount_amount . "',  '" . $grand_after_discount . "',   '" . $customer_notes . "',  '" . $grand_tax . "', '" . $grand_total . "', '" . $is_active . "'); ");

                        $id = $mysqli->insert_id;
                        // if ($insert_row) {
                        fp__(DB::INVOICES, $id);
                        $success_message = "The $module_caption has been saved successfully.";
                        $invoice_id = $id;


                        // PROCESS -> JOURNAL ENTRY


                    }
                    // ---------------------------------------------


                    // ---------------------------------------------
                    // SAVE ITEMS
                    // ---------------------------------------------

                    $item_qty           = (($item_rate == '') ? 1 : $item_qty);
                    $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                    $item_sub_total     = (($item_sub_total == '') ? 0 : $item_sub_total);
                    $item_tax           = (($item_tax == '') ? 0 : $item_tax);
                    $item_tax_amount    = (($item_tax_amount == '') ? 0 : $item_tax_amount);
                    $item_total         = (($item_total == '') ? 0 : $item_total);

                    $insert_row = $mysqli->query("INSERT INTO `" . tbl_invoice_items . "`(invoice_id, service, description, qty, rate, sub_total, tax, tax_amount, total) VALUES ('" . $invoice_id . "', '" . $item_service . "', '" . $item_description . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_sub_total . "', '" . $item_tax . "', '" . $item_tax_amount . "', '" . $item_total . "'); ");

                    if ($insert_row) $inserted_row++;

                    fp__(tbl_invoice_items, $mysqli->insert_id);
                    // ---------------------------------------------

                }
            } //for 


            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($inserted_row == 0) {
                $error_message = "No items added. Please add at least one item..";
            } else {

                if ($save_and_send == 1) {
                    header("Location:send_email.php?current_module=$module&id=$id");
                } else {
                    header("Location:listing_$module.php?success_message=$success_message");
                }
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
$created_by = getTableAttr('created_by', DB::INVOICES, $id);

if (
    (!empty($id) && Roles::hasFullAccess($session_role_id))
    ||
    (!empty($id) && $session_user_id == $created_by)
) {

    $result = $mysqli->query("SELECT * FROM `" . DB::INVOICES . "` WHERE id=$id");
    $row = $result->fetch_array();

    $customer_id            = s__($row['customer_id']);
    $invoice_no             = s__($row['invoice_no']);
    $invoice_status         = s__($row['invoice_status']);
    $invoice_date           = s__($row['invoice_date']);
    $expiry_date            = s__($row['expiry_date']);
    $reference_no           = s__($row['reference_no']);
    $warehouse_id           = s__($row['warehouse_id']);

    $expected_shipment_date = s__($row['expected_shipment_date']);
    $payment_term           = s__($row['payment_term']);

    $shipment_type          = s__($row['shipment_type']);
    $sales_person           = s__($row['sales_person']);
    $job_reference_no       = s__($row['job_reference_no']);
    $master_awb_no          = s__($row['master_awb_no']);
    $shipper                = s__($row['shipper']);
    $consignee              = s__($row['consignee']);
    $origin                 = s__($row['origin']);
    $destination            = s__($row['destination']);
    $no_of_packs            = s__($row['no_of_packs']);
    $gross_weight           = s__($row['gross_weight']);
    $chargeable_weight      = s__($row['chargeable_weight']);
    $volume                 = s__($row['volume']);

    $customer_notes         = s__($row['customer_notes']);
    $terms_and_conditions   = s__($row['terms_and_conditions']);

    $grand_subtotal             = s__($row['grand_subtotal']);
    $grand_discount_type        = s__($row['grand_discount_type']);
    $grand_discount_type_value  = s__($row['grand_discount_type_value']);
    $grand_discount_amount      = s__($row['grand_discount_amount']);
    $grand_after_discount       = s__($row['grand_after_discount']);
    $grand_tax                  = s__($row['grand_tax']);
    $grand_total                = s__($row['grand_total']);

    $is_active = s__($row['publish']);

    $invoice_date               = processDateYtoD($invoice_date);
    $expiry_date                = ($expiry_date == '1970-01-01' ? '' : processDateDtoY($expiry_date));
    $expected_shipment_date     = ($expected_shipment_date == '1970-01-01' ? '' : processDateDtoY($expected_shipment_date));


    // ------------------ TOTAL ITEMS ------------------
    $result_invoice_items     = $mysqli->query("SELECT * FROM `" . tbl_invoice_items . "` WHERE invoice_id=$id");
    $total_rows                 = $result_invoice_items->num_rows;


    if ($total_rows > 0) {
        while ($row_invoice_items = $result_invoice_items->fetch_array()) {

            array_push($item_id_arr,                $row_invoice_items['id']);
            array_push($service_arr,                $row_invoice_items['service']);
            array_push($description_arr,            $row_invoice_items['description']);
            array_push($qty_arr,                    $row_invoice_items['qty']);
            array_push($rate_arr,                   $row_invoice_items['rate']);
            array_push($sub_total_arr,              $row_invoice_items['sub_total']);
            // array_push($discount_type_arr,          $row_invoice_items['discount_type']);
            // array_push($discount_type_value_arr,    $row_invoice_items['discount_type_value']);
            // array_push($discount_amount_arr,        $row_invoice_items['discount_amount']);
            array_push($tax_arr,                    $row_invoice_items['tax']);
            array_push($tax_amount_arr,             $row_invoice_items['tax_amount']);
            array_push($total_arr,                  $row_invoice_items['total']);
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
        <input type="hidden" name="invoice_status" id="invoice_status" value="" />
        <input type="hidden" name="save_and_send" id="save_and_send" value="" />
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h1 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h1>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <div class="p-3 rounded mt-1">
                        <div class="form-check form-check-inline form-switch">
                            <label class="form-check-label fw-semibold" for="sc_r_success">Invoice #: <?php echo $invoice_no; ?></label>
                        </div>
                    </div>
                <?php } ?>

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch">
                        <label class="form-check-label" for="sc_r_success"> <strong><?php echo ((!empty($invoice_status)) ? ucwords($invoice_status) : ''); ?></strong></label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto mt-1" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

                            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                <?php if (!empty($id)) { ?>
                                    <button type="button" class="submit-form btn btn-primary btn-sm me-2">Save</button>
                                <?php } else { ?>
                                    <button type="button" class="save-draft-invoice btn btn-primary btn-sm me-2">Save as Draft</button>
                                <?php } ?>

                                <button type="button" class="save-and-send-invoice btn btn-info btn-sm me-2">Save and Send</button>

                            <?php } ?>

                            <?php if (!empty($id)) { ?>
                                <a href="invoice_overview.php?invoice_id=<?php echo $id; ?>" class="btn btn-light btn-sm">
                                    Cancel
                                </a>
                            <?php } else { ?>
                                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                            <?php } ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->


        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>


                <div class="col-xl-12">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">

                                <div class="card-body">

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Customer Name:*</span></label>
                                        <div class="col-lg-9">
                                            <select name="customer_id" id="customer_id" class="form-control select">
                                                <option value='0'>Please select</option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $customer_details = '';
                                                $result = $mysqli->query("SELECT * FROM `" . tbl_customers  . "` WHERE is_active=1 AND approved=1 ORDER BY id DESC");
                                                // $result = $mysqli->query("SELECT * FROM `" . tbl_customers  . "` ORDER BY id DESC");
                                                while ($rows = $result->fetch_array()) {
                                                    // $display_name           = $rows["display_name"];
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $customer_id) { ?>selected <?php } else if ($rows['id'] == $customer_id) { ?>selected <?php } ?>>
                                                        <?php echo $rows["display_name"]; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Invoice Date:*</span></label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="Requested Date" name="invoice_date" id="invoice_date" value="<?php echo $invoice_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Reference no:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="" name="reference_no" id="reference_no" value="<?php echo $reference_no; ?>">
                                        </div>
                                    </div>



                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Expiry Date:</label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="Expiry Date" name="expiry_date" id="expiry_date" value="<?php echo $expiry_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Organizations:*</span> </label>
                                        <div class="col-lg-9">
                                            <select name="warehouse_id" id="warehouse_id" class="form-select">
                                                <!-- <option value='0'>Please select</option> -->
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . tbl_organizations  . "` WHERE is_active=1");
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

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Expected Shipment Date:</label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="Expected Shipment Date" name="expected_shipment_date" id="expected_shipment_date" value="<?php echo $expected_shipment_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="payment_term" id="payment_term" value="0">


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Delivery Method: </label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="shipment_type" id="shipment_type">
                                                <option value='0'>Please select</option>

                                                <option value="export" <?php if ($action == "edit_$module" && $shipment_type == 'export') { ?>selected <?php } else if ($shipment_type == 'export') { ?>selected <?php } ?>>Export</option>

                                                <option value="import" <?php if ($action == "edit_$module" && $shipment_type == 'import') { ?>selected <?php } else if ($shipment_type == 'import') { ?>selected <?php } ?>>Import</option>

                                                <option value="transit" <?php if ($action == "edit_$module" && $shipment_type == 'transit') { ?>selected <?php } else if ($shipment_type == 'transit') { ?>selected <?php } ?>>Transit</option>

                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Sales Person: </label>
                                        <div class="col-lg-9">
                                            <select name="sales_person" id="sales_person" class="form-select">
                                                <option value='0'>Please select</option>
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . tbl_organizations  . "` WHERE is_active=1");
                                                while ($rows = $result->fetch_array()) {
                                                    $warehouse_name = $rows["warehouse_name"];
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $sales_person) { ?>selected <?php } else if ($rows['id'] == $sales_person) { ?>selected <?php } ?>>
                                                        <?php echo $warehouse_name; ?>
                                                    </option>
                                                <?php } ?>

                                            </select>
                                        </div>
                                    </div>



                                </div>
                            </div>
                        </div>



                        <div class="col-lg-6">
                            <div class="card">

                                <!-- <div class="card-header d-flex align-items-center">
                                    <h2 class="mb-0">
                                    </h2>
                                </div> -->

                                <div class="card-body">


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Job Reference no:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="" name="job_reference_no" id="job_reference_no" value="<?php echo $job_reference_no; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Master AWB no:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="" name="master_awb_no" id="master_awb_no" value="<?php echo $master_awb_no; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Shipper: </label>
                                        <div class="col-lg-9">
                                            <select name="shipper" id="shipper" class="form-select">
                                                <option value='0'>Please select</option>
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . tbl_shippers  . "` WHERE is_active=1");
                                                while ($rows = $result->fetch_array()) {
                                                    $shipper_name = $rows["shipper_name"];
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $shipper) { ?>selected <?php } else if ($rows['id'] == $shipper) { ?>selected <?php } ?>>
                                                        <?php echo $shipper_name; ?>
                                                    </option>
                                                <?php } ?>

                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Consignee: </label>
                                        <div class="col-lg-9">
                                            <select name="consignee" id="consignee" class="form-select">
                                                <option value='0'>Please select</option>
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . tbl_consignees  . "` WHERE is_active=1");
                                                while ($rows = $result->fetch_array()) {
                                                    $consignee_name = $rows["consignee_name"];
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $consignee) { ?>selected <?php } else if ($rows['id'] == $consignee) { ?>selected <?php } ?>>
                                                        <?php echo $consignee_name; ?>
                                                    </option>
                                                <?php } ?>

                                            </select>
                                        </div>
                                    </div>


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Origin: </label>
                                        <div class="col-lg-9">
                                            <select required class="form-select" name="origin" id="origin">
                                                <option value="0">Please select</option>
                                                <?php
                                            // UAE is the only country - use constants
                                            $selected = ($origin == UAE_COUNTRY_ID || $origin == 1) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo UAE_COUNTRY_ID; ?>" <?php echo $selected; ?>>
                                                    <?php echo UAE_COUNTRY_ALPHA3_CODE; ?> - <?php echo UAE_COUNTRY_NAME; ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Destination: </label>
                                        <div class="col-lg-9">
                                            <select required class="form-select" name="destination" id="destination">
                                                <option value="0">Please select</option>
                                                <?php
                                            // UAE is the only country - use constants
                                            $selected = ($destination == UAE_COUNTRY_ID || $destination == 1) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo UAE_COUNTRY_ID; ?>" <?php echo $selected; ?>>
                                                    <?php echo UAE_COUNTRY_ALPHA3_CODE; ?> - <?php echo UAE_COUNTRY_NAME; ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">No of Packs:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="" name="no_of_packs" id="no_of_packs" value="<?php echo $no_of_packs; ?>">
                                        </div>
                                    </div>


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Gross Weigth:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="" name="gross_weight" id="gross_weight" value="<?php echo $gross_weight; ?>">
                                        </div>
                                    </div>


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Chargeable Weight:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="" name="chargeable_weight" id="chargeable_weight" value="<?php echo $chargeable_weight; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Volume (CBM):</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="" name="volume" id="volume" value="<?php echo $volume; ?>">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>


                    </div>
                </div>


                <div>

                    <div class="col-xl-12">

                        <div class="row mb-2">

                            <div class="col-lg-2">
                                <label class="form-label ms-3"><span class="text-danger">ITEM DETAILS*</span></label>
                            </div>

                            <div class="col-lg-3">
                                <label class="form-label ms-4">DESCRIPTION</label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-3">QUANTITY </label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-4">RATE </label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-3">SUBTOTAL </label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-1">TAX </label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label ms-2"><span class="text-danger">TOTAL*</span></label>
                            </div>

                        </div>

                        <div class="card">

                            <div class="row card-body">

                                <div class="col-lg-12">

                                    <?php
                                    // ----------------------------------------------------------------------------
                                    for ($invoice_item = 1; $invoice_item <= $total_rows; $invoice_item++) {
                                        $index = $invoice_item;
                                        $index = $index - 1;

                                        // ----------------------------------------------------------------------------
                                    ?>

                                        <div class="mb-2">
                                            <div class="row mb-3 pb-3" id="row_<?php echo $invoice_item; ?>">


                                                <div class="col-lg-12">
                                                    <div class="row">

                                                        <input type="hidden" name="item_id[]" id="item_id<?php echo $invoice_item; ?>" value="<?php echo (!empty($item_id_arr[$index]) ? $item_id_arr[$index] : ''); ?>">

                                                        <div class="col-lg-2">
                                                            <select class="form-select item-selector" name="service[]" id="service<?php echo $invoice_item; ?>" data-item-id="<?php echo $invoice_item; ?>">
                                                                <option value="0">Please select</option>
                                                                <?php
                                                                $result = $mysqli->query("SELECT * FROM `" . tbl_items . "` WHERE is_active=1 AND item_type='services' ORDER BY item_name");
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
                                                            <textarea name="description[]" id="description<?php echo $invoice_item; ?>" rows="2" class="form-control" placeholder="Add a description to your item"><?php echo (!empty($description_arr[$index]) ? $description_arr[$index] : ''); ?></textarea>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" step="1" name="qty[]" id="qty<?php echo $invoice_item; ?>" min="0" class="form-control text-center calc-item" data-item-id="<?php echo $invoice_item; ?>" value="<?php echo (!empty($qty_arr[$index]) ? $qty_arr[$index] : '1'); ?>"> <!--  step="0.1" value="0.0" -->
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" step="1" name="rate[]" id="rate<?php echo $invoice_item; ?>" min="0" class="form-control text-center calc-item" data-item-id="<?php echo $invoice_item; ?>" value="<?php echo (!empty($rate_arr[$index]) ? $rate_arr[$index] : '0'); ?>"> <!--  step="0.1" value="0.0" -->
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input readonly type="number" name="sub_total[]" id="sub_total<?php echo $invoice_item; ?>" min="0" class="form-control bg-light bg-opacity-75 text-end" value="<?php echo (!empty($sub_total_arr[$index]) ? $sub_total_arr[$index] : '0'); ?>"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <select name="tax[]" id="tax<?php echo $invoice_item; ?>" class="form-select calc-item" data-item-id="<?php echo $invoice_item; ?>">
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
                                                                <span class="badge bg-light text-black" style="font-weight: normal;" id="div_tax_amount<?php echo $invoice_item; ?>" style="display: <?php if (!empty($tax_arr[$index])) { ?> block <?php } else { ?> none <?php } ?>;">
                                                                    <span id="span_tax_amount<?php echo $invoice_item; ?>">
                                                                        <?php echo (!empty($tax_amount_arr[$index]) ? $tax_amount_arr[$index] : '0'); ?>
                                                                    </span>
                                                                </span>
                                                            </div>

                                                            <input type="hidden" name="tax_amount[]" id="tax_amount<?php echo $invoice_item; ?>" class="form-control" placeholder="0" value="<?php echo (!empty($tax_amount_arr[$index]) ? $tax_amount_arr[$index] : '0'); ?>">
                                                            <!-- <div class="form-text bg-light border border-top-0 rounded-bottom text-end px-2 py-1 mt-0">15,584</div> -->
                                                        </div>


                                                        <div class="col-lg-1">
                                                            <input readonly type="number" name="total[]" id="total<?php echo $invoice_item; ?>" min="0" class="form-control bg-light bg-opacity-75 text-end calc-grand" data-item-id="<?php echo $invoice_item; ?>" placeholder="0" value="<?php echo (!empty($total_arr[$index]) ? $total_arr[$index] : ''); ?>"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-2 mt-1">
                                                            <?php if ($invoice_item > 1) { ?>
                                                                <a href="#" class="clear-row-item" data-item-id="<?php echo $invoice_item; ?>"><span class="badge bg-warning"> <i class="ph-x"></i> </span></a>
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
                                    <span id="span_add_item_row<?php echo $invoice_item; ?>"><a href="#" class="add-item-row"><span class="badge bg-primary"> Add New Row </a></span></span>
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
                                        new_row += "<select class=\"form-select item-selector\" data-item-id=\"" + total_rows + "\" name=\"service[]\" id=\"service" + total_rows + "\">";
                                        new_row += "<option value=\"0\">Please select</option>";
                                        new_row += "</select>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-3\">";
                                        new_row += "<textarea type=\"text\" name=\"description[]\" id=\"description" + total_rows + "\" rows=\"2\" min=\"0\" placeholder=\"Add a description to your item\" class=\"form-control\"></textarea>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"qty[]\" id=\"qty" + total_rows + "\" min=\"1\" class=\"form-control text-center calc-item\" data-item-id=\"" + total_rows + "\" placeholder=\"1\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"rate[]\" id=\"rate" + total_rows + "\" min=\"0\" class=\"form-control text-center calc-item\" data-item-id=\"" + total_rows + "\" placeholder=\"0\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input readonly type=\"number\" name=\"sub_total[]\" id=\"sub_total" + total_rows + "\" min=\"0\" placeholder=\"0\" class=\"form-control bg-light bg-opacity-75 text-end\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<select name=\"tax[]\" id=\"tax" + total_rows + "\" class=\"form-select calc-item\" data-item-id=\"" + total_rows + "\">";
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

                                        new_row += "<div class=\"col-lg-1 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" class=\"clear-row-item\" data-item-id=\"" + total_rows + "\"><span class=\"badge bg-warning\"> <i class=\"ph-x\"></i> </span></a></span> </div>";

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
                                        <textarea class="form-control" name="customer_notes" id="customer_notes" style="field-sizing: content;" placeholder="Enter any notes to be displayed in your transaction"><?php echo $customer_notes; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">Terms & Conditions: </label>
                                        <textarea class="form-control" name="terms_and_conditions" id="terms_and_conditions" style="field-sizing: content;" placeholder="Enter the terms and conditions of your business to be displayed in your transaction"><?php echo $terms_and_conditions; ?></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-2">
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
                                                <select name="grand_discount_type" id="grand_discount_type" class="form-select clear-discount">
                                                    <option value='0'></option>
                                                    <option value="percent" <?php if ($grand_discount_type == 'percent') { ?>selected <?php } ?>>Percent %</option>
                                                    <option value="fixed" <?php if ($grand_discount_type == 'fixed') { ?>selected <?php } ?>>Fixed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <input type="number" min="0" step="any" class="form-control calc-grand" name="grand_discount_type_value" id="grand_discount_type_value" value="<?php echo $grand_discount_type_value; ?>" placeholder="0">
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
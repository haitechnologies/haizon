<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'sale_orders';
$module_caption     = 'Sale Order';
$tbl_name = DB::SALE_ORDERS;
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




// ---------------------- Dim Items -----------------------------
$dim_item_id_arr        = array();
$dim_pcs_arr            = array();
$dim_unit_arr           = array();
$dim_length_arr         = array();
$dim_width_arr          = array();
$dim_height_arr         = array();
$dim_formula_arr        = array();
$dim_cbm_arr            = array();
$dim_volume_arr         = array();


if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows            = e_s__($_POST['total_rows']);
    // if ($total_rows == 0 || $total_rows == '') $total_rows = 1;
} else {
    $total_rows            = 1;
}



if ($action == "update_$module" || $action == "add_$module") {

    for ($sale_order_item = 1; $sale_order_item <= $total_rows; $sale_order_item++) {

        $index = $sale_order_item;
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
    $sale_order_date             = ((isset($_POST['sale_order_date']) && !empty($_POST['sale_order_date'])) ? e_s__($_POST['sale_order_date']) : '');
    $expiry_date                 = ((isset($_POST['expiry_date']) && !empty($_POST['expiry_date'])) ? e_s__($_POST['expiry_date']) : '');

    $customer_id                 = ((isset($_POST['customer_id']) && !empty($_POST['customer_id'])) ? e_s__($_POST['customer_id']) : '');
    $sale_order_status           = ((isset($_POST['sale_order_status']) && !empty($_POST['sale_order_status'])) ? e_s__($_POST['sale_order_status']) : '');
    $reference_no                = ((isset($_POST['reference_no']) && !empty($_POST['reference_no'])) ? e_s__($_POST['reference_no']) : '');
    $warehouse_id                = ((isset($_POST['warehouse_id']) && !empty($_POST['warehouse_id'])) ? e_s__($_POST['warehouse_id']) : '');

    $expected_shipment_date      = ((isset($_POST['expected_shipment_date']) && !empty($_POST['expected_shipment_date'])) ? e_s__($_POST['expected_shipment_date']) : '');
    $payment_term                = ((isset($_POST['payment_term']) && !empty($_POST['payment_term'])) ? e_s__($_POST['payment_term']) : '');

    $shipment_type               = ((isset($_POST['shipment_type']) && !empty($_POST['shipment_type'])) ? e_s__($_POST['shipment_type']) : '');
    $sales_person                = ((isset($_POST['sales_person']) && !empty($_POST['sales_person'])) ? e_s__($_POST['sales_person']) : '');
    $job_reference_no            = ((isset($_POST['job_reference_no']) && !empty($_POST['job_reference_no'])) ? e_s__($_POST['job_reference_no']) : '');
    $mawb_bol                    = ((isset($_POST['mawb_bol']) && !empty($_POST['mawb_bol'])) ? e_s__($_POST['mawb_bol']) : '');
    $hwb_hbol                    = ((isset($_POST['hwb_hbol']) && !empty($_POST['hwb_hbol'])) ? e_s__($_POST['hwb_hbol']) : '');
    $shipper_id                  = ((isset($_POST['shipper_id']) && !empty($_POST['shipper_id'])) ? e_s__($_POST['shipper_id']) : '');
    $consignee_id                = ((isset($_POST['consignee_id']) && !empty($_POST['consignee_id'])) ? e_s__($_POST['consignee_id']) : '');
    $origin_port                 = ((isset($_POST['origin_port']) && !empty($_POST['origin_port'])) ? e_s__($_POST['origin_port']) : '');
    $origin_country              = ((isset($_POST['origin_country']) && !empty($_POST['origin_country'])) ? e_s__($_POST['origin_country']) : '');
    $destination_port            = ((isset($_POST['destination_port']) && !empty($_POST['destination_port'])) ? e_s__($_POST['destination_port']) : '');
    $destination_country         = ((isset($_POST['destination_country']) && !empty($_POST['destination_country'])) ? e_s__($_POST['destination_country']) : '');
    $gross_weight                = ((isset($_POST['gross_weight']) && !empty($_POST['gross_weight'])) ? e_s__($_POST['gross_weight']) : '');
    $volume                      = ((isset($_POST['volume']) && !empty($_POST['volume'])) ? e_s__($_POST['volume']) : '');
    $chargeable_weight           = ((isset($_POST['chargeable_weight']) && !empty($_POST['chargeable_weight'])) ? e_s__($_POST['chargeable_weight']) : '');
    $cbm                         = ((isset($_POST['cbm']) && !empty($_POST['cbm'])) ? e_s__($_POST['cbm']) : '');

    $customer_notes              = ((isset($_POST['customer_notes']) && !empty($_POST['customer_notes'])) ? e_s__($_POST['customer_notes']) : '');
    $terms_and_conditions        = ((isset($_POST['terms_and_conditions']) && !empty($_POST['terms_and_conditions'])) ? e_s__($_POST['terms_and_conditions']) : '');

    $grand_subtotal              = ((isset($_POST['grand_subtotal']) && !empty($_POST['grand_subtotal'])) ? e_s__($_POST['grand_subtotal']) : '');
    $grand_discount_type         = ((isset($_POST['grand_discount_type']) && !empty($_POST['grand_discount_type'])) ? e_s__($_POST['grand_discount_type']) : '');
    $grand_discount_type_value   = ((isset($_POST['grand_discount_type_value']) && !empty($_POST['grand_discount_type_value'])) ? e_s__($_POST['grand_discount_type_value']) : '');
    $grand_discount_amount       = ((isset($_POST['grand_discount_amount']) && !empty($_POST['grand_discount_amount'])) ? e_s__($_POST['grand_discount_amount']) : '');
    $grand_after_discount        = ((isset($_POST['grand_after_discount']) && !empty($_POST['grand_after_discount'])) ? e_s__($_POST['grand_after_discount']) : '');
    $grand_tax                   = ((isset($_POST['grand_tax']) && !empty($_POST['grand_tax'])) ? e_s__($_POST['grand_tax']) : '');
    $grand_total                 = ((isset($_POST['grand_total']) && !empty($_POST['grand_total'])) ? e_s__($_POST['grand_total']) : '');
} else {
    $sale_order_date             = date('d-m-Y', time());
    $expiry_date                 = '';

    $customer_id                 = '';
    $sale_order_status           = '';
    $reference_no                = '';
    $warehouse_id                = '';

    $expected_shipment_date      = '';
    $payment_term                = '';

    $shipment_type               = '';
    $sales_person                = '';
    $job_reference_no            = '';
    $mawb_bol                    = '';
    $hwb_hbol                    = '';
    $shipper_id                  = '';
    $consignee_id                = '';
    $origin_port                 = '';
    $origin_country              = '';
    $destination_port            = '';
    $destination_country         = '';
    $gross_weight                = '';
    $chargeable_weight           = '';
    $volume                      = '';
    $cbm                         = '';

    $customer_notes              = '';
    $terms_and_conditions        = '';

    $grand_subtotal              = '';
    $grand_discount_type         = '';
    $grand_discount_type_value   = '';
    $grand_discount_amount       = '';
    $grand_after_discount        = '';
    $grand_tax                   = '';
    $grand_total                 = '';
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
    } else if (empty($sale_order_date)) {
    } else {

        if (empty($sale_order_status)) {
            $sale_order_status = getTableAttr('sale_order_status', $tbl_name, $id);
        }

        if ($grand_subtotal == '')                      $grand_subtotal = '0.00';
        if ($grand_discount_type == '')                 $grand_discount_type = '0.00';
        if ($grand_discount_type_value == '')           $grand_discount_type_value = '0.00';
        if ($grand_discount_amount == '')               $grand_discount_amount = '0.00';
        if ($grand_after_discount == '')                $grand_after_discount = '0.00';
        if ($grand_tax == '')                           $grand_tax = '0.00';
        if ($grand_total == '')                         $grand_total = '0.00';

        $sale_order_date        = processDateDtoY($sale_order_date);
        $expiry_date            = (empty($expiry_date) ? '1970-01-01' : processDateDtoY($expiry_date));
        $expected_shipment_date = (empty($expected_shipment_date) ? '1970-01-01' : processDateDtoY($expected_shipment_date));

        $payment_term           = (empty($payment_term) ? '0' : $payment_term);
        $sales_person           = (empty($sales_person) ? '0' : $sales_person);
        $shipper_id             = (empty($shipper_id) ? '0' : $shipper_id);
        $consignee_id           = (empty($consignee_id) ? '0' : $consignee_id);
        $origin_port            = (empty($origin_port) ? '0' : $origin_port);
        $origin_country         = (empty($origin_country) ? '0' : $origin_country);
        $destination_port       = (empty($destination_port) ? '0' : $destination_port);
        $destination_country    = (empty($destination_country) ? '0' : $destination_country);
        $gross_weight           = (empty($gross_weight) ? '0' : $gross_weight);
        $volume                 = (empty($volume) ? '0' : $volume);
        $chargeable_weight      = (empty($chargeable_weight) ? '0' : $chargeable_weight);
        $cbm                    = (empty($cbm) ? '0' : $cbm);

        // ---------------------------------------------
        // UPDATE 
        // ---------------------------------------------
        $update_row = $mysqli->query("
                                        UPDATE `$tbl_name` SET
                                            sale_order_date		        = '" . $sale_order_date . "',
                                            expiry_date		            = '" . $expiry_date . "',
                                            customer_id					= '" . $customer_id . "',
                                            sale_order_status		    = '" . $sale_order_status . "',
                                            reference_no		        = '" . $reference_no . "',
                                            warehouse_id		        = '" . $warehouse_id . "',
                                            
                                            expected_shipment_date		= '" . $expected_shipment_date . "',
                                            payment_term		        = '" . $payment_term . "',
                                            
                                            shipment_type		        = '" . $shipment_type . "',
                                            sales_person		        = '" . $sales_person . "',
                                            job_reference_no		    = '" . $job_reference_no . "',
                                            mawb_bol		            = '" . $mawb_bol . "',
                                            hwb_hbol		            = '" . $hwb_hbol . "',
                                            shipper_id		            = '" . $shipper_id . "',
                                            consignee_id		        = '" . $consignee_id . "',
                                            origin_port		            = '" . $origin_port . "',
                                            origin_country		        = '" . $origin_country . "',
                                            destination_port		    = '" . $destination_port . "',
                                            destination_country		    = '" . $destination_country . "',
                                            gross_weight		        = '" . $gross_weight . "',
                                            volume		                = '" . $volume . "',
                                            chargeable_weight		    = '" . $chargeable_weight . "',
                                            cbm		                    = '" . $cbm . "',
                                            
                                            customer_notes		        = '" . $customer_notes . "',
                                            terms_and_conditions		= '" . $terms_and_conditions . "',
                                            
                                            grand_subtotal		        = '" . $grand_subtotal . "',
                                            grand_discount_type		    = '" . $grand_discount_type . "',
                                            grand_discount_type_value   = '" . $grand_discount_type_value . "',
                                            grand_discount_amount		= '" . $grand_discount_amount . "',
                                            grand_after_discount		= '" . $grand_after_discount . "',
                                            grand_tax		            = '" . $grand_tax . "',
                                            grand_total		            = '" . $grand_total . "',
                                            
                                            is_active 					= '" . $publish . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            $sale_order_id = $id;
            ///////////////////////////////////////////////////////////

            // -- PROCESS ITEMS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                for ($sale_order_item = 1; $sale_order_item <= $total_rows; $sale_order_item++) {

                    $index = $sale_order_item;
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

                        $update_row = $mysqli->query("UPDATE `" . DB::SALE_ORDER_ITEMS . "` SET 
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
                        fp__(DB::SALE_ORDER_ITEMS, $item_id);

                        // Process New Items
                    } else if (empty($item_id) && !empty($item_service)) {

                        $insert_row = $mysqli->query("INSERT INTO `" . DB::SALE_ORDER_ITEMS . "`(sale_order_id, service, description, qty, rate, sub_total, tax, tax_amount, total) VALUES ('" . $sale_order_id . "', '" . $item_service . "', '" . $item_description . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_sub_total . "', '" . $item_tax . "', '" . $item_tax_amount . "', '" . $item_total . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__(DB::SALE_ORDER_ITEMS, $mysqli->insert_id);

                        // Process Deleted Items
                    } else if (!empty($item_id) && empty($item_service) && empty($item_rate) && empty($item_tax) && empty($item_total)) {

                        $mysqli->query("DELETE FROM `" . DB::SALE_ORDER_ITEMS . "` WHERE id=$item_id");
                    }
                    // ---------------------------------------------

                } //for 

            }
            ///////////////////////////////////////////////////////////

            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $sale_order_date = processDateYtoD($sale_order_date);
                $expiry_date    = processDateYtoD($expiry_date);
                $error_message = "No items added. Please add at least one item..";
            } else {

                if ($save_and_send == 1) {
                    header("Location:send_sale_order.php?id=$id");
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
        //     $sale_order_date = processDateYtoD($sale_order_date);
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
    } else if (empty($sale_order_date)) {
        $error_message = 'Please select Sale order Date.';
    } else {

        ///////////////////////////////////////////////////////////

        // -- PROCESS ITEMS - ITNS
        if ($total_rows > 0) {

            $inserted_row = 0;

            for ($sale_order_item = 1; $sale_order_item <= $total_rows; $sale_order_item++) {

                $index = $sale_order_item;
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
                    // SAVE Sale Order
                    // ---------------------------------------------
                    if ($inserted_row == 0) {

                        if (empty($sale_order_status)) {
                            $sale_order_status = 'draft';
                        }

                        if ($grand_subtotal == '')                      $grand_subtotal = '0.00';
                        if ($grand_discount_type == '')                 $grand_discount_type = '0.00';
                        if ($grand_discount_type_value == '')           $grand_discount_type_value = '0.00';
                        if ($grand_discount_amount == '')               $grand_discount_amount = '0.00';
                        if ($grand_after_discount == '')                $grand_after_discount = '0.00';
                        if ($grand_tax == '')                           $grand_tax = '0.00';
                        if ($grand_total == '')                         $grand_total = '0.00';


                        $sale_order_date        = processDateDtoY($sale_order_date);
                        $expiry_date            = (empty($expiry_date) ? '1970-01-01' : processDateDtoY($expiry_date));
                        $expected_shipment_date = (empty($expected_shipment_date) ? '1970-01-01' : processDateDtoY($expected_shipment_date));

                        $payment_term           = (empty($payment_term) ? '0' : $payment_term);
                        $sales_person           = (empty($sales_person) ? '0' : $sales_person);
                        $shipper_id             = (empty($shipper_id) ? '0' : $shipper_id);
                        $consignee_id           = (empty($consignee_id) ? '0' : $consignee_id);
                        $origin_port            = (empty($origin_port) ? '0' : $origin_port);
                        $origin_country         = (empty($origin_country) ? '0' : $origin_country);
                        $destination_port       = (empty($destination_port) ? '0' : $destination_port);
                        $destination_country    = (empty($destination_country) ? '0' : $destination_country);
                        $gross_weight           = (empty($gross_weight) ? '0' : $gross_weight);
                        $chargeable_weight      = (empty($chargeable_weight) ? '0' : $chargeable_weight);
                        $volume                 = (empty($volume) ? '0' : $volume);
                        $cbm                    = (empty($cbm) ? '0' : $cbm);



                        $item_qty           = (($item_qty == '') ? 1 : $item_qty);
                        $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                        $item_sub_total     = (($item_sub_total == '') ? 0 : $item_sub_total);
                        $item_tax           = (($item_tax == '') ? 0 : $item_tax);
                        $item_tax_amount    = (($item_tax_amount == '') ? 0 : $item_tax_amount);



                        // ======================================================
                        // Sale_Order NO Auto Generation System
                        // ======================================================

                        // Build the prefix for this month
                        $prefix = 'FL-SO' . date('ym');

                        // Get the last sale_order number for this month
                        $sql = "SELECT sale_order_no  FROM `" . DB::SALE_ORDERS . "`  WHERE sale_order_no LIKE '{$prefix}-%'ORDER BY sale_order_no DESC LIMIT 1";
                        $result = $mysqli->query($sql);

                        if ($row = $result->fetch_assoc()) {
                            // Extract the serial part after the dash
                            $last_serial = (int) substr($row['sale_order_no'], -4);
                            $new_serial = $last_serial + 1;
                        } else {
                            // First sale_order of the month
                            $new_serial = 1;
                        }

                        // Build new sale_order number with zero padding
                        $sale_order_no = $prefix . '-' . str_pad($new_serial, 4, '0', STR_PAD_LEFT);
                        // ======================================================


                        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(sale_order_no, customer_id, sale_order_status, sale_order_date, expiry_date, reference_no, warehouse_id, expected_shipment_date, payment_term, shipment_type, sales_person, job_reference_no, mawb_bol, hwb_hbol, shipper_id, consignee_id, origin_port, origin_country, destination_port, destination_country, gross_weight, volume, chargeable_weight, cbm, terms_and_conditions, grand_subtotal, grand_discount_type, grand_discount_type_value, grand_discount_amount, grand_after_discount, customer_notes, grand_tax, grand_total, is_active) VALUES ('" . $sale_order_no . "', '" . $customer_id . "', '" . $sale_order_status . "',  '" . $sale_order_date . "', '" . $expiry_date . "', '" . $reference_no . "', '" . $warehouse_id . "', '" . $expected_shipment_date . "', '" . $payment_term . "', '" . $shipment_type . "', '" . $sales_person . "', '" . $job_reference_no . "', '" . $mawb_bol . "', '" . $hwb_hbol . "', '" . $shipper_id . "', '" . $consignee_id . "', '" . $origin_port . "', '" . $origin_country . "', '" . $destination_port . "', '" . $destination_country . "', '" . $gross_weight . "', '" . $volume . "', '" . $chargeable_weight . "', '" . $cbm . "', '" . $terms_and_conditions . "',   '" . $grand_subtotal . "',  '" . $grand_discount_type . "',  '" . $grand_discount_type_value . "',  '" . $grand_discount_amount . "',  '" . $grand_after_discount . "',   '" . $customer_notes . "',  '" . $grand_tax . "', '" . $grand_total . "', '" . $publish . "'); ");

                        $id = $mysqli->insert_id;
                        // if ($insert_row) {
                        fp__($tbl_name, $id);
                        $success_message = "The $module_caption has been saved successfully.";
                        $sale_order_id = $id;
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

                    $insert_row = $mysqli->query("INSERT INTO `" . DB::SALE_ORDER_ITEMS . "`(sale_order_id, service, description, qty, rate, sub_total, tax, tax_amount, total) VALUES ('" . $sale_order_id . "', '" . $item_service . "', '" . $item_description . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_sub_total . "', '" . $item_tax . "', '" . $item_tax_amount . "', '" . $item_total . "'); ");

                    if ($insert_row) $inserted_row++;

                    fp__(DB::SALE_ORDER_ITEMS, $mysqli->insert_id);
                    // ---------------------------------------------

                }
            } //for 


            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($inserted_row == 0) {
                $error_message = "No items added. Please add at least one item..";
            } else {

                if ($save_and_send == 1) {
                    header("Location:send_sale_order.php?id=$id");
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
$created_by = getTableAttr('created_by', DB::SALE_ORDERS, $id);

if (
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1')
    ||
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['admin_id'] == $created_by)
) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $customer_id            = s__($row['customer_id']);
    $sale_order_no          = s__($row['sale_order_no']);
    $sale_order_status      = s__($row['sale_order_status']);
    $sale_order_date        = s__($row['sale_order_date']);
    $expiry_date            = s__($row['expiry_date']);
    $reference_no           = s__($row['reference_no']);
    $warehouse_id           = s__($row['warehouse_id']);

    $expected_shipment_date = s__($row['expected_shipment_date']);
    $payment_term           = s__($row['payment_term']);

    $shipment_type          = s__($row['shipment_type']);
    $sales_person           = s__($row['sales_person']);
    $job_reference_no       = s__($row['job_reference_no']);
    $mawb_bol               = (isset($row['mawb_bol']) ? s__($row['mawb_bol']) : '');
    $hwb_hbol               = (isset($row['hwb_hbol']) ? s__($row['hwb_hbol']) : '');
    $shipper_id             = (isset($row['shipper_id']) ? s__($row['shipper_id']) : '');
    $consignee_id           = (isset($row['consignee_id']) ? s__($row['consignee_id']) : '');
    $origin_port            = (isset($row['origin_port']) ? s__($row['origin_port']) : '');
    $origin_country         = (isset($row['origin_country']) ? s__($row['origin_country']) : '');
    $destination_port       = (isset($row['destination_port']) ? s__($row['destination_port']) : '');
    $destination_country    = (isset($row['destination_country']) ? s__($row['destination_country']) : '');
    $gross_weight           = s__($row['gross_weight']);
    $chargeable_weight      = s__($row['chargeable_weight']);
    $volume                 = s__($row['volume']);
    $cbm                    = (isset($row['cbm']) ? s__($row['cbm']) : '');

    $customer_notes         = s__($row['customer_notes']);
    $terms_and_conditions   = s__($row['terms_and_conditions']);

    $grand_subtotal             = s__($row['grand_subtotal']);
    $grand_discount_type        = s__($row['grand_discount_type']);
    $grand_discount_type_value  = s__($row['grand_discount_type_value']);
    $grand_discount_amount      = s__($row['grand_discount_amount']);
    $grand_after_discount       = s__($row['grand_after_discount']);
    $grand_tax                  = s__($row['grand_tax']);
    $grand_total                = s__($row['grand_total']);

    $publish                    = s__($row['is_active']);

    $sale_order_date            = processDateYtoD($sale_order_date);
    $expiry_date                = ($expiry_date == '1970-01-01' ? '' : processDateDtoY($expiry_date));
    $expected_shipment_date     = ($expected_shipment_date == '1970-01-01' ? '' : processDateDtoY($expected_shipment_date));

    // Preserve form values on validation errors / refresh after POST
    if (!empty($_POST['mawb_bol'])) {
        $mawb_bol = e_s__($_POST['mawb_bol']);
    }
    if (!empty($_POST['hwb_hbol'])) {
        $hwb_hbol = e_s__($_POST['hwb_hbol']);
    }
    if (!empty($_POST['shipper_id'])) {
        $shipper_id = e_s__($_POST['shipper_id']);
    }
    if (!empty($_POST['consignee_id'])) {
        $consignee_id = e_s__($_POST['consignee_id']);
    }
    if (!empty($_POST['origin_port'])) {
        $origin_port = e_s__($_POST['origin_port']);
    }
    if (!empty($_POST['origin_country'])) {
        $origin_country = e_s__($_POST['origin_country']);
    }
    if (!empty($_POST['destination_port'])) {
        $destination_port = e_s__($_POST['destination_port']);
    }
    if (!empty($_POST['destination_country'])) {
        $destination_country = e_s__($_POST['destination_country']);
    }
    if (!empty($_POST['gross_weight'])) {
        $gross_weight = e_s__($_POST['gross_weight']);
    }
    if (!empty($_POST['volume'])) {
        $volume = e_s__($_POST['volume']);
    }
    if (!empty($_POST['chargeable_weight'])) {
        $chargeable_weight = e_s__($_POST['chargeable_weight']);
    }
    if (!empty($_POST['cbm'])) {
        $cbm = e_s__($_POST['cbm']);
    }

    // ------------------ TOTAL ITEMS ------------------
    $result_sale_order_items     = $mysqli->query("SELECT * FROM `" . DB::SALE_ORDER_ITEMS . "` WHERE sale_order_id=$id");
    $total_rows                 = $result_sale_order_items->num_rows;


    if ($total_rows > 0) {
        while ($row_sale_order_items = $result_sale_order_items->fetch_array()) {

            array_push($item_id_arr,                $row_sale_order_items['id']);
            array_push($service_arr,                $row_sale_order_items['service']);
            array_push($description_arr,            $row_sale_order_items['description']);
            array_push($qty_arr,                    $row_sale_order_items['qty']);
            array_push($rate_arr,                   $row_sale_order_items['rate']);
            array_push($sub_total_arr,              $row_sale_order_items['sub_total']);
            // array_push($discount_type_arr,          $row_sale_order_items['discount_type']);
            // array_push($discount_type_value_arr,    $row_sale_order_items['discount_type_value']);
            // array_push($discount_amount_arr,        $row_sale_order_items['discount_amount']);
            array_push($tax_arr,                    $row_sale_order_items['tax']);
            array_push($tax_amount_arr,             $row_sale_order_items['tax_amount']);
            array_push($total_arr,                  $row_sale_order_items['total']);
        }
    }
}


if ($total_rows == 0) $total_rows = 1;


// Initialize dimension variables
$total_dim_rows = 1;

// ------------------ TOTAL DIMENSION ITEMS ------------------
// Updated to support multiple modules via new schema
// Only query if we're editing an existing record
if (!empty($id)) {
    $result_dim_items       = $mysqli->query("SELECT * FROM `" . DB::DIMENSION_ITEMS . "` WHERE module_type='sale_orders' AND record_id=$id ORDER BY id ASC");
    $total_dim_rows         = $result_dim_items->num_rows;

    if ($total_dim_rows > 0) {
        while ($row_dim_items = $result_dim_items->fetch_array()) {

            array_push($dim_item_id_arr, $row_dim_items['id']);
            array_push($dim_pcs_arr,     $row_dim_items['pcs']);         // change if table column is pcs
            array_push($dim_unit_arr,    $row_dim_items['unit']);        // adjust accordingly
            array_push($dim_length_arr,  $row_dim_items['length']);      // fix typo: was $dim_leght_arr
            array_push($dim_width_arr,   $row_dim_items['width']);
            array_push($dim_height_arr,  $row_dim_items['height']);
            array_push($dim_formula_arr, $row_dim_items['formula']);
            array_push($dim_cbm_arr,     $row_dim_items['cbm']);
            array_push($dim_volume_arr,  $row_dim_items['volume']);
        } // while
    } // if
} // if !empty($id)


if ($total_dim_rows == 0)   $total_dim_rows = 1;


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
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <span class="badge bg-success bg-opacity-10 text-success ms-2">Sale Order #: <?php echo $sale_order_no; ?></span>
                <?php } ?>
                <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?php echo ((!empty($sale_order_status)) ? ucwords($sale_order_status) : ''); ?></span>
            </div>

            <div class="my-1 d-flex align-items-center gap-2">
                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <?php if (!empty($id)) { ?>
                        <button type="button" onclick="document.getElementById('frmsale_orders').submit();" class="btn btn-primary btn-sm">Save</button>
                    <?php } else { ?>
                        <button type="button" onclick="document.getElementById('sale_order_status').value='draft'; document.getElementById('frmsale_orders').submit();" class="btn btn-primary btn-sm">Save as Draft</button>
                    <?php } ?>
                    <button type="button" onclick="document.getElementById('save_and_send').value='1';<?php echo (!empty($id) ? '' : " document.getElementById('sale_order_status').value='draft';"); ?> document.getElementById('frmsale_orders').submit();" class="btn btn-info btn-sm">Save and Send</button>
                <?php } ?>

                <?php if (!empty($id)) { ?>
                    <a href="sale_order_overview.php?sale_order_id=<?php echo $id; ?>" class="btn btn-light btn-sm">Cancel</a>
                <?php } else { ?>
                    <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
                <input type="hidden" name="sale_order_status" id="sale_order_status" value="<?php echo $sale_order_status; ?>" />
                <input type="hidden" name="save_and_send" id="save_and_send" value="" />
                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php } else { ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php } ?>


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
                                                $result = $mysqli->query("SELECT * FROM `" . DB::CUSTOMERS  . "` WHERE is_active=1 AND approved=1 ORDER BY id DESC");
                                                // $result = $mysqli->query("SELECT * FROM `" . DB::CUSTOMERS  . "` ORDER BY id DESC");
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

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Quotation Date:*</span></label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="Quotation Date" name="sale_order_date" id="sale_order_date" value="<?php echo $sale_order_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Job Reference no:*</span></label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="" name="job_reference_no" id="job_reference_no" value="<?php echo $job_reference_no; ?>">
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

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Payment Terms: </label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="payment_term" id="payment_term">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_payment_terms = $mysqli->query("SELECT * FROM `" . DB::PAYMENT_TERMS  . "` WHERE is_active=1 ORDER BY id ASC");
                                                while ($rows_payment_terms = $result_payment_terms->fetch_array()) {
                                                    // $payment_terms        = s__($rows_payment_terms['payment_terms']);
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_payment_terms['id']; ?>" <?php if ($action == "edit_$module" && $rows_payment_terms['id'] == $payment_term) { ?>selected <?php } else if ($rows_payment_terms['id'] == $payment_term) { ?>selected <?php } ?>>
                                                        <?php echo $rows_payment_terms['payment_term']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>
                                        </div>
                                    </div>

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
                                                $result = $mysqli->query("SELECT * FROM `" . DB::WAREHOUSES  . "` WHERE is_active=1");
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
                                    <h6 class="mb-0">
                                    </h6>
                                </div> -->

                                <div class="card-body">


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Warehouses: </label>
                                        <div class="col-lg-9">
                                            <select name="warehouse_id" id="warehouse_id" class="form-select">
                                                <!-- <option value='0'></option> -->
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . DB::WAREHOUSES  . "` WHERE is_active=1");
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
                                        <label class="col-lg-3 col-form-label">MAWB/BOL:</label>
                                        <div class="col-lg-3">
                                            <input type="text" class="form-control" name="mawb_bol" id="mawb_bol" value="<?php echo $mawb_bol; ?>">
                                        </div>

                                        <label class="col-lg-2 col-form-label">HWB/HBOL:</label>
                                        <div class="col-lg-4">
                                            <input type="text" class="form-control" name="hwb_hbol" id="hwb_hbol" value="<?php echo $hwb_hbol; ?>">
                                        </div>
                                    </div>

                                    <!--
                                    |--------------------------------------------------------------------------------------- 
                                    | SHIPPER DROPDOWN / ADD NEW SHIPPER 
                                    |--------------------------------------------------------------------------------------- 
                                     -->

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Shipper: <i class="ph-plus-circle" id="openShipperPopup" style="cursor:pointer;"></i> </label>
                                        <div class="col-lg-9">
                                            <select name="shipper_id" id="shipper_id" class="form-select">
                                                <option value='0'></option>
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . DB::SHIPPERS  . "` WHERE is_active=1 ORDER BY shipper_name ASC");
                                                while ($rows = $result->fetch_array()) {
                                                    $shipper_name = $rows["shipper_name"];
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $shipper_id) { ?>selected <?php } else if ($rows['id'] == $shipper_id) { ?>selected <?php } ?>>
                                                        <?php echo $shipper_name; ?>
                                                    </option>
                                                <?php } ?>

                                            </select>
                                        </div>
                                    </div>


                                    <!-- Popup Modal -->
                                    <div class="modal fade" id="shipperModal" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form id="popupForm">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Shipper Info</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <!-- <input type="hidden" id="shipper_id" name="shipper_id"> -->

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label"></label>
                                                            <div class="col-lg-9">
                                                                <span id="ajax_shipper_error_message" class="text-danger"></span>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label"><span class="text-danger">Name:*</span></label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_name" name="shipper_name" required>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label"><span class="text-danger">Street Address1:*</span></label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_address_line1" name="shipper_address_line1" required>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Street Address2:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_address_line2" name="shipper_address_line2">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">City:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_city" name="shipper_city">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Zip/Postal Code:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_zipcode" name="shipper_zipcode">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Province:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_province" name="shipper_province">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Country:</label>
                                                            <div class="col-lg-9">
                                                                <select required class="form-select" name="shipper_country" id="shipper_country">
                                                                    <option value="0"></option>
                                                                    <?php
                                                                    // -------------------------------------------------------------------------------------------------
                                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                                                    while ($rows = $result->fetch_array()) {
                                                                        // -------------------------------------------------------------------------------------------------
                                                                    ?>
                                                                        <option value="<?php echo $rows['id']; ?>">
                                                                            <?php echo $rows['country']; ?>
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Email:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_email" name="shipper_email">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Telephone:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_telephone" name="shipper_telephone">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Mobile:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_mobile" name="shipper_mobile">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Fax:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="shipper_fax" name="shipper_fax">
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" onclick="create_shipper();" class="btn btn-success">Save</button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        function create_shipper() {
                                            // $("#shipperModal").modal("hide");

                                            let shipper_name = $("#shipper_name").val();
                                            let shipper_address_line1 = $("#shipper_address_line1").val();
                                            let shipper_address_line2 = $("#shipper_address_line2").val();
                                            let shipper_city = $("#shipper_city").val();
                                            let shipper_zipcode = $("#shipper_zipcode").val();
                                            let shipper_province = $("#shipper_province").val();
                                            let shipper_country = $("#shipper_country").val();
                                            let shipper_email = $("#shipper_email").val();
                                            let shipper_telephone = $("#shipper_telephone").val();
                                            let shipper_mobile = $("#shipper_mobile").val();
                                            let shipper_fax = $("#shipper_fax").val();

                                            if (shipper_name == '') {
                                                document.getElementById('ajax_shipper_error_message').innerHTML = 'Name is mandatory';
                                            } else if (shipper_address_line1 == '') {
                                                document.getElementById('ajax_shipper_error_message').innerHTML = 'Street Address1 is mandatory';
                                            } else {

                                                // Call function
                                                ajax_add_shipper(
                                                    shipper_name, shipper_address_line1, shipper_address_line2,
                                                    shipper_city, shipper_zipcode, shipper_province, shipper_country,
                                                    shipper_email, shipper_telephone, shipper_mobile, shipper_fax
                                                );

                                            } // end if

                                        } // function


                                        $(document).ready(function() {
                                            // Open popup with existing data
                                            $("#openShipperPopup").on("click", function() {
                                                $("#popupId").val($("#shipperIdMaster").val());
                                                $("#popupName").val($("#shipperName").val());
                                                // $("#popupEmail").val($("#shipperEmail").val());
                                                $("#shipperModal").modal("show");
                                            });
                                        });
                                    </script>



                                    <!--
                                    |--------------------------------------------------------------------------------------- 
                                    | CONSIGNEE DROPDOWN / ADD NEW CONSIGNEE 
                                    |--------------------------------------------------------------------------------------- 
                                     -->


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Consignee: <i class="ph-plus-circle" id="openConsigneePopup" style="cursor:pointer;"></i> </label>
                                        <div class="col-lg-9">
                                            <select name="consignee_id" id="consignee_id" class="form-select">
                                                <option value='0'></option>
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . DB::CONSIGNEES  . "` WHERE is_active=1 ORDER BY consignee_name ASC");
                                                while ($rows = $result->fetch_array()) {
                                                    $consignee_name = $rows["consignee_name"];
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $consignee_id) { ?>selected <?php } else if ($rows['id'] == $consignee_id) { ?>selected <?php } ?>>
                                                        <?php echo $consignee_name; ?>
                                                    </option>
                                                <?php } ?>

                                            </select>
                                        </div>
                                    </div>



                                    <!-- Popup Modal -->
                                    <div class="modal fade" id="consigneeModal" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form id="popupForm">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Consignee Info</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <!-- <input type="hidden" id="consignee_id" name="consignee_id"> -->

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label"></label>
                                                            <div class="col-lg-9">
                                                                <span id="ajax_consignee_error_message" class="text-danger"></span>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label"><span class="text-danger">Name:*</span></label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_name" name="consignee_name" required>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label"><span class="text-danger">Street Address1:*</span></label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_address_line1" name="consignee_address_line1" required>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Street Address2:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_address_line2" name="consignee_address_line2">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">City:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_city" name="consignee_city">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Zip/Postal Code:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_zipcode" name="consignee_zipcode">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Province:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_province" name="consignee_province">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Country:</label>
                                                            <div class="col-lg-9">
                                                                <select required class="form-select" name="consignee_country" id="consignee_country">
                                                                    <option value="0"></option>
                                                                    <?php
                                                                    // -------------------------------------------------------------------------------------------------
                                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                                                    while ($rows = $result->fetch_array()) {
                                                                        // -------------------------------------------------------------------------------------------------
                                                                    ?>
                                                                        <option value="<?php echo $rows['id']; ?>">
                                                                            <?php echo $rows['country']; ?>
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Email:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_email" name="consignee_email">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Telephone:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_telephone" name="consignee_telephone">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Mobile:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_mobile" name="consignee_mobile">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <label class="col-lg-3 col-form-label">Fax:</label>
                                                            <div class="col-lg-9">
                                                                <input type="text" class="form-control" id="consignee_fax" name="consignee_fax">
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" onclick="create_consignee();" class="btn btn-success">Save</button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        function create_consignee() {
                                            // $("#consigneeModal").modal("hide");

                                            let consignee_name = $("#consignee_name").val();
                                            let consignee_address_line1 = $("#consignee_address_line1").val();
                                            let consignee_address_line2 = $("#consignee_address_line2").val();
                                            let consignee_city = $("#consignee_city").val();
                                            let consignee_zipcode = $("#consignee_zipcode").val();
                                            let consignee_province = $("#consignee_province").val();
                                            let consignee_country = $("#consignee_country").val();
                                            let consignee_email = $("#consignee_email").val();
                                            let consignee_telephone = $("#consignee_telephone").val();
                                            let consignee_mobile = $("#consignee_mobile").val();
                                            let consignee_fax = $("#consignee_fax").val();

                                            if (consignee_name == '') {
                                                document.getElementById('ajax_consignee_error_message').innerHTML = 'Name is mandatory';
                                            } else if (consignee_address_line1 == '') {
                                                document.getElementById('ajax_consignee_error_message').innerHTML = 'Street Address1 is mandatory';
                                            } else {

                                                // Call function
                                                ajax_add_consignee(
                                                    consignee_name, consignee_address_line1, consignee_address_line2,
                                                    consignee_city, consignee_zipcode, consignee_province, consignee_country,
                                                    consignee_email, consignee_telephone, consignee_mobile, consignee_fax
                                                );

                                            } // end if

                                        } // function


                                        $(document).ready(function() {
                                            // Open popup with existing data
                                            $("#openConsigneePopup").on("click", function() {
                                                $("#popupId").val($("#consigneeIdMaster").val());
                                                $("#popupName").val($("#consigneeName").val());
                                                // $("#popupEmail").val($("#consigneeEmail").val());
                                                $("#consigneeModal").modal("show");
                                            });
                                        });
                                    </script>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Origin Port: </label>
                                        <div class="col-lg-3">
                                            <select class="form-select" name="origin_port" id="origin_port" onchange="ajax_select_port_country('origin', this.value);">
                                                <option value="0"></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                if (!empty($origin_country)) {
                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "ports` WHERE is_active=1 AND country_id=$origin_country ORDER BY port_name");
                                                } else {
                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "ports` WHERE is_active=1 ORDER BY port_name");
                                                }
                                                while ($rows = $result->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $origin_port) { ?>selected <?php } else if ($rows['id'] == $origin_port) { ?>selected <?php } ?>>
                                                        <?php echo $rows['port_code']; ?> - <?php echo $rows['port_name']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <label class="col-lg-2 col-form-label">Country: </label>
                                        <div class="col-lg-4">
                                            <select class="form-select <?php echo ((!empty($destination_port)) ? 'bg-light' : '') ?>" name="origin_country" id="origin_country" <?php echo ((!empty($destination_port)) ? 'style="pointer-events:none;"' : '') ?> onchange="ajax_select_country_ports('origin');">
                                                <option value="0"></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                                while ($rows = $result->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $origin_country) { ?>selected <?php } else if ($rows['id'] == $origin_country) { ?>selected <?php } ?>>
                                                        <?php echo $rows['country']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Destination Port: </label>
                                        <div class="col-lg-3">
                                            <select class="form-select" name="destination_port" id="destination_port" onchange="ajax_select_port_country('destination', this.value);">
                                                <option value="0"></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                if (!empty($destination_country)) {
                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "ports` WHERE is_active=1 AND country_id=$destination_country ORDER BY port_name");
                                                } else {
                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "ports` WHERE is_active=1 ORDER BY port_name");
                                                }
                                                while ($rows = $result->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $destination_port) { ?>selected <?php } else if ($rows['id'] == $destination_port) { ?>selected <?php } ?>>
                                                        <?php echo $rows['port_code']; ?> - <?php echo $rows['port_name']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <label class="col-lg-2 col-form-label">Country: </label>
                                        <div class="col-lg-4">
                                            <select class="form-select <?php echo ((!empty($destination_port)) ? 'bg-light' : '') ?>" name="destination_country" id="destination_country" <?php echo ((!empty($destination_port)) ? 'style="pointer-events:none;"' : '') ?> onchange="ajax_select_country_ports('destination');">
                                                <option value="0"></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                                while ($rows = $result->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $destination_country) { ?>selected <?php } else if ($rows['id'] == $destination_country) { ?>selected <?php } ?>>
                                                        <?php echo $rows['country']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row border-top-black border-top-lg mt-3">

                                        <!--
                                        |--------------------------------------------------------------------------------------- 
                                        | DIMENSIONS 
                                        |--------------------------------------------------------------------------------------- 
                                        -->
                                        <div class="row mb-2 mt-3">
                                            <label class="col-lg-3 col-form-label"><span class="badge bg-info text-white" id="openDimensionPopup" style="cursor:pointer;">Dimensions</span></label>
                                            <div class="col-lg-3"></div>
                                        </div>

                                        <style>
                                            /* Ensure proper font for all form elements */
                                            .form-control,
                                            input,
                                            textarea,
                                            .select2-selection__rendered {
                                                font-family: 'Noto Sans', 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
                                                font-size: 1rem;
                                            }

                                            /* Optional: force full display */
                                            .form-control {
                                                overflow: visible;
                                                white-space: nowrap;
                                            }

                                            /* target only the dimension modal so other modals are unaffected */
                                            #dimensionModal .modal-dialog {
                                                max-width: 1200px;
                                                /* maximum width (px) */
                                                width: 80%;
                                                /* default width relative to viewport */
                                                margin: 1.75rem auto;
                                                /* vertical spacing + center horizontally */
                                                transform: none;
                                                /* avoid Bootstrap centering transform issues */
                                            }

                                            /* if you prefer very wide screens to use more room */
                                            @media (min-width: 1400px) {
                                                #dimensionModal .modal-dialog {
                                                    max-width: 1500px;
                                                    width: 85%;
                                                }
                                            }

                                            /* on small screens, make sure modal is near-fullscreen */
                                            @media (max-width: 767.98px) {
                                                #dimensionModal .modal-dialog {
                                                    width: 95%;
                                                    max-width: none;
                                                    margin: 0.5rem auto;
                                                }
                                            }

                                            /* make modal content take the safe vertical space and allow scrolling inside the body */
                                            #dimensionModal .modal-content {
                                                border-radius: 0.5rem;
                                                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
                                                max-height: calc(100vh - 100px);
                                                /* keep some space from top/bottom */
                                                display: flex;
                                                flex-direction: column;
                                                overflow: hidden;
                                            }

                                            /* modal header/footer fixed height; body scrolls */
                                            #dimensionModal .modal-body {
                                                overflow-y: auto;
                                                -webkit-overflow-scrolling: touch;
                                                padding: 1rem;
                                            }

                                            /* optional: keep footer visible and pinned to bottom */
                                            #dimensionModal .modal-footer {
                                                flex-shrink: 0;
                                                padding: 0.75rem 1rem;
                                            }

                                            /* minor aesthetic tweaks */
                                            #dimensionModal .modal-header .modal-title {
                                                font-weight: 600;
                                            }
                                        </style>


                                        <!-- Popup Modal -->
                                        <div class="modal fade" id="dimensionModal" tabindex="-1">
                                            <div class="modal-dialog modal-xl">
                                                <div class="modal-content">
                                                    <form id="popupForm">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Dimension Info</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body">

                                                            <div class="row mb-2">

                                                                <div class="col-lg-2">
                                                                    <label class="form-label fw-semibold">NO. OF PCS</label>
                                                                </div>

                                                                <div class="col-lg-2">
                                                                    <label class="form-label fw-semibold">UNITS</label>
                                                                </div>

                                                                <div class="col-lg-1">
                                                                    <label class="form-label fw-semibold">LENGTH</label>
                                                                </div>

                                                                <div class="col-lg-1">
                                                                    <label class="form-label fw-semibold">WIDTH</label>
                                                                </div>

                                                                <div class="col-lg-1">
                                                                    <label class="form-label fw-semibold">HEIGHT</label>
                                                                </div>

                                                                <div class="col-lg-1">
                                                                    <label class="form-label ms-1 fw-semibold">FORMULA</label>
                                                                </div>

                                                                <div class="col-lg-1">
                                                                    <label class="form-label ms-1 fw-semibold">CBM</label>
                                                                </div>

                                                                <div class="col-lg-1">
                                                                    <label class="form-label ms-2 fw-semibold">VOLUME</label>
                                                                </div>

                                                                <div class="col-lg-1">
                                                                    <label class="form-label ms-2 fw-semibold"></label>
                                                                </div>

                                                            </div>

                                                            <span id="ajax_dimension_error_message" class="text-danger"></span>


                                                            <?php

                                                            // ----------------------------------------------------------------------------
                                                            $sale_order_item = 1;
                                                            $total_pcs    = 0;
                                                            $total_cbm    = 0.0;
                                                            $total_volume = 0.0;

                                                            // ----------------------------------------------------------------------------
                                                            for ($dim_item = 1; $dim_item <= $total_dim_rows; $dim_item++) {
                                                                $dim_index = $dim_item;
                                                                $dim_index = $dim_index - 1;


                                                                $dim_item_id = (!empty($dim_item_id_arr[$dim_index]) ? $dim_item_id_arr[$dim_index] : '');
                                                                // ----------------------------------------------------------------------------

                                                                if ($total_dim_rows > 1) {
                                                                    $total_pcs    += (isset($dim_pcs_arr[$dim_index]) ? (float)$dim_pcs_arr[$dim_index] : '');
                                                                    $total_cbm    += (float)$dim_cbm_arr[$dim_index];
                                                                    $total_volume += (float)$dim_volume_arr[$dim_index];
                                                                }

                                                                // ----------------------------------------------------------------------------
                                                            ?>
                                                                <div class="col-lg-12 mt-2">
                                                                    <div class="row mb-3 pb-3" id="dim_row_<?php echo $dim_item; ?>">

                                                                        <input type="hidden" name="dim_item_id[]" id="dim_item_id<?php echo $dim_item; ?>" value="<?php echo (!empty($dim_item_id_arr[$dim_index]) ? $dim_item_id_arr[$dim_index] : ''); ?>">

                                                                        <div class="col-lg-2">
                                                                            <input type="number" step="1" name="dim_pcs[]" id="dim_pcs<?php echo $dim_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($dim_pcs_arr[$dim_index]) ? $dim_pcs_arr[$dim_index] : ''); ?>" onkeyup="calculateDim('<?php echo $dim_item; ?>');" onchange=" calculateDim('<?php echo $dim_item; ?>');">
                                                                        </div>

                                                                        <div class="col-lg-2">
                                                                            <select name="dim_unit[]" id="dim_unit<?php echo $dim_item; ?>" class="form-select" onchange="calculateDim(<?php echo $dim_item; ?>, this.value); ">
                                                                                <option value="cm" <?php echo ((!empty($dim_unit_arr[$dim_index]) && $dim_unit_arr[$dim_index] == 'cm') ? 'selected="selected"' : ''); ?>>Centimetre</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-lg-1">
                                                                            <input type="number" step="1" name="dim_length[]" id="dim_length<?php echo $dim_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($dim_length_arr[$dim_index]) ? $dim_length_arr[$dim_index] : ''); ?>" onkeyup="calculateDim('<?php echo $dim_item; ?>');" onchange=" calculateDim('<?php echo $dim_item; ?>');">
                                                                        </div>

                                                                        <div class="col-lg-1">
                                                                            <input type="number" step="1" name="dim_width[]" id="dim_width<?php echo $dim_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($dim_width_arr[$dim_index]) ? $dim_width_arr[$dim_index] : ''); ?>" onkeyup="calculateDim('<?php echo $dim_item; ?>');" onchange=" calculateDim('<?php echo $dim_item; ?>');">
                                                                        </div>

                                                                        <div class="col-lg-1">
                                                                            <input type="number" step="1" name="dim_height[]" id="dim_height<?php echo $dim_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($dim_height_arr[$dim_index]) ? $dim_height_arr[$dim_index] : ''); ?>" onkeyup="calculateDim('<?php echo $dim_item; ?>');" onchange=" calculateDim('<?php echo $dim_item; ?>');">
                                                                        </div>

                                                                        <div class="col-lg-1">
                                                                            <select name="dim_formula[]" id="dim_formula<?php echo $dim_item; ?>" class="form-select" style="min-width: 100px;" onchange="calculateDim(<?php echo $dim_item; ?>, this.value); ">
                                                                                <option value="6000" <?php echo ((!empty($dim_formula_arr[$dim_index]) && $dim_formula_arr[$dim_index] == 6000) ? 'selected="selected"' : ''); ?>>6000</option>
                                                                                <option value="5000" <?php echo ((!empty($dim_formula_arr[$dim_index]) && $dim_formula_arr[$dim_index] == 5000) ? 'selected="selected"' : ''); ?>>5000</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-lg-1">
                                                                            <input readonly type="number" name="dim_cbm[]" id="dim_cbm<?php echo $dim_item; ?>" class="form-control text-end bg-light text-bg-light" style="min-width: 100px;" value="<?php echo (!empty($dim_cbm_arr[$dim_index]) ? $dim_cbm_arr[$dim_index] : ''); ?>">
                                                                        </div>

                                                                        <div class="col-lg-1">
                                                                            <input readonly type="number" name="dim_volume[]" id="dim_volume<?php echo $dim_item; ?>" class="form-control text-end bg-light text-bg-light" style="min-width: 100px;" value="<?php echo (!empty($dim_volume_arr[$dim_index]) ? $dim_volume_arr[$dim_index] : ''); ?>">
                                                                        </div>

                                                                        <div class="col-lg-1 mt-1">
                                                                            <span class="badge bg-warning" onclick="ajax_delete_dimension_item('<?php echo $dim_item_id; ?>', '<?php echo $dim_item; ?>')" style="cursor: pointer;"> <i class="ph-x"></i> </span>
                                                                        </div>


                                                                    </div>
                                                                </div>

                                                            <?php } // for 
                                                            ?>



                                                            <!--
                                                            |--------------------------------------------------------------------------------------- 
                                                            | ADD NEW DIM ROW
                                                            |--------------------------------------------------------------------------------------- 
                                                            -->

                                                            <div id="add_dim_row_here"></div>


                                                            <!--
                                                            |--------------------------------------------------------------------------------------- 
                                                            | TOTAL NO. OF PCS / CBM / VOLUME
                                                            |--------------------------------------------------------------------------------------- 
                                                            -->

                                                            <div class="col-lg-12 mt-2">
                                                                <div class="row">

                                                                    <div class="col-lg-2">
                                                                        <input readonly type="text" name="total_dim_pcs[]" id="total_dim_pcs" class="form-control bg-light text-center" value="<?php echo $total_pcs; ?>">
                                                                    </div>

                                                                    <div class="col-lg-2"></div>
                                                                    <div class="col-lg-1"></div>
                                                                    <div class="col-lg-1"></div>
                                                                    <div class="col-lg-1"></div>
                                                                    <div class="col-lg-1"></div>

                                                                    <div class="col-lg-1">
                                                                        <input readonly type="text" name="total_dim_cbm[]" style="min-width: 100px;" id="total_dim_cbm" class="form-control bg-light text-center" value="<?php echo $total_cbm; ?>">
                                                                    </div>

                                                                    <div class="col-lg-1">
                                                                        <input readonly type="text" name="total_dim_volume[]" style="min-width: 100px;" id="total_dim_volume" class="form-control bg-light text-center" value="<?php echo $total_volume; ?>">
                                                                    </div>

                                                                </div>
                                                            </div>


                                                            <div class="mt-4">
                                                                <span id="span_add_dim_item_row<?php echo $sale_order_item; ?>"><a href="#" onclick="add_dim_item_row(); "><span class="badge bg-primary"> Add New Dimension </a></span></span>
                                                            </div>

                                                            <div class="col-lg-12">
                                                                <div class="row mt-4"><small class="text-muted">Mandtory fields --> No. of PCs, Lenght, Width, Height </small></div>
                                                            </div>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" onclick="saveDimensions();" class="btn btn-success">Save</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <script>
                                            $(document).ready(function() {
                                                // Initialize display table on page load
                                                initializeDimensionDisplay();
                                                
                                                // Open popup with existing data
                                                $("#openDimensionPopup").on("click", function() {
                                                    $("#popupId").val($("#dimensionIdMaster").val());
                                                    $("#popupName").val($("#dimensionName").val());
                                                    $("#dimensionModal").modal("show");
                                                });

                                                // Persist dimensions on close and restore on reopen
                                                $("#dimensionModal").on("hidden.bs.modal", function() {
                                                    saveDimensionsSnapshot();
                                                });

                                                $("#dimensionModal").on("shown.bs.modal", function() {
                                                    restoreDimensionsFromSnapshot();
                                                });
                                            });
                                            
                                            function initializeDimensionDisplay() {
                                                // On page load, ensure display table shows any existing data
                                                updateDimensionDisplayTable();
                                            }

                                            function saveDimensionsSnapshot() {
                                                const snapshotEl = document.getElementById('dimensions_snapshot');
                                                if (!snapshotEl) return;
                                                snapshotEl.value = JSON.stringify(collectDimensionsJSON());
                                            }

                                            function restoreDimensionsFromSnapshot() {
                                                const snapshotEl = document.getElementById('dimensions_snapshot');
                                                if (!snapshotEl || !snapshotEl.value) return;

                                                // If there is already data in the modal, do not overwrite it
                                                const hasData = Array.from(document.querySelectorAll("[id^='dim_pcs']")).some(el => el.value && parseFloat(el.value) > 0);
                                                if (hasData) {
                                                    calculateDimGrand();
                                                    return;
                                                }

                                                let dims = [];
                                                try {
                                                    dims = JSON.parse(snapshotEl.value);
                                                } catch (e) {
                                                    return;
                                                }

                                                if (!Array.isArray(dims) || dims.length === 0) return;

                                                let currentRows = document.querySelectorAll("[id^='dim_pcs']").length;
                                                while (currentRows < dims.length) {
                                                    add_dim_item_row();
                                                    currentRows++;
                                                }

                                                dims.forEach((dim, idx) => {
                                                    const i = idx + 1;
                                                    if (document.getElementById("dim_item_id" + i)) document.getElementById("dim_item_id" + i).value = dim.item_id || '';
                                                    if (document.getElementById("dim_pcs" + i)) document.getElementById("dim_pcs" + i).value = dim.pcs || '';
                                                    if (document.getElementById("dim_unit" + i)) document.getElementById("dim_unit" + i).value = dim.unit || 'cm';
                                                    if (document.getElementById("dim_length" + i)) document.getElementById("dim_length" + i).value = dim.length || '';
                                                    if (document.getElementById("dim_width" + i)) document.getElementById("dim_width" + i).value = dim.width || '';
                                                    if (document.getElementById("dim_height" + i)) document.getElementById("dim_height" + i).value = dim.height || '';
                                                    if (document.getElementById("dim_formula" + i)) document.getElementById("dim_formula" + i).value = dim.formula || '6000';
                                                    if (document.getElementById("dim_cbm" + i)) document.getElementById("dim_cbm" + i).value = dim.cbm || '';
                                                    if (document.getElementById("dim_volume" + i)) document.getElementById("dim_volume" + i).value = dim.volume || '';
                                                });

                                                calculateDimGrand();
                                            }

                                            function collectDimensionsJSON() {
                                                let dimensions = [];

                                                // let totalDimRows = parseInt(document.getElementById('total_dim_rows').value, 10);
                                                // for (let i = 1; i <= totalDimRows; i++) {

                                                let totalRows = document.querySelectorAll("[id^='dim_pcs']").length; // count rows by pcs field
                                                for (let i = 1; i <= totalRows; i++) {
                                                    let dimension = {
                                                        module_type: 'sale_orders',
                                                        record_id: document.getElementById("id")?.value || "",
                                                        sale_order_id: document.getElementById("id")?.value || "",
                                                        item_id: document.getElementById("dim_item_id" + i)?.value || "",
                                                        pcs: document.getElementById("dim_pcs" + i)?.value || "",
                                                        unit: document.getElementById("dim_unit" + i)?.value || "",
                                                        length: document.getElementById("dim_length" + i)?.value || "",
                                                        width: document.getElementById("dim_width" + i)?.value || "",
                                                        height: document.getElementById("dim_height" + i)?.value || "",
                                                        formula: document.getElementById("dim_formula" + i)?.value || "",
                                                        cbm: document.getElementById("dim_cbm" + i)?.value || "",
                                                        volume: document.getElementById("dim_volume" + i)?.value || ""
                                                    };

                                                    dimensions.push(dimension);
                                                }

                                                return dimensions;
                                            }


                                            function saveDimensions() {

                                                let dimensions = collectDimensionsJSON();

                                                $.ajax({
                                                    url: "internal_request.php",
                                                    type: "POST",
                                                    data: {
                                                        ajax_action: "save_dimensions",
                                                        dimensions: dimensions
                                                    },
                                                    dataType: "json",
                                                    success: function(response) {
                                                        if (response.status === "success") {
                                                            // alert(response.saved_rows + " dimensions saved!");
                                                            console.log(response.item_id);
                                                        } else {
                                                            alert("Error: " + response.error_message);
                                                        }
                                                    }
                                                });

                                                document.getElementById('volume').value = document.getElementById('total_dim_volume').value;
                                                document.getElementById('cbm').value = document.getElementById('total_dim_cbm').value;
                                                calculateChargeableWeight();
                                                
                                                // Update the display table below the modal
                                                updateDimensionDisplayTable();

                                                // Hide Modal
                                                $("#dimensionModal").modal("hide");
                                            }
                                            
                                            function updateDimensionDisplayTable() {
                                                const totalDimRows = parseInt(document.getElementById('total_dim_rows').value, 10);
                                                let hasData = false;
                                                
                                                for (let i = 1; i <= totalDimRows; i++) {
                                                    // Get values from modal inputs
                                                    const pcs = document.getElementById(`dim_pcs${i}`)?.value || '';
                                                    const unit = document.getElementById(`dim_unit${i}`)?.value || '';
                                                    const length = document.getElementById(`dim_length${i}`)?.value || '';
                                                    const width = document.getElementById(`dim_width${i}`)?.value || '';
                                                    const height = document.getElementById(`dim_height${i}`)?.value || '';
                                                    const cbm = document.getElementById(`dim_cbm${i}`)?.value || '';
                                                    const volume = document.getElementById(`dim_volume${i}`)?.value || '';
                                                    
                                                    // Check if there's any data in this row
                                                    if (pcs || unit || length || width || height || cbm || volume) {
                                                        hasData = true;
                                                    }
                                                    
                                                    // Update the display labels in the table below using the new IDs
                                                    const displayPcs = document.getElementById(`display_dim_pcs_${i}`);
                                                    const displayUnit = document.getElementById(`display_dim_unit_${i}`);
                                                    const displayLength = document.getElementById(`display_dim_length_${i}`);
                                                    const displayWidth = document.getElementById(`display_dim_width_${i}`);
                                                    const displayHeight = document.getElementById(`display_dim_height_${i}`);
                                                    const displayCbm = document.getElementById(`display_dim_cbm_${i}`);
                                                    const displayVolume = document.getElementById(`display_dim_volume_${i}`);
                                                    
                                                    if (displayPcs) displayPcs.textContent = pcs;
                                                    if (displayUnit) displayUnit.textContent = unit;
                                                    if (displayLength) displayLength.textContent = length;
                                                    if (displayWidth) displayWidth.textContent = width;
                                                    if (displayHeight) displayHeight.textContent = height;
                                                    if (displayCbm) displayCbm.textContent = cbm;
                                                    if (displayVolume) displayVolume.textContent = volume;
                                                }
                                                
                                                // Show/hide header based on data
                                                const header = document.getElementById('dim_display_header');
                                                if (header) {
                                                    header.style.display = hasData ? '' : 'none';
                                                }
                                            }
                                        </script>

                                        <script>
                                            function add_dim_item_row() {
                                                var div_add_dim_here = document.getElementById('add_dim_row_here');
                                                var total_dim_rows = document.getElementById('total_dim_rows').value;
                                                total_dim_rows++;

                                                var new_dim_row = "";

                                                new_dim_row += "<div class=\"row mt-2\" id=\"dim_row_" + total_dim_rows + "\">";
                                                new_dim_row += "<input type=\"hidden\" name=\"dim_item_id[]\" id=\"dim_item_id" + total_dim_rows + "\">";

                                                new_dim_row += "<div class=\"col-lg-2\">";
                                                new_dim_row += "<input type=\"number\" step=\"1\" name=\"dim_pcs[]\" id=\"dim_pcs" + total_dim_rows + "\" min=\"1\" onkeyup=\"calculateDim('" + total_dim_rows + "');\" onchange=\"calculateDim('" + total_dim_rows + "');\" placeholder=\"\" class=\"form-control text-center\">";
                                                new_dim_row += "</div>";

                                                new_dim_row += "<div class=\"col-lg-2\">";
                                                new_dim_row += "<select class=\"form-select\" name=\"dim_unit[]\" id=\"dim_unit" + total_dim_rows + "\">";
                                                new_dim_row += "<option value=\"cm\">Centimetre</option>";
                                                new_dim_row += "</select>";
                                                new_dim_row += "</div>";

                                                new_dim_row += "<div class=\"col-lg-1\">";
                                                new_dim_row += "<input type=\"number\" step=\"1\" name=\"dim_length[]\" id=\"dim_length" + total_dim_rows + "\" min=\"1\" onkeyup=\"calculateDim('" + total_dim_rows + "');\" onchange=\"calculateDim('" + total_dim_rows + "');\" placeholder=\"\" class=\"form-control text-center\">";
                                                new_dim_row += "</div>";

                                                new_dim_row += "<div class=\"col-lg-1\">";
                                                new_dim_row += "<input type=\"number\" step=\"1\" name=\"dim_width[]\" id=\"dim_width" + total_dim_rows + "\" min=\"1\" onkeyup=\"calculateDim('" + total_dim_rows + "');\" onchange=\"calculateDim('" + total_dim_rows + "');\" placeholder=\"\" class=\"form-control text-center\">";
                                                new_dim_row += "</div>";

                                                new_dim_row += "<div class=\"col-lg-1\">";
                                                new_dim_row += "<input type=\"number\" step=\"1\" name=\"dim_height[]\" id=\"dim_height" + total_dim_rows + "\" min=\"1\" onkeyup=\"calculateDim('" + total_dim_rows + "');\" onchange=\"calculateDim('" + total_dim_rows + "');\" placeholder=\"\" class=\"form-control text-center\">";
                                                new_dim_row += "</div>";

                                                new_dim_row += "<div class=\"col-lg-1\">";
                                                new_dim_row += "<select class=\"form-select\" style=\"min-width: 100px;\" name=\"dim_formula[]\" id=\"dim_formula" + total_dim_rows + "\" onkeyup=\"calculateDim('" + total_dim_rows + "');\" onchange=\"calculateDim('" + total_dim_rows + "');\">";
                                                new_dim_row += "<option value=\"6000\">6000</option>";
                                                new_dim_row += "<option value=\"5000\">5000</option>";
                                                new_dim_row += "</select>";
                                                new_dim_row += "</div>";

                                                new_dim_row += "<div class=\"col-lg-1\">";
                                                new_dim_row += "<input readonly type=\"number\" step=\"1\" name=\"dim_cbm[]\" id=\"dim_cbm" + total_dim_rows + "\" class=\"form-control bg-light text-end\" style=\"min-width: 100px;\">";
                                                new_dim_row += "</div>";

                                                new_dim_row += "<div class=\"col-lg-1\">";
                                                new_dim_row += "<input readonly type=\"number\" step=\"1\" name=\"dim_volume[]\" id=\"dim_volume" + total_dim_rows + "\" class=\"form-control bg-light text-end\" style=\"min-width: 100px;\">";
                                                new_dim_row += "</div>";


                                                new_dim_row += "<div class=\"col-lg-1 mt-1\"><span id=\"span_remove_dim_item_row" + total_dim_rows + "\"> <a href=\"#\" onclick=\"clear_dim_row(" + total_dim_rows + ")\"><span class=\"badge bg-warning\"> <i class=\"ph-x\"></i> </span></a></span> </div>";

                                                new_dim_row += "</div>";

                                                // This is to preserve the values of previously dynamicall created elements
                                                document.getElementById('add_dim_row_here').insertAdjacentHTML("beforebegin", new_dim_row);

                                                document.getElementById('total_dim_rows').value = total_dim_rows;

                                                // ajax_populate_services();

                                            }


                                            function clear_dim_row(dim_row_no) {
                                                // console.log(dim_row_no);

                                                document.getElementById('dim_pcs' + dim_row_no).value = '0';
                                                document.getElementById('dim_unit' + dim_row_no).text = '';
                                                document.getElementById('dim_unit' + dim_row_no).value = '';
                                                document.getElementById('dim_length' + dim_row_no).value = '';
                                                document.getElementById('dim_width' + dim_row_no).value = '';
                                                document.getElementById('dim_height' + dim_row_no).value = '';
                                                document.getElementById('dim_formula' + dim_row_no).text = '';
                                                document.getElementById('dim_formula' + dim_row_no).value = '';
                                                document.getElementById('dim_cbm' + dim_row_no).value = '';
                                                document.getElementById('dim_volume' + dim_row_no).value = '';

                                                document.getElementById('dim_row_' + dim_row_no).style.display = 'none';

                                                calculateDim(dim_row_no);
                                                calculateDimGrand();

                                            }



                                            // -------------------------------------------------------------------------
                                            // Helper function to check if a field has a valid, non-empty value
                                            // -------------------------------------------------------------------------
                                            function isValidValue(element) {
                                                return element &&
                                                    element.value !== '' &&
                                                    element.value !== null &&
                                                    element.value !== undefined &&
                                                    !isNaN(element.value) &&
                                                    parseFloat(element.value) > 0;
                                            }

                                            // -------------------------------------------------------------------------
                                            //  CALCULATE CBM / VOLUME
                                            // -------------------------------------------------------------------------
                                            function calculateDim(dim_row_no) {

                                                // console.log(row_no);

                                                let dim_pcs = document.getElementById('dim_pcs' + dim_row_no);
                                                let dim_unit = document.getElementById('dim_unit' + dim_row_no);
                                                let dim_length = document.getElementById('dim_length' + dim_row_no);
                                                let dim_width = document.getElementById('dim_width' + dim_row_no);
                                                let dim_height = document.getElementById('dim_height' + dim_row_no);
                                                let dim_formula = document.getElementById('dim_formula' + dim_row_no); // Fixed: "6000" or "5000"

                                                // Check if ALL fields are valid and non-empty
                                                if (isValidValue(dim_pcs) &&
                                                    isValidValue(dim_length) &&
                                                    isValidValue(dim_width) &&
                                                    isValidValue(dim_height) &&
                                                    isValidValue(dim_formula)) {

                                                    // All fields are filled with valid positive numbers
                                                    // Your code here..

                                                    // // ---  Calculate VOLUME ------------------
                                                    let volume = ((parseFloat(dim_length.value) * parseFloat(dim_width.value) * parseFloat(dim_height.value)) / dim_formula.value);
                                                    volume = parseFloat(volume) * parseFloat(dim_pcs.value);
                                                    document.getElementById('dim_volume' + dim_row_no).value = volume.toFixed(2);

                                                    // ---  Calculate CBM ------------------
                                                    let cbm = (parseFloat(volume) / 166.67);
                                                    document.getElementById('dim_cbm' + dim_row_no).value = cbm.toFixed(2);

                                                    calculateDimGrand();

                                                    // if removed any mandatory input
                                                } else {

                                                    document.getElementById('dim_cbm' + dim_row_no).value = '';
                                                    document.getElementById('dim_volume' + dim_row_no).value = '';

                                                    calculateDimGrand();

                                                } // if

                                            } // function


                                            function updateFormula(dim_row_no) {
                                                const unit = document.getElementById('dim_unit' + dim_row_no).value;
                                                const formulaField = document.getElementById('dim_formula' + dim_row_no);
                                                formulaField.value = (unit === 'in' || unit === 'inch' || unit === 'inches') ? 166.67 : 5000;
                                                calculateDim(dim_row_no); // recalculate
                                            }


                                            // -------------------------------------------------------------------------
                                            //  DIM GRAND CALCULATIONS
                                            // -------------------------------------------------------------------------
                                            function calculateDimGrand() {
                                                const totalDimRows = parseInt(document.getElementById('total_dim_rows').value, 10);

                                                // Initialize as numbers (0), not strings!
                                                let finalTotalPcs = 0;
                                                let finalTotalCbm = 0;
                                                let finalTotalVolume = 0;

                                                for (let i = 1; i <= totalDimRows; i++) {
                                                    const pcs = parseFloat(document.getElementById('dim_pcs' + i)?.value) || 0;
                                                    const cbm = parseFloat(document.getElementById('dim_cbm' + i)?.value) || 0;
                                                    const volume = parseFloat(document.getElementById('dim_volume' + i)?.value) || 0;


                                                    if (pcs > 0 && cbm > 0 && volume > 0)
                                                        finalTotalPcs += pcs;
                                                    finalTotalCbm += cbm;
                                                    finalTotalVolume += volume;
                                                }


                                                // Update fields — only format to 2 decimals if needed
                                                document.getElementById('total_dim_pcs').value = finalTotalPcs.toFixed(2);
                                                document.getElementById('total_dim_cbm').value = finalTotalCbm.toFixed(2);
                                                document.getElementById('total_dim_volume').value = finalTotalVolume.toFixed(2);
                                            }

                                            function calculateChargeableWeight() {
                                                const grossWeight = document.getElementById('gross_weight');
                                                const volumeWeight = document.getElementById('volume');
                                                const chargeableWeightField = document.getElementById('chargeable_weight');

                                                // Helper: safely parse and validate a field's value
                                                function getValidNumber(element) {
                                                    if (!element || element.value === '' || isNaN(element.value)) {
                                                        return null;
                                                    }
                                                    const num = parseFloat(element.value);
                                                    return num > 0 ? num : null;
                                                }

                                                const volume = getValidNumber(volumeWeight);
                                                const gross = getValidNumber(grossWeight);

                                                let chargeable = 0;

                                                if (volume !== null && gross !== null) {
                                                    // Both valid → take the greater one
                                                    chargeable = Math.max(volume, gross);
                                                } else if (volume !== null) {
                                                    // Only volume is valid
                                                    chargeable = volume;
                                                } else if (gross !== null) {
                                                    // Only gross is valid
                                                    chargeable = gross;
                                                }
                                                // If neither is valid, chargeable remains 0

                                                // Set the result (or empty if 0 and you prefer blank)
                                                chargeableWeightField.value = chargeable > 0 ? chargeable : '';
                                            }
                                        </script>

                                        <input type="hidden" name="total_dim_rows" id="total_dim_rows" value="<?php echo $total_dim_rows; ?>">
                                        <input type="hidden" name="dimensions_snapshot" id="dimensions_snapshot" value="">


                                        <div class="row mb-2 mt-3">

                                            <div class="row mb-2" id="dim_display_header" style=" <?php if ($total_dim_rows <= 1 && empty($dim_pcs_arr[0])) {
                                                                                echo 'display:none';
                                                                            } ?>">

                                                <div class="col-lg-1">
                                                    <label class="form-label fw-semibold">PCS</label>
                                                </div>

                                                <div class="col-lg-1">
                                                    <label class="form-label fw-semibold">UNITS</label>
                                                </div>

                                                <div class="col-lg-2">
                                                    <label class="form-label fw-semibold">LENGTH </label>
                                                </div>

                                                <div class="col-lg-2">
                                                    <label class="form-label fw-semibold">WIDTH</label>
                                                </div>

                                                <div class="col-lg-2">
                                                    <label class="form-label fw-semibold">HEIGHT</label>
                                                </div>

                                                <div class="col-lg-2">
                                                    <label class="form-label fw-semibold">CBM</label>
                                                </div>

                                                <div class="col-lg-2">
                                                    <label class="form-label fw-semibold">VOLUME</label>
                                                </div>

                                            </div>


                                            <div class="row mb-2" id="dim_display_rows">

                                                <?php
                                                // ----------------------------------------------------------------------------
                                                for ($dim_item = 1; $dim_item <= $total_dim_rows; $dim_item++) {
                                                    $dim_index = $dim_item;
                                                    $dim_index = $dim_index - 1;
                                                    // ----------------------------------------------------------------------------
                                                ?>
                                                    <div class="col-lg-12 mt-2" id="display_dim_row_<?php echo $dim_item; ?>">
                                                        <div class="row">

                                                            <div class="col-lg-1">
                                                                <label class="form-label" id="display_dim_pcs_<?php echo $dim_item; ?>"><?php echo (!empty($dim_pcs_arr[$dim_index]) ? $dim_pcs_arr[$dim_index] : ''); ?></label>
                                                            </div>

                                                            <div class="col-lg-1">
                                                                <label class="form-label" id="display_dim_unit_<?php echo $dim_item; ?>"><?php echo (!empty($dim_unit_arr[$dim_index]) ? $dim_unit_arr[$dim_index] : ''); ?></label>
                                                            </div>

                                                            <div class="col-lg-2">
                                                                <label class="form-label" id="display_dim_length_<?php echo $dim_item; ?>"><?php echo (!empty($dim_length_arr[$dim_index]) ? $dim_length_arr[$dim_index] : ''); ?></label>
                                                            </div>

                                                            <div class="col-lg-2">
                                                                <label class="form-label" id="display_dim_width_<?php echo $dim_item; ?>"><?php echo (!empty($dim_width_arr[$dim_index]) ? $dim_width_arr[$dim_index] : ''); ?></label>
                                                            </div>

                                                            <div class="col-lg-2">
                                                                <label class="form-label" id="display_dim_height_<?php echo $dim_item; ?>"><?php echo (!empty($dim_height_arr[$dim_index]) ? $dim_height_arr[$dim_index] : ''); ?></label>
                                                            </div>

                                                            <div class="col-lg-2">
                                                                <label class="form-label" id="display_dim_cbm_<?php echo $dim_item; ?>"><?php echo (!empty($dim_cbm_arr[$dim_index]) ? $dim_cbm_arr[$dim_index] : ''); ?></label>
                                                            </div>

                                                            <div class="col-lg-2">
                                                                <label class="form-label" id="display_dim_volume_<?php echo $dim_item; ?>"><?php echo (!empty($dim_volume_arr[$dim_index]) ? $dim_volume_arr[$dim_index] : ''); ?></label>
                                                            </div>

                                                        </div>
                                                    </div>

                                                <?php } // for 
                                                ?>

                                            </div>


                                        </div>

                                    </div>



                                    <div class="row mb-2 mt-3">
                                        <label class="col-lg-3 col-form-label">Gross Weight:</label>
                                        <div class="col-lg-3">
                                            <input type="number" step="1" class="form-control" name="gross_weight" id="gross_weight" value="<?php echo $gross_weight; ?>" onkeyup="calculateChargeableWeight();" onchange="calculateChargeableWeight();">
                                        </div>

                                        <label class="col-lg-3 col-form-label">Volume:</label>
                                        <div class="col-lg-3">
                                            <input type="number" step="1" class="form-control bg-light" name="volume" id="volume" value="<?php echo $volume; ?>" readonly onkeyup="calculateChargeableWeight();" onchange="calculateChargeableWeight();">
                                        </div>
                                    </div>

                                    <div class="row mb-2">

                                        <label class="col-lg-3 col-form-label">Chargeable Weight:</label>
                                        <div class="col-lg-3">
                                            <input readonly type="number" step="1" class="form-control bg-light" name="chargeable_weight" id="chargeable_weight" value="<?php echo $chargeable_weight; ?>">
                                        </div>

                                        <label class="col-lg-3 col-form-label">CBM:</label>
                                        <div class="col-lg-3">
                                            <input readonly type="number" step="1" class="form-control bg-light" name="cbm" id="cbm" value="<?php echo $cbm; ?>">
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
                                    for ($sale_order_item = 1; $sale_order_item <= $total_rows; $sale_order_item++) {
                                        $index = $sale_order_item;
                                        $index = $index - 1;

                                        // ----------------------------------------------------------------------------
                                    ?>

                                        <div class="mb-2">
                                            <div class="row mb-3 pb-3" id="row_<?php echo $sale_order_item; ?>">


                                                <div class="col-lg-12">
                                                    <div class="row">

                                                        <input type="hidden" name="item_id[]" id="item_id<?php echo $sale_order_item; ?>" value="<?php echo (!empty($item_id_arr[$index]) ? $item_id_arr[$index] : ''); ?>">

                                                        <div class="col-lg-2">
                                                            <select class="form-select" name="service[]" id="service<?php echo $sale_order_item; ?>" onchange="ajax_populate_item_rate(this.value, <?php echo $sale_order_item; ?>); ">
                                                                <option value="0">Please select</option>
                                                                <?php
                                                                $result = $mysqli->query("SELECT * FROM `" . DB::ITEMS . "` WHERE is_active=1 AND item_type='services' ORDER BY item_name");
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
                                                            <textarea name="description[]" id="description<?php echo $sale_order_item; ?>" rows="2" class="form-control" placeholder="Add a description to your item"><?php echo (!empty($description_arr[$index]) ? $description_arr[$index] : ''); ?></textarea>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" step="1" name="qty[]" id="qty<?php echo $sale_order_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($qty_arr[$index]) ? $qty_arr[$index] : '1'); ?>" onkeyup="calculateItemAmount('<?php echo $sale_order_item; ?>');" onchange=" calculateItemAmount('<?php echo $sale_order_item; ?>');"> <!--  step="0.1" value="0.0" -->
                                                        </div>


                                                        <div class="col-lg-1">
                                                            <input type="number" step="1" name="rate[]" id="rate<?php echo $sale_order_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($rate_arr[$index]) ? $rate_arr[$index] : '0'); ?>" onkeyup="calculateItemAmount('<?php echo $sale_order_item; ?>');" onchange=" calculateItemAmount('<?php echo $sale_order_item; ?>');"> <!--  step="0.1" value="0.0" -->
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input readonly type="number" name="sub_total[]" id="sub_total<?php echo $sale_order_item; ?>" min="0" class="form-control bg-light bg-opacity-75 text-end" value="<?php echo (!empty($sub_total_arr[$index]) ? $sub_total_arr[$index] : '0'); ?>"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <select name="tax[]" id="tax<?php echo $sale_order_item; ?>" class="form-select" onchange="calculateItemAmount(<?php echo $sale_order_item; ?>, this.value); ">
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
                                                                <span class="badge bg-light text-black" style="font-weight: normal;" id="div_tax_amount<?php echo $sale_order_item; ?>" style="display: <?php if (!empty($tax_arr[$index])) { ?> block <?php } else { ?> none <?php } ?>;">
                                                                    <span id="span_tax_amount<?php echo $sale_order_item; ?>">
                                                                        <?php echo (!empty($tax_amount_arr[$index]) ? $tax_amount_arr[$index] : '0'); ?>
                                                                    </span>
                                                                </span>
                                                            </div>

                                                            <input type="hidden" name="tax_amount[]" id="tax_amount<?php echo $sale_order_item; ?>" class="form-control" placeholder="0" value="<?php echo (!empty($tax_amount_arr[$index]) ? $tax_amount_arr[$index] : '0'); ?>">
                                                            <!-- <div class="form-text bg-light border border-top-0 rounded-bottom text-end px-2 py-1 mt-0">15,584</div> -->
                                                        </div>


                                                        <div class="col-lg-1">
                                                            <input readonly type="number" name="total[]" id="total<?php echo $sale_order_item; ?>" min="0" class="form-control bg-light bg-opacity-75 text-end" placeholder="0" value="<?php echo (!empty($total_arr[$index]) ? $total_arr[$index] : ''); ?>" onchange="calculateGrand(<?php echo $sale_order_item; ?>);" onkeyup="calculateGrand(<?php echo $sale_order_item; ?>);"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-2 mt-1">
                                                            <?php if ($sale_order_item > 1) { ?>
                                                                <a href="#" onclick="calculateItemAmount('<?php echo $sale_order_item; ?>'); clear_row(<?php echo $sale_order_item; ?>)"><span class="badge bg-warning"> <i class="ph-x"></i> </span></a>
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
                                    <span id="span_add_item_row<?php echo $sale_order_item; ?>"><a href="#" onclick="add_item_row(); "><span class="badge bg-primary"> Add New Row </a></span></span>
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
                                        new_row += "<input type=\"number\" step=\"1\" name=\"qty[]\" id=\"qty" + total_rows + "\" min=\"1\" onkeyup=\"calculateItemAmount('" + total_rows + "');\" onchange=\"calculateItemAmount('" + total_rows + "');\" placeholder=\"1\" class=\"form-control text-center\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"rate[]\" id=\"rate" + total_rows + "\" min=\"0\" placeholder=\"0\" class=\"form-control text-center\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input readonly type=\"number\" name=\"sub_total[]\" id=\"sub_total" + total_rows + "\" min=\"0\" placeholder=\"0\" class=\"form-control bg-light bg-opacity-75 text-end\">";
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
                                        <textarea class="form-control" name="customer_notes" id="customer_notes" style="field-sizing: content;" placeholder="Enter any notes to be displayed in your transaction"><?php echo $customer_notes; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">Terms & Conditions: </label>
                                        <textarea class="form-control text-wrap" name="terms_and_conditions" id="terms_and_conditions" style="field-sizing: content;" placeholder="Enter the terms and conditions of your business to be displayed in your transaction"><?php echo $terms_and_conditions; ?></textarea>
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
        </form>
    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>
</div>


<?php include('admin_elements/admin_footer.php'); ?>
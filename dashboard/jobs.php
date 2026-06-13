<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'jobs';
$module_caption     = 'Job';
$tbl_name = DB::JOBS;
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



if (isset($_REQUEST['customer_id']) && !empty($_REQUEST['customer_id'])) {
    $customer_id     = e_s__($_REQUEST['customer_id']);
} else {
    $customer_id = 0;
}



if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 0;


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


$modified_by                = '';
$customer_type              = '';
$quote_id                   = '';
$books_customer_id          = '';
$approved_time              = '';
$project_id                 = '';
$approved_time_resubmission = '';




// ---------------------- Tags Array -----------------------------
$tags_arr           = array();
$posted_tags_arr    = array();
$tags_string        = '';
$tag                = '';


if (isset($_POST['tags'])) {

    $posted_tags = $_POST['tags'];

    foreach ($posted_tags as $tag) {
        $tags_string .= $tag . ', ';
    }
    if (strlen($tags_string) > 2) {
        $tags_string = substr($tags_string, 0, -2);
    }
    // echo $tags_string;

    $posted_tags_arr = explode(',', $tags_string);
    // print_r($posted_tags_arr);
}

// ---------------------- Services Array -----------------------------
$services_arr           = array();
$posted_services_arr    = array();
$services_string        = '';
$service                = '';


if (isset($_POST['services'])) {

    $posted_services = $_POST['services'];

    foreach ($posted_services as $service) {
        $services_string .= $service . ', ';
    }
    if (strlen($services_string) > 2) {
        $services_string = substr($services_string, 0, -2);
    }
    // echo $services_string;

    $posted_services_arr = explode(',', $services_string);
    // print_r($posted_tags_arr);
}




// ---------------------- Job Items -----------------------------
$item_dim_id_arr            = array();
$dim_length_arr             = array();
$dim_width_arr              = array();
$dim_height_arr             = array();
$dim_pcs_arr                = array();
$dim_volume_arr             = array();
$dim_cbm_arr                = array();


$total_rows            = 1;
if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows = e_s__($_POST['total_rows']);
}



if ($action == "update_$module" || $action == "add_$module") {

    for ($dim_item = 1; $dim_item <= $total_rows; $dim_item++) {

        $index = $dim_item;
        $index = $index - 1;

        $post_item_dim_id   = (isset($_POST['item_dim_id'][$index]) && !empty($_POST['item_dim_id'][$index]) ? $_POST['item_dim_id'][$index] :  0);
        $post_dim_length    = (isset($_POST['dim_length'][$index]) && !empty($_POST['dim_length'][$index]) ? $_POST['dim_length'][$index] :  0);
        $post_dim_width     = (isset($_POST['dim_width'][$index]) && !empty($_POST['dim_width'][$index]) ? $_POST['dim_width'][$index] :  '');
        $post_dim_height    = (isset($_POST['dim_height'][$index]) && !empty($_POST['dim_height'][$index]) ? $_POST['dim_height'][$index] :  '');
        $post_dim_pcs       = (isset($_POST['dim_pcs'][$index]) && !empty($_POST['dim_pcs'][$index]) ? $_POST['dim_pcs'][$index] :  1);
        $post_dim_volume    = (isset($_POST['dim_volume'][$index]) && !empty($_POST['dim_volume'][$index]) ? $_POST['dim_volume'][$index] :  0);
        $post_dim_cbm       = (isset($_POST['dim_cbm'][$index]) && !empty($_POST['dim_cbm'][$index]) ? $_POST['dim_cbm'][$index] :  0);


        array_push($item_dim_id_arr,            e_s__($post_item_dim_id));
        array_push($dim_length_arr,             e_s__($post_dim_length));
        array_push($dim_width_arr,              e_s__($post_dim_width));
        array_push($dim_height_arr,             e_s__($post_dim_height));
        array_push($dim_pcs_arr,                e_s__($post_dim_pcs));
        array_push($dim_volume_arr,             e_s__($post_dim_volume));
        array_push($dim_cbm_arr,                e_s__($post_dim_cbm));
    } //for 
}


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {

    $job_date                   = e_s__($_POST['job_date']);
    $job_status                 = e_s__($_POST['job_status']);

    $warehouse_id               = e_s__($_POST['warehouse_id']);
    $customer_id                = e_s__($_POST['customer_id']);
    $sales_person_from_lead     = e_s__($_POST['sales_person_from_lead']);
    $job_seq                    = e_s__($_POST['job_seq']);
    $sales_person               = e_s__($_POST['sales_person']);

    $currency                   = e_s__($_POST['currency']);
    $exchange_rate              = e_s__($_POST['exchange_rate']);
    $transport_mode             = e_s__($_POST['transport_mode']);
    $shipment_type              = e_s__($_POST['shipment_type']);
    $job_owner                  = e_s__($_POST['job_owner']);

    $job_no                     = e_s__($_POST['job_no']);
    $job_ref_no                 = e_s__($_POST['job_ref_no']);
    $cs_agent                   = e_s__($_POST['cs_agent']);
    $incoterm                   = e_s__($_POST['incoterm']);
    $email                      = e_s__($_POST['email']);
    $supplier_rate              = e_s__($_POST['supplier_rate']);
    $estimated_net_profit       = e_s__($_POST['estimated_net_profit']);

    $estimated_invoice_amount   = e_s__($_POST['estimated_invoice_amount']);
    $etd                        = e_s__($_POST['etd']);
    $eta                        = e_s__($_POST['eta']);
    $carrier                    = e_s__($_POST['carrier']);
    $vessel_name                = e_s__($_POST['vessel_name']);
    $vessel_departure_date      = e_s__($_POST['vessel_departure_date']);
    $flight_no                  = e_s__($_POST['flight_no']);
    $flight_departure_date      = e_s__($_POST['flight_departure_date']);
    $job_completion_date        = e_s__($_POST['job_completion_date']);
    $payment_terms              = e_s__($_POST['payment_terms']);
    $hawb                       = e_s__($_POST['hawb']);
    $mawb                       = e_s__($_POST['mawb']);
    $estimated_cost_amount      = e_s__($_POST['estimated_cost_amount']);
    $declaration_no             = e_s__($_POST['declaration_no']);

    $gross_weight               = e_s__($_POST['gross_weight']);
    $volume_weight              = e_s__($_POST['volume_weight']);
    $chargeable_weight          = e_s__($_POST['chargeable_weight']);
    $no_of_pieces               = e_s__($_POST['no_of_pieces']);
    $commodity_type             = e_s__($_POST['commodity_type']);

    $no_of_containers           = e_s__($_POST['no_of_containers']);
    $insurance_needed           = e_s__($_POST['insurance_needed']);
    $container_type             = e_s__($_POST['container_type']);
    $temperature_control_required = e_s__($_POST['temperature_control_required']);
    $container_number           = e_s__($_POST['container_number']);
    $special_comments           = e_s__($_POST['special_comments']);

    $landing_country            = e_s__($_POST['landing_country']);
    $landing_port               = e_s__($_POST['landing_port']);
    $loading_place              = e_s__($_POST['loading_place']);
    $billing_city               = e_s__($_POST['billing_city']);
    $billing_state              = e_s__($_POST['billing_state']);
    $billing_code               = e_s__($_POST['billing_code']);
    $billing_country            = e_s__($_POST['billing_country']);

    $destination_country        = e_s__($_POST['destination_country']);
    $destination_port           = e_s__($_POST['destination_port']);
    $fdp                        = e_s__($_POST['fdp']);
    $shipping_city              = e_s__($_POST['shipping_city']);
    $shipping_state             = e_s__($_POST['shipping_state']);
    $shipping_code              = e_s__($_POST['shipping_code']);
    $shipping_country           = e_s__($_POST['shipping_country']);

    $happy_customer             = e_s__($_POST['happy_customer']);
    $unhappy_reason             = e_s__($_POST['unhappy_reason']);
    $shipment_on_time           = e_s__($_POST['shipment_on_time']);
    $referral                   = e_s__($_POST['referral']);
    $notes                      = e_s__($_POST['notes']);
} else {

    $job_date                   = date('d-m-Y', time());
    $job_status                 = '';

    $warehouse_id               = '';
    $customer_id                = '';
    $sales_person_from_lead     = '';
    $job_seq                    = '';
    $sales_person               = '';

    $currency                   = '';
    $exchange_rate              = '';
    $transport_mode             = '';
    $shipment_type              = '';
    $job_owner                  = '';


    $job_no                     = '';
    $job_ref_no                 = '';
    $cs_agent                   = '';
    $incoterm                   = '';
    $email                      = '';
    $supplier_rate              = '';
    $estimated_net_profit       = '';

    $estimated_invoice_amount   = '';
    $etd                        = '';
    $eta                        = '';
    $carrier                    = '';
    $vessel_name                = '';
    $vessel_departure_date      = '';
    $flight_no                  = '';
    $flight_departure_date      = '';
    $job_completion_date        = '';
    $payment_terms              = '';
    $hawb                       = '';
    $mawb                       = '';
    $estimated_cost_amount      = '';
    $declaration_no             = '';

    $gross_weight               = '';
    $volume_weight              = '';
    $chargeable_weight          = '';
    $no_of_pieces               = '';
    $commodity_type             = '';

    $no_of_containers           = '';
    $insurance_needed           = '';
    $container_type             = '';
    $temperature_control_required = '';
    $container_number           = '';
    $special_comments           = '';

    $landing_country            = '';
    $landing_port               = '';
    $loading_place              = '';
    $billing_city               = '';
    $billing_state              = '';
    $billing_code               = '';
    $billing_country            = '';

    $destination_country        = '';
    $destination_port           = '';
    $fdp                        = '';
    $shipping_city              = '';
    $shipping_state             = '';
    $shipping_code              = '';
    $shipping_country           = '';

    $happy_customer             = '';
    $unhappy_reason             = '';
    $shipment_on_time          = '';
    $referral                   = '';
    $notes                      = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {

    if (empty($warehouse_id) || $warehouse_id == 'Please select') {
        $error_message = 'Please select warehouse.';
    } else if (empty($customer_id) || $customer_id == 'Please select') {
        $error_message = 'Please select Customer.';
    } else if (empty($job_owner) || $job_owner == 'Please select') {
        $error_message = 'Please select Job Owner.';
    } else if (empty($declaration_no)) {
        $error_message = 'Customs Declaration No is mandatory.';
    } else {

        // if ($grand_total == '')                         $grand_total = '0.00';

        $job_date               = processDateDtoY($job_date);
        $sales_person           = ((empty($sales_person)) ? '0' : $sales_person);

        $currency               = ((empty($currency)) ? '0' : $currency);
        $exchange_rate          = ((empty($exchange_rate)) ? '0' : $exchange_rate);
        $transport_mode         = ((empty($transport_mode)) ? '0' : $transport_mode);
        $shipment_type          = ((empty($shipment_type)) ? '0' : $shipment_type);
        $job_owner              = ((empty($job_owner)) ? '0' : $job_owner);

        $cs_agent               = ((empty($cs_agent)) ? '0' : $cs_agent);
        $incoterm               = ((empty($incoterm)) ? '0' : $incoterm);
        $supplier_rate          = ((empty($supplier_rate)) ? '0' : $supplier_rate);
        $estimated_net_profit   = ((empty($estimated_net_profit)) ? '0' : $estimated_net_profit);

        $estimated_invoice_amount   = ((empty($estimated_invoice_amount)) ? '0' : $estimated_invoice_amount);
        $etd                        = (empty($etd) ? '1970-01-01' : processDateDtoY($etd));
        $eta                        = (empty($eta) ? '1970-01-01' : processDateDtoY($eta));
        $carrier                    = ((empty($carrier)) ? '0' : $carrier);
        $vessel_departure_date      = (empty($vessel_departure_date) ? '1970-01-01' : processDateDtoY($vessel_departure_date));
        $flight_departure_date      = (empty($flight_departure_date) ? '1970-01-01' : processDateDtoY($flight_departure_date));
        $job_completion_date        = (empty($job_completion_date) ? '1970-01-01' : processDateDtoY($job_completion_date));
        $estimated_cost_amount      = ((empty($estimated_cost_amount)) ? '0' : $estimated_cost_amount);

        $gross_weight               = ((empty($gross_weight)) ? '0' : $gross_weight);
        $volume_weight              = ((empty($volume_weight)) ? '0' : $volume_weight);
        $chargeable_weight          = ((empty($chargeable_weight)) ? '0' : $chargeable_weight);
        $no_of_pieces               = ((empty($no_of_pieces)) ? '0' : $no_of_pieces);
        $commodity_type             = ((empty($commodity_type)) ? '0' : $commodity_type);
        $no_of_containers           = ((empty($no_of_containers)) ? '0' : $no_of_containers);
        $insurance_needed           = ((empty($insurance_needed)) ? '0' : $insurance_needed);
        $temperature_control_required = ((empty($temperature_control_required)) ? '0' : $temperature_control_required);
        $container_type             = ((empty($container_type)) ? '0' : $container_type);
        $container_number           = ((empty($container_number)) ? '0' : $container_number);

        $landing_country            = ((empty($landing_country)) ? '0' : $landing_country);
        $landing_port               = ((empty($landing_port)) ? '0' : $landing_port);
        $loading_place              = ((empty($loading_place)) ? '0' : $loading_place);
        $billing_country            = ((empty($billing_country)) ? '0' : $billing_country);
        $destination_country        = ((empty($destination_country)) ? '0' : $destination_country);
        $destination_port           = ((empty($destination_port)) ? '0' : $destination_port);
        $fdp                        = ((empty($fdp)) ? '0' : $fdp);
        $shipping_country           = ((empty($shipping_country)) ? '0' : $shipping_country);

        // ---------------------------------------------
        // UPDATE 
        // ---------------------------------------------
        $update_row = $mysqli->query("
                                        UPDATE `$tbl_name` SET
                                            job_date		            = '" . $job_date . "',
                                            job_status		            = '" . $job_status . "',
                                            
                                            warehouse_id		        = '" . $warehouse_id . "',
                                            customer_id					= '" . $customer_id . "',
                                            job_seq					    = '" . $job_seq . "',
                                            sales_person			    = '" . $sales_person . "',
                                            
                                            currency			        = '" . $currency . "',
                                            exchange_rate			    = '" . $exchange_rate . "',
                                            transport_mode			    = '" . $transport_mode . "',
                                            shipment_type			    = '" . $shipment_type . "',
                                            job_owner			        = '" . $job_owner . "',
                                            tags			            = '" . $tags_string . "',
                                            
                                            job_no			            = '" . $job_no . "',
                                            job_ref_no			        = '" . $job_ref_no . "',
                                            cs_agent			        = '" . $cs_agent . "',
                                            services			        = '" . $services_string . "',
                                            incoterm			        = '" . $incoterm . "',
                                            email			            = '" . $email . "',
                                            supplier_rate			    = '" . $supplier_rate . "',
                                            estimated_net_profit        = '" . $estimated_net_profit . "',

                                            estimated_invoice_amount   = '" . $estimated_invoice_amount . "',
                                            etd                        = '" . $etd . "',
                                            eta                        = '" . $eta . "',
                                            carrier                    = '" . $carrier . "',
                                            vessel_name                = '" . $vessel_name . "',
                                            vessel_departure_date      = '" . $vessel_departure_date . "',
                                            flight_no                  = '" . $flight_no . "',
                                            flight_departure_date      = '" . $flight_departure_date . "',
                                            job_completion_date        = '" . $job_completion_date . "',
                                            payment_terms              = '" . $payment_terms . "',
                                            hawb                       = '" . $hawb . "',
                                            mawb                       = '" . $mawb . "',
                                            estimated_cost_amount      = '" . $estimated_cost_amount . "',
                                            declaration_no             = '" . $declaration_no . "',

                                            gross_weight               = '" . $gross_weight . "',
                                            volume_weight              = '" . $volume_weight . "',
                                            chargeable_weight          = '" . $chargeable_weight . "',
                                            no_of_pieces               = '" . $no_of_pieces . "',
                                            commodity_type             = '" . $commodity_type . "',

                                            no_of_containers           = '" . $no_of_containers . "',
                                            insurance_needed           = '" . $insurance_needed . "',
                                            container_type             = '" . $container_type . "',
                                            temperature_control_required = '" . $temperature_control_required . "',
                                            container_number           = '" . $container_number . "',
                                            special_comments           = '" . $special_comments . "',

                                            landing_country            = '" . $landing_country . "',
                                            landing_port               = '" . $landing_port . "',
                                            loading_place              = '" . $loading_place . "',
                                            billing_city               = '" . $billing_city . "',
                                            billing_state              = '" . $billing_state . "',
                                            billing_code               = '" . $billing_code . "',
                                            billing_country            = '" . $billing_country . "',

                                            destination_country        = '" . $destination_country . "',
                                            destination_port           = '" . $destination_port . "',
                                            fdp                        = '" . $fdp . "',
                                            shipping_city              = '" . $shipping_city . "',
                                            shipping_state             = '" . $shipping_state . "',
                                            shipping_code              = '" . $shipping_code . "',
                                            shipping_country           = '" . $shipping_country . "',
                                            
                                            happy_customer              = '" . $happy_customer . "',
                                            unhappy_reason              = '" . $unhappy_reason . "',
                                            shipment_on_time            = '" . $shipment_on_time . "',
                                            referral                    = '" . $referral . "',
                                            notes                       = '" . $notes . "',
                                            
                                            is_active 					= '" . $publish . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            $job_id = $id;
            ///////////////////////////////////////////////////////////

            // -------------------------------------------------------------
            // PROCESS ITEMS
            // -------------------------------------------------------------
            if ($total_rows > 0) {

                for ($dim_item = 1; $dim_item <= $total_rows; $dim_item++) {

                    $index = $dim_item;
                    $index = $index - 1;

                    $item_dim_id                    = e_s__($_POST['item_dim_id'][$index]);
                    $item_dim_length                = e_s__($_POST['dim_length'][$index]);
                    $item_dim_width                 = e_s__($_POST['dim_width'][$index]);
                    $item_dim_height                = e_s__($_POST['dim_height'][$index]);
                    $item_dim_pcs                   = e_s__($_POST['dim_pcs'][$index]);
                    $item_dim_volume                = e_s__($_POST['dim_volume'][$index]);
                    $item_dim_cbm                   = e_s__($_POST['dim_cbm'][$index]);

                    // UPDATE
                    if (!empty($item_dim_id) && !empty($item_dim_volume) && !empty($item_dim_cbm)) {

                        $update_row = $mysqli->query("UPDATE `" . tbl_job_items . "` SET 
                                                            dim_length      = '" . $item_dim_length . "',
                                                            dim_width       = '" . $item_dim_width . "',
                                                            dim_height      = '" . $item_dim_height . "',
                                                            dim_pcs         = '" . $item_dim_pcs . "',
                                                            dim_volume      = '" . $item_dim_volume . "',
                                                            dim_cbm         = '" . $item_dim_cbm . "'
                                                        WHERE id=$item_dim_id");
                        // fp__(tbl_job_items, $item_dim_id);

                        // NEW
                    } else if (empty($item_dim_id) && !empty($item_dim_volume) && !empty($item_dim_cbm)) {
                        $mysqli->query("INSERT INTO `" . tbl_job_items . "`(job_id, dim_length, dim_width, dim_height, dim_pcs, dim_volume, dim_cbm) VALUES ('" . $job_id . "', '" . $item_dim_length . "', '" . $item_dim_width . "', '" . $item_dim_height . "', '" . $item_dim_pcs . "', '" . $item_dim_volume . "', '" . $item_dim_cbm . "'); ");

                        // fp__(tbl_job_items, $mysqli->insert_id);

                        // DELETE
                    } else if (!empty($item_dim_id) && empty($item_dim_volume) && empty($item_dim_cbm)) {
                        $mysqli->query("DELETE FROM `" . tbl_job_items . "` WHERE id=$item_dim_id");
                    }
                } //for 
            } // if
            ///////////////////////////////////////////////////////////
            header("Location:listing_$module.php?success_message=$success_message");
            exit;
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }
        // header("Location:listing_$module.php?success_message=$success_message");

    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module") {

    if (empty($warehouse_id) || $warehouse_id == 'Please select') {
        $error_message = 'Please select warehouse.';
    } else if (empty($customer_id) || $customer_id == 'Please select') {
        $error_message = 'Please select Customer.';
    } else if (empty($job_owner) || $job_owner == 'Please select') {
        $error_message = 'Please select Job Owner.';
    } else if (empty($declaration_no)) {
        $error_message = 'Customs Declaration No is mandatory.';
    } else {

        ///////////////////////////////////////////////////////////

        $job_date               = processDateDtoY($job_date);
        $sales_person           = ((empty($sales_person)) ? '0' : $sales_person);

        $currency               = ((empty($currency)) ? '0' : $currency);
        $exchange_rate          = ((empty($exchange_rate)) ? '0' : $exchange_rate);
        $transport_mode         = ((empty($transport_mode)) ? '0' : $transport_mode);
        $shipment_type          = ((empty($shipment_type)) ? '0' : $shipment_type);
        $job_owner              = ((empty($job_owner)) ? '0' : $job_owner);

        $cs_agent               = ((empty($cs_agent)) ? '0' : $cs_agent);
        $incoterm               = ((empty($incoterm)) ? '0' : $incoterm);
        $supplier_rate          = ((empty($supplier_rate)) ? '0' : $supplier_rate);
        $estimated_net_profit   = ((empty($estimated_net_profit)) ? '0' : $estimated_net_profit);

        $estimated_invoice_amount   = ((empty($estimated_invoice_amount)) ? '0' : $estimated_invoice_amount);
        $etd                        = (empty($etd) ? '1970-01-01' : processDateDtoY($etd));
        $eta                        = (empty($eta) ? '1970-01-01' : processDateDtoY($eta));
        $carrier                    = ((empty($carrier)) ? '0' : $carrier);
        $vessel_departure_date      = (empty($vessel_departure_date) ? '1970-01-01' : processDateDtoY($vessel_departure_date));
        $flight_departure_date      = (empty($flight_departure_date) ? '1970-01-01' : processDateDtoY($flight_departure_date));
        $job_completion_date        = (empty($job_completion_date) ? '1970-01-01' : processDateDtoY($job_completion_date));
        $estimated_cost_amount      = ((empty($estimated_cost_amount)) ? '0' : $estimated_cost_amount);

        $gross_weight               = ((empty($gross_weight)) ? '0' : $gross_weight);
        $volume_weight              = ((empty($volume_weight)) ? '0' : $volume_weight);
        $chargeable_weight          = ((empty($chargeable_weight)) ? '0' : $chargeable_weight);
        $no_of_pieces               = ((empty($no_of_pieces)) ? '0' : $no_of_pieces);
        $commodity_type             = ((empty($commodity_type)) ? '0' : $commodity_type);
        $no_of_containers           = ((empty($no_of_containers)) ? '0' : $no_of_containers);
        $insurance_needed           = ((empty($insurance_needed)) ? '0' : $insurance_needed);
        $temperature_control_required = ((empty($temperature_control_required)) ? '0' : $temperature_control_required);
        $container_type             = ((empty($container_type)) ? '0' : $container_type);
        $container_number           = ((empty($container_number)) ? '0' : $container_number);

        $landing_country            = ((empty($landing_country)) ? '0' : $landing_country);
        $landing_port               = ((empty($landing_port)) ? '0' : $landing_port);
        $loading_place              = ((empty($loading_place)) ? '0' : $loading_place);
        $billing_country            = ((empty($billing_country)) ? '0' : $billing_country);
        $destination_country        = ((empty($destination_country)) ? '0' : $destination_country);
        $destination_port           = ((empty($destination_port)) ? '0' : $destination_port);
        $fdp                        = ((empty($fdp)) ? '0' : $fdp);
        $shipping_country           = ((empty($shipping_country)) ? '0' : $shipping_country);




        // ======================================================
        $insert_row = $mysqli->query("
                                        INSERT INTO `$tbl_name` (
                                            job_date, job_status, warehouse_id, customer_id, job_seq, sales_person, currency, exchange_rate, transport_mode, shipment_type, job_owner, tags, job_no, job_ref_no, cs_agent, services, incoterm, email, supplier_rate, estimated_net_profit, estimated_invoice_amount, etd, eta, carrier, vessel_name, vessel_departure_date, flight_no, flight_departure_date, job_completion_date, payment_terms, hawb, mawb, estimated_cost_amount, declaration_no, gross_weight, volume_weight, chargeable_weight, no_of_pieces, commodity_type, no_of_containers, insurance_needed, container_type, temperature_control_required, container_number, special_comments, landing_country, landing_port, loading_place, billing_city, billing_state, billing_code, billing_country, destination_country, destination_port, fdp, shipping_city, shipping_state,shipping_code, shipping_country, happy_customer, unhappy_reason, shipment_on_time, referral, notes, books_customer_id, is_active) VALUES (
                                            '" . $job_date . "', 
                                            '" . $job_status . "', 
                                            '" . $warehouse_id . "', 
                                            '" . $customer_id . "', 
                                            '" . $job_seq . "', 
                                            '" . $sales_person . "', 
                                            
                                            '" . $currency . "', 
                                            '" . $exchange_rate . "', 
                                            '" . $transport_mode . "', 
                                            '" . $shipment_type . "', 
                                            '" . $job_owner . "', 
                                            '" . $tags_string . "', 
                                            '" . $job_no . "', 
                                            '" . $job_ref_no . "', 
                                            
                                            '" . $cs_agent . "', 
                                            '" . $services_string . "', 
                                            '" . $incoterm . "', 
                                            '" . $email . "', 
                                            '" . $supplier_rate . "', 
                                            '" . $estimated_net_profit . "', 

                                            '" . $estimated_invoice_amount . "',
                                            '" . $etd . "',
                                            '" . $eta . "',
                                            '" . $carrier . "',
                                            '" . $vessel_name . "',
                                            '" . $vessel_departure_date . "',
                                            '" . $flight_no . "',
                                            '" . $flight_departure_date . "',
                                            '" . $job_completion_date . "',
                                            '" . $payment_terms . "',
                                            '" . $hawb . "',
                                            '" . $mawb . "',
                                            '" . $estimated_cost_amount . "',
                                            '" . $declaration_no . "',

                                            '" . $gross_weight . "',
                                            '" . $volume_weight . "',
                                            '" . $chargeable_weight . "',
                                            '" . $no_of_pieces . "',
                                            '" . $commodity_type . "',

                                            '" . $no_of_containers . "',
                                            '" . $insurance_needed . "',
                                            '" . $container_type . "',
                                            '" . $temperature_control_required . "',
                                            '" . $container_number . "',
                                            '" . $special_comments . "',

                                            '" . $landing_country . "',
                                            '" . $landing_port . "',
                                            '" . $loading_place . "',
                                            '" . $billing_city . "',
                                            '" . $billing_state . "',
                                            '" . $billing_code . "',
                                            '" . $billing_country . "',

                                            '" . $destination_country . "',
                                            '" . $destination_port . "',
                                            '" . $fdp . "',
                                            '" . $shipping_city . "',
                                            '" . $shipping_state . "',
                                            '" . $shipping_code . "',
                                            '" . $shipping_country . "',

                                            '" . $happy_customer . "',
                                            '" . $unhappy_reason . "',
                                            '" . $shipment_on_time . "',
                                            '" . $referral . "',
                                            '" . $notes . "',
                                            '" . $customer_id . "',

                                            '" . $publish . "'
                                        )
                                    ");

        $job_id = $mysqli->insert_id;

        if ($insert_row) {
            fp__($tbl_name, $job_id);
            $success_message = "The $module_caption has been saved successfully.";

            // -------------------------------------------------------------
            // PROCESS ITEMS
            // -------------------------------------------------------------

            if ($total_rows > 0) {
                for ($dim_item = 1; $dim_item <= $total_rows; $dim_item++) {

                    $index = $dim_item;
                    $index = $index - 1;

                    $item_dim_length        = e_s__($_POST['dim_length'][$index]);
                    $item_dim_width         = e_s__($_POST['dim_width'][$index]);
                    $item_dim_height        = e_s__($_POST['dim_height'][$index]);
                    $item_dim_pcs           = e_s__($_POST['dim_pcs'][$index]);
                    $item_dim_volume        = e_s__($_POST['dim_volume'][$index]);
                    $item_dim_cbm           = e_s__($_POST['dim_cbm'][$index]);

                    // SAVE
                    if (!empty($item_dim_length) && !empty($item_dim_width) && !empty($item_dim_height) && !empty($item_dim_pcs) && !empty($item_dim_volume) && !empty($item_dim_cbm)) {

                        $mysqli->query("INSERT INTO `" . tbl_job_items . "`(job_id, dim_length, dim_width, dim_height, dim_pcs, dim_volume, dim_cbm) VALUES ('" . $job_id . "', '" . $item_dim_length . "', '" . $item_dim_width . "', '" . $item_dim_height . "', '" . $item_dim_pcs . "', '" . $item_dim_volume . "', '" . $item_dim_cbm . "'); ");
                    }
                } // for
            } // if

            header("Location:listing_$module.php?success_message=$success_message");
            exit;

        }
        // ---------------------------------------------


    }
}


// die('aaaaaaaa');


/*
|--------------------------------------------------------------------------
| EDIT - ONLY SUPERADMIN or RELEVANT USER
|--------------------------------------------------------------------------
|
*/
$created_by = getTableAttr('created_by', DB::JOBS, $id);

if (!empty($id) && (is_SystemAdmin() || is_SuperAdmin() || is_role() == 'accounts' || is_role() == 'operations' || $session_user_id == $created_by)) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $job_date               = s__($row['job_date']);
    $job_date               = processDateYtoD($job_date);
    $job_status             = s__($row['job_status']);

    $warehouse_id           = s__($row['warehouse_id']);
    $customer_id            = s__($row['customer_id']);
    $job_seq                = s__($row['job_seq']);
    $sales_person           = s__($row['sales_person']);

    $currency               = s__($row['currency']);
    $exchange_rate          = s__($row['exchange_rate']);
    $transport_mode         = s__($row['transport_mode']);
    $shipment_type          = s__($row['shipment_type']);
    $job_owner              = s__($row['job_owner']);

    // -- Tags
    $tags                   = s__($row['tags']);
    $tags_arr               = array();
    if ($tags != NULL) {
        $tags_arr               = explode(',', $tags);
    }

    // -- Services
    $services                   = s__($row['services']);
    $services_arr               = array();
    if ($services != NULL) {
        $services_arr               = explode(',', $services);
    }


    $job_no                 = s__($row['job_no']);
    $job_ref_no             = s__($row['job_ref_no']);
    $cs_agent               = s__($row['cs_agent']);
    $incoterm               = s__($row['incoterm']);
    $email                  = s__($row['email']);
    $supplier_rate          = s__($row['supplier_rate']);
    $estimated_net_profit   = s__($row['estimated_net_profit']);

    $estimated_invoice_amount   = s__($row['estimated_invoice_amount']);
    $etd                        = s__($row['etd']);
    $etd                        = (($etd == '1970-01-01') ? '' : processDateYtoD($etd));

    $eta                        = s__($row['eta']);
    $eta                        = (($eta == '1970-01-01') ? '' : processDateYtoD($eta));

    $carrier                    = s__($row['carrier']);
    $vessel_name                = s__($row['vessel_name']);
    $vessel_departure_date      = s__($row['vessel_departure_date']);
    $vessel_departure_date      = (($vessel_departure_date == '1970-01-01') ? '' : processDateYtoD($vessel_departure_date));

    $flight_no                  = s__($row['flight_no']);
    $flight_departure_date      = s__($row['flight_departure_date']);
    $flight_departure_date      = (($flight_departure_date == '1970-01-01') ? '' : processDateYtoD($flight_departure_date));

    $job_completion_date        = s__($row['job_completion_date']);
    $job_completion_date        = (($job_completion_date == '1970-01-01') ? '' : processDateYtoD($job_completion_date));

    $payment_terms              = s__($row['payment_terms']);
    $hawb                       = s__($row['hawb']);
    $mawb                       = s__($row['mawb']);
    $estimated_cost_amount      = s__($row['estimated_cost_amount']);
    $declaration_no             = s__($row['declaration_no']);


    $gross_weight               = s__($row['gross_weight']);
    $volume_weight              = s__($row['volume_weight']);
    $chargeable_weight          = s__($row['chargeable_weight']);
    $no_of_pieces               = s__($row['no_of_pieces']);
    $commodity_type             = s__($row['commodity_type']);

    $no_of_containers           = s__($row['no_of_containers']);
    $insurance_needed           = s__($row['insurance_needed']);
    $container_type             = s__($row['container_type']);
    $temperature_control_required = s__($row['temperature_control_required']);
    $container_number           = s__($row['container_number']);
    $special_comments           = s__($row['special_comments']);

    $landing_country            = s__($row['landing_country']);
    $landing_port               = s__($row['landing_port']);
    $loading_place              = s__($row['loading_place']);
    $billing_city               = s__($row['billing_city']);
    $billing_state              = s__($row['billing_state']);
    $billing_code               = s__($row['billing_code']);
    $billing_country            = s__($row['billing_country']);

    $destination_country        = s__($row['destination_country']);
    $destination_port           = s__($row['destination_port']);
    $fdp                        = s__($row['fdp']);
    $shipping_city              = s__($row['shipping_city']);
    $shipping_state             = s__($row['shipping_state']);
    $shipping_code              = s__($row['shipping_code']);
    $shipping_country           = s__($row['shipping_country']);

    $happy_customer             = s__($row['happy_customer']);
    $unhappy_reason             = s__($row['unhappy_reason']);
    $shipment_on_time           = s__($row['shipment_on_time']);
    $referral                   = s__($row['referral']);
    $notes                      = s__($row['notes']);

    // $created_by                 = s__($row['created_by']);
    $modified_by                = s__($row['modified_by']);
    $customer_type              = getTableAttr('customer_type', DB::CUSTOMERS, $customer_id);
    $quote_id                   = s__($row['quote_id']);
    $books_customer_id          = s__($row['books_customer_id']);
    $approved_time              = s__($row['created_at']);
    $project_id                 = s__($row['project_id']);
    $approved_time_resubmission = s__($row['approved_time_resubmission']);


    $publish                = s__($row['is_active'] ?? 0);

    // $expiry_date        = ($expiry_date == '1970-01-01' ? '' : processDateDtoY($expiry_date));


    // ------------------ TOTAL ITEMS ------------------
    $result_job_items     = $mysqli->query("SELECT * FROM `" . tbl_job_items . "` WHERE job_id=$id");
    $total_rows           = $result_job_items->num_rows;


    if ($total_rows > 0) {
        while ($row_job_items = $result_job_items->fetch_array()) {

            array_push($item_dim_id_arr,            $row_job_items['id']);
            array_push($dim_length_arr,             $row_job_items['dim_length']);
            array_push($dim_width_arr,              $row_job_items['dim_width']);
            array_push($dim_height_arr,             $row_job_items['dim_height']);
            array_push($dim_pcs_arr,                $row_job_items['dim_pcs']);
            array_push($dim_volume_arr,             $row_job_items['dim_volume']);
            array_push($dim_cbm_arr,                $row_job_items['dim_cbm']);
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

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module" || $action == "change_password") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" <?php if ($publish == '1') { ?>checked="checked" <?php } ?> form="frmjobs">
                    <label class="form-check-label" for="publish">Publish</label>
                </div>
            </div>
            <div class="my-1">
                <?php if (empty($id) || (isset($module_id) && granted('create', $module_id)) || (isset($module_id) && granted('edit', $module_id)) || $file === 'profile.php' || $file === 'change_password.php') { ?>
                    <button type="submit" form="frmjobs" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>

        <!-- Page header -->



                <div class="col-xl-12">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">

                                <div class="card-body">

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Warehouse:*</span></label>
                                        <div class="col-lg-9">
                                            <select name="warehouse_id" id="warehouse_id" class="form-select">
                                                <!-- <option value='0'></option> -->
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . DB::WAREHOUSES  . "` WHERE is_active=1 LIMIT 1");
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
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Customer Name:*</span></label>
                                        <div class="col-lg-9">
                                            <select name="customer_id" id="customer_id" class="form-control select">
                                                <option value='0'>&nbsp;</option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result = $mysqli->query("SELECT * FROM `" . DB::CUSTOMERS  . "` ORDER BY id DESC");
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
                                        <label class="col-lg-3 col-form-label">Sale Person from Lead:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="sales_person_from_lead" id="sales_person_from_lead" value="<?php echo $sales_person_from_lead; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Job Seq:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="job_seq" id="job_seq" value="<?php echo $job_seq; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Sales Person: </label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="sales_person" id="sales_person">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_users = $mysqli->query("SELECT * FROM `" . DB::USERS  . "` WHERE is_active=1 ORDER BY full_name");
                                                while ($rows_users = $result_users->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_users['id']; ?>" <?php if ($action == "edit_$module" && $rows_users['id'] == $sales_person) { ?>selected <?php } else if ($rows_users['id'] == $sales_person) { ?>selected <?php } ?>>
                                                        <?php echo $rows_users['full_name']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>



                        <div class="col-lg-6">
                            <div class="card">

                                <!-- <div class="card-header d-flex align-items-center">
                                    <h6 class="mb-0"></h6>
                                </div> -->

                                <div class="card-body">

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Currency: </label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="currency" id="currency">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_currency = $mysqli->query("SELECT * FROM `" . DB::CURRENCIES  . "` WHERE is_active=1 ORDER BY id ASC");
                                                while ($rows_currency = $result_currency->fetch_array()) {
                                                    // $currency        = s__($rows_currency['currency']);
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_currency['id']; ?>" <?php if ($action == "edit_$module" && $rows_currency['id'] == $currency) { ?>selected <?php } else if ($rows_currency['id'] == $currency) { ?>selected <?php } ?>>
                                                        <?php echo $rows_currency['currency']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Exchange Rate:</label>
                                        <div class="col-lg-9">
                                            <input type="number" class="form-control" name="exchange_rate" id="exchange_rate" value="<?php echo $exchange_rate; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Transport Mode: </label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="transport_mode" id="transport_mode">
                                                <option value='0'></option>

                                                <option value="air" <?php if ($action == "edit_$module" && $transport_mode == 'air') { ?>selected <?php } else if ($transport_mode == 'air') { ?>selected <?php } ?>>Air</option>

                                                <option value="sea" <?php if ($action == "edit_$module" && $transport_mode == 'sea') { ?>selected <?php } else if ($transport_mode == 'sea') { ?>selected <?php } ?>>Sea</option>

                                                <option value="land" <?php if ($action == "edit_$module" && $transport_mode == 'land') { ?>selected <?php } else if ($transport_mode == 'land') { ?>selected <?php } ?>>Land</option>

                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Type of Shipment:</label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <select class="form-select" name="shipment_type" id="shipment_type">
                                                    <option value='0'></option>

                                                    <option value="export" <?php if ($action == "edit_$module" && $shipment_type == 'export') { ?>selected <?php } else if ($shipment_type == 'export') { ?>selected <?php } ?>>Export</option>

                                                    <option value="import" <?php if ($action == "edit_$module" && $shipment_type == 'import') { ?>selected <?php } else if ($shipment_type == 'import') { ?>selected <?php } ?>>Import</option>

                                                    <option value="transit" <?php if ($action == "edit_$module" && $shipment_type == 'transit') { ?>selected <?php } else if ($shipment_type == 'transit') { ?>selected <?php } ?>>Transit</option>

                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Job Owner:*</span></label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="job_owner" id="job_owner">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_users = $mysqli->query("SELECT * FROM `" . DB::USERS  . "` WHERE is_active=1 ORDER BY full_name");
                                                while ($rows_users = $result_users->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_users['id']; ?>" <?php if ($action == "edit_$module" && $rows_users['id'] == $job_owner) { ?>selected <?php } else if ($rows_users['id'] == $job_owner) { ?>selected <?php } ?>>
                                                        <?php echo $rows_users['full_name']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Tag:</label>
                                        <div class="col-lg-9">
                                            <select name="tags[]" id="tags[]" class="form-control select" multiple="multiple" data-tags="true">
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_tags = $mysqli->query("SELECT * FROM `" . DB::TAXONOMIES  . "` WHERE is_active=1 AND type = 'job_tag' ORDER BY value");
                                                while ($rows_tags = $result_tags->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>

                                                    <option value="<?php echo $rows_tags['id']; ?>" <?php if ($action == "edit_$module" && in_array($rows_tags['id'], $tags_arr)) { ?>selected <?php } else if (in_array($rows_tags['id'], $posted_tags_arr)) { ?>selected <?php } ?>>
                                                        <?php echo $rows_tags['value']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>


                    </div>
                </div>



                <div class="row">
                    <div class="col-xl-12">

                        <div class="card">

                            <div class="card-header d-flex align-items-center">
                                <h6 class="mb-0">Job Status Details</h6>
                            </div>

                            <div class="card-body">

                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label">Job Status: </label>
                                    <div class="col-lg-4">

                                        <?php $draft_job_status_id = getTableAttrV('id', DB::JOB_STATUSES, " job_status = 'draft' "); ?>

                                        <select class="form-select" name="job_status" id="job_status">
                                            <!-- <option value='0'></option> -->
                                            <?php
                                            // -------------------------------------------------------------------------------------------------
                                            $result_job_status = $mysqli->query("SELECT * FROM `" . DB::JOB_STATUSES  . "` WHERE is_active=1 ORDER BY job_status");
                                            while ($rows_job_status = $result_job_status->fetch_array()) {
                                                // -------------------------------------------------------------------------------------------------
                                            ?>
                                                <option value="<?php echo $rows_job_status['id']; ?>" <?php if ($action == "edit_$module" && $rows_job_status['id'] == $job_status) { ?>selected <?php } else if ($rows_job_status['id'] == $job_status) { ?>selected <?php } else if (empty($id) && $rows_job_status['id'] == $draft_job_status_id) { ?>selected <?php } ?>>
                                                    <?php echo $rows_job_status['job_status']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>
                                    </div>

                                    <!-- <label class="col-lg-2">
                                        <button type="button" class="btn btn-light">Create Sales Order</button></label> -->
                                    <!-- <div class="col-lg-4">
                                            <input type="text" class="form-control" placeholder="Reference no" name="reference_no" id="reference_no" value="<?php echo $reference_no ?? ''; ?>">
                                        </div> -->
                                </div>

                            </div>
                        </div>

                    </div>
                </div>



                <div class="col-xl-12">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">

                                <div class="card-header d-flex align-items-center">
                                    <h6 class="mb-0">
                                        Detailed Job Information
                                    </h6>
                                </div>

                                <div class="card-body">

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Job No:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="job_no" id="job_no" value="<?php echo $job_no; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Job Ref No:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="job_ref_no" id="job_ref_no" value="<?php echo $job_ref_no; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">CS Agent: </label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="cs_agent" id="cs_agent">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_users = $mysqli->query("SELECT * FROM `" . DB::USERS  . "` WHERE is_active=1 ORDER BY full_name");
                                                while ($rows_users = $result_users->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_users['id']; ?>" <?php if ($action == "edit_$module" && $rows_users['id'] == $cs_agent) { ?>selected <?php } else if ($rows_users['id'] == $cs_agent) { ?>selected <?php } ?>>
                                                        <?php echo $rows_users['full_name']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Type of Services: </label>
                                        <div class="col-lg-9">
                                            <select name="services[]" id="services[]" class="form-control select" multiple="multiple" data-tags="true">
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_services = $mysqli->query("SELECT * FROM `" . DB::ITEMS  . "` WHERE is_active=1 ORDER BY item_name");
                                                while ($rows_services = $result_services->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>

                                                    <option value="<?php echo $rows_services['id']; ?>" <?php if ($action == "edit_$module" && in_array($rows_services['id'], $services_arr)) { ?>selected <?php } else if (in_array($rows_services['id'], $posted_services_arr)) { ?>selected <?php } ?>>
                                                        <?php echo $rows_services['item_name']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Incoterms: </label>
                                        <div class="col-lg-9">
                                            <select name="incoterm" id="incoterm" class="form-control">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result = $mysqli->query("SELECT * FROM `" . DB::INCOTERMS  . "` ORDER BY incoterm ASC");
                                                while ($rows = $result->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $incoterm) { ?> selected <?php } else if ($rows['id'] == $incoterm) { ?> selected <?php } ?>>
                                                        <?php echo $rows["incoterm"]; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Email:</label>
                                        <div class="col-lg-9">
                                            <input type="email" class="form-control" name="email" id="email" value="<?php echo $email; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Supplier Rates:</label>
                                        <div class="col-lg-9">
                                            <input type="number" step="any" class="form-control" name="supplier_rate" id="supplier_rate" value="<?php echo $supplier_rate; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Estimated Net Profit:</label>
                                        <div class="col-lg-9">
                                            <input type="number" step="any" class="form-control" name="estimated_net_profit" id="estimated_net_profit" value="<?php echo $estimated_net_profit; ?>">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>



                        <div class="col-lg-6">
                            <div class="card">

                                <!-- <div class="card-header d-flex align-items-center">
                                    <h6 class="mb-0"></h6>
                                </div> -->

                                <div class="card-body">

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Job Date:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="job_date" id="job_date" value="<?php echo $job_date; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Estimated Invoice Amount:</label>
                                        <div class="col-lg-9">
                                            <input type="number" step="any" class="form-control" name="estimated_invoice_amount" id="estimated_invoice_amount" value="<?php echo $estimated_invoice_amount; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">ETD: </label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="DD/MM/YYYY" name="etd" id="etd" value="<?php echo $etd; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">ETA: </label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="DD/MM/YYYY" name="eta" id="eta" value="<?php echo $eta; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Carrier Name: </label>
                                        <div class="col-lg-9">
                                            <select name=carrier id=carrier class="form-control select">
                                                <option value='0'>&nbsp;</option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result = $mysqli->query("SELECT * FROM `" . DB::CARRIERS  . "` ORDER BY carrier_name ASC");
                                                while ($rows = $result->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $carrier) { ?>selected <?php } else if ($rows['id'] == $carrier) { ?>selected <?php } ?>>
                                                        <?php echo $rows["carrier_name"]; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Vessel Name:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="vessel_name" id="vessel_name" value="<?php echo $vessel_name; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Vessel Departure Date:</label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="DD/MM/YYYY" name="vessel_departure_date" id="vessel_departure_date" value="<?php echo $vessel_departure_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Flight No:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="flight_no" id="flight_no" value="<?php echo $flight_no; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Flight Departure Date:</label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="DD/MM/YYYY" name="flight_departure_date" id="flight_departure_date" value="<?php echo $flight_departure_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Job Completed Date:</label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="DD/MM/YYYY" name="job_completion_date" id="job_completion_date" value="<?php echo $job_completion_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Payment Terms:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="payment_terms" id="payment_terms" value="<?php echo $payment_terms; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">HAWB / HBL:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="hawb" id="hawb" value="<?php echo $hawb; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">MAWB / MBL:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="mawb" id="mawb" value="<?php echo $mawb; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Estimated Cost Amount:</label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="number" steps="any" class="form-control" name="estimated_cost_amount" id="estimated_cost_amount" value="<?php echo $estimated_cost_amount; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Custom Declaration No:*</span></label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="declaration_no" id="declaration_no" value="<?php echo $declaration_no; ?>">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>


                    </div>
                </div>




                <!-- L × W × H ÷ 6000 ÷ 166.66 -->
                <div>

                    <div class="col-xl-12">

                        <div class="row mb-2">

                            <div class="col-lg-2">
                                <label class="form-label ms-3 fw-semibold">LENGTH <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label ms-4 fw-semibold">WIDTH <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label ms-3 fw-semibold">HEIGHT <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-4 fw-semibold">NO OF PCS <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label ms-3 fw-semibold">VOLUME WEIGHT </label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label ms-2 fw-semibold">CBM</label>
                            </div>

                        </div>

                        <div class="card">

                            <div class="row card-body">

                                <div class="col-lg-12">

                                    <?php

                                    $total_cbm = 0;
                                    $total_volume = 0;
                                    $total_pcs = 0;

                                    // ----------------------------------------------------------------------------
                                    for ($dim_item = 1; $dim_item <= $total_rows; $dim_item++) {
                                        $index = $dim_item;
                                        $index = $index - 1;

                                        // $item_dim_id = (!empty($item_dim_id_arr[$index]) ? $item_dim_id_arr[$index] : '');

                                        $dim_length     = (!empty($dim_length_arr[$index]) ? $dim_length_arr[$index] : '0');
                                        $dim_width      = (!empty($dim_width_arr[$index]) ? $dim_width_arr[$index] : '0');
                                        $dim_height     = (!empty($dim_height_arr[$index]) ? $dim_height_arr[$index] : '0');
                                        $dim_pcs        = (!empty($dim_pcs_arr[$index]) ? $dim_pcs_arr[$index] : '1');
                                        $dim_volume     = (!empty($dim_volume_arr[$index]) ? $dim_volume_arr[$index] : '0');
                                        $dim_cbm        = (!empty($dim_cbm_arr[$index]) ? $dim_cbm_arr[$index] : '0');

                                        $total_cbm += $dim_cbm;
                                        $total_volume += $dim_volume;
                                        $total_pcs += $dim_pcs;

                                        // ----------------------------------------------------------------------------
                                    ?>

                                        <div class="mb-2">
                                            <div class="row mb-3 pb-3" id="row_<?php echo $dim_item; ?>">


                                                <div class="col-lg-12">
                                                    <div class="row">

                                                        <input type="hidden" name="item_dim_id[]" id="item_dim_id<?php echo $dim_item; ?>" value="<?php echo (!empty($item_dim_id_arr[$index]) ? $item_dim_id_arr[$index] : ''); ?>">

                                                        <div class="col-lg-2">
                                                            <input type="number" step="any" name="dim_length[]" id="dim_length<?php echo $dim_item; ?>" min="0" class="form-control text-center" value="<?php echo $dim_length; ?>" onkeyup="calculateItemCBM('<?php echo $dim_item; ?>');" onchange=" calculateItemCBM('<?php echo $dim_item; ?>');">
                                                        </div>


                                                        <div class="col-lg-2">
                                                            <input type="number" step="any" name="dim_width[]" id="dim_width<?php echo $dim_item; ?>" min="0" class="form-control text-center" value="<?php echo $dim_width; ?>" onkeyup="calculateItemCBM('<?php echo $dim_item; ?>');" onchange=" calculateItemCBM('<?php echo $dim_item; ?>');">
                                                        </div>


                                                        <div class="col-lg-2">
                                                            <input type="number" step="any" name="dim_height[]" id="dim_height<?php echo $dim_item; ?>" min="0" class="form-control text-center" value="<?php echo $dim_height; ?>" onkeyup="calculateItemCBM('<?php echo $dim_item; ?>');" onchange=" calculateItemCBM('<?php echo $dim_item; ?>');">
                                                        </div>


                                                        <div class="col-lg-1">
                                                            <div class="input-group">
                                                                <button type="button" class="btn btn-light btn-icon" onclick="this.parentNode.querySelector('input[type=number]').stepDown(); calculateItemCBM('<?php echo $dim_item; ?>'); ">

                                                                    <i class="ph-minus ph-sm"></i></button>
                                                                <input class="form-control form-control-number text-center" type="number" name="dim_pcs[]" id="dim_pcs<?php echo $dim_item; ?>" value="<?php echo $dim_pcs; ?>" min="1" onkeyup="calculateItemCBM('<?php echo $dim_item; ?>');" onchange="calculateItemCBM('<?php echo $dim_item; ?>');">

                                                                <button type="button" class="btn btn-light btn-icon" onclick="this.parentNode.querySelector('input[type=number]').stepUp(); calculateItemCBM('<?php echo $dim_item; ?>'); "><i class="ph-plus ph-sm"></i></button>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-2">
                                                            <input readonly type="number" name="dim_volume[]" id="dim_volume<?php echo $dim_item; ?>" min="0" class="form-control bg-light bg-opacity-75 text-end" value="<?php echo $dim_volume; ?>"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-2">
                                                            <input readonly type="number" name="dim_cbm[]" id="dim_cbm<?php echo $dim_item; ?>" min="0" class="form-control bg-light bg-opacity-75 text-end" value="<?php echo $dim_cbm; ?>"> <!--  oninput="this.value = Math.abs(this.value)" -->
                                                        </div>

                                                        <div class="col-lg-2 mt-1">
                                                            <?php if ($dim_item > 1) { ?>
                                                                <a href="#" onclick="calculateItemCBM('<?php echo $dim_item; ?>'); clear_row(<?php echo $dim_item; ?>)"><span class="badge bg-warning"> <i class="ph-x"></i> </span></a>
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
                                    <span id="span_add_item_row<?php echo $dim_item; ?>"><a href="#" onclick="add_item_row(); "><span class="badge bg-primary"> Add New Row </a></span></span>
                                </div>


                                <!-- </div> -->


                                <script>
                                    function add_item_row() {
                                        var div_add_here = document.getElementById('div_add_here');
                                        var total_rows = document.getElementById('total_rows').value;
                                        total_rows++;

                                        var new_row = "";

                                        new_row += "<div class=\"row mb-3 pb-3\" id=\"row_" + total_rows + "\">";
                                        new_row += "<input type=\"hidden\" name=\"item_dim_id[]\" id=\"item_dim_id" + total_rows + "\">";

                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"dim_length[]\" id=\"dim_length" + total_rows + "\" min=\"0\" class=\"form-control text-center\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"dim_width[]\" id=\"dim_width" + total_rows + "\" min=\"0\" class=\"form-control text-center\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"dim_height[]\" id=\"dim_height" + total_rows + "\" min=\"0\" class=\"form-control text-center\">";
                                        new_row += "</div>";


                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<div class=\"input-group\">";
                                        new_row += "<button type=\"button\" class=\"btn btn-light btn-icon\" onclick=\"this.parentNode.querySelector('input[type=number]').stepDown(); calculateItemCBM('" + total_rows + "'); \"><i class=\"ph-minus ph-sm\"></i></button>";
                                        new_row += "<input class=\"form-control form-control-number text-center\" type=\"number\" name=\"dim_pcs[]\" id=\"dim_pcs" + total_rows + "\" value=\"1\" min=\"1\" onkeyup=\"calculateItemCBM('" + total_rows + "');\" onchange=\"calculateItemCBM('" + total_rows + "');\">";
                                        new_row += "<button type=\"button\" class=\"btn btn-light btn-icon\" onclick=\"this.parentNode.querySelector('input[type=number]').stepUp(); calculateItemCBM('" + total_rows + "'); \"><i class=\"ph-plus ph-sm\"></i></button>";
                                        new_row += "</div>";
                                        new_row += "</div>";


                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<input type=\"number\" step=\"1\" name=\"dim_volume[]\" id=\"dim_volume" + total_rows + "\" min=\"0\" class=\"form-control text-center\">";
                                        new_row += "</div>";


                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<input readonly type=\"number\" name=\"dim_cbm[]\" id=\"dim_cbm" + total_rows + "\" min=\"0\" class=\"form-control bg-light bg-opacity-75 text-end\" placeholder=\"0\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" onclick=\"clear_row(" + total_rows + ")\"><span class=\"badge bg-warning\"> <i class=\"ph-x\"></i> </span></a></span> </div>";

                                        new_row += "</div>";

                                        // document.getElementById('add_row_here').innerHTML += new_row;

                                        // This is to preserve the values of previously dynamicall created elements
                                        document.getElementById('add_row_here').insertAdjacentHTML("beforebegin", new_row);

                                        document.getElementById('total_rows').value = total_rows;

                                    }


                                    function clear_row(row_no) {

                                        calculateItemCBM(row_no);

                                        document.getElementById('dim_length' + row_no).value = '';
                                        document.getElementById('dim_width' + row_no).value = '';
                                        document.getElementById('dim_height' + row_no).value = '';
                                        document.getElementById('dim_pcs' + row_no).value = '';
                                        document.getElementById('dim_volume' + row_no).value = '';
                                        document.getElementById('dim_cbm' + row_no).value = '';

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
                                    function calculateItemCBM(row_no) {

                                        let dim_length = document.getElementById('dim_length' + row_no);
                                        let dim_length_value = document.getElementById('dim_length' + row_no).value;

                                        let dim_width = document.getElementById('dim_width' + row_no);
                                        let dim_width_value = document.getElementById('dim_width' + row_no).value;

                                        let dim_height = document.getElementById('dim_height' + row_no);
                                        let dim_height_value = document.getElementById('dim_height' + row_no).value;

                                        if (dim_length != NaN && dim_length != '' && dim_length_value != 'undefined' && dim_length_value != '0') {

                                            // ---  Calculate Item dim_pcs ------------------
                                            dim_pcs = document.getElementById('dim_pcs' + row_no).value;
                                            dim_pcs = Number(dim_pcs);

                                            // VOLUME -> L × W × H ÷ 6000 ÷ 166.66 
                                            var sum = parseFloat(dim_length_value) * parseFloat(dim_width_value) * parseFloat(dim_height_value);
                                            var final_total = parseFloat(sum) * parseFloat(dim_pcs);
                                            var final_volume = parseFloat(final_total) / 1.66;
                                            document.getElementById('dim_volume' + row_no).value = parseFloat(final_volume).toFixed(2);

                                            // CBM -> L × W × H ÷ 6000 
                                            var sum = parseFloat(dim_length_value) * parseFloat(dim_width_value) * parseFloat(dim_height_value);
                                            var final_total = parseFloat(sum) * parseFloat(dim_pcs);
                                            var final_cbm = parseFloat(final_total).toFixed(2);
                                            document.getElementById('dim_cbm' + row_no).value = parseFloat(final_cbm);

                                            // calculateGrand();

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

                        <div class="col-lg-7"></div>

                        <div class="col-lg-4">
                            <div class="card ">

                                <div class="card-body"> <!--  bg-info bg-opacity-10 -->

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">TOTAL CBM:</label>
                                        <div class="col-lg-6">
                                            <input readonly type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" placeholder="0" name="grand_subtotal" id="grand_subtotal" value="<?php echo $total_cbm; ?>" />
                                        </div>
                                    </div>

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label">Total Volume Weight</label>
                                        <div class="col-lg-6">
                                            <input readonly type="number" class="form-control bg-light bg-opacity-50 text-end" name="grand_tax" id="grand_tax" value="<?php echo $total_volume; ?>" placeholder="0">
                                        </div>
                                    </div>


                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Total Pieces</label>
                                        <div class="col-lg-6">
                                            <input type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" name="grand_total" id="grand_total" value="<?php echo $total_pcs; ?>" readonly>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>



                    </div>


                    <div class="col-xl-12">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card">

                                    <div class="card-header d-flex align-items-center">
                                        <h6 class="mb-0">
                                            Commodity Details
                                        </h6>
                                    </div>

                                    <div class="card-body">

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Gross Weight: </label>
                                            <div class="col-lg-9">
                                                <input type="number" step="any" class="form-control" name="gross_weight" id="gross_weight" value="<?php echo $gross_weight; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Volume Weight: </label>
                                            <div class="col-lg-9">
                                                <input type="number" step="any" class="form-control" name="volume_weight" id="volume_weight" value="<?php echo $volume_weight; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Chargable Weight: </label>
                                            <div class="col-lg-9">
                                                <input type="number" step="any" class="form-control" name="chargeable_weight" id="chargeable_weight" value="<?php echo $chargeable_weight; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">No. of Pieces: </label>
                                            <div class="col-lg-9">
                                                <input type="number" step="any" class="form-control" name="no_of_pieces" id="no_of_pieces" value="<?php echo $no_of_pieces; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Commodity Type: </label>
                                            <div class="col-lg-9">
                                                <select class="form-select" name="commodity_type" id="commodity_type">
                                                    <option value='0'></option>
                                                    <?php
                                                    // -------------------------------------------------------------------------------------------------
                                                    $result = $mysqli->query("SELECT * FROM `" . DB::COMMODITY_TYPES  . "` WHERE is_active=1 ORDER BY commodity_type");
                                                    while ($rows = $result->fetch_array()) {
                                                        // -------------------------------------------------------------------------------------------------
                                                    ?>
                                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $commodity_type) { ?>selected <?php } else if ($rows['id'] == $commodity_type) { ?>selected <?php } ?>>
                                                            <?php echo $rows['commodity_type']; ?>
                                                        </option>

                                                    <?php
                                                    }  // while
                                                    ?>
                                                </select>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>



                            <div class="col-lg-6">
                                <div class="card">

                                    <!-- <div class="card-header d-flex align-items-center">
                                    <h6 class="mb-0"></h6>
                                </div> -->

                                    <div class="card-body">

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">No. of Contaiers:</label>
                                            <div class="col-lg-9">
                                                <select class="form-select" name="no_of_containers" id="no_of_containers">
                                                    <option value='0'></option>
                                                    <?php
                                                    // ---------------------------
                                                    for ($i = 1; $i <= 100; $i++) {
                                                        // ---------------------------
                                                    ?>
                                                        <option value="<?php echo $i; ?>" <?php if ($action == "edit_$module" && $i == $no_of_containers) { ?>selected <?php } else if ($i == $no_of_containers) { ?>selected <?php } ?>>
                                                            <?php echo $i; ?>
                                                        </option>

                                                    <?php
                                                    }  // for
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Insurance Needed?:</label>
                                            <div class="col-lg-9">
                                                <select class="form-select" name="insurance_needed" id="insurance_needed">
                                                    <option value=''>Please select</option>
                                                    <option value="yes" <?php if ($action == "edit_$module" && $insurance_needed == 'yes') { ?>selected <?php } else if ($insurance_needed == 'yes') { ?>selected <?php } ?>>Yes</option>
                                                    <option value="no" <?php if ($action == "edit_$module" && $insurance_needed == 'no') { ?>selected <?php } else if ($insurance_needed == 'no') { ?>selected <?php } ?>>No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Container Type: </label>
                                            <div class="col-lg-9">
                                                <select class="form-select" name="container_type" id="container_type">
                                                    <option value='0'></option>
                                                    <?php
                                                    // -------------------------------------------------------------------------------------------------
                                                    $result = $mysqli->query("SELECT * FROM `" . DB::CONTAINER_TYPES  . "` WHERE is_active=1 ORDER BY container_type");
                                                    while ($rows = $result->fetch_array()) {
                                                        // -------------------------------------------------------------------------------------------------
                                                    ?>
                                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $container_type) { ?>selected <?php } else if ($rows['id'] == $container_type) { ?>selected <?php } ?>>
                                                            <?php echo $rows['container_type']; ?>
                                                        </option>

                                                    <?php
                                                    }  // while
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Temperature Control Required: </label>
                                            <div class="col-lg-9">
                                                <select class="form-select" name="temperature_control_required" id="temperature_control_required">
                                                    <option value=''>Please select</option>
                                                    <option value="yes" <?php if ($action == "edit_$module" && $temperature_control_required == 'yes') { ?>selected <?php } else if ($temperature_control_required == 'yes') { ?>selected <?php } ?>>Yes</option>
                                                    <option value="no" <?php if ($action == "edit_$module" && $temperature_control_required == 'no') { ?>selected <?php } else if ($temperature_control_required == 'no') { ?>selected <?php } ?>>No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Container Number:</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="container_number" id="container_number" value="<?php echo $container_number; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Special Comments:</label>
                                            <div class="col-lg-9">
                                                <textarea class="form-control" name="special_comments" id="special_comments" style="field-sizing: content;" placeholder=""><?php echo $special_comments; ?></textarea>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>


                    <div class="col-xl-12">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card">

                                    <div class="card-header d-flex align-items-center">
                                        <h6 class="mb-0">
                                            Port Details
                                        </h6>
                                    </div>

                                    <div class="card-body">

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Landing Country: </label>
                                            <div class="col-lg-9">
                                                <select required class="form-select select" name="landing_country" id="landing_country" onchange="ajax_populate_landing_ports(this.value);">
                                                    <option value="0">Please select</option>
                                                    <?php
                                                    // -------------------------------------------------------------------------------------------------
                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                                    while ($rows = $result->fetch_array()) {
                                                        // -------------------------------------------------------------------------------------------------
                                                    ?>
                                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $landing_country) { ?>selected <?php } else if ($rows['id'] == $landing_country) { ?>selected <?php } ?>>
                                                            <?php echo $rows['country']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Port of Landing (POL): </label>
                                            <div class="col-lg-9">

                                                <select class="form-select" name="landing_port" id="landing_port">
                                                    <option value='0'></option>
                                                    <?php
                                                    // -------------------------------------------------------------------------------------------------
                                                    if (!empty($landing_country)) {
                                                        $result_ports = $mysqli->query("SELECT * FROM `" . DB::PORTS  . "` WHERE is_active=1 AND country=$landing_country");
                                                    } else {
                                                        $result_ports = $mysqli->query("SELECT * FROM `" . DB::PORTS  . "` WHERE id=0");
                                                    }

                                                    while ($rows_ports = $result_ports->fetch_array()) {
                                                        $port        = s__($rows_ports['port']);
                                                        // -------------------------------------------------------------------------------------------------
                                                    ?>
                                                        <option value="<?php echo $rows_ports['id']; ?>" <?php if ($action == "edit_$module" && $rows_ports['id'] == $landing_port) { ?>selected <?php } else if ($rows_ports['id'] == $landing_port) { ?>selected <?php } ?>>
                                                            <?php echo $port; ?>
                                                        </option>

                                                    <?php
                                                    }  // while

                                                    ?>
                                                </select>

                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Place of Loading: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="loading_place" id="loading_place" value="<?php echo $loading_place; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Billing City: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="billing_city" id="billing_city" value="<?php echo $billing_city; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Billing State: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="billing_state" id="billing_state" value="<?php echo $billing_state; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Billing Code: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="billing_code" id="billing_code" value="<?php echo $billing_code; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Billing Country: </label>
                                            <div class="col-lg-9">
                                                <select required class="form-select select" name="billing_country" id="billing_country" onchange="ajax_populate_states(this.value);">
                                                    <option value="0">Please select</option>
                                                    <?php
                                                    // -------------------------------------------------------------------------------------------------
                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                                    while ($rows = $result->fetch_array()) {
                                                        // -------------------------------------------------------------------------------------------------
                                                    ?>
                                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $billing_country) { ?>selected <?php } else if ($rows['id'] == $billing_country) { ?>selected <?php } ?>>
                                                            <?php echo $rows['country']; ?>
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
                                    <h6 class="mb-0"></h6>
                                </div> -->

                                    <div class="card-body">

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Destination Country: </label>
                                            <div class="col-lg-9">
                                                <select required class="form-select select" name="destination_country" id="destination_country" onchange="ajax_populate_destination_ports(this.value);">
                                                    <option value="0">Please select</option>
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

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Port of Destination (POD): </label>
                                            <div class="col-lg-9">

                                                <select class="form-select" name="destination_port" id="destination_port">
                                                    <option value='0'></option>
                                                    <?php
                                                    // -------------------------------------------------------------------------------------------------
                                                    if (!empty($destination_country)) {
                                                        $result_ports = $mysqli->query("SELECT * FROM `" . DB::PORTS  . "` WHERE is_active=1 AND country=$destination_country");
                                                    } else {
                                                        $result_ports = $mysqli->query("SELECT * FROM `" . DB::PORTS  . "` WHERE id=0");
                                                    }

                                                    while ($rows_ports = $result_ports->fetch_array()) {
                                                        $port        = s__($rows_ports['port']);
                                                        // -------------------------------------------------------------------------------------------------
                                                    ?>
                                                        <option value="<?php echo $rows_ports['id']; ?>" <?php if ($action == "edit_$module" && $rows_ports['id'] == $destination_port) { ?>selected <?php } else if ($rows_ports['id'] == $destination_port) { ?>selected <?php } ?>>
                                                            <?php echo $port; ?>
                                                        </option>

                                                    <?php
                                                    }  // while

                                                    ?>
                                                </select>

                                            </div>
                                        </div>


                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Final Destination (FDP): </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="fdp" id="fdp" value="<?php echo $fdp; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Shipping City: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="shipping_city" id="shipping_city" value="<?php echo $shipping_city; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Shipping State: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="shipping_state" id="shipping_state" value="<?php echo $shipping_state; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Shipping Code: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="shipping_code" id="shipping_code" value="<?php echo $shipping_code; ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Shipping Country: </label>
                                            <div class="col-lg-9">
                                                <select required class="form-select select" name="shipping_country" id="shipping_country" onchange="ajax_populate_states(this.value);">
                                                    <option value="0">Please select</option>
                                                    <?php
                                                    // -------------------------------------------------------------------------------------------------
                                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                                    while ($rows = $result->fetch_array()) {
                                                        // -------------------------------------------------------------------------------------------------
                                                    ?>
                                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $shipping_country) { ?>selected <?php } else if ($rows['id'] == $shipping_country) { ?>selected <?php } ?>>
                                                            <?php echo $rows['country']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>



                    <div class="col-xl-12">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card">

                                    <div class="card-header d-flex align-items-center">
                                        <h6 class="mb-0">
                                            After Service
                                        </h6>
                                    </div>

                                    <div class="card-body">

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Customer happy with service: </label>
                                            <div class="col-lg-9">
                                                <select name="happy_customer" id="happy_customer" class="form-select">
                                                    <option value='0'></option>
                                                    <option value="yes" <?php if ($happy_customer == 'yes') { ?>selected <?php } ?>>Yes</option>
                                                    <option value="no" <?php if ($happy_customer == 'no') { ?>selected <?php } ?>>No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Reason for Customer Unsatisfaction: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="unhappy_reason" id="unhappy_reason" value="<?php echo $unhappy_reason; ?>">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>



                            <div class="col-lg-6">
                                <div class="card">

                                    <!-- <div class="card-header d-flex align-items-center">
                                    <h6 class="mb-0"></h6>
                                </div> -->

                                    <div class="card-body">

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Shipment Delivered on Time: </label>
                                            <div class="col-lg-9">
                                                <select name="shipment_on_time" id="shipment_on_time" class="form-select">
                                                    <option value='0'></option>
                                                    <option value="yes" <?php if ($shipment_on_time == 'yes') { ?>selected <?php } ?>>Yes</option>
                                                    <option value="no" <?php if ($shipment_on_time == 'no') { ?>selected <?php } ?>>No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Referral: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="referral" id="referral" value="<?php echo $referral; ?>">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>



                    <div class="col-xl-12">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card">

                                    <div class="card-header d-flex align-items-center">
                                        <h6 class="mb-0">
                                            System Fields
                                        </h6>
                                    </div>

                                    <div class="card-body">

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Created By: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $created_by; ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Modified By: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $modified_by; ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Customer Type: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $customer_type; ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Quote: </label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $quote_id; ?>" disabled>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>



                            <div class="col-lg-6">
                                <div class="card">

                                    <!-- <div class="card-header d-flex align-items-center">
                                    <h6 class="mb-0"></h6>
                                </div> -->

                                    <div class="card-body">

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Books Customer ID:</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $books_customer_id; ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Approved Time:</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $approved_time; ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Project ID:</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $project_id; ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <label class="col-lg-3 col-form-label">Approved Time ReSubmission:</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $approved_time_resubmission; ?>" disabled>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>



                    <div class="row">

                        <div class="col-lg-4">

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ms-sm-3 mb-3 mb-sm-0">
                                        <label class="col-lg-6 col-form-label">Notes:</label>
                                        <textarea class="form-control" name="notes" id="notes" style="field-sizing: content;" placeholder=""><?php echo $notes; ?></textarea>
                                        <!-- Looking forward for your business -->
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
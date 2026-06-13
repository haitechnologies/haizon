<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'quotations';
$module_caption     = 'Quotation';
$tbl_name = DB::QUOTATIONS;
$create_by          = $_SESSION[$project_pre]['DASHBOARD']['user_id'];


$error_message              = '';
$success_message            = '';


/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

// print_r($_POST);



// ------------------ CHECK IF PAST QUOTATION ----------------
$past_quotation = 0;
$rs_past     = $mysqli->query("SELECT id FROM `" . DB::QUOTATIONS . "` WHERE id ='" . $id . "' AND quotation_date < '" . date('Y-m-d', time()) . "' ");
if ($rs_past->num_rows > 0) $past_quotation = 1;



$lead_id = '';
if (isset($_REQUEST['lead_id']))        $lead_id     = e_s__($_REQUEST['lead_id']);
if (isset($_POST['lead_id']))           $lead_id     = e_s__($_POST['lead_id']);



/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/


if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 0;


if (isset($_REQUEST['quotation_status']))  $quotation_status     = e_s__($_REQUEST['quotation_status']);
else $quotation_status = 'not_confirmed';


if (isset($_REQUEST['quotation_id']))     $id     = e_s__($_REQUEST['quotation_id']);


if (empty($id)) header("Location:listing_$module.php");;



$quotation_item_id = 0;
if (isset($_REQUEST['quotation_item_id']) && !empty($_REQUEST['quotation_item_id']))     $quotation_item_id     = e_s__($_REQUEST['quotation_item_id']);


/*
|--------------------------------------------------------------------------
| UPDATE quotation STATUS
|--------------------------------------------------------------------------
|
*/
if (($action == "update_$module" && !empty($id) && !empty($quotation_status))) {

    $result = $mysqli->query("UPDATE `$tbl_name` SET quotation_status = '" . $quotation_status . "'  WHERE id=$id");

    if ($result) {
        $success_message = "The $module_caption status has been updated successfully.";


        // ------------ Quotation Log -------------
        // if (isset($_POST['quotation_log_comments']) && !empty($_POST['quotation_log_comments'])) {
        //     $quotation_log_comments     = e_s__($_POST['quotation_log_comments']);

        //     $mysqli->query("INSERT INTO `" . tbl_quotation_logs . "` (quotation_id, quotation_status, comments) VALUES ('" . $id . "', '" . $quotation_status . "', '" . $quotation_log_comments . "'); ");
        //     fp__(tbl_quotation_logs, $mysqli->insert_id);
        // }





        if ($quotation_status == 'not_confirmed') {


            // Delete PDF - Next Time System will Generate New with Confirmed Status 
            $pdf        = getTableAttr('pdf', DB::QUOTATIONS, $id);
            unlink("../pdfs_quotations/" . $pdf . ".pdf");
            $mysqli->query("UPDATE " . DB::QUOTATIONS . "  SET pdf = '' WHERE id=$id");
        } else if ($quotation_status == 'confirmed') {


            // Delete PDF - Next Time System will Generate New with Confirmed Status 
            // $pdf        = getTableAttr('pdf', DB::QUOTATIONS, $id);
            // unlink("../pdfs_quotations/" . $pdf . ".pdf");
            // $mysqli->query("UPDATE " . DB::QUOTATIONS . "  SET pdf = '' WHERE id=$id");


            // -----------------------------------------------------------------------------------------------------------------------------------------------
            // DELETE TRIPS - IF quotation STATUS IS CANCELLED
            // -----------------------------------------------------------------------------------------------------------------------------------------------
        } else if ($quotation_status == 'cancelled' || $quotation_status == 'on_hold') {

            // Delete PDF - Next Time System will Generate New 
            // $pdf        = getTableAttr('pdf', DB::QUOTATIONS, $id);
            // unlink("../pdfs_quotations/" . $pdf . ".pdf");
            // $mysqli->query("UPDATE " . DB::QUOTATIONS . "  SET pdf = '' WHERE id=$id");


            /* ---------------------- NOTIFICATIONS QUERY ---------------------- */
        }


        // --------------------------------------------------------------------------------
        header("Location:quotation.php?id=$id&success_message=$success_message");
        // $error_message = "Sorry! $module Status Could Not Be Updated.";
    } else {
        $error_message = "Sorry! $module Status Could Not Be Updated.";
    }
}




/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|
*/

$quotation_item_id_arr      = array();
$item_id_arr                = array();
$service_arr                = array();
$description_arr            = array();
$qty_arr                    = array();
$rate_arr                   = array();
$sub_total_arr              = array();
$tax_arr                    = array();
$tax_amount_arr             = array();
$total_arr                  = array();



if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows            = e_s__($_POST['total_rows']);
    // if ($total_rows == 0 || $total_rows == '') $total_rows = 1;
} else {
    $total_rows            = 1;
}



/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
if (!empty($id)) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $customer_id            = s__($row['customer_id']);

    $quotation_no           = s__($row['quotation_no']);
    $quotation_status       = s__($row['quotation_status']);
    $quotation_date         = s__($row['quotation_date']);
    $expiry_date            = s__($row['expiry_date']);
    $customer_notes         = s__($row['customer_notes']);
    $terms_and_conditions   = s__($row['terms_and_conditions']);

    $grand_subtotal             = s__($row['grand_subtotal']);
    $grand_discount_type        = s__($row['grand_discount_type']);
    $grand_discount_type_value  = s__($row['grand_discount_type_value']);
    $grand_discount_amount      = s__($row['grand_discount_amount']);
    $grand_after_discount       = s__($row['grand_after_discount']);
    $grand_tax                  = s__($row['grand_tax']);
    $grand_total                = s__($row['grand_total']);

    $publish                = s__($row['is_active']);



    // --- Customer Information
    $rs = $mysqli->query("SELECT * FROM `" . DB::CUSTOMERS . "` WHERE id=$customer_id");
    $row_customer = $rs->fetch_array();
    $salutation             = s__($row_customer['salutation']);
    $first_name             = s__($row_customer['first_name']);
    $last_name              = s__($row_customer['last_name']);
    $company_name           = s__($row_customer['company_name']);
    $display_name           = s__($row_customer['display_name']);
    $email                  = s__($row_customer['email']);
    $phone                  = s__($row_customer['phone']);
    $mobile                 = s__($row_customer['mobile']);
    $trn                    = s__($row_customer['trn']);

    // $rs_billing     = $mysqli->query("SELECT * FROM `$tbl_name` WHERE customer_id=$customer_id AND type='billing' ");
    // $row_billing    = $rs_billing->fetch_array();

    // $billing_attention      = (!empty($row_billing['attention']) ? s__($row_billing['attention']) : '');
    // $billing_country        = (!empty($row_billing['country']) ? s__($row_billing['country']) : '');
    // $billing_address_line1  = (!empty($row_billing['address_line1']) ? s__($row_billing['address_line1']) : '');
    // $billing_address_line2  = (!empty($row_billing['address_line2']) ? s__($row_billing['address_line2']) : '');
    // $billing_city           = (!empty($row_billing['city']) ? s__($row_billing['city']) : '');
    // $billing_state          = (!empty($row_billing['state']) ? s__($row_billing['state']) : '');
    // $billing_zipcode        = (!empty($row_billing['zipcode']) ? s__($row_billing['zipcode']) : '');
    // $billing_phone          = (!empty($row_billing['phone']) ? s__($row_billing['phone']) : '');
    // $billing_fax            = (!empty($row_billing['fax']) ? s__($row_billing['fax']) : '');


    // $rs_shipping     = $mysqli->query("SELECT * FROM `$tbl_name` WHERE customer_id=$customer_id AND type='shipping' ");
    // $row_shipping    = $rs_shipping->fetch_array();

    // $shipping_attention      = (!empty($row_shipping['attention']) ? s__($row_shipping['attention']) : '');
    // $shipping_country        = (!empty($row_shipping['country']) ? s__($row_shipping['country']) : '');
    // $shipping_address_line1  = (!empty($row_shipping['address_line1']) ? s__($row_shipping['address_line1']) : '');
    // $shipping_address_line2  = (!empty($row_shipping['address_line2']) ? s__($row_shipping['address_line2']) : '');
    // $shipping_city           = (!empty($row_shipping['city']) ? s__($row_shipping['city']) : '');
    // $shipping_state          = (!empty($row_shipping['state']) ? s__($row_shipping['state']) : '');
    // $shipping_zipcode        = (!empty($row_shipping['zipcode']) ? s__($row_shipping['zipcode']) : '');
    // $shipping_phone          = (!empty($row_shipping['phone']) ? s__($row_shipping['phone']) : '');
    // $shipping_fax            = (!empty($row_shipping['fax']) ? s__($row_shipping['fax']) : '');




    $quotation_date         = processDateYtoD($quotation_date);
    $expiry_date            = ($expiry_date == '1970-01-01') ? '' : processDateDtoY($expiry_date);


    // ------------------ TOTAL QUOTATIONS ITEMS ------------------
    // echo "SELECT * FROM `" . DB::QUOTATION_ITEMS . "` WHERE quotation_id=$id ORDER BY requested_date";
    $result_quotation_items     = $mysqli->query("SELECT * FROM `" . DB::QUOTATION_ITEMS . "` WHERE quotation_id=$id ");
    $total_rows                 = $result_quotation_items->num_rows;


    if ($total_rows > 0) {
        while ($row_quotation_items = $result_quotation_items->fetch_array()) {

            array_push($quotation_item_id_arr,      $row_quotation_items['id']);
            array_push($service_arr,                $row_quotation_items['service']);
            array_push($description_arr,            $row_quotation_items['description']);
            array_push($qty_arr,                    $row_quotation_items['qty']);
            array_push($rate_arr,                   $row_quotation_items['rate']);
            array_push($sub_total_arr,              $row_quotation_items['sub_total']);
            array_push($tax_arr,                    $row_quotation_items['tax']);
            array_push($tax_amount_arr,             $row_quotation_items['tax_amount']);
            array_push($total_arr,                  $row_quotation_items['total']);
        }
    }
}


if ($total_rows == 0)           $total_rows = 1;


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

// Set Page BG Color
// if ($quotation_status == 'not_confirmed')         $quotation_bgcolor = 'bg-info bg-opacity-10';
// else if ($quotation_status == 'confirmed')        $quotation_bgcolor = 'bg-success bg-opacity-10';
// else if ($quotation_status == 'on_hold')          $quotation_bgcolor = 'bg-black bg-opacity-10';
// else if ($quotation_status == 'cancelled')        $quotation_bgcolor = 'bg-warning bg-opacity-10';


// -------------------------------------------------------------------------------------------
?>
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module" || $action == "change_password") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1">
                
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="quotation.php" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />

        <!-- Page header -->


                <div class="row p-lg-2">

                    <div class="col-lg-1">
                    </div>

                    <div class="card col-lg-10">
                        <div class="card-header d-flex align-items-center py-0">
                            <h5 class="py-3 mb-0">Quotation</h5>
                            <div class="d-inline-flex ms-auto">
                                <?php
                                // ------------------ CHECK IF NOT PAST quotation ----------------
                                //if ($past_quotation == 0) {
                                ?>

                                <?php //if ($quotation_status != 'confirmed') { 
                                ?>
                                <button type="button" onclick="window.location.href='lead_quotations.php?action=edit_lead_quotations&id=<?php echo $id; ?>&customer_id=<?php echo $customer_id; ?>'" class="btn btn-light"><i class="ph-pencil me-2"></i> Edit</button>
                                <?php //} 
                                ?>

                                <?php //} 
                                ?>

                                <?php
                                $token = hash("sha512", 'bushogai' . $id);
                                ?>

                                <button type="button" onclick="window.open('pdf_quotation.php?id=<?php echo $id; ?>&token=<?php echo $token; ?>', '_blank');" class="btn btn-light ms-3"><i class="ph-file-pdf me-2"></i> Download</button>

                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <!-- <div class="col-sm-6">
                                    <div class="mb-4">
                                        <div class="d-inline-flex align-items-center mt-2 mb-3">
                                            <img src="assets/images/logo_icon.svg" class="h-24px" alt="">
                                            <h4 class="d-none d-sm-inline-block text-body mb-0 ms-2">Limitless</h4>
                                        </div>

                                        <ul class="list list-unstyled mt-2 mb-0">
                                            <li>2269 Elba Lane</li>
                                            <li>Paris, France</li>
                                            <li>888-555-2311</li>
                                        </ul>
                                    </div>
                                </div> -->

                                <div class="col-sm-6">
                                    <div class="mb-4">

                                        <span class="text-muted">Quotation To:</span>
                                        <ul class="list list-unstyled mb-0">
                                            <li>
                                                <h5 class="my-2"><?php echo $display_name; ?></h5>
                                            </li>
                                            <li><span class="fw-semibold"><?php echo $company_name; ?></span></li>
                                            <li><?php echo $street1; ?></li>
                                            <li><?php echo $street2; ?></li>
                                            <li><?php echo $city; ?></li>
                                            <li><?php echo $state; ?></li>
                                            <li><?php echo $country; ?></li>
                                            <li><?php echo $mobile; ?></li>
                                            <li><a href="#"><?php echo $email; ?></a></li>
                                        </ul>

                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="text-sm-end mb-4">
                                        <h4 class="text-primary mb-2 mt-lg-2">Quotation #<?php echo $quotation_no; ?></h4>
                                        <ul class="list list-unstyled mb-0">
                                            <li>Date: <span class="fw-semibold"><?php echo $quotation_date; ?></span></li>
                                            <li>Due date: <span class="fw-semibold"><?php echo $expiry_date; ?></span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="d-lg-flex flex-lg-wrap">
                                <!-- <div class="mb-4 mb-lg-2">
                                    <span class="text-muted">Quotation To:</span>
                                    <ul class="list list-unstyled mb-0">
                                        <li>
                                            <h5 class="my-2"><?php echo $display_name; ?></h5>
                                        </li>
                                        <li><span class="fw-semibold"><?php echo $company_name; ?></span></li>
                                        <li><?php echo $street1; ?></li>
                                        <li><?php echo $street2; ?></li>
                                        <li><?php echo $city; ?></li>
                                        <li><?php echo $state; ?></li>
                                        <li><?php echo $country; ?></li>
                                        <li><?php echo $mobile; ?></li>
                                        <li><a href="#"><?php echo $email; ?></a></li>
                                    </ul>
                                </div> -->

                                <!-- <div class="mb-2 ms-auto">
                                    <span class="text-muted">Payment Details:</span>
                                    <div class="d-flex flex-wrap wmin-lg-400">
                                        <ul class="list list-unstyled mb-0">
                                            <li>
                                                <h5 class="my-2">Total Due:</h5>
                                            </li>
                                            <li>Bank name:</li>
                                            <li>Country:</li>
                                            <li>City:</li>
                                            <li>Address:</li>
                                            <li>IBAN:</li>
                                            <li>SWIFT code:</li>
                                        </ul>

                                        <ul class="list list-unstyled text-end mb-0 ms-auto">
                                            <li>
                                                <h5 class="my-2">$8,750</h5>
                                            </li>
                                            <li><span class="fw-semibold">Profit Bank Europe</span></li>
                                            <li>United Kingdom</li>
                                            <li>London E1 8BF</li>
                                            <li>3 Goodman Street</li>
                                            <li><span class="fw-semibold">KFH37784028476740</span></li>
                                            <li><span class="fw-semibold">BPT4E</span></li>
                                        </ul>
                                    </div>
                                </div> -->
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-lg">
                                <thead>
                                    <tr>
                                        <th>ITEM DETAILS</th>
                                        <th>DESCRIPTION</th>
                                        <th class="text-center">QUANTITY</th>
                                        <th class="text-center">RATE</th>
                                        <th class="text-center">SUBTOTAL</th>
                                        <th class="text-center">TAX</th>
                                        <th class="text-center">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- <tr>
                                        <td>
                                            <div class="fw-bold">Create UI design model</div>
                                            <span class="text-muted">One morning, when Gregor Samsa woke from troubled.</span>
                                        </td>
                                        <td>$70</td>
                                        <td>57</td>
                                        <td><span class="fw-semibold">$3,990</span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">Support tickets list doesn't support commas</div>
                                            <span class="text-muted">I'd have gone up to the boss and told him just what i think.</span>
                                        </td>
                                        <td>$70</td>
                                        <td>12</td>
                                        <td><span class="fw-semibold">$840</span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">Fix website issues on mobile</div>
                                            <span class="text-muted">I am so happy, my dear friend, so absorbed in the exquisite.</span>
                                        </td>
                                        <td>$70</td>
                                        <td>31</td>
                                        <td><span class="fw-semibold">$2,170</span></td>
                                    </tr> -->

                                    <?php
                                    /*
                                    |--------------------------------------------------------------------------------------------------------------------------------|
                                    |------------------------------------------------------ quotation ITEMS  ----------------------------------------------------------|
                                    |--------------------------------------------------------------------------------------------------------------------------------|
                                    */
                                    // echo $total_rows;

                                    for ($quotation_item = 1; $quotation_item <= $total_rows; $quotation_item++) {
                                        $index = $quotation_item;
                                        $index = $index - 1;

                                        // $fee_included = '';
                                        // if (isset($fee_included_arr[$index]) && !empty($fee_included_arr[$index])) {
                                        //     $fee_included    = $fee_included_arr[$index];
                                        // }

                                        // $requested_date_time = '';
                                        // if (isset($requested_date_time_arr[$index]) && !empty($requested_date_time_arr[$index])) {
                                        //     $requested_date_time    = $requested_date_time_arr[$index];
                                        //     $requested_date_time    = date('j F Y | H:i', strtotime($requested_date_time)); // Remove Seconds
                                        // }


                                        //--------------------------------------------------------------------------------------------------------------------------------|
                                        $quotation_item_id                = $quotation_item_id_arr[$index];
                                        // $quotation_requested_date         = date('j F Y', strtotime($requested_date_arr[$index]));
                                        // $quotation_itn_stops        = process_stops(getTableAttr("stop_name", tbl_stops, $itn_arr[$index]));
                                        // $quotation_itn_stops              = process_stops($itn_arr[$index]);
                                        // $quotation_vehicle_type           = getTableAttr('vehicle_type', tbl_vehicle_types, $vehicle_type_arr[$index]);

                                        //--------------------------------------------------------------------------------------------------------------------------------|
                                    ?>

                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo getTableAttr('item_name', DB::ITEMS, $service_arr[$index]); ?></div>
                                                <span class="text-muted">
                                                    <?php
                                                    // ----------------------------------------------
                                                    // Seprate Line Number on base of Space new line
                                                    // ----------------------------------------------
                                                    $desc = explode("\r", $description_arr[$index]);
                                                    // print_r($desc);
                                                    $d_counter = 1;
                                                    if (count($desc) > 0) {
                                                        foreach ($desc as $d) {
                                                            if (!empty($d)) {
                                                                echo $d_counter++ . '. ' . $d;
                                                                echo '<br />';
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo $description_arr[$index]; ?></td>
                                            <td class="text-center"><?php echo $qty_arr[$index]; ?></td>
                                            <td class="text-end"><?php echo $rate_arr[$index]; ?></td>
                                            <td class="text-end"><?php echo $sub_total_arr[$index]; ?></td>
                                            <td class="text-end"><?php echo $tax_arr[$index]; ?>% (<?php echo $tax_amount_arr[$index]; ?>)</td>
                                            <td class="text-end"><span class="fw-semibold"><?php echo $total_arr[$index]; ?></span></td>
                                        </tr>
                                    <?php
                                    } // for
                                    /*
                                    |--------------------------------------------------------------------------------------------------------------------------------
                                    |--------------------------------------------------------------------------------------------------------------------------------
                                    |--------------------------------------------------------------------------------------------------------------------------------
                                    */
                                    ?>


                                </tbody>
                            </table>
                        </div>

                        <div class="card-body border-top">
                            <div class="d-lg-flex flex-lg-wrap">

                                <div class="pt-2 mb-3">
                                    <ul class="list-unstyled text-muted">
                                        <li class="mb-3">Customer Notes: <br /><?php echo $customer_notes; ?></li>
                                        <li class="mb-3">Terms and Conditions: <br /><?php echo $terms_and_conditions; ?></li>
                                    </ul>
                                </div>

                                <div class="pt-2 mb-3 wmin-lg-400 ms-auto">
                                    <!-- <h6 class="mb-3">Total due</h6> -->
                                    <div class="table-responsive">
                                        <table class="table">
                                            <tbody>
                                                <tr>
                                                    <td>Grand Subtotal:</td>
                                                    <td class="text-end"><?php echo $grand_subtotal; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Discount Type: <?php echo $grand_discount_type; ?></td>
                                                    <td class="text-end"><?php echo $grand_discount_type_value; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Discount Amount: </td>
                                                    <td class="text-end"><?php echo $grand_discount_amount; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Subtotal: (Discounted): </td>
                                                    <td class="text-end"><?php echo $grand_after_discount; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Total Tax Amount:</td>
                                                    <td class="text-end"><?php echo $grand_tax; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Grand Total:</td>
                                                    <td class="text-end text-primary">
                                                        <h5 class="fw-semibold"><?php echo $grand_total; ?></h5>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- <div class="text-end mt-3">
                                        <button type="button" class="btn btn-primary">
                                            Send invoice
                                            <i class="ph-paper-plane-tilt ms-2"></i>
                                        </button>
                                    </div> -->

                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <span class="text-muted">Thank you for your Business.</span>
                        </div>
                    </div>


                    <div class="col-lg-1">
                    </div>

                </div>

            </div>
        </div>


        </form>
    <?php include('admin_elements/copyright.php'); ?>
</div>

</div>
<?php include('admin_elements/admin_footer.php'); ?>
<?php

include('admin_elements/admin_header.php');

$module = 'debit_notes';
$module_caption = 'Debit Note';
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


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

$debit_note_id = '';
if (isset($_REQUEST['debit_note_id']))        $debit_note_id     = e_s__($_REQUEST['debit_note_id']);
if (isset($_POST['debit_note_id']))           $debit_note_id     = e_s__($_POST['debit_note_id']);



// ------------------ CHECK IF EXISTS ----------------
//VERIFY IF IS VALID 
$rs_valid     = $mysqli->query("SELECT id FROM `" . tbl_debit_notes . "` WHERE id='" . $debit_note_id . "'");
if ($rs_valid->num_rows == 0) {
    header("Location:listing_debit_notes.php?error_message=Invalid Record in the database.");
}


/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

$publish = 1;


$debit_note_status = 0;
if (isset($_REQUEST['debit_note_status']) && !empty($_REQUEST['debit_note_status'])) {
    $debit_note_status   = e_s__($_REQUEST['debit_note_status']);
}


/*
|--------------------------------------------------------------------------
| CLONE
|--------------------------------------------------------------------------
|
*/
if (($action == "clone_$module" && !empty($debit_note_id))) {

    // ======================================================
    // DEBIT NOTE NO Auto Generation System
    // ======================================================

    // Build the prefix for this month
    $prefix = 'FL-DN' . date('ym');

    // Get the last debit note number for this month
    $sql = "SELECT debit_note_no  FROM `" . tbl_debit_notes . "`  WHERE debit_note_no LIKE '{$prefix}-%'ORDER BY debit_note_no DESC LIMIT 1";
    $result = $mysqli->query($sql);

    if ($row = $result->fetch_assoc()) {
        // Extract the serial part after the dash
        $last_serial = (int) substr($row['debit_note_no'], -4);
        $new_serial = $last_serial + 1;
    } else {
        // First debit note of the month
        $new_serial = 1;
    }

    // Build new debit note number with zero padding
    $debit_note_no = $prefix . '-' . str_pad($new_serial, 4, '0', STR_PAD_LEFT);


    // -- Debit Note
    $result = $mysqli->query("INSERT INTO `" . tbl_debit_notes . "` (vendor_id, warehouse_id, debit_note_no, reference_no, purchase_id, debit_note_date, grand_subtotal, grand_discount_type, grand_discount_type_value, grand_discount_amount, grand_after_discount, grand_tax, grand_total, vendor_notes, terms_and_conditions, debit_note_status, is_active, created_at, updated_at)
    SELECT vendor_id, warehouse_id, '" . $debit_note_no . "', reference_no, purchase_id, NOW(), grand_subtotal, grand_discount_type, grand_discount_type_value, grand_discount_amount, grand_after_discount, grand_tax, grand_total, vendor_notes, terms_and_conditions, 'draft', is_active, NOW(), NOW() FROM `" . tbl_debit_notes . "` WHERE id = $debit_note_id;");

    $new_cloned_id = $mysqli->insert_id;
    fp__($tbl_name, $new_cloned_id);

    // -- Debit Note Items
    $result = $mysqli->query("INSERT INTO `" . tbl_debit_note_items . "` (debit_note_id, service, description, qty, rate, tax, tax_amount, sub_total, total, created_at, updated_at, created_by) 
    SELECT $new_cloned_id, service, description, qty, rate, tax, tax_amount, sub_total, total, NOW(), NOW(), '" . $session_user_id . "' FROM `" . tbl_debit_note_items . "` WHERE debit_note_id = $debit_note_id");

    fp__(tbl_debit_note_items, $mysqli->insert_id);


    $success_message = 'Debit Note has been cloned successfully. Please click here to view. <a href="debit_note_overview.php?debit_note_id=' . $new_cloned_id . '"> ' . $debit_note_no . '</a>';

    header("Location:debit_note_overview.php?debit_note_id=$new_cloned_id&success_message=$success_message");
    exit;





    /*
|--------------------------------------------------------------------------
| UPDATE Debit Note STATUS
|--------------------------------------------------------------------------
|
*/
} else if (($action == "update_$module" && !empty($debit_note_id) && !empty($debit_note_status))) {

    $result = $mysqli->query("UPDATE `$tbl_name` SET debit_note_status = '" . $debit_note_status . "' WHERE id=$debit_note_id");

    if ($result) {
        $success_message = "The $module_caption status has been updated successfully.";

        // =====================================================================
        // CREATE JOURNAL ENTRY WHEN STATUS IS 'VOID'
        // =====================================================================
        if ($debit_note_status === 'void') {
            try {
                // Define table names
                $journals_table = defined('tbl_journals') ? tbl_journals : 'fls_journals';
                $journal_items_table = defined('tbl_journal_items') ? tbl_journal_items : 'fls_journal_items';

                // Check if void journal entry already exists
                $void_check = $mysqli->query("SELECT id FROM `{$journals_table}` WHERE reference_type='debit_note_void' AND reference_id={$debit_note_id} LIMIT 1");
                
                if ($void_check && $void_check->num_rows > 0) {
                    $success_message .= " Note: Void journal entry already exists.";
                } else {
                    // Initialize Journal Manager
                    require_once('../classes/AccountingJournalManager.php');
                    $journal = new AccountingJournalManager($mysqli);
                    
                    // Get debit note data
                    $debit_note_no = getTableAttr('debit_note_no', tbl_debit_notes, $debit_note_id);
                    $grand_subtotal = getTableAttr('grand_subtotal', tbl_debit_notes, $debit_note_id);
                    $grand_total = getTableAttr('grand_total', tbl_debit_notes, $debit_note_id);
                    
                    // Check if original journal entry exists
                    $journal_check = $mysqli->query("SELECT id FROM `{$journals_table}` WHERE reference_type='debit_note' AND reference_id={$debit_note_id} LIMIT 1");
                    
                    if ($journal_check && $journal_check->num_rows > 0) {
                        // Get original journal entries
                        $original_journal = $journal_check->fetch_assoc();
                        $original_journal_id = $original_journal['id'];
                        
                        // Get all original journal items
                        $items_result = $mysqli->query("SELECT account, debit, credit FROM `{$journal_items_table}` WHERE journal_id={$original_journal_id}");
                        
                        // Build reversing entries (swap debit and credit)
                        $reversing_entries = array();
                        while ($item = $items_result->fetch_assoc()) {
                            if ($item['debit'] > 0) {
                                // Original debit becomes credit
                                $reversing_entries[] = array(
                                    'account' => $item['account'],
                                    'amount'  => $item['debit'],
                                    'type'    => 'credit'
                                );
                            }
                            if ($item['credit'] > 0) {
                                // Original credit becomes debit
                                $reversing_entries[] = array(
                                    'account' => $item['account'],
                                    'amount'  => $item['credit'],
                                    'type'    => 'debit'
                                );
                            }
                        }
                        
                        if (count($reversing_entries) > 0) {
                            // Create reversing journal entry
                            $void_journal_result = $journal->createJournalEntry(
                                array(
                                    'reference_type'   => 'debit_note_void',
                                    'reference_id'     => $debit_note_id,
                                    'reference_no'     => $debit_note_no . ' (VOID)',
                                    'journal_date'     => date('Y-m-d'),
                                    'description'      => 'VOID - Reversal of Debit Note #' . $debit_note_no,
                                    'currency'         => 'AED',
                                    'grand_subtotal'   => -$grand_subtotal,
                                    'grand_total'      => -$grand_total,
                                    'reporting_method' => 'accrual'
                                ),
                                $reversing_entries
                            );
                            
                            if ($void_journal_result['success']) {
                                error_log("Void journal entry created: ID {$void_journal_result['journal_id']} for Debit Note {$debit_note_id}");
                                $success_message .= " Reversing journal entry created successfully.";
                            } else {
                                error_log("Failed to create void journal for Debit Note {$debit_note_id}: " . $void_journal_result['message']);
                                $success_message .= " Warning: " . $void_journal_result['message'];
                            }
                        }
                    } else {
                        error_log("No original journal found to reverse for Debit Note {$debit_note_id}");
                        $success_message .= " Note: No journal entry found to reverse.";
                    }
                }
            } catch (Exception $e) {
                error_log("Void journal creation exception for Debit Note {$debit_note_id}: " . $e->getMessage());
                $success_message .= " Note: Reversing journal entry not created (" . $e->getMessage() . ")";
            }
        }

        // =====================================================================
        // CREATE JOURNAL ENTRY WHEN STATUS IS 'OPEN' OR 'ISSUED'
        // =====================================================================
        else if ($debit_note_status === 'open' || $debit_note_status === 'issued') {
            try {
                // Define table names
                $journals_table = defined('tbl_journals') ? tbl_journals : 'fls_journals';
                $journal_items_table = defined('tbl_journal_items') ? tbl_journal_items : 'fls_journal_items';

                // Check if journal entry already exists to avoid duplicates
                $journal_check = $mysqli->query("SELECT id FROM `{$journals_table}` WHERE reference_type='debit_note' AND reference_id={$debit_note_id} LIMIT 1");
                
                if ($journal_check && $journal_check->num_rows > 0) {
                    $success_message .= " Note: Journal entry already exists.";
                } else {
                    // Initialize Journal Manager
                    require_once('../classes/AccountingJournalManager.php');
                    $journal = new AccountingJournalManager($mysqli);
                    
                    // Get debit note data
                    $debit_note_no = getTableAttr('debit_note_no', tbl_debit_notes, $debit_note_id);
                    $vendor_id = getTableAttr('vendor_id', tbl_debit_notes, $debit_note_id);
                    $grand_subtotal = getTableAttr('grand_subtotal', tbl_debit_notes, $debit_note_id);
                    $grand_tax = getTableAttr('grand_tax', tbl_debit_notes, $debit_note_id);
                    $grand_total = getTableAttr('grand_total', tbl_debit_notes, $debit_note_id);
                    
                    // Get account mappings from config
                    require_once('../config/accounting.php');
                    
                    // Accounts for Debit Note
                    // DR: Accounts Payable (decrease what we owe vendor)
                    // CR: Purchase Returns/COGS (decrease expense/returns inventory value)
                    
                    $ap_account = DEBIT_NOTE_AP;
                    $purchase_returns_account = DEBIT_NOTE_PURCHASE_RETURNS;
                    
                    // Verify accounts exist
                    $ap_exists = $mysqli->query("SELECT account_code FROM `" . tbl_accounts . "` WHERE account_code = '{$ap_account}' LIMIT 1");
                    $returns_exists = $mysqli->query("SELECT account_code FROM `" . tbl_accounts . "` WHERE account_code = '{$purchase_returns_account}' LIMIT 1");
                    
                    if ($ap_exists && $ap_exists->num_rows > 0 && $returns_exists && $returns_exists->num_rows > 0) {
                        
                        // Build journal entries
                        $journal_entries = array(
                            array(
                                'account' => $ap_account,
                                'amount'  => $grand_total,
                                'type'    => 'debit'
                            ),
                            array(
                                'account' => $purchase_returns_account,
                                'amount'  => $grand_total,
                                'type'    => 'credit'
                            )
                        );
                        
                        // Create journal entry
                        $journal_result = $journal->createJournalEntry(
                            array(
                                'reference_type'   => 'debit_note',
                                'reference_id'     => $debit_note_id,
                                'reference_no'     => $debit_note_no,
                                'journal_date'     => date('Y-m-d'),
                                'description'      => 'Debit Note #' . $debit_note_no . ' - Vendor ID: ' . $vendor_id,
                                'currency'         => 'AED',
                                'grand_subtotal'   => $grand_subtotal,
                                'grand_total'      => $grand_total,
                                'reporting_method' => 'accrual'
                            ),
                            $journal_entries
                        );
                        
                        if ($journal_result['success']) {
                            error_log("Debit Note journal entry created: ID {$journal_result['journal_id']} for Debit Note {$debit_note_id}");
                            $success_message .= " Journal entry created successfully.";
                        } else {
                            error_log("Failed to create journal for Debit Note {$debit_note_id}: " . $journal_result['message']);
                            $success_message .= " Warning: " . $journal_result['message'];
                        }
                    } else {
                        $missing = array();
                        if (!$ap_exists || $ap_exists->num_rows == 0) $missing[] = "AP ({$ap_account})";
                        if (!$returns_exists || $returns_exists->num_rows == 0) $missing[] = "Purchase Returns ({$purchase_returns_account})";
                        $error_msg = "Missing accounts: " . implode(', ', $missing);
                        error_log("Debit Note {$debit_note_id}: {$error_msg}");
                        $success_message .= " Warning: {$error_msg}";
                    }
                }
            } catch (Exception $e) {
                error_log("Journal creation exception for Debit Note {$debit_note_id}: " . $e->getMessage());
                $success_message .= " Note: Journal entry not created (" . $e->getMessage() . ")";
            }
        }


        if ($debit_note_status == 'not_confirmed') {


            // Delete PDF - Next Time System will Generate New with Confirmed Status 
            $pdf        = getTableAttr('pdf', tbl_debit_notes, $debit_note_id);
            unlink("../pdfs_debit_notes/" . $pdf . ".pdf");
            $mysqli->query("UPDATE " . tbl_debit_notes . "  SET pdf = '' WHERE id=$debit_note_id");
        } else if ($debit_note_status == 'confirmed') {


            // Delete PDF - Next Time System will Generate New with Confirmed Status 
            // $pdf        = getTableAttr('pdf', tbl_debit_notes, $debit_note_id);
            // unlink("../pdfs_debit_notes/" . $pdf . ".pdf");
            // $mysqli->query("UPDATE " . tbl_debit_notes . "  SET pdf = '' WHERE id=$debit_note_id");


            // -----------------------------------------------------------------------------------------------------------------------------------------------
            // DELETE TRIPS - IF Debit Note STATUS IS CANCELLED
            // -----------------------------------------------------------------------------------------------------------------------------------------------
        } else if ($debit_note_status == 'cancelled' || $debit_note_status == 'on_hold') {

            // Delete PDF - Next Time System will Generate New 
            // $pdf        = getTableAttr('pdf', tbl_debit_notes, $debit_note_id);
            // unlink("../pdfs_debit_notes/" . $pdf . ".pdf");
            // $mysqli->query("UPDATE " . tbl_debit_notes . "  SET pdf = '' WHERE id=$debit_note_id");


            /* ---------------------- NOTIFICATIONS QUERY ---------------------- */
        }


        // --------------------------------------------------------------------------------
        header("Location:debit_note_overview.php?debit_note_id=$debit_note_id&success_message=$success_message");
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



if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows            = e_s__($_POST['total_rows']);
    // if ($total_rows == 0 || $total_rows == '') $total_rows = 1;
} else {
    $total_rows            = 1;
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
    <?php include('admin_elements/sidebar_debit_note.php'); ?>
    <!-- /sidebar content -->

</div>

<div class="content-wrapper">

    <!-- Inner content -->
    <div class="content-inner">

        <!-- Page header -->
        <?php include('admin_elements/page_header_debit_note.php'); ?>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <?php
            /*
                |--------------------------------------------------------------------------
                | EDIT
                |--------------------------------------------------------------------------
                |
                */
            if (!empty($debit_note_id)) {

                $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$debit_note_id");
                $row = $result->fetch_array();

                $vendor_id              = s__($row['vendor_id']);
                $debit_note_no          = s__($row['debit_note_no']);
                $debit_note_status      = s__($row['debit_note_status']);
                $debit_note_date        = s__($row['debit_note_date']);
                $reference_no           = s__($row['reference_no']);

                $vendor_notes           = s__($row['vendor_notes']);
                $terms_and_conditions   = s__($row['terms_and_conditions']);
                // Seprate Line Number on base of Space new line
                $final_terms_and_conditions = '';

                if (!empty($terms_and_conditions)) {
                    $desc = explode("\r", $terms_and_conditions);
                    $d_counter = 1;
                    if (count($desc) > 0) {
                        foreach ($desc as $d) {
                            if (!empty($d)) {
                                // $final_terms_and_conditions .= $d_counter++ . '. ' . $d . '<br />';
                                $final_terms_and_conditions .= $d . '<br />';
                            }
                        }
                    }
                }



                $grand_subtotal             = s__($row['grand_subtotal']);
                $grand_discount_type        = s__($row['grand_discount_type']);
                $grand_discount_type_value  = s__($row['grand_discount_type_value']);
                $grand_discount_amount      = s__($row['grand_discount_amount']);
                $grand_after_discount       = s__($row['grand_after_discount']);
                $grand_tax                  = s__($row['grand_tax']);
                $grand_total                = s__($row['grand_total']);

                $publish                = s__($row['is_active']);



                // --- Vendor Information
                $rs = $mysqli->query("SELECT * FROM `" . tbl_vendors . "` WHERE id=$vendor_id");
                $row_vendor = $rs->fetch_array();
                $display_name           = s__($row_vendor['display_name']);
                $company_name           = s__($row_vendor['company_name']);
                $email                  = s__($row_vendor['email']);
                $phone                  = s__($row_vendor['phone']);
                $mobile                 = s__($row_vendor['mobile']);
                $trn                    = s__($row_vendor['trn']);

                // Vendor Billing Address 
                $rs_billing     = $mysqli->query("SELECT * FROM `" . tbl_vendor_addresses . "` WHERE vendor_id=$vendor_id AND type='billing' ");
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


                $debit_note_date         = processDateYtoD($debit_note_date);


                // Initialize all arrays to avoid the "null given" error
                $debit_note_item_id_arr = [];
                $service_arr             = [];
                $description_arr         = [];
                $qty_arr                 = [];
                $rate_arr                = [];
                $sub_total_arr           = [];
                $tax_arr                 = [];
                $tax_amount_arr          = [];
                $total_arr               = [];

                // ------------------ TOTAL ITEMS ------------------
                $result_debit_note_items       = $mysqli->query("SELECT * FROM `" . tbl_debit_note_items . "` WHERE debit_note_id=$debit_note_id ORDER BY id");
                $total_rows                     = $result_debit_note_items->num_rows;


                if ($total_rows > 0) {
                    while ($row_debit_note_items = $result_debit_note_items->fetch_array()) {

                        array_push($debit_note_item_id_arr,    $row_debit_note_items['id']);
                        array_push($service_arr,                $row_debit_note_items['service']);
                        array_push($description_arr,            $row_debit_note_items['description']);
                        array_push($qty_arr,                    $row_debit_note_items['qty']);
                        array_push($rate_arr,                   $row_debit_note_items['rate']);
                        array_push($sub_total_arr,              $row_debit_note_items['sub_total']);
                        array_push($tax_arr,                    $row_debit_note_items['tax']);
                        array_push($tax_amount_arr,             $row_debit_note_items['tax_amount']);
                        array_push($total_arr,                  $row_debit_note_items['total']);
                    }
                }
            }

            if ($total_rows == 0)           $total_rows = 1;

            ?>


            <div class="row">

                <div class="row p-lg-2">

                    <div class="col-lg-1">
                    </div>


                    <div class="card col-lg-10">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-4">

                                        <span class="text-muted">Debit Note To:</span>
                                        <ul class="list list-unstyled mb-0">
                                            <li>
                                                <h5 class="my-2"><?php echo $display_name; ?></h5>
                                            </li>
                                            <li><span class="fw-semibold"><?php echo $company_name; ?></span></li>
                                            <li><?php echo $billing_attention; ?></li>
                                            <li><?php echo $billing_country; ?></li>
                                            <li><?php echo $billing_address_line1; ?></li>
                                            <li><?php echo $billing_address_line2; ?></li>
                                            <li><?php echo $billing_city; ?></li>
                                            <li><?php echo $billing_state; ?></li>
                                            <li><?php echo $billing_zipcode; ?></li>
                                            <li><?php echo $billing_phone; ?></li>
                                            <li><?php echo $billing_fax; ?></li>
                                        </ul>

                                    </div>
                                </div>

                                <?php
                                $warehouse_information = '';
                                $rs_warehouse   = $mysqli->query("SELECT * FROM `" . tbl_warehouses . "` WHERE id=1");
                                $row_warehouse  = $rs_warehouse->fetch_array();

                                $warehouse_no       = s__($row_warehouse['warehouse_no']);
                                $warehouse_name     = s__($row_warehouse['warehouse_name']);
                                $street1            = s__($row_warehouse['street1']);
                                $street2            = s__($row_warehouse['street2']);

                                $country            = s__($row_warehouse['country']);
                                $country            = getTableAttr('country_name', tbl_geo_countries, $country);

                                $state              = s__($row_warehouse['state']);
                                $state            = getTableAttr('state_name', tbl_geo_states, $state);

                                $phone              = s__($row_warehouse['phone']);
                                $email              = s__($row_warehouse['email']);
                                $trn                = s__($row_warehouse['trn']);

                                $warehouse_information .= (!empty($warehouse_name) ? '<strong>' . $warehouse_name . '</strong><br />' : '');
                                $warehouse_information .= (!empty($warehouse_no) ? $warehouse_no . '<br />' : '');
                                $warehouse_information .= (!empty($street1) ? $street1 . '<br />' : '');
                                $warehouse_information .= (!empty($street2) ? $street2 . '<br />' : '');
                                $warehouse_information .= (!empty($state) ? $state . ', ' : '');
                                $warehouse_information .= (!empty($country) ? $country . '<br />' : '');
                                $warehouse_information .= (!empty($phone) ? $phone . '<br />' : '');
                                $warehouse_information .= (!empty($email) ? $email . '<br />' : '');
                                $warehouse_information .= (!empty($trn) ? $trn : '');
                                ?>
                                <div class="col-sm-6">
                                    <div class="text-sm-end mb-4">
                                        <?php echo $warehouse_information; ?>
                                        <h6 class="text-primary mb-2 mt-lg-2">Debit Note #<?php echo $debit_note_no; ?></h6>
                                        <ul class="list list-unstyled mb-0">
                                            <li>Date: <span class="fw-semibold"><?php echo $debit_note_date; ?></span></li>
                                        </ul>
                                    </div>
                                </div>
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
                                    <?php
                                    /*
                                    |------------------------------------------------------ Debit Note ITEMS  ----------------------------------------------------------|
                                    */
                                    // echo $total_rows;

                                    for ($debit_note_item = 1; $debit_note_item <= $total_rows; $debit_note_item++) {
                                        $index = $debit_note_item;
                                        $index = $index - 1;
                                        $debit_note_item_id                = $debit_note_item_id_arr[$index];
                                        //--------------------------------------------------------------------------------------------------------------------------------|
                                    ?>

                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo getTableAttr('item_name', tbl_items, $service_arr[$index]); ?></div>
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
                                        <li class="mb-3">Vendor Notes: <br /><?php echo $vendor_notes; ?></li>
                                        <li class="mb-3"><span class="fw-semibold">Terms and Conditions: </span> <br /><?php echo $final_terms_and_conditions; ?></li>
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
                                            Send debit note
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


                <?php
                // ---------------------------------------------------------------------------------------------------------------------------------------
                $journal_id = getTableAttrV('id', tbl_journals, " reference_type='debit_note' AND reference_id='$debit_note_id' ");
                // ---------------------------------------------------------------------------------------------------------------------------------------

                if (!empty($journal_id)) {
                ?>

                    <p class="mb-0 opacity-50" id="journal">JOURNAL</p>
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <!-- <p class="mb-0 fw-semibold"></p> -->

                            <div class="ms-auto small text-muted">
                                Amount is displayed in your base currency <span class="badge bg-success"><?php echo BASE_CURRENCY['code']; ?></span>
                            </div>
                        </div>

                        <!-- <div class="card-header">
                        <h5 class="mb-0">Basic table</h5>
                    </div> -->

                        <!-- <div class="card-body">
                        Example of a <code>basic</code> table. For basic styling (light padding and only horizontal dividers) add the base class <code>.table</code> to any <code>&lt;table&gt;</code>. It may seem super redundant, but given the widespread use of tables for other plugins like calendars and date pickers, we've opted to isolate our custom table styles.
                    </div> -->

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="opacity-50">ACCOUNT</th>
                                        <th class="text-end opacity-50">DEBIT</th>
                                        <th class="text-end opacity-50">CREDIT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_debit = 0;
                                    $total_credit = 0;

                                    //-------------------------------------------------------------------
                                    // -------- JOURNAL ENTRIES 
                                    //-------------------------------------------------------------------

                                    $result_journal_items     = $mysqli->query("SELECT * FROM `" . tbl_journal_items . "` WHERE journal_id=$journal_id");
                                    while ($row_journal_items = $result_journal_items->fetch_array()) {

                                        $account    = $row_journal_items['account'];
                                        $account    = getTableAttr('account_name', tbl_accounts, $account);
                                        $debit      = $row_journal_items['debit'];
                                        $credit     = $row_journal_items['credit'];

                                        $total_debit += $debit;
                                        $total_credit += $credit;
                                    ?>
                                        <tr>
                                            <td><?php echo $account; ?></td>
                                            <td class="text-end"><?php echo $debit; ?>.00</td>
                                            <td class="text-end"><?php echo $credit; ?>.00</td>
                                        </tr>
                                    <?php } ?>
                                    <tr>
                                        <td></td>
                                        <td class="text-end fw-semibold"><?php echo $total_debit; ?>.00</td>
                                        <td class="text-end fw-semibold"><?php echo $total_credit; ?>.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php }  // JOURNAL 
                ?>

            </div>

        </div>


    </div>
    <!-- /content area -->

    <?php include('admin_elements/copyright.php'); ?>
</div>
</div>



<?php include('admin_elements/admin_footer.php'); ?>

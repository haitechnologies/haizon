<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

// =========================================================================
// ACCOUNTING JOURNAL MANAGER INTEGRATION
// =========================================================================
// Removed legacy require for autoloader compatibility: require_once(__DIR__ . '/../classes/AccountingJournalManager.php');
require_once(__DIR__ . '/../config/accounting.php');

$module             = 'payments_received';
$module_caption     = 'Payment Received';
$tbl_name = DB::PAYMENTS_RECEIVED;
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


// $customer_id = 0;

// if (isset($_REQUEST['customer_id']) && !empty($_REQUEST['customer_id'])) {
//     $customer_id     = e_s__($_REQUEST['customer_id']);
// }

$customer_id = e_s__($_REQUEST['customer_id'] ?? 0);


// Single Invoice - Record Payment
$post_invoice_id = 0;

if (isset($_REQUEST['post_invoice_id']) && !empty($_REQUEST['post_invoice_id'])) {
    $post_invoice_id     = e_s__($_REQUEST['post_invoice_id']);
}


/* -------------------------------------------------------------------------- */

// $invoice_id = '';
// if (isset($_REQUEST['invoice_id']))        $invoice_id     = e_s__($_REQUEST['invoice_id']);
// if (isset($_POST['invoice_id']))           $invoice_id     = e_s__($_POST['invoice_id']);


$invoice_no     = getTableAttr('invoice_no', DB::INVOICES, $post_invoice_id);
$invoice_status = getTableAttr('invoice_status', DB::INVOICES, $post_invoice_id);

if (!empty($post_invoice_id)) {
    $customer_id = getTableAttr('customer_id', DB::INVOICES, $post_invoice_id);
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


// ---------------------- Items -----------------------------
$item_id_arr                = array();
$amount_received_on_arr     = array();
$amount_received_arr        = array();


// Count items from POST arrays instead of relying on total_rows hidden field
$total_rows = 0;
if ($action == "update_$module" || $action == "add_$module") {
    if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
        $total_rows = count($_POST['item_id']);
    }
}



if ($action == "update_$module" || $action == "add_$module") {

    for ($payment_item = 1; $payment_item <= $total_rows; $payment_item++) {

        $index = $payment_item;
        $index = $index - 1;

        $post_item_id               = (isset($_POST['item_id'][$index]) && !empty($_POST['item_id'][$index]) ? $_POST['item_id'][$index] :  0);
        $post_amount_received_on    = (isset($_POST['amount_received_on'][$index]) && !empty($_POST['amount_received_on'][$index]) ? $_POST['amount_received_on'][$index] :  '');
        $post_amount_received       = (isset($_POST['amount_received'][$index]) && !empty($_POST['amount_received'][$index]) ? $_POST['amount_received'][$index] :  0);


        array_push($item_id_arr,                e_s__($post_item_id));
        array_push($amount_received_on_arr,     e_s__($post_amount_received_on));
        array_push($amount_received_arr,        e_s__($post_amount_received));
    } //for 
}


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $payment_status             = e_s__($_POST['payment_status']);
    $total_amount_received      = e_s__($_POST['total_amount_received']);
    $bank_charges               = e_s__($_POST['bank_charges']);
    $payment_date               = e_s__($_POST['payment_date']);
    $payment_method             = e_s__($_POST['payment_method']);
    $deposit_to                 = e_s__($_POST['deposit_to']);
    $reference_no               = e_s__($_POST['reference_no']);
} else {
    $payment_status             = '';
    $total_amount_received      = '';
    $bank_charges               = '';
    $payment_date               = date('d-m-Y', time());
    $payment_method             = '';
    $deposit_to                 = '';
    $reference_no               = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {

    if (empty($customer_id)) {
        $error_message = 'Please select Customer.';
    } else if (empty($payment_date)) {
        $error_message = 'Please select Payment Date.';
    } else if (empty($deposit_to) || $deposit_to == 'Please select') {
        $error_message = 'Please select Deposit To.';
    } else {

        $payment_date   = processDateDtoY($payment_date);
        if ($bank_charges == '')        $bank_charges = '0.00';
        if ((float)$total_amount_received > 0) {
            $payment_status = 'paid';
        } else if (empty($payment_status)) {
            $payment_status = 'draft';
        }

        // ---------------------------------------------
        // UPDATE
        // ---------------------------------------------

        $update_row = $mysqli->query("
                                        UPDATE `$tbl_name` SET
                                            customer_id                 = '" . $customer_id . "',
                                            payment_status              = '" . $payment_status . "',
                                            total_amount_received		= '" . $total_amount_received . "',
                                            bank_charges		        = '" . $bank_charges . "',
                                            payment_date		        = '" . $payment_date . "',
                                            payment_method		        = '" . $payment_method . "',
                                            deposit_to		            = '" . $deposit_to . "',
                                            reference_no		        = '" . $reference_no . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            $payment_id = $id;
            ///////////////////////////////////////////////////////////

            // -- PROCESS ITEMS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                for ($payment_item = 1; $payment_item <= $total_rows; $payment_item++) {

                    $index = $payment_item;
                    $index = $index - 1;

                    $item_id                        = e_s__($_POST['item_id'][$index]);
                    $item_amount_received_on        = e_s__($_POST['amount_received_on'][$index]);
                    $item_amount_received           = e_s__($_POST['amount_received'][$index]);


                    // ---------------------------------------------
                    // UPDATE ITEMS
                    // ---------------------------------------------

                    $item_amount_received         = (($item_amount_received == '') ? 0 : $item_amount_received);

                    // Process Updated Items
                    if (!empty($item_id) && !empty($item_amount_received)) {

                        $update_row = $mysqli->query("UPDATE `" . DB::table('payment_received_items') . "` SET 
                                                            invoice_id              = '" . $item_id . "',
                                                            amount_received_on      = '" . $item_amount_received_on . "',
                                                            amount_received         = '" . $item_amount_received . "' 
                                                        WHERE id=$item_id");

                        if ($update_row) $updated_row++;
                        fp__(DB::table('payment_received_items'), $item_id);

                        // Process New Items
                    } else if (empty($item_id) && !empty($item_amount_received)) {

                        $insert_row = $mysqli->query("INSERT INTO `" . DB::table('payment_received_items') . "`(payment_id, invoice_id, amount_received_on, amount_received) VALUES ('" . $payment_id . "', '" . $item_id . "', '" . $item_amount_received_on . "', '" . $item_amount_received . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__(DB::table('payment_received_items'), $mysqli->insert_id);

                        // Process Deleted Items
                    } else if (!empty($item_id) && empty($item_amount_received)) {

                        $mysqli->query("DELETE FROM `" . DB::table('payment_received_items') . "` WHERE id=$item_id");
                    }
                    // ---------------------------------------------

                } //for 

            }
            ///////////////////////////////////////////////////////////

            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $payment_date   = processDateYtoD($payment_date);
                $error_message = "No items added. Please add at least one item.";
            } else {
                // JOURNAL ENTRY (Payment Received) - AccountingJournalManager
                $journal_table = DB::JOURNALS;
                $journal_items_table = DB::JOURNAL_ITEMS;

                $existing_journal_id = getTableAttrV('id', $journal_table, " reference_type='payment' AND reference_id='$payment_id' ");

                if ($payment_status == 'paid' && (float)$total_amount_received > 0) {
                    // Remove old journal (if any) to recreate with updated values
                    if (!empty($existing_journal_id)) {
                        $mysqli->query("DELETE FROM `{$journal_items_table}` WHERE journal_id={$existing_journal_id}");
                        $mysqli->query("DELETE FROM `{$journal_table}` WHERE id={$existing_journal_id}");
                    }

                    // Lookup accounts
                    $accounts_table = DB::ACCOUNTS;
                    $ar_account = $mysqli->query("SELECT id FROM `{$accounts_table}` WHERE account_code IN ('1200', '1210', '1100') OR account_name LIKE '%Receivable%' LIMIT 1")->fetch_assoc();

                    if (!empty($deposit_to) && !empty($ar_account['id'])) {
                        $journal = new AccountingJournalManager($mysqli);
                        $customer_name = getTableAttr('display_name', DB::CUSTOMERS, $customer_id);

                        $journal_entries = array(
                            array(
                                'account' => (int)$deposit_to,
                                'amount'  => (float)$total_amount_received,
                                'type'    => 'debit'
                            ),
                            array(
                                'account' => (int)$ar_account['id'],
                                'amount'  => (float)$total_amount_received,
                                'type'    => 'credit'
                            )
                        );

                        $journal->createJournalEntry(
                            array(
                                'reference_type'   => 'payment',
                                'reference_id'     => $payment_id,
                                'reference_no'     => $reference_no,
                                'journal_date'     => $payment_date,
                                'description'      => 'Payment Received #' . $payment_id . ' - ' . $customer_name,
                                'currency'         => 'AED',
                                'grand_subtotal'   => $total_amount_received,
                                'grand_total'      => $total_amount_received,
                                'reporting_method' => 'cash'
                            ),
                            $journal_entries
                        );
                    }
                } else if (!empty($existing_journal_id)) {
                    $mysqli->query("DELETE FROM `{$journal_items_table}` WHERE journal_id={$existing_journal_id}");
                    $mysqli->query("DELETE FROM `{$journal_table}` WHERE id={$existing_journal_id}");
                }

                header("Location:listing_$module.php?success_message=$success_message");
            }
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
} else if ($action == "add_$module") {
  
    if (empty($customer_id)) {
        $error_message = 'Please select Customer.';
    } else if (empty($payment_date)) {
        $error_message = 'Please select Payment Date.';
    } else if (empty($deposit_to) || $deposit_to == 'Please select') {
        $error_message = 'Please select Deposit To.';
    } else {

        ///////////////////////////////////////////////////////////

        // DEBUG: Check what's being received
        // echo "<pre>DEBUG - total_rows: $total_rows</pre>";
        // echo "<pre>DEBUG - POST item_id: "; print_r($_POST['item_id']); echo "</pre>";
        // echo "<pre>DEBUG - POST amount_received_on: "; print_r($_POST['amount_received_on']); echo "</pre>";
        // echo "<pre>DEBUG - POST amount_received: "; print_r($_POST['amount_received']); echo "</pre>";
        // exit;

        // -- PROCESS ITEMS
        if ($total_rows > 0) {

            $inserted_row = 0;

            for ($payment_item = 1; $payment_item <= $total_rows; $payment_item++) {

                $index = $payment_item;
                $index = $index - 1;

                $item_id                        = e_s__($_POST['item_id'][$index]);
                $item_amount_received_on        = e_s__($_POST['amount_received_on'][$index]);
                $item_amount_received           = e_s__($_POST['amount_received'][$index]);


                if (!empty($item_id) && !empty($item_amount_received_on) && !empty($item_amount_received) && $item_amount_received > 0) {

                    // ---------------------------------------------
                    // SAVE
                    // ---------------------------------------------
                    if ($inserted_row == 0) {

                        $payment_date   = processDateDtoY($payment_date);
                        if ($bank_charges == '')        $bank_charges = '0.00';
                        if ((float)$total_amount_received > 0) {
                            $payment_status = 'paid';
                        } else if (empty($payment_status)) {
                            $payment_status = 'draft';
                        }

                        $mysqli->query("INSERT INTO `$tbl_name`(payment_status, customer_id, total_amount_received, bank_charges, payment_date, payment_method, deposit_to, reference_no) VALUES ('" . $payment_status . "', '" . $customer_id . "', '" . $total_amount_received . "', '" . $bank_charges . "', '" . $payment_date . "', '" . $payment_method . "', '" . $deposit_to . "', '" . $reference_no . "'); ");

                        //////////////////////////////////////////////////////////////
                        $id = $mysqli->insert_id;
                        fp__($tbl_name, $id);
                        $success_message = "The $module_caption has been saved successfully.";
                        $payment_id = $id;

                        // PROCESS -> JOURNAL ENTRY
                        // $mysqli->query("INSERT INTO `" . DB::JOURNALS . "` (journal_date, reference_type, reference_id) VALUES ('" . date('Y-m-d') . "', 'payment', '" . $payment_id . "'); ");
                        // $journal_id = $mysqli->insert_id;
                        // fp__(DB::JOURNALS, $journal_id);

                        //////////////////////////////////////////////////////////////
                    }

                    // SAVE ITEMS
                    $insert_row = $mysqli->query("INSERT INTO `" . DB::table('payment_received_items') . "`(payment_id, invoice_id, amount_received_on, amount_received) VALUES ('" . $payment_id . "', '" . $item_id . "', '" . $item_amount_received_on . "', '" . $item_amount_received . "'); ");

                    if ($insert_row) $inserted_row++;
                    fp__(DB::table('payment_received_items'), $mysqli->insert_id);
                    // -------------------------------------------------------

                    // ------------ DEBIT -------------> Specific payment Account (payment ↑)
                    // $mysqli->query("INSERT INTO `" . DB::JOURNAL_ITEMS . "` (journal_id, account, debit, credit) VALUES ('" . $journal_id . "', '" . $item_payment_account . "', '" . $item_amount_received . "', '0.00'); ");

                    // ------------ CREDIT -------------> Bank/Cash or Accounts Payable (Asset ↓ or Liability ↑)
                    // $mysqli->query("INSERT INTO `" . DB::JOURNAL_ITEMS . "` (journal_id, account, debit, credit) VALUES ('" . $journal_id . "', $paid_through, '0.00', '" . $grand_amount_received . "'); ");
                }
            } //for 


            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($inserted_row == 0) {
                $error_message = "No items added. Please add at least one item.";
            } else {
                // JOURNAL ENTRY (Payment Received) - AccountingJournalManager
                $journal_table = DB::JOURNALS;
                $journal_items_table = DB::JOURNAL_ITEMS;

                if ($payment_status == 'paid' && (float)$total_amount_received > 0) {
                    // Lookup accounts
                    $accounts_table = DB::ACCOUNTS;
                    $ar_account = $mysqli->query("SELECT id FROM `{$accounts_table}` WHERE account_code IN ('1200', '1210', '1100') OR account_name LIKE '%Receivable%' LIMIT 1")->fetch_assoc();

                    if (!empty($deposit_to) && !empty($ar_account['id'])) {
                        $journal = new AccountingJournalManager($mysqli);
                        $customer_name = getTableAttr('display_name', DB::CUSTOMERS, $customer_id);

                        $journal_entries = array(
                            array(
                                'account' => (int)$deposit_to,
                                'amount'  => (float)$total_amount_received,
                                'type'    => 'debit'
                            ),
                            array(
                                'account' => (int)$ar_account['id'],
                                'amount'  => (float)$total_amount_received,
                                'type'    => 'credit'
                            )
                        );

                        $journal->createJournalEntry(
                            array(
                                'reference_type'   => 'payment',
                                'reference_id'     => $payment_id,
                                'reference_no'     => $reference_no,
                                'journal_date'     => $payment_date,
                                'description'      => 'Payment Received #' . $payment_id . ' - ' . $customer_name,
                                'currency'         => 'AED',
                                'grand_subtotal'   => $total_amount_received,
                                'grand_total'      => $total_amount_received,
                                'reporting_method' => 'cash'
                            ),
                            $journal_entries
                        );
                    }
                }

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
$created_by = getTableAttr('created_by', DB::PAYMENTS_RECEIVED, $id);

if (
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1')
    ||
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['admin_id'] == $created_by)
) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $customer_id                = s__($row['customer_id']);
    $payment_status             = s__($row['payment_status']);
    $total_amount_received      = s__($row['total_amount_received']);
    $bank_charges               = s__($row['bank_charges']);
    $payment_date               = s__($row['payment_date']);
    $payment_method             = s__($row['payment_method']);
    $deposit_to                 = s__($row['deposit_to']);
    $reference_no               = s__($row['reference_no']);

    $payment_date       = processDateYtoD($payment_date);

    // ------------------ TOTAL ITEMS ------------------
    $result_payment_items       = $mysqli->query("SELECT * FROM `" . DB::table('payment_received_items') . "` WHERE payment_id=$id");
    $total_rows                 = $result_payment_items->num_rows;

    if ($total_rows > 0) {
        while ($row_payment_items = $result_payment_items->fetch_array()) {

            array_push($item_id_arr,                $row_payment_items['id']);
            array_push($amount_received_on_arr,     $row_payment_items['amount_received_on']);
            array_push($amount_received_arr,        $row_payment_items['amount_received']);
        }
    }
}

// if ($total_rows == 0) $total_rows = 0;


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
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1 d-flex align-items-center gap-2">
                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <button type="button" onclick="document.getElementById('payment_status').value='draft'; document.getElementById('frmpayments_received').submit();" class="btn btn-primary btn-sm">Save</button>
                <?php } ?>

                <?php if (!empty($id)) { ?>
                    <a href="payment_received_overview.php?payment_received_id=<?php echo $id; ?>" class="btn btn-light btn-sm">Cancel</a>
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
                <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $customer_id; ?>" />
                <input type="hidden" name="post_invoice_id" id="post_invoice_id" value="<?php echo $post_invoice_id; ?>" />
                <input type="hidden" name="payment_status" id="payment_status" value="<?php echo $payment_status; ?>" />
                <input type="hidden" name="save_and_send" id="save_and_send" value="" />

                <?php if (($action == "edit_payments_received" || $action == "update_payments_received") && !empty($id)) { ?>
                    <input type="hidden" name="action" id="action" value="update_payments_received" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php } else { ?>
                    <input type="hidden" name="action" id="action" value="add_payments_received" />
                <?php } ?>


                <div class="col-xl-12">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card">

                                <div class="card-body">

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Customer Name:*</span> </label>

                                        <div class="col-lg-9">
                                            <?php if (!empty($id) || !empty($post_invoice_id)) { ?>
                                                <input type="hidden" class="form-control" name="customer_id" id="customer_id" value="<?php echo $customer_id; ?>">
                                                <input type="text" readonly class="form-control bg-light" name="" id="" value="<?php echo getTableAttr('display_name', DB::CUSTOMERS, $customer_id); ?>">
                                            <?php } else { ?>

                                                <select name="customer_id" id="customer_id" class="form-control select" onchange="if(this.value > 0) { window.location.href='?mod=payments_received&customer_id=' + this.value; }">
                                                    <option value='0'>Please select</option>
                                                    <?php
                                                    // -------------------------------------------------------------------------------------------------
                                                    $customer_details = '';
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
                                            <?php } ?>
                                        </div>

                                    </div>

                                    <div class="row mb-2">
                                        <!-- <label class="col-lg-6 col-form-label fw-semibold">Grand Subtotal: (VAT Excluded)</label> -->
                                        <label class="col-lg-3 col-form-label fw-semibold">Total Amount Received:</label>
                                        <div class="col-lg-9">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control fw-semibold bg-light opacity-50" placeholder="0" name="total_amount_received" id="total_amount_received" value="<?php echo $total_amount_received; ?>" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Bank Charges (if any):</label>
                                        <div class="col-lg-9">
                                            <input type="number" class="form-control" name="bank_charges" id="bank_charges" value="<?php echo $bank_charges; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Payment Date:*</span></label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" name="payment_date" id="payment_date" value="<?php echo $payment_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-1">
                                        <label class="col-lg-3 col-form-label">Payment Mode: </label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="payment_method" id="payment_method">
                                                <!-- <option value='0'></option> -->
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result = $mysqli->query("SELECT * FROM `" . DB::PAYMENT_METHODS  . "` WHERE is_active=1 ORDER BY payment_method");
                                                while ($rows = $result->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $payment_method) { ?>selected <?php } else if ($rows['id'] == $payment_method) { ?>selected <?php } ?>>
                                                        <?php echo $rows['payment_method']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-1">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Deposit To:*</span></label>
                                        <div class="col-lg-9">
                                            <select required class="form-select" name="deposit_to" id="deposit_to">
                                                <option value="0" class="fw-semibold text-black" disabled></option>
                                                <?php 
                                                // Get all asset accounts and their sub-accounts
                                                $all_accounts = fetchAccountsDropdown($account_type = array(1), $prefix = '', $deposit_to);
                                                // Filter out Accounts Receivable (ID 124) and preserve hierarchy
                                                echo preg_replace('/<option[^>]*value="124"[^>]*>.*?<\/option>/i', '', $all_accounts);
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Reference#:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="Reference no" name="reference_no" id="reference_no" value="<?php echo $reference_no; ?>">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- <div class="col-lg-6">
                            <div class="card">

                                <div class="card-header d-flex align-items-center">
                                    <h6 class="mb-0">&nbsp;</h6>
                                </div>

                                <div class="card-body">

                                    

                                </div>
                            </div>
                        </div> -->


                    </div>
                </div>


                <div>

                    <div class="col-xl-12">

                        <div class="row mb-2">

                            <div class="col-lg-2">
                                <label class="form-label ms-3">DATE</label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label">INVOICE NUMBER</label>
                            </div>

                            <div class="col-lg-2 text-end">
                                <label class="form-label">INVOICE AMOUNT</label>
                            </div>

                            <div class="col-lg-2 text-end">
                                <label class="form-label">AMOUNT DUE</label>
                            </div>

                            <div class="col-lg-2 text-end">
                                <label class="form-label pe-4"><span class="text-danger">AMOUNT RECEIVED ON*</span></label>
                            </div>

                            <div class="col-lg-2 text-end">
                                <label class="form-label pe-4"><span class="text-danger">PAYMENT*</span></label>
                            </div>

                        </div>

                        <div class="card">

                            <div class="row card-body">

                                <div class="col-lg-12">

                                    <?php
                                    // =============================================================================
                                    // FETCH: UNPAID INVOICES
                                    // =============================================================================

                                    if (!empty($invoice_id)) {
                                        $customer_id = getTableAttr('customer_id', DB::INVOICES, $invoice_id);
                                    }

                                    // --- ADD THIS BLOCK HERE ---
                                    $unpaid_invoices = array();
                                    if ($customer_id > 0) {
                                        // Fetch invoices for this customer (optionally restricted to a single invoice)
                                        $sql_invoices = "SELECT id, invoice_no, invoice_date, grand_total, 
                                                        (grand_total - IFNULL((SELECT SUM(amount_received) FROM " . DB::table('payment_received_items') . " WHERE invoice_id = i.id), 0)) as balance_due 
                                                        FROM " . $tbl_prefix . "invoices i 
                                                        WHERE customer_id = $customer_id";

                                        if (!empty($post_invoice_id)) {
                                            $sql_invoices .= " AND id = $post_invoice_id";
                                        }

                                        $res_invoices   = $mysqli->query($sql_invoices);
                                        $total_rows     = $res_invoices->num_rows;

                                        while ($inv_row = $res_invoices->fetch_assoc()) {
                                            $unpaid_invoices[] = $inv_row;
                                        }
                                    }

                                    // =============================================================================
                                    // MODULE:
                                    // =============================================================================
                                    // ----------------------------------------------------------------------------
                                    // for ($payment_item = 1; $payment_item <= $total_rows; $payment_item++) {
                                    //     $index = $payment_item;
                                    //     $index = $index - 1;

                                    //     $item_id                = (!empty($item_id_arr[$index]) ? $item_id_arr[$index] : '');
                                    //     $amount_received_on     = (!empty($amount_received_on_arr[$index]) ? $amount_received_on_arr[$index] : '');
                                    //     $amount_received        = (!empty($amount_received_arr[$index]) ? $amount_received_arr[$index] : '');

                                    if (!empty($unpaid_invoices)) {
                                        $counter = 0;
                                        // foreach ($unpaid_invoices as $inv) {
                                        foreach ($unpaid_invoices as $index => $inv) {

                                            // If you need $payment_item to start at 1 (for display/labels):
                                            $payment_item = $index + 1;
                                            // $index = $payment_item;
                                            // $index = $index - 1;

                                            $counter++;
                                            // Check if we are in EDIT mode to populate existing values, 
                                            // otherwise, use the fresh invoice data
                                            $item_id        = $inv['id'];
                                            // $invoice_id     = $inv['invoice_id'];
                                            $invoice_date   = $inv['invoice_date'];
                                            $invoice_no     = $inv['invoice_no'];
                                            $grand_total    = $inv['grand_total'];
                                            $amount_due     = $inv['balance_due'];

                                            // ----------------------------------------------------------------------------
                                    ?>

                                            <div class="mb-2">
                                                <div class="row mb-3 pb-3" id="row_<?php echo $payment_item; ?>">

                                                    <div class="col-lg-12">
                                                        <div class="row">

                                                            <input type="hidden" name="item_id[]" id="item_id<?php echo $payment_item; ?>" value="<?php echo $item_id; ?>">

                                                            <!-- 18 Sep 2025
                                                            Due Date: 18 Sep 2025 -->

                                                            <?php
                                                            // Calculate Overdue
                                                            $payment_term           = getTableAttr('payment_term', DB::CUSTOMERS, $customer_id);
                                                            $payment_term_duration  = getTableAttr('payment_term', DB::PAYMENT_TERMS, $payment_term);
                                                            // $display_due_days       = getInvoiceDueDay($invoice_status, $invoice_date, $payment_term_duration);
                                                            // echo $display_due_days;
                                                            ?>

                                                            <?php $display_due_date = calculateInvoiceDueDate($invoice_status, $invoice_date, $payment_term_duration); ?>


                                                            <div class="col-lg-2">
                                                                <?php echo dd_($invoice_date); ?>
                                                                <div class="small text-muted">Due Date: <?php echo dd_($display_due_date); ?></div>
                                                            </div>

                                                            <div class="col-lg-2">
                                                                <a href="invoice_overview.php?invoice_id=<?php echo $item_id; ?>" target="_blank"><?php echo $invoice_no; ?></a>
                                                            </div>

                                                            <div class="col-lg-2 text-end">
                                                                <?php echo $amount_due; ?>
                                                            </div>

                                                            <div class="col-lg-2 text-end">
                                                                <?php echo $grand_total; ?>
                                                            </div>

                                                            <div class="col-lg-2 text-end">
                                                                <input type="date" name="amount_received_on[]" id="amount_received_on<?php echo $payment_item; ?>" class="form-control text-end" value="<?php echo (!empty($amount_received_on_arr[$index]) ? $amount_received_on_arr[$index] : ''); ?>" onchange="calculateGrand(<?php echo $payment_item; ?>);" onkeyup="calculateGrand(<?php echo $payment_item; ?>);">
                                                            </div>


                                                            <div class="col-lg-2 text-end">
                                                                <?php $default_amount_received = (!empty($amount_received_arr[$index]) ? $amount_received_arr[$index] : (!empty($post_invoice_id) ? $amount_due : '')); ?>
                                                                <input type="number" name="amount_received[]" id="amount_received<?php echo $payment_item; ?>" min="0" value="<?php echo $default_amount_received; ?>" class="form-control text-end" onchange="calculateGrand(<?php echo $payment_item; ?>);" onkeyup="calculateGrand(<?php echo $payment_item; ?>);">
                                                            </div>

                                                        </div>
                                                    </div>


                                                </div>

                                            </div>

                                    <?php
                                            // =============================================================================
                                        } // foreach 
                                        // =============================================================================
                                    } // if
                                    ?>

                                </div>

                                <script>
                                    function percentage(num, percentage) {
                                        const result = num * (percentage / 100);
                                        return parseFloat(result.toFixed(3));
                                    }
                                    // const percntVal = percentage(1, 5);
                                    // console.log(percntVal);


                                    // -------------------------------------------------------------------------
                                    //  CALCULATE AMOUNT + TAX
                                    // -------------------------------------------------------------------------
                                    // function calculateItemAmount(row_no) {

                                    //     // console.log(row_no);

                                    //     let payment_account = document.getElementById('payment_account' + row_no);
                                    //     let payment_account_value = payment_account.options[payment_account.selectedIndex].value;
                                    //     // let payment_account_text = payment_account.options[payment_account.selectedIndex].text;
                                    //     // console.log("payment_account " + row_no + " text:", payment_account_text);

                                    //     if (payment_account_value != NaN && payment_account_value != '' && payment_account_value != 'undefined' && payment_account_value != '0') {

                                    //         var total = document.getElementById('total' + row_no).value;

                                    //         // --- Calculate Total
                                    //         document.getElementById('total' + row_no).value = parseFloat(total).toFixed(2);

                                    //         calculateGrand();

                                    //     } // if


                                    // } // function




                                    // -------------------------------------------------------------------------
                                    //  GRAND CALCULATIONS
                                    // -------------------------------------------------------------------------
                                    function calculateGrand() {

                                        // ------ GRAND CALCULATIONS
                                        var total_rows = document.getElementById('total_rows').value;

                                        // --- Grand Subttotal
                                        var final_total = 0;
                                        for (var i = 1; i <= total_rows; i++) {
                                            var total = document.getElementById('amount_received' + i).value;
                                            final_total += Number(total);
                                        } // for


                                        // document.getElementById('grand_total').value = parseFloat(grand_total.toFixed(2));
                                        document.getElementById('grand_total').value = parseFloat(final_total.toFixed(2));
                                        document.getElementById('total_amount_received').value = parseFloat(final_total.toFixed(2));

                                    }
                                </script>

                                <input type="hidden" name="total_rows" id="total_rows" value="<?php echo $total_rows; ?>">


                            </div>
                        </div>
                    </div>


                    <div class="row">

                        <div class="col-lg-8">
                            <span class="text-muted">**List contains only PAID invoices</span>
                        </div>

                        <div class="col-lg-4">
                            <div class="card ">

                                <div class="card-body"> <!--  bg-info bg-opacity-10 -->

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Total</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" name="grand_total" id="grand_total" value="<?php echo $total_amount_received; ?>" readonly>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>



            </div>
        </form>
    </div>

    </div>
</div>
<?php include('admin_elements/copyright.php'); ?>
</div>
</div>


<?php include('admin_elements/admin_footer.php'); ?>
<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

// =========================================================================
// ACCOUNTING JOURNAL MANAGER INTEGRATION
// =========================================================================
// Removed legacy require for autoloader compatibility: require_once(__DIR__ . '/../classes/AccountingJournalManager.php');
require_once(__DIR__ . '/../config/accounting.php');

$module             = 'payments_made';
$module_caption     = 'Payment Made';
$tbl_name = DB::PAYMENTS_MADE;
$error_message      = '';
$success_message    = '';


/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();


$vendor_id = e_s__($_REQUEST['vendor_id'] ?? 0);


// Single Purchase - Record Payment
$post_purchase_id = 0;

if (isset($_REQUEST['purchase_id']) && !empty($_REQUEST['purchase_id'])) {
    $post_purchase_id = e_s__($_REQUEST['purchase_id']);
}


/* -------------------------------------------------------------------------- */

$purchase_no     = getTableAttr('purchase_no', DB::PURCHASES, $post_purchase_id);
$purchase_status = getTableAttr('purchase_status', DB::PURCHASES, $post_purchase_id);

if (!empty($post_purchase_id)) {
    $vendor_id = getTableAttr('vendor_id', DB::PURCHASES, $post_purchase_id);
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


// ---------------------- Items (Purchases/Line Items) -------------------
$item_id_arr                = array();
$amount_paid_on_arr         = array();
$amount_paid_arr            = array();


// Count items from POST arrays
$total_rows = 0;
if ($action == "update_$module" || $action == "add_$module") {
    if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
        $total_rows = count($_POST['item_id']);
    }
}



if ($action == "update_$module" || $action == "add_$module") {

    for ($payment_item = 1; $payment_item <= $total_rows; $payment_item++) {

        $index = $payment_item - 1;

        $post_item_id               = (isset($_POST['item_id'][$index]) && !empty($_POST['item_id'][$index]) ? $_POST['item_id'][$index] :  0);
        $post_amount_paid_on        = (isset($_POST['amount_paid_on'][$index]) && !empty($_POST['amount_paid_on'][$index]) ? $_POST['amount_paid_on'][$index] :  '');
        $post_amount_paid           = (isset($_POST['amount_paid'][$index]) && !empty($_POST['amount_paid'][$index]) ? $_POST['amount_paid'][$index] :  0);


        array_push($item_id_arr,                e_s__($post_item_id));
        array_push($amount_paid_on_arr,         e_s__($post_amount_paid_on));
        array_push($amount_paid_arr,            e_s__($post_amount_paid));
    } 
}


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $payment_status             = e_s__($_POST['payment_status']);
    $total_amount_paid          = e_s__($_POST['total_amount_paid']);
    $bank_charges               = e_s__($_POST['bank_charges']);
    $payment_date               = e_s__($_POST['payment_date']);
    $payment_method             = e_s__($_POST['payment_method']);
    $paid_from                  = e_s__($_POST['paid_from']);
    $reference_no               = e_s__($_POST['reference_no']);
} else {
    $payment_status             = '';
    $total_amount_paid          = '';
    $bank_charges               = '';
    $payment_date               = date('d-m-Y', time());
    $payment_method             = '';
    $paid_from                  = '';
    $reference_no               = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {

    if (empty($vendor_id)) {
        $error_message = 'Please select Vendor.';
    } else if (empty($payment_date)) {
        $error_message = 'Please select Payment Date.';
    } else if (empty($paid_from) || $paid_from == 'Please select') {
        $error_message = 'Please select Paid From.';
    } else {

        $payment_date   = processDateDtoY($payment_date);
        if ($bank_charges == '')        $bank_charges = '0.00';
        if ((float)$total_amount_paid > 0) {
            $payment_status = 'paid';
        } else if (empty($payment_status)) {
            $payment_status = 'draft';
        }

        // UPDATE
        $tbl_payment_made = defined('DB::PAYMENTS_MADE') ? DB::PAYMENTS_MADE : $tbl_name;
        $update_row = $mysqli->query("
                                        UPDATE `$tbl_payment_made` SET
                                            vendor_id                   = '" . $vendor_id . "',
                                            payment_status              = '" . $payment_status . "',
                                            total_amount_paid		    = '" . $total_amount_paid . "',
                                            bank_charges		        = '" . $bank_charges . "',
                                            payment_date		        = '" . $payment_date . "',
                                            payment_method		        = '" . $payment_method . "',
                                            paid_from		            = '" . $paid_from . "',
                                            reference_no		        = '" . $reference_no . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_payment_made, $id);
            $payment_id = $id;
            
            // PROCESS ITEMS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                $tbl_payment_made_items = DB::table('payment_made_items');

                for ($payment_item = 1; $payment_item <= $total_rows; $payment_item++) {

                    $index = $payment_item - 1;

                    $item_id                        = e_s__($_POST['item_id'][$index]);
                    $item_amount_paid_on            = e_s__($_POST['amount_paid_on'][$index]);
                    $item_amount_paid               = e_s__($_POST['amount_paid'][$index]);


                    $item_amount_paid               = (($item_amount_paid == '') ? 0 : $item_amount_paid);

                    // Update Items
                    if (!empty($item_id) && !empty($item_amount_paid)) {

                        $update_row = $mysqli->query("UPDATE `$tbl_payment_made_items` SET 
                                                            purchase_id             = '" . $item_id . "',
                                                            amount_paid_on          = '" . $item_amount_paid_on . "',
                                                            amount_paid             = '" . $item_amount_paid . "' 
                                                        WHERE id=$item_id");

                        if ($update_row) $updated_row++;
                        fp__($tbl_payment_made_items, $item_id);

                        // New Items
                    } else if (empty($item_id) && !empty($item_amount_paid)) {

                        $insert_row = $mysqli->query("INSERT INTO `$tbl_payment_made_items`(payment_id, purchase_id, amount_paid_on, amount_paid) VALUES ('" . $payment_id . "', '" . $item_id . "', '" . $item_amount_paid_on . "', '" . $item_amount_paid . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__($tbl_payment_made_items, $mysqli->insert_id);

                        // Deleted Items
                    } else if (!empty($item_id) && empty($item_amount_paid)) {

                        $mysqli->query("DELETE FROM `$tbl_payment_made_items` WHERE id=$item_id");
                    }

                } 

            }

            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $payment_date   = processDateYtoD($payment_date);
                $error_message = "No items added. Please add at least one item.";
            } else {
                // JOURNAL ENTRY (Payment Made) - AccountingJournalManager
                $journal_table = DB::JOURNALS;
                $journal_items_table = DB::JOURNAL_ITEMS;

                $existing_journal_id = getTableAttrV('id', $journal_table, " reference_type='payment_made' AND reference_id='$payment_id' ");

                if ($payment_status == 'paid' && (float)$total_amount_paid > 0) {
                    // Remove old journal (if any)
                    if (!empty($existing_journal_id)) {
                        $mysqli->query("DELETE FROM `{$journal_items_table}` WHERE journal_id={$existing_journal_id}");
                        $mysqli->query("DELETE FROM `{$journal_table}` WHERE id={$existing_journal_id}");
                    }

                    // Lookup Accounts Payable account
                    $accounts_table = DB::ACCOUNTS;
                    $ap_account = $mysqli->query("SELECT id FROM `{$accounts_table}` WHERE account_code IN ('2100', '2110', '2000') OR account_name LIKE '%Payable%' LIMIT 1")->fetch_assoc();

                    if (!empty($paid_from) && !empty($ap_account['id'])) {
                        $journal = new AccountingJournalManager($mysqli);
                        $vendor_name = getTableAttr('display_name', DB::VENDORS, $vendor_id);

                        $journal_entries = array(
                            array(
                                'account' => (int)$ap_account['id'],
                                'amount'  => (float)$total_amount_paid,
                                'type'    => 'debit'
                            ),
                            array(
                                'account' => (int)$paid_from,
                                'amount'  => (float)$total_amount_paid,
                                'type'    => 'credit'
                            )
                        );

                        $journal->createJournalEntry(
                            array(
                                'reference_type'   => 'payment_made',
                                'reference_id'     => $payment_id,
                                'reference_no'     => $reference_no,
                                'journal_date'     => $payment_date,
                                'description'      => 'Payment Made #' . $payment_id . ' - ' . $vendor_name,
                                'currency'         => 'AED',
                                'grand_subtotal'   => $total_amount_paid,
                                'grand_total'      => $total_amount_paid,
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
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module") {

    if (empty($vendor_id)) {
        $error_message = 'Please select Vendor.';
    } else if (empty($payment_date)) {
        $error_message = 'Please select Payment Date.';
    } else if (empty($paid_from) || $paid_from == 'Please select') {
        $error_message = 'Please select Paid From.';
    } else {

        // PROCESS ITEMS
        if ($total_rows > 0) {

            $inserted_row = 0;

            for ($payment_item = 1; $payment_item <= $total_rows; $payment_item++) {

                $index = $payment_item - 1;

                $item_id                        = e_s__($_POST['item_id'][$index]);
                $item_amount_paid_on            = e_s__($_POST['amount_paid_on'][$index]);
                $item_amount_paid               = e_s__($_POST['amount_paid'][$index]);


                if (!empty($item_id) && !empty($item_amount_paid_on) && !empty($item_amount_paid) && $item_amount_paid > 0) {

                    // SAVE
                    if ($inserted_row == 0) {

                        $payment_date   = processDateDtoY($payment_date);
                        if ($bank_charges == '')        $bank_charges = '0.00';
                        if ((float)$total_amount_paid > 0) {
                            $payment_status = 'paid';
                        } else if (empty($payment_status)) {
                            $payment_status = 'draft';
                        }

                        $tbl_payment_made = defined('DB::PAYMENTS_MADE') ? DB::PAYMENTS_MADE : $tbl_name;
                        $mysqli->query("INSERT INTO `$tbl_payment_made`(payment_status, vendor_id, total_amount_paid, bank_charges, payment_date, payment_method, paid_from, reference_no) VALUES ('" . $payment_status . "', '" . $vendor_id . "', '" . $total_amount_paid . "', '" . $bank_charges . "', '" . $payment_date . "', '" . $payment_method . "', '" . $paid_from . "', '" . $reference_no . "'); ");

                        $id = $mysqli->insert_id;
                        fp__($tbl_payment_made, $id);
                        $success_message = "The $module_caption has been saved successfully.";
                        $payment_id = $id;
                    }

                    // SAVE ITEMS
                    $tbl_payment_made_items = DB::table('payment_made_items');
                    $item_amount_paid_on_formatted = !empty($item_amount_paid_on) ? processDateDtoY($item_amount_paid_on) : NULL;
                    $insert_row = $mysqli->query("INSERT INTO `$tbl_payment_made_items`(payment_id, purchase_id, amount_paid_on, amount_paid) VALUES ('" . $payment_id . "', '" . $item_id . "', '" . $item_amount_paid_on_formatted . "', '" . $item_amount_paid . "'); ");

                    if ($insert_row) $inserted_row++;
                    fp__($tbl_payment_made_items, $mysqli->insert_id);
                }
            } 


            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($inserted_row == 0) {
                $error_message = "No items added. Please add at least one item.";
            } else {
                // JOURNAL ENTRY
                $journal_table = DB::JOURNALS;
                $journal_items_table = DB::JOURNAL_ITEMS;

                if ($payment_status == 'paid' && (float)$total_amount_paid > 0) {
                    $accounts_table = DB::ACCOUNTS;
                    $ap_account = $mysqli->query("SELECT id FROM `{$accounts_table}` WHERE account_code IN ('2100', '2110', '2000') OR account_name LIKE '%Payable%' LIMIT 1")->fetch_assoc();

                    if (!empty($paid_from) && !empty($ap_account['id'])) {
                        $journal = new AccountingJournalManager($mysqli);
                        $vendor_name = getTableAttr('display_name', DB::VENDORS, $vendor_id);

                        $journal_entries = array(
                            array(
                                'account' => (int)$ap_account['id'],
                                'amount'  => (float)$total_amount_paid,
                                'type'    => 'debit'
                            ),
                            array(
                                'account' => (int)$paid_from,
                                'amount'  => (float)$total_amount_paid,
                                'type'    => 'credit'
                            )
                        );

                        $journal->createJournalEntry(
                            array(
                                'reference_type'   => 'payment_made',
                                'reference_id'     => $payment_id,
                                'reference_no'     => $reference_no,
                                'journal_date'     => $payment_date,
                                'description'      => 'Payment Made #' . $payment_id . ' - ' . $vendor_name,
                                'currency'         => 'AED',
                                'grand_subtotal'   => $total_amount_paid,
                                'grand_total'      => $total_amount_paid,
                                'reporting_method' => 'cash'
                            ),
                            $journal_entries
                        );
                    }
                }

                header("Location:listing_$module.php?success_message=$success_message");
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
$tbl_payment_made = defined('DB::PAYMENTS_MADE') ? DB::PAYMENTS_MADE : $tbl_name;
$created_by = getTableAttr('created_by', $tbl_payment_made, $id);

if (
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1')
    ||
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['user_id'] == $created_by)
) {

    $result = $mysqli->query("SELECT * FROM `$tbl_payment_made` WHERE id=$id");
    $row = $result->fetch_array();

    $vendor_id                  = s__($row['vendor_id']);
    $payment_status             = s__($row['payment_status']);
    $total_amount_paid          = s__($row['total_amount_paid']);
    $bank_charges               = s__($row['bank_charges']);
    $payment_date               = s__($row['payment_date']);
    $payment_method             = s__($row['payment_method']);
    $paid_from                  = s__($row['paid_from']);
    $reference_no               = s__($row['reference_no']);

    $payment_date       = processDateYtoD($payment_date);

    // TOTAL ITEMS
    $tbl_payment_made_items = DB::table('payment_made_items');
    $result_payment_items       = $mysqli->query("SELECT * FROM `$tbl_payment_made_items` WHERE payment_id=$id");
    $total_rows                 = $result_payment_items->num_rows;

    if ($total_rows > 0) {
        while ($row_payment_items = $result_payment_items->fetch_array()) {

            array_push($item_id_arr,                $row_payment_items['id']);
            array_push($amount_paid_on_arr,         $row_payment_items['amount_paid_on']);
            array_push($amount_paid_arr,            $row_payment_items['amount_paid']);
        }
    }
}

?>


<div class="content-wrapper">


    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <input type="hidden" name="vendor_id" id="vendor_id" value="<?php echo $vendor_id; ?>" />
        <input type="hidden" name="post_purchase_id" id="post_purchase_id" value="<?php echo $post_purchase_id; ?>" />
        <input type="hidden" name="payment_status" id="payment_status" value="" />
        <input type="hidden" name="save_and_send" id="save_and_send" value="" />

        <?php if (($action == "edit_payments_made" || $action == "update_payments_made") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_payments_made" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_payments_made" />
        <?php } ?>

        <!-- Page header -->
        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="collapse d-lg-block ms-lg-auto mt-1" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

                            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                <button type="button" onclick=" document.getElementById('payment_status').value='draft'; this.form.submit();" class="btn btn-primary btn-sm me-2">Save</button>

                            <?php } ?>

                            <?php if (!empty($id)) { ?>
                                <a href="payments_made_overview.php?payment_id=<?php echo $id; ?>" class="btn btn-light btn-sm">
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

        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>


                <div class="col-xl-12">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card">

                                <div class="card-body">

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Vendor Name:*</span> </label>

                                        <div class="col-lg-9">
                                            <?php if (!empty($id) || !empty($post_purchase_id)) { ?>
                                                <input type="hidden" class="form-control" name="vendor_id" id="vendor_id" value="<?php echo $vendor_id; ?>">
                                                <input type="text" readonly class="form-control bg-light" name="" id="" value="<?php echo getTableAttr('display_name', DB::VENDORS, $vendor_id); ?>">
                                            <?php } else { ?>

                                                <select name="vendor_id" id="vendor_id" class="form-control select" onchange="if(this.value > 0) { window.location.href='?mod=payments_made&vendor_id=' + this.value; }">
                                                    <option value='0'>Please select</option>
                                                    <?php
                                                    $result = $mysqli->query("SELECT * FROM `" . DB::VENDORS  . "` ORDER BY id DESC");
                                                    while ($rows = $result->fetch_array()) {
                                                        $display_name           = $rows["display_name"];
                                                    ?>
                                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $vendor_id) { ?>selected <?php } else if ($rows['id'] == $vendor_id) { ?>selected <?php } ?>>
                                                            <?php echo $display_name; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            <?php } ?>
                                        </div>

                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label fw-semibold">Total Amount Paid:</label>
                                        <div class="col-lg-9">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control fw-semibold bg-light opacity-50" placeholder="0" name="total_amount_paid" id="total_amount_paid" value="<?php echo $total_amount_paid; ?>" />
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
                                                <?php
                                                $result = $mysqli->query("SELECT * FROM `" . DB::PAYMENT_METHODS  . "` WHERE publish=1 ORDER BY payment_method");
                                                while ($rows = $result->fetch_array()) {
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $payment_method) { ?>selected <?php } else if ($rows['id'] == $payment_method) { ?>selected <?php } ?>>
                                                        <?php echo $rows['payment_method']; ?>
                                                    </option>

                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-1">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Paid From:*</span></label>
                                        <div class="col-lg-9">
                                            <select required class="form-select" name="paid_from" id="paid_from">
                                                <option value="0" class="fw-semibold text-black" disabled></option>
                                                <?php 
                                                $all_accounts = fetchAccountsDropdown($account_type = array(1), $prefix = '', $paid_from);
                                                echo $all_accounts;
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

                    </div>
                </div>


                <div>

                    <div class="col-xl-12">

                        <div class="row mb-2">

                            <div class="col-lg-2">
                                <label class="form-label ms-3">DATE</label>
                            </div>

                            <div class="col-lg-2">
                                <label class="form-label">PURCHASE NUMBER</label>
                            </div>

                            <div class="col-lg-2 text-end">
                                <label class="form-label">PURCHASE AMOUNT</label>
                            </div>

                            <div class="col-lg-2 text-end">
                                <label class="form-label">AMOUNT DUE</label>
                            </div>

                            <div class="col-lg-2 text-end">
                                <label class="form-label pe-4"><span class="text-danger">AMOUNT PAID ON*</span></label>
                            </div>

                            <div class="col-lg-2 text-end">
                                <label class="form-label pe-4"><span class="text-danger">PAYMENT*</span></label>
                            </div>

                        </div>

                        <div class="card">

                            <div class="row card-body">

                                <div class="col-lg-12">

                                    <?php
                                    $unpaid_purchases = array();
                                    if ($vendor_id > 0) {
                                        $result_unpaid_purchases = $mysqli->query("
                                            SELECT *
                                            FROM `" . DB::PURCHASES . "`
                                            WHERE vendor_id = " . intval($vendor_id) . "
                                            AND purchase_status NOT IN ('draft', 'declined', 'expired')
                                            ORDER BY id DESC
                                        ");

                                        if ($result_unpaid_purchases && $result_unpaid_purchases->num_rows > 0) {
                                            while ($row_purchase = $result_unpaid_purchases->fetch_array()) {
                                                $unpaid_purchases[] = $row_purchase;
                                            }
                                        }
                                    }

                                    if (!empty($unpaid_purchases)) {

                                        for ($i = 0; $i < count($unpaid_purchases); $i++) {

                                            $purchase_id_row            = $unpaid_purchases[$i]['id'];
                                            $purchase_no_row            = $unpaid_purchases[$i]['purchase_no'];
                                            $purchase_date_row          = $unpaid_purchases[$i]['purchase_date'];
                                            $purchase_grand_total       = $unpaid_purchases[$i]['grand_total'];

                                            // GET PAID AMOUNT
                                            $tbl_payment_made_items = DB::table('payment_made_items');
                                            $result_amount_paid = $mysqli->query("
                                                SELECT COALESCE(SUM(amount_paid), 0) as total_paid
                                                FROM `$tbl_payment_made_items`
                                                WHERE purchase_id = " . intval($purchase_id_row) . "
                                            ");

                                            $row_amount_paid = $result_amount_paid->fetch_array();
                                            $amount_paid_row = $row_amount_paid['total_paid'];
                                            $amount_due_row = round($purchase_grand_total - $amount_paid_row, 2);

                                            $item_index = array_search($purchase_id_row, $item_id_arr);

                                    ?>

                                            <div class="row mb-2">

                                                <div class="col-lg-2">
                                                    <input type="hidden" name="item_id[]" value="<?php echo (($item_index !== false) ? $item_id_arr[$item_index] : $purchase_id_row); ?>" />
                                                    <input type="text" readonly class="form-control form-control-sm text-muted" value="<?php echo processDateYtoD($purchase_date_row); ?>" />
                                                </div>

                                                <div class="col-lg-2">
                                                    <input type="text" readonly class="form-control form-control-sm text-muted fw-semibold" value="<?php echo $purchase_no_row; ?>" />
                                                </div>

                                                <div class="col-lg-2 text-end">
                                                    <input type="text" readonly class="form-control form-control-sm text-muted text-end" value="<?php echo round($purchase_grand_total, 2); ?>" />
                                                </div>

                                                <div class="col-lg-2 text-end">
                                                    <input type="text" readonly class="form-control form-control-sm text-muted text-end text-danger fw-semibold" value="<?php echo $amount_due_row; ?>" />
                                                </div>

                                                <div class="col-lg-2 text-end">
                                                    <input type="text" class="form-control form-control-sm amount_paid_on_date" name="amount_paid_on[]" value="<?php echo (($item_index !== false) ? $amount_paid_on_arr[$item_index] : ''); ?>" />
                                                </div>

                                                <div class="col-lg-2 text-end pe-4">
                                                    <input type="number" class="form-control form-control-sm text-end fw-semibold" step="0.01" min="0" max="<?php echo $amount_due_row; ?>" name="amount_paid[]" value="<?php echo (($item_index !== false) ? $amount_paid_arr[$item_index] : ''); ?>" onchange="calculateTotalAmount();" />
                                                </div>

                                            </div>

                                    <?php

                                        }

                                    } else {

                                        echo '<div class="alert alert-warning" role="alert">No unpaid purchases found for selected vendor.</div>';

                                    }

                                    ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </div>


        <?php include('admin_elements/copyright.php'); ?>
    </form>

</div>

<script type="text/javascript">
    function calculateTotalAmount() {
        var payment_inputs = document.getElementsByName("amount_paid[]");
        var total = 0;

        for (var i = 0; i < payment_inputs.length; i++) {
            if (payment_inputs[i].value != '' && !isNaN(parseFloat(payment_inputs[i].value))) {
                total += parseFloat(payment_inputs[i].value);
            }
        }

        // Update total with proper formatting
        var totalField = document.getElementById('total_amount_paid');
        totalField.value = parseFloat(total.toFixed(2));
    }

    $(document).ready(function() {
        // Initialize date pickers for amount_paid_on fields
        $('.amount_paid_on_date').datepicker({
            dateFormat: 'dd-mm-yy',
            changeMonth: true,
            changeYear: true
        });
        
        // Attach change event to all amount_paid inputs for real-time calculation
        $(document).on('input change', 'input[name="amount_paid[]"]', function() {
            calculateTotalAmount();
        });
        
        // Initial calculation on page load
        calculateTotalAmount();
    });
</script>


<?php if (isset($module_id) && granted('view', $module_id) && !granted('create', $module_id) && !granted('edit', $module_id)) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>


<?php include('admin_elements/admin_footer.php'); ?>

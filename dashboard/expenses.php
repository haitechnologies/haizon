<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

// ACCOUNTING JOURNAL MANAGER INTEGRATION
// =====================================================================
// Removed legacy require for autoloader compatibility: require_once(__DIR__ . '/../classes/AccountingJournalManager.php');

$module             = 'expenses';
$module_caption     = 'Expense';
$tbl_name = DB::EXPENSES;
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



if (isset($_POST['billable']))                                 $billable     = 1;
else $billable = 0;



if (isset($_REQUEST['customer_id']) && !empty($_REQUEST['customer_id'])) {
    $customer_id     = e_s__($_REQUEST['customer_id']);
} else {
    $customer_id = 0;
}




// ---------------------- Items -----------------------------
$item_id_arr                = array();
$expense_account_arr        = array();
$description_arr            = array();
$total_arr                  = array();


if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows            = e_s__($_POST['total_rows']);
    // if ($total_rows == 0 || $total_rows == '') $total_rows = 1;
} else {
    $total_rows            = 1;
}



if ($action == "update_$module" || $action == "add_$module") {

    for ($expense_item = 1; $expense_item <= $total_rows; $expense_item++) {

        $index = $expense_item;
        $index = $index - 1;

        $post_item_id           = (isset($_POST['item_id'][$index]) && !empty($_POST['item_id'][$index]) ? $_POST['item_id'][$index] :  0);
        $post_expense_account   = (isset($_POST['expense_account'][$index]) && !empty($_POST['expense_account'][$index]) ? $_POST['expense_account'][$index] :  0);
        $post_description       = (isset($_POST['description'][$index]) && !empty($_POST['description'][$index]) ? $_POST['description'][$index] :  '');
        $post_total             = (isset($_POST['total'][$index]) && !empty($_POST['total'][$index]) ? $_POST['total'][$index] :  0);


        array_push($item_id_arr,                e_s__($post_item_id));
        array_push($expense_account_arr,        e_s__($post_expense_account));
        array_push($description_arr,            e_s__($post_description));
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
    $expense_date               = e_s__($_POST['expense_date']);
    $paid_through               = e_s__($_POST['paid_through']);
    $vendor_id                  = e_s__($_POST['vendor_id']);
    $reference_no               = e_s__($_POST['reference_no']);
    // $customer_id                = e_s__($_POST['customer_id']);
    $grand_total                = e_s__($_POST['grand_total']);
} else {
    $expense_date               = date('d-m-Y', time());
    $paid_through               = '';
    $vendor_id                  = '';
    $reference_no               = '';
    // $customer_id                = '';
    $grand_total                = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {

    if (empty($expense_date)) {
        $error_message = 'Please select expense Date.';
    } else if (empty($paid_through) || $paid_through == 'Please select') {
        $error_message = 'Please select Paid Through.';
    } else {

        $expense_date   = processDateDtoY($expense_date);
        $vendor_id      = ($vendor_id == '' ? 0 : $vendor_id);
        $customer_id    = ($customer_id == '' ? 0 : $customer_id);

        if ($grand_total == '') $grand_total = '0.00';

        // ===================================================================
        // UPDATE Expense
        // ===================================================================
        $update_row = $mysqli->query("
                                        UPDATE `$tbl_name` SET
                                            billable		            = '" . $billable . "',
                                            expense_date		        = '" . $expense_date . "',
                                            paid_through		        = '" . $paid_through . "',
                                            vendor_id					= '" . $vendor_id . "',
                                            reference_no		        = '" . $reference_no . "',
                                            customer_id					= '" . $customer_id . "',
                                            grand_total		            = '" . $grand_total . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            $expense_id = $id;

            // -- PROCESS ITEMS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                for ($expense_item = 1; $expense_item <= $total_rows; $expense_item++) {

                    $index = $expense_item;
                    $index = $index - 1;

                    $item_id                        = e_s__($_POST['item_id'][$index]);
                    $item_expense_account           = e_s__($_POST['expense_account'][$index]);
                    $item_description               = e_s__($_POST['description'][$index]);
                    $item_total                     = e_s__($_POST['total'][$index]);


                    // ===================================================================
                    // UPDATE ITEMS
                    // ===================================================================

                    $item_total         = (($item_total == '') ? 0 : $item_total);

                    // Process Updated Items
                    if (!empty($item_id) && !empty($item_expense_account) && !empty($item_total)) {

                        $update_row = $mysqli->query("UPDATE `" . tbl_expense_items . "` SET 
                                                            expense_account = '" . $item_expense_account . "',
                                                            description     = '" . $item_description . "',
                                                            total           = '" . $item_total . "' 
                                                        WHERE id=$item_id");

                        if ($update_row) $updated_row++;
                        fp__(tbl_expense_items, $item_id);

                        // Process New Items
                    } else if (empty($item_id) && !empty($item_expense_account) && !empty($item_total)) {

                        $insert_row = $mysqli->query("INSERT INTO `" . tbl_expense_items . "`(expense_id, expense_account, description, total) VALUES ('" . $expense_id . "', '" . $item_expense_account . "', '" . $item_description . "', '" . $item_total . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__(tbl_expense_items, $mysqli->insert_id);

                        // Process Deleted Items
                    } else if (!empty($item_id) && empty($item_expense_account) && empty($item_total)) {

                        $mysqli->query("DELETE FROM `" . tbl_expense_items . "` WHERE id=$item_id");
                    }
                    // ===================================================================

                } //for 

            }

            // ===================================================================
            // RECREATE JOURNAL ENTRY AFTER UPDATE (DELETE OLD, CREATE NEW)
            // ===================================================================
            try {
                // Delete old journal entry and items
                $old_journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='expense' AND reference_id='$expense_id' ");
                if (!empty($old_journal_id)) {
                    $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$old_journal_id");
                    $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE id=$old_journal_id");
                }

                if ($updated_row > 0 || $inserted_row > 0) {
                    // Get expense details for journal
                    $expense_vendor = getTableAttr('display_name', DB::VENDORS, $vendor_id);
                    if (empty($expense_vendor)) {
                        $expense_vendor = 'Vendor ID: ' . $vendor_id;
                    }

                    // Initialize Journal Manager
                    $journal = new AccountingJournalManager($mysqli);

                    // Prepare journal entries array
                    $journal_entries = array();

                    // Get all expense items and their accounts
                    $expense_items_result = $mysqli->query("SELECT * FROM `" . tbl_expense_items . "` WHERE expense_id=$expense_id");
                    
                    // Add debit entries for each item
                    while ($item = $expense_items_result->fetch_array()) {
                        $item_account = $item['expense_account'];  // This is the ACCOUNT ID, not code
                        $item_amount = (float) $item['total'];

                        if ($billable == 1) {
                            // BILLABLE: Debit AR account (lookup by code)
                            $debit_account_code = '1200';  // Accounts Receivable
                            $account_result = $mysqli->query("SELECT id FROM `" . DB::ACCOUNTS . "` WHERE account_code='$debit_account_code' LIMIT 1");
                            $account_row = $account_result->fetch_assoc();
                            $debit_account_id = $account_row ? $account_row['id'] : null;
                        } else {
                            // NON-BILLABLE: Use the account ID directly from expense items
                            $debit_account_id = $item_account;
                        }

                        // Verify account exists
                        $account_check = $mysqli->query("SELECT id FROM `" . DB::ACCOUNTS . "` WHERE id='$debit_account_id' LIMIT 1");
                        $account_row = $account_check->fetch_assoc();

                        if ($account_row) {
                            $journal_entries[] = array(
                                'account'      => $debit_account_id,
                                'amount'       => $item_amount,
                                'type'         => 'debit',
                                'description'  => $item['description']
                            );
                        }
                    }

                    // Add credit entry for paid_through account (single entry for total)
                    $paid_through_account_result = $mysqli->query("SELECT id FROM `" . DB::ACCOUNTS . "` WHERE id='$paid_through' LIMIT 1");
                    $paid_through_account = $paid_through_account_result->fetch_assoc();

                    if ($paid_through_account) {
                        $journal_entries[] = array(
                            'account'      => $paid_through_account['id'],
                            'amount'       => (float) $grand_total,
                            'type'         => 'credit',
                            'description'  => 'Payment for expense'
                        );
                    }

                    // Create journal entry using AccountingJournalManager
                    $journal_result = $journal->createJournalEntry(
                        array(
                            'reference_type'  => 'expense',
                            'reference_id'    => $expense_id,
                            'reference_no'    => 'EXP-' . $expense_id,
                            'journal_date'    => date('Y-m-d', strtotime($expense_date)),
                            'description'     => ($billable == 1 ? 'Billable ' : 'Non-Billable ') . 'Expense - ' . $expense_vendor,
                            'currency'        => 'AED',
                            'grand_total'     => (float) $grand_total
                        ),
                        $journal_entries
                    );

                    // Log result
                    if ($journal_result['success']) {
                        error_log("Journal entry updated: ID {$journal_result['journal_id']} for Expense {$expense_id}");
                    } else {
                        error_log("Failed to update journal for Expense {$expense_id}: " . $journal_result['message']);
                    }
                }

            } catch (Exception $e) {
                error_log("Journal update exception for Expense {$expense_id}: " . $e->getMessage());
            }

            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $expense_date   = processDateYtoD($expense_date);
                $error_message = "No items added. Please add at least one item..";
            } else {
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

    if (empty($expense_date)) {
        $error_message = 'Please select expense Date.';
    } else if (empty($paid_through) || $paid_through == 'Please select') {
        $error_message = 'Please select Paid Through.';
    } else {

        ///////////////////////////////////////////////////////////

        // -- PROCESS ITEMS
        if ($total_rows > 0) {

            $inserted_row = 0;

            for ($expense_item = 1; $expense_item <= $total_rows; $expense_item++) {

                $index = $expense_item;
                $index = $index - 1;

                $item_expense_account           = e_s__($_POST['expense_account'][$index]);
                $item_description               = e_s__($_POST['description'][$index]);
                $item_total                     = e_s__($_POST['total'][$index]);


                if (!empty($item_expense_account) && !empty($item_total)) {

                    // ===================================================================
                    // SAVE EXPENSE (FIRST TIME ONLY)
                    // ===================================================================
                    if ($inserted_row == 0) {

                        $expense_date   = processDateDtoY($expense_date);
                        $vendor_id      = ($vendor_id == '' ? 0 : $vendor_id);
                        $customer_id    = ($customer_id == '' ? 0 : $customer_id);

                        if ($grand_total == '') $grand_total = '0.00';

                        $mysqli->query("INSERT INTO `$tbl_name`(billable, vendor_id, expense_date, paid_through, reference_no, customer_id, grand_total) VALUES ('" . $billable . "','" . $vendor_id . "', '" . $expense_date . "', '" . $paid_through . "', '" . $reference_no . "', '" . $customer_id . "', '" . $grand_total . "'); ");

                        $id = $mysqli->insert_id;
                        fp__($tbl_name, $id);
                        $success_message = "The $module_caption has been saved successfully.";
                        $expense_id = $id;
                    }

                    // ===================================================================
                    // SAVE EXPENSE ITEMS
                    // ===================================================================
                    $item_total = (($item_total == '') ? 0 : $item_total);

                    $insert_row = $mysqli->query("INSERT INTO `" . tbl_expense_items . "`(expense_id, expense_account, description, total) VALUES ('" . $expense_id . "', '" . $item_expense_account . "', '" . $item_description . "', '" . $item_total . "'); ");

                    if ($insert_row) $inserted_row++;
                    fp__(tbl_expense_items, $mysqli->insert_id);
                }
            } //for 

            // ===================================================================
            // CREATE JOURNAL ENTRY (AFTER ALL ITEMS ARE SAVED)
            // ===================================================================
            if ($inserted_row > 0) {
                try {
                    // Get expense details for journal
                    $expense_vendor = getTableAttr('display_name', DB::VENDORS, $vendor_id);
                    if (empty($expense_vendor)) {
                        $expense_vendor = 'Vendor ID: ' . $vendor_id;
                    }

                    // Get paid_through account details
                    $paid_through_name = getTableAttr('account_name', DB::ACCOUNTS, $paid_through);
                    
                    error_log("=== EXPENSE JOURNAL CREATION DEBUG ===");
                    error_log("Expense ID: {$expense_id}, Billable: {$billable}, Grand Total: {$grand_total}, Paid Through: {$paid_through}");
                    
                    // Initialize Journal Manager
                    $journal = new AccountingJournalManager($mysqli);

                    // Prepare journal entries array
                    $journal_entries = array();

                    // Get all expense items and their accounts
                    $expense_items_result = $mysqli->query("SELECT * FROM `" . tbl_expense_items . "` WHERE expense_id=$expense_id");
                    error_log("ADD section: Expense items query for ID {$expense_id}: " . ($expense_items_result ? "SUCCESS" : "FAILED - " . $mysqli->error));
                    
                    // Add debit entries for each item
                    while ($item = $expense_items_result->fetch_array()) {
                        $item_account = $item['expense_account'];  // This is the ACCOUNT ID, not code
                        $item_amount = (float) $item['total'];

                        if ($billable == 1) {
                            // BILLABLE: Debit AR account (lookup by code)
                            $debit_account_code = '1200';  // Accounts Receivable
                            $account_result = $mysqli->query("SELECT id FROM `" . DB::ACCOUNTS . "` WHERE account_code='$debit_account_code' LIMIT 1");
                            $account_row = $account_result->fetch_assoc();
                            $debit_account_id = $account_row ? $account_row['id'] : null;
                        } else {
                            // NON-BILLABLE: Use the account ID directly from expense items
                            $debit_account_id = $item_account;
                        }

                        // Verify account exists
                        $account_check = $mysqli->query("SELECT id FROM `" . DB::ACCOUNTS . "` WHERE id='$debit_account_id' LIMIT 1");
                        $account_row = $account_check->fetch_assoc();

                        if ($account_row) {
                            $journal_entries[] = array(
                                'account'      => $debit_account_id,
                                'amount'       => $item_amount,
                                'type'         => 'debit',
                                'description'  => $item['description']
                            );
                            error_log("Added debit entry: Account ID {$debit_account_id}, Amount {$item_amount}");
                        } else {
                            error_log("Account not found for ID '{$debit_account_id}' in Expense {$expense_id}");
                        }
                    }

                    // ===================================================================
                    // ADD CREDIT ENTRY FOR CASH/BANK ACCOUNT
                    // ===================================================================
                    // Add credit entry for paid_through account (single entry for total)
                    $paid_through_account_result = $mysqli->query("SELECT id FROM `" . DB::ACCOUNTS . "` WHERE id='$paid_through' LIMIT 1");
                    $paid_through_account = $paid_through_account_result->fetch_assoc();

                    error_log("ADD section: Paid-through query for account ID {$paid_through}: " . ($paid_through_account ? "FOUND" : "NOT FOUND"));

                    if ($paid_through_account) {
                        $journal_entries[] = array(
                            'account'      => $paid_through_account['id'],
                            'amount'       => (float) $grand_total,
                            'type'         => 'credit',
                            'description'  => 'Payment for expense'
                        );
                        error_log("Added credit entry: Account {$paid_through_account['id']}, Amount {$grand_total}");
                    } else {
                        error_log("Paid-through account (ID: {$paid_through}) not found for Expense {$expense_id}");
                    }

                    // ===================================================================
                    // VALIDATE JOURNAL ENTRIES BALANCE (DR = CR)
                    // ===================================================================
                    $total_debit = 0;
                    $total_credit = 0;
                    
                    foreach ($journal_entries as $entry) {
                        if ($entry['type'] === 'debit') {
                            $total_debit += $entry['amount'];
                        } else {
                            $total_credit += $entry['amount'];
                        }
                    }
                    
                    // Check if entries balance
                    if (abs($total_debit - $total_credit) > 0.01) {
                        error_log("Journal entry imbalance for Expense {$expense_id}: DR={$total_debit}, CR={$total_credit}");
                    } else {
                        error_log("Journal balanced: DR={$total_debit}, CR={$total_credit}");
                    }

                    // ===================================================================
                    // VALIDATE REQUIRED ENTRIES EXIST (minimum 2: 1 debit + 1 credit)
                    // ===================================================================
                    error_log("Journal entry count: " . count($journal_entries) . " entries prepared");
                    
                    if (empty($journal_entries)) {
                        error_log("No journal entries to create for Expense {$expense_id}");
                    } else if (count($journal_entries) < 2) {
                        error_log("Insufficient journal entries for Expense {$expense_id}. Expected minimum 2 entries (1 debit + 1 credit), got " . count($journal_entries));
                    } else {
                        error_log("Creating journal entry with " . count($journal_entries) . " items for Expense {$expense_id}");
                        
                        // Create journal entry using AccountingJournalManager
                        $journal_result = $journal->createJournalEntry(
                            array(
                                'reference_type'  => 'expense',
                                'reference_id'    => $expense_id,
                                'reference_no'    => 'EXP-' . $expense_id,
                                'journal_date'    => date('Y-m-d', strtotime($expense_date)),
                                'description'     => ($billable == 1 ? 'Billable ' : 'Non-Billable ') . 'Expense - ' . $expense_vendor,
                                'currency'        => 'AED',
                                'grand_total'     => (float) $grand_total
                            ),
                            $journal_entries
                        );

                        error_log("Journal creation result: Success=" . ($journal_result['success'] ? 'YES' : 'NO') . ", Message=" . $journal_result['message']);

                        // ===================================================================
                        // HANDLE RESULT AND NOTIFY USER
                        // ===================================================================
                        if ($journal_result['success']) {
                            error_log("Journal entry created: ID {$journal_result['journal_id']} for Expense {$expense_id}");
                            $success_message .= " Journal entry created successfully.";
                        } else {
                            error_log("Failed to create journal for Expense {$expense_id}: " . $journal_result['message']);
                            $error_message = "Warning: Journal entry not created. " . $journal_result['message'];
                        }
                    }

                } catch (Exception $e) {
                    error_log("Journal creation exception for Expense {$expense_id}: " . $e->getMessage());
                }
            }

            // CHECK IF AT LEAST ONE ITEM IS ADDED
            if ($inserted_row == 0) {
                $error_message = "No items added. Please add at least one item..";
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
$created_by = getTableAttr('created_by', DB::EXPENSES, $id);

if (
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1')
    ||
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['admin_id'] == $created_by)
) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $billable               = s__($row['billable']);
    $expense_date           = s__($row['expense_date']);
    $paid_through           = s__($row['paid_through']);
    $vendor_id              = s__($row['vendor_id']);
    $reference_no           = s__($row['reference_no']);
    $customer_id            = s__($row['customer_id']);
    $grand_total            = s__($row['grand_total']);

    $expense_date       = processDateYtoD($expense_date);


    // ------------------ TOTAL ITEMS ------------------
    $result_expense_items       = $mysqli->query("SELECT * FROM `" . tbl_expense_items . "` WHERE expense_id=$id");
    $total_rows                 = $result_expense_items->num_rows;


    if ($total_rows > 0) {
        while ($row_expense_items = $result_expense_items->fetch_array()) {

            array_push($item_id_arr,                $row_expense_items['id']);
            array_push($expense_account_arr,        $row_expense_items['expense_account']);
            array_push($description_arr,            $row_expense_items['description']);
            array_push($total_arr,                  $row_expense_items['total']);
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
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox"
                            class="form-check-input form-check-input-success"
                            name="billable"
                            id="billable"
                            value="1"
                            <?php if ($billable == '1') echo 'checked'; ?>
                            <?php if (empty($customer_id) || $customer_id == 0) echo 'disabled'; ?>>

                        <label class="form-check-label" for="billable">Billable</label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

                            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                <button type="submit" class="btn btn-primary btn-sm me-2">Save</button>
                            <?php } ?>

                            <?php if (!empty($id)) { ?>
                                <a href="expense_overview.php?expense_id=<?php echo $id; ?>" class="btn btn-light btn-sm">
                                    Cancel
                                </a>

                            <?php } else { ?>
                                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">
                                    Cancel
                                </a>
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
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Expense Date:*</span></label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="Expense Date" name="expense_date" id="expense_date" value="<?php echo $expense_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">Paid Through:*</span></label>
                                        <div class="col-lg-9">
                                            <select required class="form-select" name="paid_through" id="paid_through">
                                                <option value="0" class="fw-semibold text-black" disabled>Expense</option>
                                                <?php echo fetchAccountsDropdown($account_type = array(1, 2, 3), $prefix = '', $paid_through); ?>
                                            </select>
                                        </div>
                                    </div>


                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Vendor Name: </label>
                                        <div class="col-lg-9">
                                            <select name="vendor_id" id="vendor_id" class="form-control select">
                                                <option value='0'>Please select</option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $vendor_details = '';
                                                $result = $mysqli->query("SELECT * FROM `" . DB::VENDORS  . "` ORDER BY id DESC");
                                                while ($rows = $result->fetch_array()) {
                                                    $display_name           = $rows["display_name"];
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $vendor_id) { ?>selected <?php } else if ($rows['id'] == $vendor_id) { ?>selected <?php } ?>>
                                                        <?php echo $display_name; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Reference no:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="Reference no" name="reference_no" id="reference_no" value="<?php echo $reference_no; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Customer Name: </label>
                                        <div class="col-lg-9">
                                            <select name="customer_id" id="customer_id" class="form-control select" onchange="check_billable(this.value);">
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
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <script>
                            /**
                             * ⚙️ UI LOGIC: BILLABLE TOGGLE
                             * ---------------------------------------------------------
                             * Enables or disables the billable switch based on customer selection.
                             */
                            function check_billable(val) {
                                const billableSwitch = document.getElementById('billable');

                                // If a customer is selected (value is not 0)
                                if (val != "0" && val != "") {
                                    billableSwitch.disabled = false;
                                } else {
                                    // Disable and uncheck if no customer is selected
                                    billableSwitch.disabled = true;
                                    billableSwitch.checked = false;
                                }
                            }
                        </script>



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
                                <label class="form-label ms-3"><span class="text-danger">EXPENSE DETAILS*</span></label>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label ms-4">DESCRIPTION</label>
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
                                    for ($expense_item = 1; $expense_item <= $total_rows; $expense_item++) {
                                        $index = $expense_item;
                                        $index = $index - 1;

                                        $item_id            = (!empty($item_id_arr[$index]) ? $item_id_arr[$index] : '');
                                        $expense_account    = (!empty($expense_account_arr[$index]) ? $expense_account_arr[$index] : '');
                                        $description        = (!empty($description_arr[$index]) ? $description_arr[$index] : '');
                                        $total              = (!empty($total_arr[$index]) ? $total_arr[$index] : '');
                                        // ----------------------------------------------------------------------------
                                    ?>

                                        <div class="mb-2">
                                            <div class="row mb-3 pb-3" id="row_<?php echo $expense_item; ?>">

                                                <div class="col-lg-12">
                                                    <div class="row">

                                                        <input type="hidden" name="item_id[]" id="item_id<?php echo $expense_item; ?>" value="<?php echo $item_id; ?>">

                                                        <div class="col-lg-2">
                                                            <select required class="form-select" name="expense_account[]" id="expense_account<?php echo $expense_item; ?>">
                                                                <option value="0" class="fw-semibold text-black" disabled>Expense</option>
                                                                <?php echo fetchAccountsDropdown($account_type = array(5), $prefix = '', $expense_account); ?>
                                                            </select>
                                                        </div>

                                                        <div class="col-lg-4">
                                                            <textarea name="description[]" id="description<?php echo $expense_item; ?>" rows="2" class="form-control" placeholder="Add a description to your expense"><?php echo $description; ?></textarea>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" name="total[]" id="total<?php echo $expense_item; ?>" min="0" class="form-control text-end" placeholder="0" value="<?php echo $total; ?>" onchange="calculateGrand(<?php echo $expense_item; ?>);" onkeyup="calculateGrand(<?php echo $expense_item; ?>);">
                                                        </div>

                                                        <div class="col-lg-2 mt-1">
                                                            <?php if ($expense_item > 1) { ?>
                                                                <a href="#" onclick="calculateItemAmount('<?php echo $expense_item; ?>'); clear_row(<?php echo $expense_item; ?>)"><span class="badge bg-warning"> <i class="ph-x"></i> </span></a>
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
                                    <span id="span_add_item_row<?php echo $expense_item; ?>"><a href="#" onclick="add_item_row(); "><span class="badge bg-primary"> Add New Row </a></span></span>
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
                                        new_row += "<select class=\"form-select\" \" name=\"expense_account[]\" id=\"expense_account" + total_rows + "\">";
                                        new_row += "<option value=\"0\">Please select</option>";
                                        new_row += "</select>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-4\">";
                                        new_row += "<textarea type=\"text\" name=\"description[]\" id=\"description" + total_rows + "\" rows=\"2\" min=\"0\" placeholder=\"Add a description to your expense\" class=\"form-control\"></textarea>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" name=\"total[]\" id=\"total" + total_rows + "\" min=\"0\" class=\"form-control text-end\" placeholder=\"0\" onchange=\"calculateGrand(" + total_rows + ");\" onkeyup=\"calculateGrand(" + total_rows + ");\">";
                                        new_row += "</div>";

                                        // new_row += "</div><div class=\"col-lg-1 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" onclick=\"clear_row(" + total_rows + ")\"></span></div>";

                                        new_row += "<div class=\"col-lg-1 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" onclick=\"clear_row(" + total_rows + ")\"><span class=\"badge bg-warning\"> <i class=\"ph-x\"></i> </span></a></span> </div>";

                                        new_row += "</div>";

                                        // document.getElementById('add_row_here').innerHTML += new_row;

                                        // This is to preserve the values of previously dynamicall created elements
                                        document.getElementById('add_row_here').insertAdjacentHTML("beforebegin", new_row);

                                        document.getElementById('total_rows').value = total_rows;

                                        ajax_populate_expense_coa();
                                        // ssssssssssssssssssssss

                                    }


                                    function clear_row(row_no) {

                                        calculateItemAmount(row_no);

                                        document.getElementById('expense_account' + row_no).value = '0';
                                        document.getElementById('expense_account' + row_no).text = 'Please select';
                                        document.getElementById('description' + row_no).value = '';
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

                                        let expense_account = document.getElementById('expense_account' + row_no);
                                        let expense_account_value = expense_account.options[expense_account.selectedIndex].value;
                                        // let expense_account_text = expense_account.options[expense_account.selectedIndex].text;
                                        // console.log("expense_account " + row_no + " text:", expense_account_text);

                                        if (expense_account_value != NaN && expense_account_value != '' && expense_account_value != 'undefined' && expense_account_value != '0') {

                                            var total = document.getElementById('total' + row_no).value;

                                            // --- Calculate Total
                                            document.getElementById('total' + row_no).value = parseFloat(total).toFixed(2);

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


                                        // document.getElementById('grand_total').value = parseFloat(grand_total.toFixed(2));
                                        document.getElementById('grand_total').value = parseFloat(final_total.toFixed(2));

                                    }
                                </script>

                                <input type="hidden" name="total_rows" id="total_rows" value="<?php echo $total_rows; ?>">


                            </div>
                        </div>
                    </div>


                    <div class="row">

                        <div class="col-lg-3">
                        </div>

                        <div class="col-lg-4">
                            <div class="card ">

                                <div class="card-body"> <!--  bg-info bg-opacity-10 -->

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
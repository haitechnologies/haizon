<?php

use App\Core\DB;
// Force opcache clear for this file
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
}

include('admin_elements/admin_header.php');

$module             = 'journals';
$module_caption     = 'Journal';
$tbl_name = DB::JOURNALS;
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


// ========================================================================
// FUNCTION: Build Hierarchical Account Tree for Dropdown
// ========================================================================
function buildAccountTreeOptions($mysqli, $parent_id = 0, $level = 0, $selected_id = 0) {
    $options = '';

    // Use em-dash or hyphen for visual indentation
    $prefix = str_repeat('— ', $level);

    // Alternative: Use Unicode non-breaking spaces for better spacing
    // $prefix = str_repeat("\xC2\xA0\xC2\xA0", $level * 2);

    // Get accounts for this level, ordered by account code and name
    $result = $mysqli->query("SELECT * FROM `" . DB::ACCOUNTS . "` WHERE parent_id = $parent_id AND publish = 1 ORDER BY account_code ASC, account_name ASC");

    while ($row = $result->fetch_array()) {
        $account_id = $row['id'];
        $account_name = $row['account_name'];
        $account_code = $row['account_code'] ?? '';

        // Build display name with code if available
        $display_name = !empty($account_code) ? $account_code . ' - ' . $account_name : $account_name;

        // Add prefix for hierarchy visualization
        $display_name = $prefix . $display_name;

        $selected = ($account_id == $selected_id) ? 'selected="selected"' : '';

        // Add option
        $options .= '<option value="' . $account_id . '" ' . $selected . '>' . htmlspecialchars($display_name, ENT_QUOTES) . '</option>';

        // Recursively get child accounts
        $options .= buildAccountTreeOptions($mysqli, $account_id, $level + 1, $selected_id);
    }

    return $options;
}
// ========================================================================


// print_r($_REQUEST);

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


$reporting_method     = 'accrual_cash';

if (isset($_POST['reporting_method'])) {
    $reporting_method     = e_s__($_POST['reporting_method']);
}



if (isset($_REQUEST['customer_id']) && !empty($_REQUEST['customer_id'])) {
    $customer_id     = e_s__($_REQUEST['customer_id']);
} else {
    $customer_id = 0;
}


// $q_s = getTableAttr('journal_status', DB::JOURNALS, $id);
// if ($q_s == 'booked') header("Location:listing_$module.php?error_message=journal is already booked.");;


if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 0;



// ---------------------- journal Items -----------------------------
$item_id_arr                = array();
$account_arr                = array();
$description_arr            = array();
$debit_arr                  = array();
$credit_arr                 = array();


if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows            = e_s__($_POST['total_rows']);
    // if ($total_rows == 0 || $total_rows == '') $total_rows = 1;
} else {
    $total_rows            = 1;
}



if ($action == "update_$module" || $action == "add_$module") {

    for ($journal_item = 1; $journal_item <= $total_rows; $journal_item++) {

        $index = $journal_item;
        $index = $index - 1;

        $post_item_id       = (isset($_POST['item_id'][$index]) && !empty($_POST['item_id'][$index]) ? $_POST['item_id'][$index] :  0);
        $post_account       = (isset($_POST['account'][$index]) && !empty($_POST['account'][$index]) ? $_POST['account'][$index] :  0);
        $post_description   = (isset($_POST['description'][$index]) && !empty($_POST['description'][$index]) ? $_POST['description'][$index] :  '');
        $post_debit         = (isset($_POST['debit'][$index]) && !empty($_POST['debit'][$index]) ? $_POST['debit'][$index] :  0);
        $post_credit        = (isset($_POST['credit'][$index]) && !empty($_POST['credit'][$index]) ? $_POST['credit'][$index] :  0);


        array_push($item_id_arr,                e_s__($post_item_id));
        array_push($account_arr,                e_s__($post_account));
        array_push($description_arr,            e_s__($post_description));
        array_push($debit_arr,                  e_s__($post_debit));
        array_push($credit_arr,                 e_s__($post_credit));
    } //for 
}


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $journal_status         = e_s__($_POST['journal_status']);
    $journal_date           = e_s__($_POST['journal_date']);
    $journal_no             = e_s__($_POST['journal_no']);
    $reference_no           = e_s__($_POST['reference_no']);
    $notes                  = e_s__($_POST['notes']);
    $currency               = e_s__($_POST['currency']);

    $grand_subtotal         = e_s__($_POST['grand_subtotal']);
    $grand_total            = e_s__($_POST['grand_total']);
} else {
    $journal_status         = '';
    $journal_date           = date('d-m-Y', time());
    $journal_no             = 'Auto-generated';  // Will be set to ID after insert
    $reference_no           = '';
    $notes                  = '';
    $currency               = '';

    $grand_subtotal         = '';
    $grand_total            = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {

    if (empty($journal_date)) {
        $error_message = 'Please select journal Date.';
    } else if (empty($notes)) {
        $error_message = 'Please enter Notes.';
    } else {

        // ========================================================================
        // ACCOUNTING STANDARDS VALIDATION
        // ========================================================================
        $total_debits = 0;
        $total_credits = 0;
        $valid_line_count = 0;

        for ($journal_item = 1; $journal_item <= $total_rows; $journal_item++) {
            $index = $journal_item - 1;

            $item_account = isset($account_arr[$index]) ? $account_arr[$index] : 0;
            $item_debit = isset($debit_arr[$index]) ? (float)$debit_arr[$index] : 0;
            $item_credit = isset($credit_arr[$index]) ? (float)$credit_arr[$index] : 0;

            // Skip if no account selected
            if ($item_account == 0) continue;

            // RULE 1: Cannot have both debit and credit on same line
            if ($item_debit > 0 && $item_credit > 0) {
                $journal_date = processDateYtoD($journal_date);
                $error_message = "Accounting Standard Error: Line item $journal_item cannot have both Debit and Credit. Please fix this entry.";
                break;
            }

            // RULE 2: Must have either debit or credit (not both zero)
            if ($item_debit == 0 && $item_credit == 0) continue;

            $total_debits += $item_debit;
            $total_credits += $item_credit;
            $valid_line_count++;
        }

        // RULE 3: Must have at least 2 valid entries
        if (empty($error_message) && $valid_line_count < 2) {
            $journal_date = processDateYtoD($journal_date);
            $error_message = "Accounting Standard Error: A journal entry must have at least 2 line items (one debit and one credit).";
        }

        // RULE 4: Debits must equal Credits (balanced entry)
        if (empty($error_message) && abs($total_debits - $total_credits) > 0.01) {
            $journal_date = processDateYtoD($journal_date);
            $difference = abs($total_debits - $total_credits);
            $error_message = "Accounting Standard Error: Debits and Credits must be equal. Current difference: " . number_format($difference, 2);
        }

        // ========================================================================

        if (empty($error_message)) {

        if ($grand_subtotal == '')                      $grand_subtotal = '0.00';
        if ($grand_total == '')                         $grand_total = '0.00';

        // Use validated totals from accounting standards check
        $grand_subtotal = number_format($total_debits, 2, '.', '');
        $grand_total = number_format($total_credits, 2, '.', '');

        $journal_date     = processDateDtoY($journal_date);

        // ---------------------------------------------
        // UPDATE journal
        // ---------------------------------------------
        $update_row = $mysqli->query("
                                        UPDATE `$tbl_name` SET
                                            journal_status		        = '" . $journal_status . "',
                                            journal_date		        = '" . $journal_date . "',
                                            journal_no		            = '" . $journal_no . "',
                                            reference_no		        = '" . $reference_no . "',
                                            notes		                = '" . $notes . "',
                                            reporting_method		    = '" . $reporting_method . "',
                                            
                                            grand_subtotal		        = '" . $grand_subtotal . "',
                                            grand_total		            = '" . $grand_total . "',
                                            
                                            publish 					= '" . $publish . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            $journal_id = $id;
            ///////////////////////////////////////////////////////////

            // -- PROCESS journal ITEMS - ITNS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                for ($journal_item = 1; $journal_item <= $total_rows; $journal_item++) {

                    $index = $journal_item;
                    $index = $index - 1;

                    $item_id                        = e_s__($_POST['item_id'][$index]);
                    $item_account                   = e_s__($_POST['account'][$index]);
                    $item_description               = e_s__($_POST['description'][$index]);
                    $item_debit                     = e_s__($_POST['debit'][$index]);
                    $item_credit                    = e_s__($_POST['credit'][$index]);

                    // ---------------------------------------------
                    // UPDATE journal ITEMS
                    // ---------------------------------------------

                    $item_debit         = (($item_debit == '') ? 0 : $item_debit);
                    $item_credit        = (($item_credit == '') ? 0 : $item_credit);

                    // Process Updated journal Items
                    if (!empty($item_id) && !empty($item_account)) {

                        $update_row = $mysqli->query("UPDATE `" . DB::JOURNAL_ITEMS . "` SET 
                                                            account         = '" . $item_account . "',
                                                            description     = '" . $item_description . "',
                                                            debit           = '" . $item_debit . "',
                                                            credit          = '" . $item_credit . "'
                                                        WHERE id=$item_id");

                        if ($update_row) $updated_row++;
                        fp__(DB::JOURNAL_ITEMS, $item_id);

                        // Process New journal Items
                    } else if (empty($item_id) && !empty($item_account)) {

                        $insert_row = $mysqli->query("INSERT INTO `" . DB::JOURNAL_ITEMS . "`(journal_id, account, description, debit, credit) VALUES ('" . $journal_id . "', '" . $item_account . "', '" . $item_description . "', '" . $item_debit . "', '" . $item_credit . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__(DB::JOURNAL_ITEMS, $mysqli->insert_id);

                        // Process Deleted journal Items
                    } else if (!empty($item_id) && empty($item_account)) {

                        $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE id=$item_id");
                    }
                    // ---------------------------------------------

                } //for 

            }
            ///////////////////////////////////////////////////////////

            // CHECK IF AT LEAST ONE journal ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $journal_date = processDateYtoD($journal_date);
                $error_message = "Please add at least one journal Item.";
            } else {
                header("Location:listing_$module.php?success_message=$success_message");
            }
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }

        } // end empty error_message check

        // CHECK IF AT LEAST ONE journal ITEM IS ADDED
        // if ($inserted_row == 0) {
        //     $success_message = '';
        //     $journal_date = processDateYtoD($journal_date);
        //     $error_message = "Please add at least one journal Item.";
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

    if (empty($journal_date)) {
        $error_message = 'Please select journal Date.';
    } else if (empty($notes)) {
        $error_message = 'Please enter Notes.';
    } else {

        // ========================================================================
        // ACCOUNTING STANDARDS VALIDATION
        // ========================================================================
        $total_debits = 0;
        $total_credits = 0;
        $valid_line_count = 0;

        for ($journal_item = 1; $journal_item <= $total_rows; $journal_item++) {
            $index = $journal_item - 1;

            $item_account = isset($account_arr[$index]) ? $account_arr[$index] : 0;
            $item_debit = isset($debit_arr[$index]) ? (float)$debit_arr[$index] : 0;
            $item_credit = isset($credit_arr[$index]) ? (float)$credit_arr[$index] : 0;

            // Skip if no account selected
            if ($item_account == 0) continue;

            // RULE 1: Cannot have both debit and credit on same line
            if ($item_debit > 0 && $item_credit > 0) {
                $error_message = "Accounting Standard Error: Line item $journal_item cannot have both Debit and Credit. Please fix this entry.";
                break;
            }

            // RULE 2: Must have either debit or credit (not both zero)
            if ($item_debit == 0 && $item_credit == 0) continue;

            $total_debits += $item_debit;
            $total_credits += $item_credit;
            $valid_line_count++;
        }

        // RULE 3: Must have at least 2 valid entries
        if (empty($error_message) && $valid_line_count < 2) {
            $error_message = "Accounting Standard Error: A journal entry must have at least 2 line items (one debit and one credit).";
        }

        // RULE 4: Debits must equal Credits (balanced entry)
        if (empty($error_message) && abs($total_debits - $total_credits) > 0.01) {
            $difference = abs($total_debits - $total_credits);
            $error_message = "Accounting Standard Error: Debits and Credits must be equal. Current difference: " . number_format($difference, 2);
        }

        // ========================================================================

        if (empty($error_message)) {

        ///////////////////////////////////////////////////////////

        // -- PROCESS journal ITEMS - ITNS
        if ($total_rows > 0) {

            $inserted_row = 0;

            for ($journal_item = 1; $journal_item <= $total_rows; $journal_item++) {

                $index = $journal_item;
                $index = $index - 1;

                $item_account                   = e_s__($_POST['account'][$index]);
                $item_description               = e_s__($_POST['description'][$index]);
                $item_debit                     = e_s__($_POST['debit'][$index]);
                $item_credit                    = e_s__($_POST['credit'][$index]);

                if (!empty($item_account)) {

                    // ---------------------------------------------
                    // SAVE journal
                    // ---------------------------------------------
                    if ($inserted_row == 0) {

                        if ($grand_subtotal == '')                      $grand_subtotal = '0.00';
                        if ($grand_total == '')                         $grand_total = '0.00';

                        // Use validated totals from accounting standards check
                        $grand_subtotal = number_format($total_debits, 2, '.', '');
                        $grand_total = number_format($total_credits, 2, '.', '');


                        $journal_date = processDateDtoY($journal_date);

                        $item_debit         = (($item_debit == '') ? 0 : $item_debit);
                        $item_credit        = (($item_credit == '') ? 0 : $item_credit);

                        // ======================================================
                        // Insert journal first, then set journal_no = id
                        // ======================================================
                        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(journal_status, journal_date, journal_no, reference_no, notes, reporting_method, warehouse_id, grand_subtotal, grand_total, publish) VALUES ('" . $journal_status . "',  '" . $journal_date . "', '', '" . $reference_no . "', '" . $notes . "',  '" . $reporting_method . "',  '" . $grand_subtotal . "', '" . $grand_total . "', '" . $publish . "'); ");

                        $id = $mysqli->insert_id;

                        // Set journal_no to the ID
                        $mysqli->query("UPDATE `$tbl_name` SET journal_no = '" . $id . "' WHERE id = " . $id);

                        // if ($insert_row) {
                        fp__($tbl_name, $id);
                        $success_message = "The $module_caption has been saved successfully.";
                        $journal_id = $id;
                    }
                    // ---------------------------------------------


                    // ---------------------------------------------
                    // SAVE journal ITEMS
                    // ---------------------------------------------

                    $item_debit         = (($item_debit == '') ? 0 : $item_debit);
                    $item_credit        = (($item_credit == '') ? 0 : $item_credit);

                    $insert_row = $mysqli->query("INSERT INTO `" . DB::JOURNAL_ITEMS . "`(journal_id, account, description, debit, credit) VALUES ('" . $journal_id . "', '" . $item_account . "', '" . $item_description . "', '" . $item_debit . "', '" . $item_credit . "'); ");

                    if ($insert_row) $inserted_row++;

                    fp__(DB::JOURNAL_ITEMS, $mysqli->insert_id);
                    // ---------------------------------------------

                }
            } //for 


            // CHECK IF AT LEAST ONE journal ITEM IS ADDED
            if ($inserted_row == 0) {
                $error_message = "Please add at least one journal Item.";
            } else {
                header("Location:listing_$module.php?success_message=$success_message");
            }

        } // end empty error_message check

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
$created_by = getTableAttr('created_by', DB::JOURNALS, $id);

if (
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1')
    ||
    (!empty($id) && $_SESSION[$project_pre]['DASHBOARD']['admin_id'] == $created_by)
) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $journal_status       = s__($row['journal_status']);
    $journal_date         = s__($row['journal_date']);
    $journal_no           = s__($row['journal_no']);
    $reference_no         = s__($row['reference_no']);
    $notes                = s__($row['notes']);
    $reporting_method     = s__($row['reporting_method']);

    $grand_subtotal       = s__($row['grand_subtotal']);
    $grand_total          = s__($row['grand_total']);

    $publish              = s__($row['publish']);

    $journal_date = processDateYtoD($journal_date);

    // ------------------ TOTAL journal ITEMS ------------------
    $result_journal_items     = $mysqli->query("SELECT * FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$id");
    $total_rows                 = $result_journal_items->num_rows;


    if ($total_rows > 0) {
        while ($row_journal_items = $result_journal_items->fetch_array()) {

            array_push($item_id_arr,                $row_journal_items['id']);
            array_push($account_arr,                $row_journal_items['account']);
            array_push($description_arr,            $row_journal_items['description']);
            array_push($debit_arr,                  $row_journal_items['debit']);
            array_push($credit_arr,                 $row_journal_items['credit']);
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
        <input type="hidden" name="journal_status" id="journal_status" value="" />
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
                        <a href="listing_<?php echo $module; ?>.php" class="breadcrumb-item">Journals</a>
                        <span class="breadcrumb-item active"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Create<?php } ?> </span>
                    </div>

                    <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                        <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                    </a>
                </div>


                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <div class="p-3 rounded">
                        <div class="form-check form-check-inline form-switch">
                            <label class="form-check-label fw-semibold" for="sc_r_success">Journal #: <?php echo $journal_no; ?></label>
                        </div>
                    </div>
                <?php } ?>

                <div class="p-3 rounded">
                    <div class="form-check form-check-inline form-switch">
                        <label class="form-check-label" for="sc_r_success"> <strong><?php echo ucwords($journal_status); ?></strong></label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto mt-1" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">

                        <button type="button" onclick="if(validateJournalEntry()) { document.getElementById('journal_status').value='draft'; this.form.submit(); }" class="btn btn-info my-1 me-2"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>Save as Draft<?php } ?> </button>

                        <button type="button" onclick="if(validateJournalEntry()) { this.form.submit(); }" class="btn btn-info my-1 me-2">Save and Publish</button>

                        <a href="listing_journals.php" class="btn btn-light btn-outline-light my-1 me-2">
                            <i class="ph-arrow-left"></i> Cancel
                        </a>
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

                                <div class="card-header d-flex align-items-center">
                                    <h6 class="mb-0">
                                        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Update<?php } else { ?>New<?php } ?> Journal
                                    </h6>
                                </div>

                                <div class="card-body">

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Date: <span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <div class="form-control-feedback form-control-feedback-start">
                                                <input type="text" class="form-control" placeholder="Journal Date" name="journal_date" id="journal_date" value="<?php echo $journal_date; ?>">
                                                <div class="form-control-feedback-icon">
                                                    <i class="ph-calendar"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Journal#: <span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control text-bg-light" name="journal_no" id="journal_no" readonly value="<?php echo $journal_no; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Reference no:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" placeholder="Reference no" name="reference_no" id="reference_no" value="<?php echo $reference_no; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Notes: <span class="text-danger">*</span></label>
                                        <div class="col-lg-9">
                                            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Maximum 500 Characters" maxlength="500"><?php echo $notes; ?></textarea>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-lg-3 col-form-label">Reporting Method:</label>
                                        <div class="col-lg-9">
                                            <div class="mt-2">
                                                <!-- <p class="fw-semibold">Type</p> -->
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="reporting_method" id="reporting_method" value="accrual_cash" <?php if ($reporting_method == 'accrual_cash') { ?>checked <?php } ?>>
                                                    <label class="form-check-label">Accrual and Cash</label>
                                                </div>

                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="reporting_method" id="reporting_method" value="accrual" <?php if ($reporting_method == 'accrual') { ?>checked <?php } ?>>
                                                    <label class="form-check-label">Accrual Only</label>
                                                </div>

                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="reporting_method" id="reporting_method" value="cash" <?php if ($reporting_method == 'cash') { ?>checked <?php } ?>>
                                                    <label class="form-check-label">Cash Only</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="col-lg-3 col-form-label">Currency: </label>
                                        <div class="col-lg-9">
                                            <select class="form-select" name="currency" id="currency">
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_currency = $mysqli->query("SELECT * FROM `" . DB::CURRENCIES  . "` WHERE publish=1 ORDER BY id ASC");
                                                while ($rows_currency = $result_currency->fetch_array()) {
                                                    // $currency        = s__($rows_currency['currency']);
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_currency['currency']; ?>" <?php if ($action == "edit_$module" && $rows_currency['currency'] == $currency) { ?>selected <?php } else if ($rows_currency['currency'] == $currency) { ?>selected <?php } else if (empty($currency) && $rows_currency['currency'] == 'AED') { ?>selected <?php } ?>>
                                                        <?php echo $rows_currency['currency']; ?>
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

                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <strong>Accounting Standards for Manual Journal Entry:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Each line item can have <strong>either</strong> a Debit <strong>or</strong> a Credit (not both)</li>
                                <li>Total Debits must equal Total Credits (balanced entry)</li>
                                <li>Minimum 2 line items required (at least one debit and one credit)</li>
                                <li>Each line with an amount must have an account selected</li>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>

                        <div class="row mb-2">

                            <div class="col-lg-2">
                                <label class="form-label ms-3 fw-semibold">ACCOUNT <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-lg-3">
                                <label class="form-label ms-4 fw-semibold">DESCRIPTION</label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-3 fw-semibold">DEBITS </label>
                            </div>

                            <div class="col-lg-1">
                                <label class="form-label ms-4 fw-semibold">CREDITS </label>
                            </div>

                        </div>

                        <div class="card">

                            <div class="row card-body">

                                <div class="col-lg-12">

                                    <?php
                                    // ----------------------------------------------------------------------------
                                    for ($journal_item = 1; $journal_item <= $total_rows; $journal_item++) {
                                        $index = $journal_item;
                                        $index = $index - 1;

                                        // ----------------------------------------------------------------------------
                                    ?>

                                        <div class="mb-2">
                                            <div class="row mb-3 pb-3" id="row_<?php echo $journal_item; ?>">


                                                <div class="col-lg-12">
                                                    <div class="row">

                                                        <input type="hidden" name="item_id[]" id="item_id<?php echo $journal_item; ?>" value="<?php echo (!empty($item_id_arr[$index]) ? $item_id_arr[$index] : ''); ?>">

                                                        <div class="col-lg-2">
                                                            <select class="form-select" name="account[]" id="account<?php echo $journal_item; ?>">
                                                                <option value="0">Please select</option>
                                                                <?php
                                                                // Build hierarchical tree view of accounts
                                                                $selected_account = (!empty($account_arr[$index]) ? $account_arr[$index] : 0);
                                                                echo buildAccountTreeOptions($mysqli, 0, 0, $selected_account);
                                                                ?>
                                                            </select>
                                                        </div>

                                                        <div class="col-lg-3">
                                                            <textarea name="description[]" id="description<?php echo $journal_item; ?>" rows="2" class="form-control" placeholder="Add a description to your item"><?php echo (!empty($description_arr[$index]) ? $description_arr[$index] : ''); ?></textarea>
                                                        </div>

                                                        <div class="col-lg-1">
                                                            <input type="number" step="0.01" name="debit[]" id="debit<?php echo $journal_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($debit_arr[$index]) ? $debit_arr[$index] : ''); ?>" onkeyup="handleDebitCreditEntry('<?php echo $journal_item; ?>', 'debit');" onchange="handleDebitCreditEntry('<?php echo $journal_item; ?>', 'debit');" placeholder="0.00">
                                                        </div>


                                                        <div class="col-lg-1">
                                                            <input type="number" step="0.01" name="credit[]" id="credit<?php echo $journal_item; ?>" min="0" class="form-control text-center" value="<?php echo (!empty($credit_arr[$index]) ? $credit_arr[$index] : ''); ?>" onkeyup="handleDebitCreditEntry('<?php echo $journal_item; ?>', 'credit');" onchange="handleDebitCreditEntry('<?php echo $journal_item; ?>', 'credit');" placeholder="0.00">
                                                        </div>


                                                        <div class="col-lg-2 mt-1">
                                                            <?php if ($journal_item > 1) { ?>
                                                                <a href="#" onclick="calculateItemAmount('<?php echo $journal_item; ?>'); clear_row(<?php echo $journal_item; ?>)"><span class="badge bg-warning"> <i class="ph-x"></i> </span></a>
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
                                    <span id="span_add_item_row<?php echo $journal_item; ?>"><a href="#" onclick="add_item_row(); "><span class="badge bg-primary"> Add New Row </a></span></span>
                                </div>


                                <!-- </div> -->


                                <script>
                                    // ========================================================================
                                    // Store account options for dynamic rows
                                    // ========================================================================
                                    var accountOptionsHTML = '<?php echo addslashes(buildAccountTreeOptions($mysqli, 0, 0, 0)); ?>';
                                    // ========================================================================

                                    function add_item_row() {
                                        var div_add_here = document.getElementById('div_add_here');
                                        var total_rows = document.getElementById('total_rows').value;
                                        total_rows++;

                                        var new_row = "";

                                        new_row += "<div class=\"row mb-3 pb-3\" id=\"row_" + total_rows + "\">";
                                        new_row += "<input type=\"hidden\" name=\"item_id[]\" id=\"item_id" + total_rows + "\">";

                                        new_row += "<div class=\"col-lg-2\">";
                                        new_row += "<select class=\"form-select\" name=\"account[]\" id=\"account" + total_rows + "\">";
                                        new_row += "<option value=\"0\">Please select</option>";
                                        new_row += accountOptionsHTML;
                                        new_row += "</select>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-3\">";
                                        new_row += "<textarea type=\"text\" name=\"description[]\" id=\"description" + total_rows + "\" rows=\"2\" min=\"0\" placeholder=\"Add a description to your item\" class=\"form-control\"></textarea>";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" step=\"0.01\" name=\"debit[]\" id=\"debit" + total_rows + "\" min=\"0\" class=\"form-control text-center\" placeholder=\"0.00\" onkeyup=\"handleDebitCreditEntry('" + total_rows + "', 'debit');\" onchange=\"handleDebitCreditEntry('" + total_rows + "', 'debit');\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1\">";
                                        new_row += "<input type=\"number\" step=\"0.01\" name=\"credit[]\" id=\"credit" + total_rows + "\" min=\"0\" class=\"form-control text-center\" placeholder=\"0.00\" onkeyup=\"handleDebitCreditEntry('" + total_rows + "', 'credit');\" onchange=\"handleDebitCreditEntry('" + total_rows + "', 'credit');\">";
                                        new_row += "</div>";

                                        new_row += "<div class=\"col-lg-1 mt-1\"><span id=\"span_remove_item_row" + total_rows + "\"> <a href=\"#\" onclick=\"clear_row(" + total_rows + ")\"><span class=\"badge bg-warning\"> <i class=\"ph-x\"></i> </span></a></span> </div>";

                                        new_row += "</div>";

                                        // document.getElementById('add_row_here').innerHTML += new_row;

                                        // This is to preserve the values of previously dynamicall created elements
                                        document.getElementById('add_row_here').insertAdjacentHTML("beforebegin", new_row);

                                        document.getElementById('total_rows').value = total_rows;

                                        // Account options are already included in the HTML above
                                        // No need for ajax_populate_accounts() call

                                    }


                                    function clear_row(row_no) {

                                        document.getElementById('account' + row_no).value = '0';
                                        document.getElementById('account' + row_no).text = 'Please select';
                                        document.getElementById('description' + row_no).value = '';
                                        document.getElementById('debit' + row_no).value = '';
                                        document.getElementById('credit' + row_no).value = '';

                                        document.getElementById('row_' + row_no).style.display = 'none';

                                        calculateGrand();

                                    }

                                    // Dummy function for compatibility (removed deprecated functionality)
                                    function clearGrandDiscountTypeValue() {
                                        // No longer needed - kept for compatibility
                                    }

                                    // -------------------------------------------------------------------------
                                    //  HANDLE DEBIT/CREDIT ENTRY - ACCOUNTING STANDARD
                                    //  Rule: A line item cannot have both debit AND credit
                                    // -------------------------------------------------------------------------
                                    function handleDebitCreditEntry(row_no, field_type) {
                                        var debit_elem = document.getElementById('debit' + row_no);
                                        var credit_elem = document.getElementById('credit' + row_no);

                                        if (!debit_elem || !credit_elem) return;

                                        var debit_val = parseFloat(debit_elem.value) || 0;
                                        var credit_val = parseFloat(credit_elem.value) || 0;

                                        // ACCOUNTING STANDARD: Cannot have both debit and credit on same line
                                        if (field_type === 'debit' && debit_val > 0) {
                                            // Clear credit if user enters debit
                                            credit_elem.value = '';
                                            credit_elem.classList.remove('border-danger');
                                            debit_elem.classList.remove('border-danger');
                                        } else if (field_type === 'credit' && credit_val > 0) {
                                            // Clear debit if user enters credit
                                            debit_elem.value = '';
                                            debit_elem.classList.remove('border-danger');
                                            credit_elem.classList.remove('border-danger');
                                        }

                                        // Ensure non-negative values
                                        if (debit_val < 0) debit_elem.value = '';
                                        if (credit_val < 0) credit_elem.value = '';

                                        // Update totals
                                        calculateGrand();
                                    }

                                    // -------------------------------------------------------------------------
                                    //  CALCULATE AMOUNT (LEGACY - kept for compatibility)
                                    // -------------------------------------------------------------------------
                                    function calculateItemAmount(row_no) {
                                        calculateGrand();
                                    }




                                    // -------------------------------------------------------------------------
                                    //  GRAND CALCULATIONS - STRICT ACCOUNTING STANDARDS
                                    // -------------------------------------------------------------------------
                                    function calculateGrand() {

                                        var total_rows = document.getElementById('total_rows').value;
                                        var total_debits = 0;
                                        var total_credits = 0;
                                        var has_entries = false;

                                        // Loop through all rows and sum up debits and credits
                                        for (var i = 1; i <= total_rows; i++) {
                                            var debit_elem = document.getElementById('debit' + i);
                                            var credit_elem = document.getElementById('credit' + i);
                                            var row_elem = document.getElementById('row_' + i);

                                            // Skip hidden rows
                                            if (row_elem && row_elem.style.display === 'none') continue;

                                            if (debit_elem && credit_elem) {
                                                var debit_val = parseFloat(debit_elem.value) || 0;
                                                var credit_val = parseFloat(credit_elem.value) || 0;

                                                // STRICT: Ensure only positive values
                                                if (debit_val < 0) {
                                                    debit_elem.value = '';
                                                    debit_val = 0;
                                                }
                                                if (credit_val < 0) {
                                                    credit_elem.value = '';
                                                    credit_val = 0;
                                                }

                                                total_debits += debit_val;
                                                total_credits += credit_val;

                                                if (debit_val > 0 || credit_val > 0) {
                                                    has_entries = true;
                                                }
                                            }
                                        }

                                        // Update subtotals
                                        document.getElementById('subtotal_debits').value = total_debits.toFixed(2);
                                        document.getElementById('subtotal_credits').value = total_credits.toFixed(2);

                                        // Update grand totals (same as subtotals for manual journals)
                                        document.getElementById('grand_subtotal').value = total_debits.toFixed(2);
                                        document.getElementById('grand_total').value = total_credits.toFixed(2);

                                        // Calculate and display difference (debits - credits)
                                        var difference = total_debits - total_credits;
                                        var diff_elem = document.getElementById('difference');
                                        diff_elem.value = difference.toFixed(2);

                                        // Highlight difference if not balanced (ACCOUNTING STANDARD)
                                        if (Math.abs(difference) > 0.01 && has_entries) {
                                            diff_elem.classList.add('border-danger');
                                            diff_elem.classList.add('fw-bold');
                                        } else {
                                            diff_elem.classList.remove('border-danger');
                                            diff_elem.classList.remove('fw-bold');
                                        }

                                        return {
                                            balanced: Math.abs(difference) < 0.01,
                                            difference: difference,
                                            has_entries: has_entries
                                        };
                                    }

                                    // -------------------------------------------------------------------------
                                    //  VALIDATE JOURNAL ENTRY - ACCOUNTING STANDARDS
                                    // -------------------------------------------------------------------------
                                    function validateJournalEntry() {
                                        var result = calculateGrand();

                                        // RULE 1: Must have at least 2 line items with entries
                                        var total_rows = document.getElementById('total_rows').value;
                                        var valid_entries = 0;

                                        for (var i = 1; i <= total_rows; i++) {
                                            var account_elem = document.getElementById('account' + i);
                                            var debit_elem = document.getElementById('debit' + i);
                                            var credit_elem = document.getElementById('credit' + i);
                                            var row_elem = document.getElementById('row_' + i);

                                            // Skip hidden rows
                                            if (row_elem && row_elem.style.display === 'none') continue;

                                            if (account_elem && account_elem.value != '0') {
                                                var debit_val = parseFloat(debit_elem.value) || 0;
                                                var credit_val = parseFloat(credit_elem.value) || 0;

                                                if (debit_val > 0 || credit_val > 0) {
                                                    valid_entries++;
                                                }
                                            }
                                        }

                                        if (valid_entries < 2) {
                                            alert('Accounting Standard Error:\n\nA journal entry must have at least 2 line items (one debit and one credit).\n\nPlease add more entries.');
                                            return false;
                                        }

                                        // RULE 2: Debits must equal Credits (balanced entry)
                                        if (!result.balanced && result.has_entries) {
                                            alert('Accounting Standard Error:\n\nDebits and Credits must be equal (balanced entry).\n\nCurrent Difference: ' + Math.abs(result.difference).toFixed(2) + '\n\nPlease adjust your entries to balance the journal.');
                                            return false;
                                        }

                                        // RULE 3: Each line must have an account selected
                                        for (var i = 1; i <= total_rows; i++) {
                                            var account_elem = document.getElementById('account' + i);
                                            var debit_elem = document.getElementById('debit' + i);
                                            var credit_elem = document.getElementById('credit' + i);
                                            var row_elem = document.getElementById('row_' + i);

                                            // Skip hidden rows
                                            if (row_elem && row_elem.style.display === 'none') continue;

                                            var debit_val = parseFloat(debit_elem.value) || 0;
                                            var credit_val = parseFloat(credit_elem.value) || 0;

                                            if ((debit_val > 0 || credit_val > 0) && account_elem.value == '0') {
                                                alert('Accounting Standard Error:\n\nRow ' + i + ' has an amount but no account selected.\n\nPlease select an account for each line item with an amount.');
                                                account_elem.focus();
                                                return false;
                                            }
                                        }

                                        return true;
                                    }

                                    // -------------------------------------------------------------------------
                                    //  AUTO-CALCULATE ON PAGE LOAD (for editing existing journals)
                                    // -------------------------------------------------------------------------
                                    window.addEventListener('DOMContentLoaded', function() {
                                        // Calculate totals on page load if editing existing journal
                                        calculateGrand();
                                    });
                                </script>

                                <input type="hidden" name="total_rows" id="total_rows" value="<?php echo $total_rows; ?>">


                            </div>
                        </div>
                    </div>


                    <div class="row">

                        <div class="col-lg-3"></div>

                        <div class="col-lg-4">
                            <div class="card ">

                                <div class="card-body"> <!--  bg-info bg-opacity-10 -->

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Subtotal Debits:</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" placeholder="0" name="subtotal_debits" id="subtotal_debits" value="0.00" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Subtotal Credits:</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" placeholder="0" name="subtotal_credits" id="subtotal_credits" value="0.00" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Total (AED) Debits:</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input readonly type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" placeholder="0" name="grand_subtotal" id="grand_subtotal" value="<?php echo $grand_subtotal; ?>" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Total (AED) Credits:</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end" name="grand_total" id="grand_total" value="<?php echo $grand_total; ?>" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-1">
                                        <label class="col-lg-6 col-form-label fw-semibold">Difference:</label>
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php echo BASE_CURRENCY['code']; ?></span>
                                                <input type="number" class="form-control fw-semibold bg-light bg-opacity-50 text-end text-danger" name="difference" id="difference" value="0.00" readonly>
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
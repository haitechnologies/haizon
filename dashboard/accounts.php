<?php

include('admin_elements/admin_header.php');

$module             = 'accounts';
$module_caption     = 'Account';
$tbl_name = DB::ACCOUNTS;
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
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

// if (isset($_POST['publish']))                                 $publish     = 1;
// else $publish = 0;

$publish = 1;






/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $account_type       = e_s__($_POST['account_type']);
    $account_name       = e_s__($_POST['account_name']);
    $account_code       = e_s__($_POST['account_code']);
    $description        = e_s__($_POST['description']);
} else {
    $account_type       = '';
    $account_name       = '';
    $account_code       = '';
    $description        = '';
}

/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($account_name)) {
        $error_message = 'Account name is mandatory.';
    } else {

        $parent_id     = getTableAttr('parent_id', DB::ACCOUNTS, $account_type);
        $account_id    = getTableAttr('id', DB::ACCOUNTS, $account_type);

        // if (empty($parent_id)) $level = 1; // MASTER CATEGORIES (Assets, Expense, Liability, Income, Equity)
        $level_of_parent = getTableAttr("level", DB::ACCOUNTS, $account_id);
        $next_level = $level_of_parent + 1;

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
											parent_id               = '" . $account_type . "',
											account_name            = '" . $account_name . "',
											account_code            = '" . $account_code . "',
											description             = '" . $description . "',
											publish                 = '" . $publish . "'
										WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
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
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($account_name)) {
        $error_message = 'Account name is mandatory.';
    } else {

        /* ---------------------- QUERY ---------------------- */

        $parent_id     = getTableAttr('parent_id', DB::ACCOUNTS, $account_type);
        $account_id    = getTableAttr('id', DB::ACCOUNTS, $account_type);

        // if (empty($parent_id)) $level = 1; // MASTER CATEGORIES (Assets, Expense, Liability, Income, Equity)
        $level_of_parent = getTableAttr("level", DB::ACCOUNTS, $account_id);
        $next_level = $level_of_parent + 1;

        $parent_account_type = getTableAttr('account_type', DB::ACCOUNTS, $parent_id);

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(parent_id, account_type, account_name, account_code, description, level, publish) 
                                        VALUES ('" . $account_type . "', '" . $parent_account_type . "', '" . $account_name . "', '" . $account_code . "', '" . $description . "', '" . $next_level . "', '" . $publish . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
            //header("Location:$module.php?error_message=$error_message");
        }
    }
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

    $account_type       = s__($row['account_type']);
    $account_name       = s__($row['account_name']);
    $account_code       = s__($row['account_code']);
    $description        = s__($row['description']);
    $publish            = s__($row['publish']);
    $level              = s__($row['level']);
    
    // Check if account is protected (system account or has transactions)
    $is_protected = false;
    
    // List of system accounts that should not be deleted
    $system_accounts = array(
        'Dividend', 'Drawing', 'Distribution', 'Retained Earnings',
        'Opening Balance', 'Suspense', 'Clearing', 'Rounding',
        'Currency Gain', 'Currency Loss', 'Variance', 'Adjustment'
    );
    
    // Check if this account is a system account
    foreach ($system_accounts as $sys_account) {
        if (stripos($account_name, $sys_account) !== false) {
            $is_protected = true;
            break;
        }
    }
    
    // Check if account has transactions
    if (!$is_protected) {
        $txn_check = $mysqli->query("SELECT COUNT(*) as txn_count FROM `" . DB::JOURNAL_ITEMS . "` WHERE account = '$id'");
        $txn_row = $txn_check->fetch_array(MYSQLI_ASSOC);
        if ($txn_row['txn_count'] > 0) {
            $is_protected = true;
        }
    }
    
    // System level accounts (level <= 1) are also protected
    if ($level <= 1) {
        $is_protected = true;
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">


    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
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

                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

                            <?php if (isset($module_id) && granted('delete', $module_id) && !empty($id)) { ?>
                                <?php if (isset($is_protected) && $is_protected) { ?>
                                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Cannot delete system accounts or accounts with transactions">
                                        <i class="ph-trash me-1"></i>Delete
                                    </button>
                                <?php } else { ?>
                                    <a href="listing_<?php echo $module; ?>.php" class="btn btn-danger btn-sm">Delete</a>
                                <?php } ?>
                            <?php } ?>

                            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                <button type="submit" class="btn btn-primary btn-sm me-2">Save</button>
                            <?php } ?>

                            <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- /page header -->


        <div class="content-inner">
            <div class="content">

                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <?php
                            // ----------------------------------------------------------------
                            $selected_id_from_db = getTableAttr('parent_id', DB::ACCOUNTS, $id);
                            // ----------------------------------------------------------------
                            ?>
                            <div class="content clearfix">

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Account Type:*</span></label>

                                    <div class="col-lg-9">
                                        <select name="account_type" id="account_type" class="form-select">
                                            <?php //echo fetchAccountsDropdown(null, '', $selected_id_from_db,  $selected = $id); 
                                            ?>
                                            <?php echo fetchAccountsDropdown(null, '', $selected_id_from_db); ?>
                                        </select>
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Account Name:*</span></label>

                                    <div class="col-lg-9">
                                        <input required type="text" name="account_name" id="account_name" value="<?php echo $account_name; ?>" class="form-control">
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Account Code: </label>

                                    <div class="col-lg-9">
                                        <input type="text" name="account_code" id="account_code" value="<?php echo $account_code; ?>" class="form-control">
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Description: </label>

                                    <div class="col-lg-9">
                                        <textarea class="form-control" name="description" id="description"><?php echo $description; ?></textarea>
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



<!-- 
    // ---------------------------------------------------------
    // ENABLE VIEW ONLY MODE FOR FORM ELEMENTS
    // ---------------------------------------------------------
-->
<?php if (isset($module_id) && granted('view', $module_id) && !granted('create', $module_id) && !granted('edit', $module_id)) { ?>
    <script>
        $(function() {
            toggleFormElements('true');
        });
    </script>
<?php } ?>

<?php include('admin_elements/admin_footer.php'); ?>
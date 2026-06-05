<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'banks';
$module_caption     = 'Bank Account';
$tbl_name = DB::BANKS;
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

if (isset($_POST['publish']))                                 $publish     = 1;
else $publish = 1;

if (isset($_POST['is_primary']))                                 $is_primary     = 1;
else $is_primary = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $account_name           = e_s__($_POST['account_name']);
    $account_code           = e_s__($_POST['account_code']);
    $currency               = e_s__($_POST['currency']);
    $bank_name              = e_s__($_POST['bank_name']);
    $routing_number         = e_s__($_POST['routing_number']);
    $description            = e_s__($_POST['description']);
} else {
    $account_name           = '';
    $account_code           = '';
    $currency               = '';
    $bank_name              = '';
    $routing_number         = '';
    $description            = '';
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

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
                                    UPDATE `$tbl_name` SET
                                        account_name					= '" . $account_name . "',
                                        account_code					= '" . $account_code . "',
                                        currency						= '" . $currency . "',
                                        bank_name					    = '" . $bank_name . "',
                                        routing_number					= '" . $routing_number . "',
                                        description					    = '" . $description . "',
                                        is_primary 					    = '" . $is_primary . "',
                                        publish 					    = '" . $publish . "'
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
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(account_name, account_code, currency, bank_name, routing_number, description, is_primary, publish) VALUES ('" . $account_name . "',  '" . $account_code . "', '" . $currency . "',  '" . $bank_name . "', '" . $routing_number . "', '" . $description . "', '" . $is_primary . "', '" . $publish . "'); ");

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

    $account_name           = s__($row['account_name']);
    $account_code           = s__($row['account_code']);
    $currency               = s__($row['currency']);
    $bank_name              = s__($row['bank_name']);
    $routing_number         = s__($row['routing_number']);
    $description            = s__($row['description']);
    $is_primary             = s__($row['is_primary']);
    $publish                = s__($row['publish']);
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


                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_primary" id="is_primary" <?php if ($is_primary == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Is Primary?</label>
                    </div>
                </div>


                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <div class="mt-2 mb-2">

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

                            <div class="card-body">

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Account Name:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="account_name" id="account_name" value="<?php echo $account_name; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Account Code:*</span></label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="account_code" id="account_code" value="<?php echo $account_code; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Currency:*</span></label>
                                    <div class="col-lg-9">
                                        <select class="form-select" name="currency" id="currency">
                                            <!-- <option value="0" selected="">Select</option> -->
                                            <option value='0'>Please select</option>
                                            <?php
                                            $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "currencies` WHERE publish=1 ORDER BY currency");
                                            while ($rows = $result->fetch_array()) {
                                            ?>
                                                <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $currency) { ?>selected <?php } else if ($rows['id'] == $currency) { ?>selected <?php } ?>>
                                                    <?php echo $rows['currency']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Bank Name: </label>
                                    <div class="col-lg-9">
                                        <input type="text" name="bank_name" id="bank_name" value="<?php echo $bank_name; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Rounting number (SWIFT): </label>
                                    <div class="col-lg-9">
                                        <input type="text" name="routing_number" id="routing_number" value="<?php echo $routing_number; ?>" class="form-control">
                                        <div class="form-text text-muted">e.g 026009593</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Description: </label>
                                    <div class="col-lg-9">
                                        <textarea class="form-control" name="description" id="description" style="field-sizing: content;"><?php echo $description; ?></textarea>
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
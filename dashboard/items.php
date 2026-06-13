<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'items';
$module_caption     = 'Item';
$tbl_name             = DB::ITEMS;
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
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in items.php', 'WARNING', __FILE__, __LINE__);
    }
}

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

$is_active = 1;
// if (isset($_POST['is_active']))                                 $is_active     = 1;
// else $is_active = 1;


if (isset($_POST['is_excise']) && $_POST['is_excise'] == 1) {
    $is_excise     = 1;
} else {
    $is_excise     = 0;
}


$item_type     = 'services';

if (isset($_POST['item_type'])) {
    $item_type     = e_s__($_POST['item_type']);
}



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/

// $sale_account       = (isset($sale_account) || !empty($sale_account) ? e_s__($_POST['sale_account']) : '');
// $purchase_account   = (isset($purchase_account) || !empty($purchase_account) ? e_s__($_POST['purchase_account']) : '');


if ($action == "update_$module" || $action == "add_$module") {
    $item_name              = e_s__($_POST['item_name']);
    $unit_price             = e_s__($_POST['unit_price']);
} else {
    $item_name              = '';
    $unit_price             = '';
}

/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($item_name)) {
        $error_message = 'Item name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'item_name', $item_name) && $item_name != getTableAttr('item_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Item name. Please enter different.';
    } else {

        $unit = ($unit == '') ? '0' : $unit;
        $unit_price = ($unit_price == '') ? '0' : $unit_price;

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
                                    UPDATE `$tbl_name` SET
                                        item_type					    = '" . $item_type . "',
                                        item_name					    = '" . $item_name . "',
                                        unit_price					    = '" . $unit_price . "',
                                        is_active 					    = '" . $is_active . "'
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

    if (empty($item_name)) {
        $error_message = 'Item name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'item_name', $item_name) && $item_name != getTableAttr('item_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Item name. Please enter different.';
    } else {

        $unit       = ($unit == '') ? '0' : $unit;
        $unit_price = ($unit_price == '') ? '0' : $unit_price;

        /* ---------------------- QUERY ---------------------- */
        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(item_type, item_name, unit_price, is_active) VALUES ('" . $item_type . "', '" . $item_name . "', '" . $unit_price . "', '" . $is_active . "'); ");

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

    $item_type              = s__($row['item_type']);
    $item_name              = s__($row['item_name']);
    $unit_price             = s__($row['unit_price']);
    $is_active = s__($row['is_active']);
}


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

            <div class="my-1">
                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php } else { ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php } ?>
                <?php echo csrf_field(); ?>

                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <p>Type: </p>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <div class="form-check form-check-inline">
                                                <input type="radio" class="form-check-input" name="item_type" id="item_type" value="services" <?php if ($item_type == 'services') { ?>checked <?php } ?>>
                                                <label class="form-check-label">Services</label>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Item Name:*</span> </label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="item_name" id="item_name" value="<?php echo $item_name; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-lg-3 col-form-label">Price: </label>

                                    <div class="col-lg-9">

                                        <div class="col-lg-4">
                                            <div class="form-control-feedback form-control-feedback-start mb-3">
                                                <input type="text" name="unit_price" id="unit_price" value="<?php echo $unit_price; ?>" class="form-control">
                                                <div class="form-control-feedback-icon"><?php echo BASE_CURRENCY['code']; ?></div>
                                            </div>
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
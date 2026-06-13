<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'ports';
$module_caption     = 'Port';
$tbl_name = DB::PORTS;
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
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


if (isset($_POST['publish']))       $publish     = 1;
else $publish = 1;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $port_name  = e_s__($_POST['port_name']);
    $port_code  = e_s__($_POST['port_code']);
    $country_id = e_s__($_POST['country_id']);
} else {
    $port_name  = '';
    $port_code  = '';
    $country_id = '';
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($port_name)) {
        $error_message = 'Port name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'port_name', $port_name) && $port_name != getTableAttr('port_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Port name. Please enter different.';
    } else if (empty($port_name)) {
        $error_message = 'Port code is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'port_code', $port_code) && $port_code != getTableAttr('port_code', $tbl_name, $id)) {
        $error_message = 'Duplicate Port code. Please enter different.';
    } else if (empty($country_id)) {
        $error_message = 'Please select Country.';
    } else {

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
                                            port_name	        = '" . $port_name . "',
                                            port_code	        = '" . $port_code . "',
											country_id	        = '" . $country_id . "',
											is_active 		    = '" . $publish . "'
										WHERE id=$id");
        if ($update_row) {
            $success_message = "Item has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "Could not update the item. Please try again later.";
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

    if (empty($port_name)) {
        $error_message = 'Port name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'port_name', $port_name) && $port_name != getTableAttr('port_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Port name. Please enter different.';
    } else if (empty($port_name)) {
        $error_message = 'Port code is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'port_code', $port_code) && $port_code != getTableAttr('port_code', $tbl_name, $id)) {
        $error_message = 'Duplicate Port code. Please enter different.';
    } else if (empty($country_id)) {
        $error_message = 'Please select Country.';
    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(country_id, port_name, port_code,  is_active) VALUES ('" . $country_id . "', '" . $port_name . "', '" . $port_code . "', '" . $publish . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            fp__($tbl_name, $id);
            $success_message = "Item has been saved successfully.";
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "Failed to save the item. Please try again";
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

    $port_name          = s__($row['port_name']);
    $port_code          = s__($row['port_code']);
    $country_id         = s__($row['country_id']);
    $publish            = s__($row['is_active']);
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

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
                <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
                    <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                <?php } else { ?>
                    <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                <?php } ?>

                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Port name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="port_name" id="port_name" value="<?php echo $port_name; ?>" class="form-control">
                            </div>
                        </div>


                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Port Code:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="port_code" id="port_code" value="<?php echo $port_code; ?>" class="form-control">
                                <small class="text-muted ">DXB, SHJ etc </small>
                            </div>
                        </div>


                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Country:*</span></label>
                            <div class="col-lg-9">
                                <select required class="form-select select" name="country_id" id="country_id">
                                    <option value="0">Please select</option>
                                    <?php
                                    // -------------------------------------------------------------------------------------------------
                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                    while ($rows = $result->fetch_array()) {
                                        // -------------------------------------------------------------------------------------------------
                                    ?>
                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $country_id) { ?>selected <?php } else if ($rows['id'] == $country_id) { ?>selected <?php } ?>>
                                            <?php echo $rows['country']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>



                    </div>

                </div>
            </form>
        </div>


        <?php include('admin_elements/copyright.php'); ?>
    </div>

</div>
<?php include('admin_elements/admin_footer.php'); ?>
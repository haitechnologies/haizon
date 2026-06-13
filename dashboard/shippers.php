<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'shippers';
$module_caption     = 'Shipper';
$tbl_name = DB::SHIPPERS;
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
else $publish = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $shipper_name       = e_s__($_POST['shipper_name']);
    $address_line1      = e_s__($_POST['address_line1']);
    $address_line2      = e_s__($_POST['address_line2']);
    $city               = e_s__($_POST['city']);
    $zipcode            = e_s__($_POST['zipcode']);
    $province           = e_s__($_POST['province']);
    $country            = e_s__($_POST['country']);
    $email              = e_s__($_POST['email']);
    $telephone          = e_s__($_POST['telephone']);
    $mobile             = e_s__($_POST['mobile']);
    $fax                = e_s__($_POST['fax']);
} else {
    $shipper_name       = '';
    $address_line1      = '';
    $address_line2      = '';
    $city               = '';
    $zipcode            = '';
    $province           = '';
    $country            = '';
    $email              = '';
    $telephone          = '';
    $mobile             = '';
    $fax                = '';
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {


    if (empty($shipper_name)) {
        $error_message = 'Shipper is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'shipper_name', $shipper_name) && $shipper_name != getTableAttr('shipper_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Shipper name. Please enter different.';
    } else if (empty($address_line1)) {
        $error_message = 'Street Address1 is mandatory.';
    } else {

        $country           = (($country == '') ? 0 : $country);

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
                                            shipper_name       = '" . $shipper_name . "',
                                            address_line1      = '" . $address_line1 . "',
                                            address_line2      = '" . $address_line2 . "',
                                            city               = '" . $city . "',
                                            zipcode            = '" . $zipcode . "',
                                            province           = '" . $province . "',
                                            country            = '" . $country . "',
                                            email              = '" . $email . "',
                                            telephone          = '" . $telephone . "',
                                            mobile             = '" . $mobile . "',
                                            fax                = '" . $fax . "',
											is_active 		   = '" . $publish . "'
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
    // } else if ($action == "add_$module" && granted('create', $module_id)) {

    //     if (empty($shipper_name)) {
    //         $error_message = 'Shipper name is mandatory.';
    //     } else if (checkDuplicateRow($tbl_name, 'shipper_name', $shipper_name)) {
    //         $error_message = 'Shipper name already exists. Please enter a different one.';
    //     } else if (empty($customer)) {
    //         $error_message = 'Please select Customer.';
    //     } else {

    //         $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(shipper_name, customer, publish, is_active) VALUES ('" . $shipper_name . "', '" . $customer . "', '" . $publish . "'); ");

    //         if ($insert_row) {
    //             $id = $mysqli->insert_id;
    //             fp__($tbl_name, $id);
    //             $success_message = "Item has been saved successfully.";
    //             header("Location:listing_$module.php?success_message=$success_message");
    //         } else {
    //             $error_message = "Failed to save the item. Please try again";
    //             //header("Location:$module.php?error_message=$error_message");
    //         }
    //     }
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

    $shipper_name       = s__($row['shipper_name']);
    $address_line1      = s__($row['address_line1']);
    $address_line2      = s__($row['address_line2']);
    $city               = s__($row['city']);
    $zipcode            = s__($row['zipcode']);
    $province           = s__($row['province']);
    $country            = s__($row['country']);
    $email              = s__($row['email']);
    $telephone          = s__($row['telephone']);
    $mobile             = s__($row['mobile']);
    $fax                = s__($row['fax']);
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
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Shipper name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="shipper_name" id="shipper_name" value="<?php echo $shipper_name; ?>" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Street Address1:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo $address_line1; ?>" required>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Street Address2:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="address_line2" name="address_line2" value="<?php echo $address_line2; ?>">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">City:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo $city; ?>">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Zip/Postal Code:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="zipcode" name="zipcode" value="<?php echo $zipcode; ?>">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Province:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="province" name="province" value="<?php echo $province; ?>">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Country:</label>
                            <div class="col-lg-9">
                                <select required class="form-select" name="country" id="country">
                                    <option value="0"></option>
                                    <?php
                                    // -------------------------------------------------------------------------------------------------
                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE is_active=1 ORDER BY country");
                                    while ($rows = $result->fetch_array()) {
                                        // -------------------------------------------------------------------------------------------------
                                    ?>
                                        <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $country) { ?>selected <?php } else if ($rows['id'] == $country) { ?>selected <?php } ?>>
                                            <?php echo $rows['country']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Email:</label>
                            <div class="col-lg-9">
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Telephone:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="telephone" name="telephone" value="<?php echo $telephone; ?>">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Mobile:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo $mobile; ?>">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Fax:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="fax" name="fax" value="<?php echo $fax; ?>">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-3 col-form-label">Publish:</label>
                            <div class="col-lg-9 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" value="1" <?php if ($publish == '1') { ?>checked="checked" <?php } ?>>
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
<?php include('admin_elements/admin_footer.php'); ?>
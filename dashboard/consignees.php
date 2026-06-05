<?php

include('admin_elements/admin_header.php');

$module             = 'consignees';
$module_caption     = 'Consignee';
$tbl_name = DB::CONSIGNEES;
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
    $consignee_name     = e_s__($_POST['consignee_name']);
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
    $consignee_name     = '';
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


    if (empty($consignee_name)) {
        $error_message = 'Consignee is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'consignee_name', $consignee_name) && $consignee_name != getTableAttr('consignee_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Consignee name. Please enter different.';
    } else if (empty($address_line1)) {
        $error_message = 'Street Address1 is mandatory.';
    } else {

        $country           = (($country == '') ? 0 : $country);

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
                                            consignee_name     = '" . $consignee_name . "',
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
											publish 		   = '" . $publish . "'
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

    //     if (empty($consignee_name)) {
    //         $error_message = 'Consignee name is mandatory.';
    //     } else if (checkDuplicateRow($tbl_name, 'consignee_name', $consignee_name)) {
    //         $error_message = 'Consignee name already exists. Please enter a different one.';
    //     } else if (empty($customer)) {
    //         $error_message = 'Please select Customer.';
    //     } else {

    //         $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(consignee_name, customer, publish) VALUES ('" . $consignee_name . "', '" . $customer . "', '" . $publish . "'); ");

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

    $consignee_name     = s__($row['consignee_name']);
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
    $publish            = s__($row['publish']);
}



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
                        <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" <?php if ($publish == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="sc_r_success">Publish</label>
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

                <div class="card col-lg-6">

                    <div class="card-body">

                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Consignee name:*</span></label>
                            <div class="col-lg-9">
                                <input required type="text" name="consignee_name" id="consignee_name" value="<?php echo $consignee_name; ?>" class="form-control">
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
                                    $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE publish=1 ORDER BY country");
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

                    </div>

                </div>
            </div>


            <?php include('admin_elements/copyright.php'); ?>
        </div>
    </form>

</div>
<?php include('admin_elements/admin_footer.php'); ?>
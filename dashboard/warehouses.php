<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'warehouses';
$module_caption = 'Warehouse';
$tbl_name = DB::WAREHOUSES;

$photo_upload_path         = '../uploads/' . $module . '/';
$allowed_file_size         = $GLOBALS['PHOTO']['MAX_UPLOAD_SIZE']; //MB Bytes
$allowed_file_formats     = $GLOBALS['PHOTO']['FORMATS']; //MB Bytes

// Template Sizes
// 500 x 334
// 150 x 100

$image_width            = '400';
$image_height           = '68';

$thumb_width            = '200';
$thumb_height           = '68';

$display_thumb_width    = '100';
$display_thumb_height   = '34';

$error_message = '';
$success_message = '';


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

if (isset($_POST['publish']))                 $publish = 1;
else $publish = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $warehouse_no           = e_s__($_POST['warehouse_no']);
    $warehouse_name         = e_s__($_POST['warehouse_name']);
    $street1                = e_s__($_POST['street1']);
    $street2                = e_s__($_POST['street2']);
    $country                = e_s__($_POST['country']);
    $state                  = e_s__($_POST['state']);
    $phone                  = e_s__($_POST['phone']);
    $email                  = e_s__($_POST['email']);
    $trn                    = e_s__($_POST['trn']);
} else {
    $warehouse_no           = '';
    $warehouse_name         = '';
    $street1                = '';
    $street2                = '';
    $country                = '';
    $state                  = '';
    $phone                  = '';
    $email                  = '';
    $trn                    = '';
}


/*
|--------------------------------------------------------------------------
| 	DELETE PHOTO ONLY
|--------------------------------------------------------------------------
|
*/
if (isset($_REQUEST['delete_photo']) && $_REQUEST['delete_photo'] == 1 && !empty($id)) {
    $photo = getTableAttr("photo", DB::WAREHOUSES, $id);
    if (!empty($photo)) {
        delete_photo($photo, $photo_upload_path, '1');     // DELETE OLD THUMB
        delete_photo($photo, $photo_upload_path, '0');    // DELETE OLD PHOTO

        $result = $mysqli->query("UPDATE `$tbl_name` SET photo='' WHERE id=$id");
        $success_message = 'Image Deleted Successfully.';
    }
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($warehouse_no)) {
        $error_message = 'Warehouse number is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'warehouse_no', $warehouse_no) && $warehouse_no != getTableAttr('warehouse_no', $tbl_name, $id)) {
        $error_message = 'Duplicate Warehouse number. Please enter different.';
    } else if (empty($warehouse_name)) {
        $error_message = 'Warehouse name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'warehouse_name', $warehouse_name) && $warehouse_name != getTableAttr('warehouse_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Warehouse name. Please enter different.';
    } else {


        /* ---------------------- UPLOAD PHOTO ---------------------- */
        $old_photo = getTableAttr('photo', $tbl_name, $id);

        if (isset($_FILES["photo"]["name"])) {
            $photo = $_FILES["photo"]["name"];
            if (!empty($photo)) {
                // $renamed 				= full_rename($logo, 'haipulse-logo');
                // $renamed_photo 			= renamePhoto($photo, $complete=0);
                $renamed_photo                 = full_rename($photo, $id . '_logo');
                $message                     =
                    uploadNewPhoto('photo', $renamed_photo, $photo_upload_path, $allowed_file_size, $old_photo, $image_width, $image_height, $thumb_width, $thumb_height);
                if ($message)        $error_message = $message;
                else                $result = $mysqli->query("UPDATE `$tbl_name` SET photo='$renamed_photo' WHERE id=$id");
            } //endif
        }

        //////////////////////////////////////////////////
        $update_row = $mysqli->query("
									UPDATE `$tbl_name` SET
										warehouse_no					= '" . $warehouse_no . "',
										warehouse_name					= '" . $warehouse_name . "',
										street1					        = '" . $street1 . "',
										street2					        = '" . $street2 . "',
										country					        = '" . $country . "',
										state					        = '" . $state . "',
										phone				            = '" . $phone . "',
										email				            = '" . $email . "',
										trn				                = '" . $trn . "',
										publish 						= '" . $publish . "'
									WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module" && granted('create', $module_id)) {

    if (empty($warehouse_no)) {
        $error_message = 'Warehouse number is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'warehouse_no', $warehouse_no)) {
        $error_message = 'Duplicate Warehouse number. Please enter different.';
    } else if (empty($warehouse_name)) {
        $error_message = 'Warehouse name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'warehouse_name', $warehouse_name)) {
        $error_message = 'Duplicate Warehouse name. Please enter different.';
    } else {


        /* ---------------------- UPLOAD PHOTO ---------------------- */
        $old_photo = getTableAttr('photo', $tbl_name, $id);

        if (isset($_FILES["photo"]["name"])) {
            $photo = $_FILES["photo"]["name"];
            if (!empty($photo)) {
                // $renamed 				= full_rename($logo, 'haipulse-logo');
                // $renamed_photo 			= renamePhoto($photo, $complete=0);
                $renamed_photo                 = full_rename($photo, $id . '_logo');
                $message                     =
                    uploadNewPhoto('photo', $renamed_photo, $photo_upload_path, $allowed_file_size, $old_photo, $image_width, $image_height, $thumb_width, $thumb_height);
                if ($message)        $error_message = $message;
                else                $result = $mysqli->query("UPDATE `$tbl_name` SET photo='$renamed_photo' WHERE id=$id");
            } //endif
        }

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(warehouse_no, warehouse_name, street1, street2, country, state, phone, email, trn, publish) VALUES ('" . $warehouse_no . "', '" . $warehouse_name . "', '" . $street1 . "', '" . $street2 . "',  '" . $country . "', '" . $state . "', '" . $phone . "', '" . $email . "', '" . $trn . "', '" . $publish . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            header("Location:listing_$module.php?success_message=$success_message");
            //////////////////////////////////////////////////
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

    $warehouse_no                       = s__($row['warehouse_no']);
    $warehouse_name                     = s__($row['warehouse_name']);
    $street1                            = s__($row['street1']);
    $street2                            = s__($row['street2']);
    $country                            = s__($row['country']);
    $state                              = s__($row['state']);
    $phone                              = s__($row['phone']);
    $email                              = s__($row['email']);
    $trn                                = s__($row['trn']);
    $publish                            = s__($row['publish']);
}

$photo = getTableAttr('photo', $tbl_name, $id);

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>

<div class="content-wrapper">

    <form method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <input type="hidden" name="delete_photo" id="delete_photo" value="0" />
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

                <div class="row">
                    <div class="col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Warehouse number:*</span></label>
                                    <div class="col-lg-9">
                                        <input name="warehouse_no" id="warehouse_no" value="<?php echo $warehouse_no; ?>" class="form-control" type="text">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Warehouse name:*</span></label>
                                    <div class="col-lg-9">
                                        <input name="warehouse_name" id="warehouse_name" value="<?php echo $warehouse_name; ?>" class="form-control" type="text">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Street 1: </label>
                                    <div class="col-lg-9">
                                        <input name="street1" id="street1" value="<?php echo $street1; ?>" class="form-control" type="text">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Street 2: </label>
                                    <div class="col-lg-9">
                                        <input name="street2" id="street2" value="<?php echo $street2; ?>" class="form-control" type="text">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Country:</label>
                                    <div class="col-lg-9">
                                        <select required class="form-select select" name="country" id="country" onchange="ajax_populate_states(this.value);">
                                            <!-- <option value="0">Please select *</option> -->
                                            <?php
                                            $result = $mysqli->query("SELECT * FROM `" . $tbl_prefix . "geo_countries` WHERE publish=1 ORDER BY country_name");
                                            while ($rows = $result->fetch_array()) {
                                            ?>
                                                <option value="<?php echo $rows['id']; ?>" <?php if ($action == "edit_$module" && $rows['id'] == $country) { ?>selected <?php } else if ($rows['id'] == $country) { ?>selected <?php } ?>>
                                                    <?php echo $rows['country_name']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">State:</label>
                                    <div class="col-lg-9">

                                        <select class="form-select" name="state" id="state">
                                            <option value='0'>Please select</option>
                                            <?php
                                            if (!empty($country)) {
                                                $result_states = $mysqli->query("SELECT * FROM `" . DB::GEO_STATES  . "` WHERE publish=1 AND country_id=$country");
                                            } else {
                                                $result_states = $mysqli->query("SELECT * FROM `" . DB::GEO_STATES  . "` WHERE id=0");
                                            }

                                            while ($rows_states = $result_states->fetch_array()) {
                                                $state_name        = s__($rows_states['state_name']);
                                            ?>
                                                <option value="<?php echo $rows_states['id']; ?>" <?php if ($action == "edit_$module" && $rows_states['id'] == $state) { ?>selected <?php } else if ($rows_states['id'] == $country) { ?>selected <?php } ?>>
                                                    <?php echo $state_name; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>

                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Phone:</label>
                                    <div class="col-lg-9">
                                        <input name="phone" id="phone" value="<?php echo $phone; ?>" class="form-control" type="text">
                                        <div class="form-text text-muted"><small>+971 50 1234567</small></div>
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">Email: </label>
                                    <div class="col-lg-9">
                                        <input name="email" id="email" value="<?php echo $email; ?>" class="form-control" type="email">
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-lg-3 col-form-label">TRN#: </label>
                                    <div class="col-lg-9">
                                        <input name="trn" id="trn" value="<?php echo $trn; ?>" class="form-control" type="text">
                                    </div>
                                </div>


                            </div>

                        </div>


                    </div>


                    <div class="col-lg-4">

                        <div class="card card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row mb-3">
                                        <label class="form-label">Logo (Display on Quotaitons, Sale Orders, Invoices PDFs etc..)</label>
                                        <input type="file" name="photo" id="photo" class="form-control">
                                    </div>
                                    <div class="form-text text-muted">Size <?php echo $image_width; ?>px x <?php echo $image_height; ?>px -> <?php echo $allowed_file_formats; ?>. Max file size <?php echo $allowed_file_size; ?> Mb</div>

                                    <?php if (!empty($photo) && file_exists('../uploads/warehouses/thumbs/' . $photo)) { ?>
                                        <div class="form-group">
                                            <a data-lightbox="warehouse" href="<?php echo $photo_upload_path .  $photo ?>" target="_blank">
                                                <img src="<?php echo $photo_upload_path . '/thumbs/' . $photo ?>" alt="" width="<?php echo $dispaly_thumb_width; ?>" height="<?php echo $display_thumb_height; ?>" />
                                            </a><br /><br />

                                            <button type="button" onclick="document.getElementById('delete_photo').value=1; this.form.submit();" class="btn btn-danger btn-sm" name="delete_photo" id="delete_photo">Delete</button>

                                        </div>
                                    <?php } ?>
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
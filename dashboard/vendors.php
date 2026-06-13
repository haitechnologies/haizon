<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module                 = 'vendors';
$module_caption         = 'Vendor';
$tbl_name = DB::VENDORS;

// $photo_upload_path          = '../uploads/' . $module . '/';
// $allowed_file_size          = $GLOBALS['PHOTO']['MAX_UPLOAD_SIZE']; //MB Bytes
// $allowed_file_formats       = $GLOBALS['PHOTO']['FORMATS']; //MB Bytes

// $image_width                    = '500';
// $image_height                   = '500';

// $thumb_width                    = '200';
// $thumb_height                   = '200';

// $display_thumb_width            = '100';
// $display_thumb_height           = '100';

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

// print_r($_REQUEST);

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

if (isset($_POST['publish'])) {
    $publish     = 1;
} else {
    $publish     = 0;
}



$vendor_type     = 'business';

if (isset($_POST['vendor_type'])) {
    $vendor_type     = e_s__($_POST['vendor_type']);
}


// ---------------------- Tags Array -----------------------------
$tags_arr           = array();
$posted_tags_arr    = array();
$tags_string        = '';
$tag                = '';


if (isset($_POST['tags'])) {

    $posted_tags = $_POST['tags'];

    foreach ($posted_tags as $tag) {
        $tags_string .= $tag . ', ';
    }
    if (strlen($tags_string) > 2) {
        $tags_string = substr($tags_string, 0, -2);
    }
    // echo $tags_string;

    $posted_tags_arr = explode(',', $tags_string);
    // print_r($posted_tags_arr);
}



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {

    $vendor_owner               = e_s__($_POST['vendor_owner']);
    $payment_term               = e_s__($_POST['payment_term']);

    $vendor_status              = e_s__($_POST['vendor_status']);
    $vendor_source              = e_s__($_POST['vendor_source']);
    $assigned_to                = e_s__($_POST['assigned_to']);

    $salutation                 = e_s__($_POST['salutation']);
    $first_name                 = e_s__($_POST['first_name']);
    $last_name                  = e_s__($_POST['last_name']);
    // $company_name               = e_s__($_POST['company_name']);
    $display_name               = e_s__($_POST['display_name']);
    $address                    = e_s__($_POST['address']);
    $email                      = e_s__($_POST['email']);
    $phone                      = e_s__($_POST['phone']);
    $mobile                     = e_s__($_POST['mobile']);

    $tax_treatment              = e_s__($_POST['tax_treatment']);
    $trn                        = e_s__($_POST['trn']);
    $license_number             = e_s__($_POST['license_number']);
    $license_expiry             = e_s__($_POST['license_expiry']);
    $currency                   = e_s__($_POST['currency']);
    $exchange_rate              = e_s__($_POST['exchange_rate']);

    $sales_person              = e_s__($_POST['sales_person']);
    $cs_agent                   = e_s__($_POST['cs_agent']);
    $lead_category              = e_s__($_POST['lead_category']);
    $rating                     = e_s__($_POST['rating']);

    $contacted_date             = e_s__($_POST['contacted_date']);
    $description                = e_s__($_POST['description']);

    $website                    = e_s__($_POST['website']);
    $department                 = e_s__($_POST['department']);
    $designation                = e_s__($_POST['designation']);
    $x                          = e_s__($_POST['x']);
    $facebook                   = e_s__($_POST['facebook']);
    $instagram                  = e_s__($_POST['instagram']);
} else {

    $vendor_owner               = '';
    $payment_term               = '';

    $vendor_status              = '';
    $vendor_source              = '';
    $assigned_to                = '';

    $salutation                 = '';
    $first_name                 = '';
    $last_name                  = '';
    // $company_name               = '';
    $display_name               = '';
    $address                    = '';
    $email                      = '';
    $phone                      = '';
    $mobile                     = '';

    $tax_treatment              = '';
    $trn                        = '';
    $license_number             = '';
    $license_expiry             = '';
    $currency                   = '';
    $exchange_rate              = '';

    $sales_person               = '';
    $cs_agent                   = '';
    $lead_category              = '';
    $rating                     = '';

    $contacted_date             = '';
    $description                = '';

    $website                    = '';
    $department                 = '';
    $designation                = '';
    $x                          = '';
    $facebook                   = '';
    $instagram                  = '';
}

/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {

    if (empty($display_name)) {
        $error_message = 'Company name is mandatory.';
    } else if (empty($address)) {
        $error_message = 'Address is mandatory.';
    } else {

        $vendor_owner   = (empty($vendor_owner) ? '0' : $vendor_owner);
        $vendor_status  = (empty($vendor_status) ? '0' : $vendor_status);
        $vendor_source  = (empty($vendor_status) ? '0' : $vendor_status);
        $service        = (empty($service) ? '0' : $service);
        $assigned_to    = (empty($assigned_to) ? '0' : $assigned_to);
        $contacted_date = (empty($contacted_date) ? date('Y-m-d h:i:s', time()) : date('Y-m-d h:i:s', strtotime($contacted_date)));
        $payment_term   = (empty($payment_term) ? '0' : $payment_term);
        $tax_treatment  = (empty($tax_treatment) ? '0' : $tax_treatment);
        $license_number = (empty($license_number) ? '0' : $license_number);
        $license_expiry = (empty($license_expiry) ? '1970-01-01' : processDateDtoY($license_expiry));
        $currency       = (empty($currency) ? '0' : $currency);
        $exchange_rate  = (empty($exchange_rate) ? '0' : $exchange_rate);
        $sales_person   = (empty($sales_person) ? '0' : $sales_person);
        $cs_agent       = (empty($cs_agent) ? '0' : $cs_agent);
        $rating         = (empty($rating) ? '0' : $rating);

        /* ---------------------- QUERY ---------------------- */
        $update_row = $mysqli->query("
										UPDATE `$tbl_name` SET
											
                                            vendor_owner			= '" . $vendor_owner . "',
                                            payment_term			= '" . $payment_term . "',
                                            
                                            vendor_status			= '" . $vendor_status . "',
											vendor_source			= '" . $vendor_source . "',
											assigned_to			    = '" . $assigned_to . "',
											
                                            vendor_type			    = '" . $vendor_type . "',
											salutation			    = '" . $salutation . "',
											first_name			    = '" . $first_name . "',
											last_name			    = '" . $last_name . "',
											display_name			= '" . $display_name . "',
											address			        = '" . $address . "',
											email			        = '" . $email . "',
											phone			        = '" . $phone . "',
											mobile			        = '" . $mobile . "',
											
                                            tax_treatment		    = '" . $tax_treatment . "',
                                            trn			            = '" . $trn . "',
                                            license_number			= '" . $license_number . "',
                                            license_expiry			= '" . $license_expiry . "',
                                            currency			    = '" . $currency . "',
                                            exchange_rate			= '" . $exchange_rate . "',
                                            
                                            sales_person			= '" . $sales_person . "',
                                            cs_agent			    = '" . $cs_agent . "',
                                            lead_category			= '" . $lead_category . "',
                                            rating			        = '" . $rating . "',
											
                                            contacted_date			= '" . $contacted_date . "',
											description			    = '" . $description . "',
											tags			        = '" . $tags_string . ",',
                                            
											website			        = '" . $website . "',
											department			    = '" . $department . "',
											designation			    = '" . $designation . "',
											x			            = '" . $x . "',
											facebook			    = '" . $facebook . "',
											instagram			    = '" . $instagram . "'
											
										WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            // vendor Logs
            updatevendorLogs($vendor_id, 'vendor', 'updated');
            // header("Location:listing_$module.php?success_message=$success_message");
            header("Location:vendor_overview.php?vendor_id=$id&success_message=$success_message");
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


    if (empty($display_name)) {
        $error_message = 'Company name is mandatory.';
    } else if (empty($address)) {
        $error_message = 'Address name is mandatory.';
    } else {

        $vendor_owner   = (empty($vendor_owner) ? '0' : $vendor_owner);
        $vendor_status  = (empty($vendor_status) ? '0' : $vendor_status);
        $vendor_source  = (empty($vendor_status) ? '0' : $vendor_status);
        $service        = (empty($service) ? '0' : $service);
        $assigned_to    = (empty($assigned_to) ? '0' : $assigned_to);
        $contacted_date = (empty($contacted_date) ? date('Y-m-d h:i:s', time()) : date('Y-m-d h:i:s', strtotime($contacted_date)));
        $payment_term   = (empty($payment_term) ? '0' : $payment_term);
        $tax_treatment  = (empty($tax_treatment) ? '0' : $tax_treatment);
        $license_number = (empty($license_number) ? '0' : $license_number);
        $license_expiry = (empty($license_expiry) ? '1970-01-01' : processDateDtoY($license_expiry));
        $currency       = (empty($currency) ? '0' : $currency);
        $exchange_rate  = (empty($exchange_rate) ? '0' : $exchange_rate);
        $sales_person   = (empty($sales_person) ? '0' : $sales_person);
        $cs_agent       = (empty($cs_agent) ? '0' : $cs_agent);
        $rating         = (empty($rating) ? '0' : $rating);


        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(vendor_type, vendor_owner, payment_term, vendor_status, vendor_source, assigned_to, salutation, first_name, last_name, display_name, address, email, phone, mobile, tax_treatment, trn, license_number, license_expiry, currency, exchange_rate, sales_person, cs_agent, lead_category, rating, contacted_date, description, tags, website, department, designation, x, facebook, instagram) VALUES ('" . $vendor_type . "', '" . $vendor_owner . "', '" . $payment_term . "', '" . $vendor_status . "', '" . $vendor_source . "', '" . $assigned_to . "', '" . $salutation . "', '" . $first_name . "', '" . $last_name . "', '" . $display_name . "', '" . $address . "', '" . $email . "', '" . $phone . "', '" . $mobile . "', '" . $tax_treatment . "', '" . $trn . "', '" . $license_number . "', '" . $license_expiry . "', '" . $currency . "', '" . $exchange_rate . "', '" . $sales_person . "', '" . $cs_agent . "', '" . $lead_category . "', '" . $rating . "', '" . $contacted_date . "', '" . $description . "', '" . $tags_string . ",',  '" . $website . "', '" . $department . "', '" . $designation . "', '" . $x . "', '" . $facebook . "', '" . $instagram . "'); ");


        if ($insert_row) {
            $id = $mysqli->insert_id;
            $success_message = "The $module_caption has been saved successfully.";
            fp__($tbl_name, $id);
            // vendor Logs
            updatevendorLogs($vendor_id, 'vendor', 'created');
            ////////////////////////////////////////////////////////////////////////
            // header("Location:listing_$module.php?success_message=$success_message");
            header("Location:vendor_overview.php?vendor_id=$id&success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
            // header("Location:$module.php?error_message=$error_message");
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

    $vendor_owner           = s__($row['vendor_owner']);
    $payment_term           = s__($row['payment_term']);

    $vendor_status          = s__($row['vendor_status']);
    $vendor_source          = s__($row['vendor_source']);
    $assigned_to            = s__($row['assigned_to']);

    $vendor_type            = s__($row['vendor_type']);
    $salutation             = s__($row['salutation']);
    $first_name             = s__($row['first_name']);
    $last_name              = s__($row['last_name']);
    // $company_name           = s__($row['company_name']);
    $display_name           = s__($row['display_name']);
    $address                = s__($row['address']);
    $email                  = s__($row['email']);
    $phone                  = s__($row['phone']);
    $mobile                 = s__($row['mobile']);

    $tax_treatment          = s__($row['tax_treatment']);
    $trn                    = s__($row['trn']);
    $license_number         = s__($row['license_number']);
    $license_expiry         = s__($row['license_expiry']);
    $license_expiry         = ($license_expiry == '1970-01-01' ? '' : processDateYtoD($license_expiry));

    $currency               = s__($row['currency']);
    $exchange_rate          = s__($row['exchange_rate']);

    $sales_person           = s__($row['sales_person']);
    $cs_agent               = s__($row['cs_agent']);
    $lead_category          = s__($row['lead_category']);
    $rating                 = s__($row['rating']);

    $contacted_date         = s__($row['contacted_date']);
    $contacted_date         = processDateTimeYtoD($contacted_date);

    $description            = s__($row['description']);

    // -- Tags
    $tags                   = s__($row['tags']);
    $tags_arr               = array();
    if ($tags != NULL) {
        $tags_arr               = explode(',', $tags);
    }

    $website                = s__($row['website']);
    $department             = s__($row['department']);
    $designation            = s__($row['designation']);
    $x                      = s__($row['x']);
    $facebook               = s__($row['facebook']);
    $instagram              = s__($row['instagram']);
    $publish                = s__($row['is_active']);
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
            <div class="my-1 d-flex align-items-center gap-2">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
                <span class="text-muted small">(<?php if ($publish == '1') { ?>Active<?php } else { ?>InActive<?php } ?>)</span>
            </div>

            <div class="my-1">
                <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                    <button type="submit" form="frm<?php echo $module; ?>" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <?php if (!empty($id)) { ?>
                    <a href="vendor_overview.php?vendor_id=<?php echo $id; ?>" class="btn btn-light btn-sm">Cancel</a>
                <?php } else { ?>
                    <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
                <?php } ?>
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

                <div class="row">

                    <?php //include('admin_elements/vendor_navbar.php'); 
                    ?>

                    <div class="col-lg-6">
                        <div class="card">


                            <div class="card-body">

                                <div class="row mb-2">
                                    <label class="col-lg-3 col-form-label">&nbsp;</label>
                                    <div class="col-lg-9">
                                        <div class="mt-2">
                                            <!-- <p class="fw-semibold">Type</p> -->
                                            <div class="form-check form-check-inline">
                                                <input type="radio" class="form-check-input" name="vendor_type" id="vendor_type" value="business" <?php if ($vendor_type == 'business') { ?>checked <?php } ?>>
                                                <label class="form-check-label">Business</label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input type="radio" class="form-check-input" name="vendor_type" id="vendor_type" value="individual" <?php if ($vendor_type == 'individual') { ?>checked <?php } ?>>
                                                <label class="form-check-label">Individual</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <label class="col-lg-3 col-form-label">Primary Contact: <i class="ph-info ms-2" data-bs-popup="tooltip" data-bs-placement="right" data-bs-original-salutation="The name you enter here will be for your primary contact. You can continue to add multiple contact persons from the details page"></i> </label>

                                    <div class="col-lg-3">
                                        <div class="mb-2">
                                            <select class="form-select" name="salutation" id="salutation">
                                                <option value="0"></option>
                                                <option value="mr." <?php if ($action == "edit_$module" && $salutation == 'mr.') { ?>selected <?php } else if ($salutation == 'mr.') { ?>selected <?php } ?>>Mr.</option>
                                                <option value="ms." <?php if ($action == "edit_$module" && $salutation == 'ms.') { ?>selected <?php } else if ($salutation == 'ms.') { ?>selected <?php } ?>>Ms.</option>
                                                <option value="mrs." <?php if ($action == "edit_$module" && $salutation == 'mrs.') { ?>selected <?php } else if ($salutation == 'mrs.') { ?>selected <?php } ?>>Mrs.</option>
                                                <option value="miss." <?php if ($action == "edit_$module" && $salutation == 'miss.') { ?>selected <?php } else if ($salutation == 'miss.') { ?>selected <?php } ?>>Miss.</option>
                                                <option value="dr." <?php if ($action == "edit_$module" && $salutation == 'dr.') { ?>selected <?php } else if ($salutation == 'dr.') { ?>selected <?php } ?>>Dr.</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-2">
                                            <!-- <label class="form-label">Care of:</label> -->
                                            <input type="text" class="form-control" name="first_name" id="first_name" value="<?php echo $first_name; ?>" placeholder="First Name">
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-2">
                                            <input type="text" class="form-control" name="last_name" id="last_name" value="<?php echo $last_name; ?>" placeholder="Last Name">
                                        </div>
                                    </div>

                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Company Name:*</span> <i class="ph-info ms-2" data-bs-popup="tooltip" data-bs-placement="right" data-bs-original-title="This name will be displayed on the transactions you create for this vendor"></i> </label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="display_name" id="display_name" value="<?php echo $display_name; ?>" class="form-control">
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Address:*</span> </label>
                                    <div class="col-lg-9">
                                        <input required type="text" name="address" id="address" value="<?php echo $address; ?>" class="form-control">
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-3 col-form-label">Email Address: <i class="ph-info ms-2" data-bs-popup="tooltip" data-bs-placement="right" data-bs-original-title="Privacy Info: This data will be stored without encryption and will be visible only to your organisation users who have the required permission."></i> </label>
                                    <div class="col-lg-9">
                                        <input type="email" name="email" id="email" value="<?php echo $email; ?>" class="form-control">
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-3 col-form-label">Phone: <i class="ph-info ms-2" data-bs-popup="tooltip" data-bs-placement="right" data-bs-original-title="Privacy Info: This data will be stored without encryption and will be visible only to your organisation users who have the required permission."></i> </label>

                                    <div class="col-lg-4">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ph-phone"></i></span>
                                            <input type="text" class="form-control" name="phone" id="phone" value="<?php echo $phone; ?>" placeholder="Work Phone">
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ph-device-mobile"></i></span>
                                            <input type="text" class="form-control" name="mobile" id="mobile" value="<?php echo $mobile; ?>" placeholder="Mobile">
                                        </div>
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-3 col-form-label">Contacted: </label>
                                    <div class="col-lg-4">
                                        <input type="text" name="contacted_date" id="contacted_date" value="<?php echo $contacted_date; ?>" class="form-control">
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-3 col-form-label">Description: </label>
                                    <div class="col-lg-9">
                                        <textarea class="form-control" name="description" id="description"><?php echo $description; ?></textarea>
                                    </div>
                                </div>


                                <div class="mb-2 row">
                                    <label class="col-lg-3 col-form-label">Tags: </label>
                                    <div class="col-lg-9">

                                        <select name="tags[]" id="tags[]" class="form-control select" multiple="multiple" data-tags="true">
                                            <?php
                                            // -------------------------------------------------------------------------------------------------
                                            $result_tags = $mysqli->query("SELECT * FROM `" . DB::TAXONOMIES  . "` WHERE is_active=1 AND type='vendor_tag' ORDER BY value");
                                            while ($rows_tags = $result_tags->fetch_array()) {
                                                // -------------------------------------------------------------------------------------------------
                                            ?>

                                                <option value="<?php echo $rows_tags['id']; ?>" <?php if ($action == "edit_$module" && in_array($rows_tags['id'], $tags_arr)) { ?>selected <?php } else if (in_array($rows_tags['id'], $posted_tags_arr)) { ?>selected <?php } ?>>
                                                    <?php echo $rows_tags['value']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>

                                    </div>
                                </div>

                                <div class="row">

                                    <div class="col-lg-4">
                                        <div class="mt-2">

                                            <label class="form-label">Status:</label>
                                            <select class="form-select" name="vendor_status" id="vendor_status">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_statuses = $mysqli->query("SELECT * FROM `" . DB::TAXONOMIES  . "` WHERE is_active=1 AND type='vendor_status' ORDER BY value");
                                                while ($rows_statuses = $result_statuses->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_statuses['id']; ?>" <?php if ($action == "edit_$module" && $rows_statuses['id'] == $vendor_status) { ?>selected <?php } else if ($rows_statuses['id'] == $vendor_status) { ?>selected <?php } ?>>
                                                        <?php echo $rows_statuses['value']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>

                                        </div>
                                    </div>


                                    <div class="col-lg-4">
                                        <div class="mt-2">

                                            <label class="form-label">Source:</label>
                                            <select class="form-select" name="vendor_source" id="vendor_source">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_sources = $mysqli->query("SELECT * FROM `" . DB::TAXONOMIES  . "` WHERE is_active=1 AND type='vendor_source' ORDER BY value");
                                                while ($rows_sources = $result_sources->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>

                                                    <option value="<?php echo $rows_sources['id']; ?>" <?php if ($action == "edit_$module" && $rows_sources['id'] == $vendor_source) { ?>selected <?php } else if ($rows_sources['id'] == $vendor_source) { ?>selected <?php } ?>>
                                                        <?php echo $rows_sources['value']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>

                                        </div>
                                    </div>


                                    <div class="col-lg-4">
                                        <div class="mt-2">

                                            <label class="form-label">Assigned To: </label>
                                            <select class="form-select" name="assigned_to" id="assigned_to">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_users = $mysqli->query("SELECT * FROM `" . DB::USERS  . "` WHERE is_active=1 ORDER BY full_name");
                                                while ($rows_users = $result_users->fetch_array()) {
                                                    // $assigned_to        = s__($rows_users['full_name']);
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_users['id']; ?>" <?php if ($action == "edit_$module" && $rows_users['id'] == $assigned_to) { ?>selected <?php } else if ($rows_users['id'] == $assigned_to) { ?>selected <?php } ?>>
                                                        <?php echo $rows_users['full_name']; ?>
                                                    </option>

                                                <?php
                                                }  // while
                                                ?>
                                            </select>

                                        </div>
                                    </div>


                                </div>

                                <!-- <div class="border-bottom-black border-bottom-lg">&nbsp;</div> -->


                            </div>




                        </div>
                    </div>


                    <div class="col-lg-3">
                        <div class="card">

                            <div class="card-header">
                                <span class="fw-semibold">Vendor Owner</span>
                            </div>

                            <div class="content clearfix">
                                <div class="row mb-2">
                                    <!-- <label class="col-lg-4 col-form-label">vendor Type:</label> -->
                                    <div class="col-lg-12">
                                        <div class="">
                                            <select class="form-select" name="vendor_owner" id="vendor_owner">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_users = $mysqli->query("SELECT * FROM `" . DB::USERS  . "` WHERE is_active=1 ORDER BY full_name");
                                                while ($rows_users = $result_users->fetch_array()) {
                                                    // $assigned_to        = s__($rows_users['full_name']);
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_users['id']; ?>" <?php if ($action == "edit_$module" && $rows_users['id'] == $vendor_owner) { ?>selected <?php } else if ($rows_users['id'] == $vendor_owner) { ?>selected <?php } ?>>
                                                        <?php echo $rows_users['full_name']; ?>
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


                        <div class="card">

                            <!-- <div class="card-header">
                                <h6 class="mb-0">Terms </h6>
                            </div> -->

                            <div class="card-body">

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Payment Terms: </label>
                                    <div class="col-lg-8">
                                        <select class="form-select" name="payment_term" id="payment_term">
                                            <!-- <option value='0'></option> -->
                                            <?php
                                            // -------------------------------------------------------------------------------------------------
                                            $result_payment_terms = $mysqli->query("SELECT * FROM `" . DB::PAYMENT_TERMS  . "` WHERE is_active=1 ORDER BY id ASC");
                                            while ($rows_payment_terms = $result_payment_terms->fetch_array()) {
                                                // $payment_terms        = s__($rows_payment_terms['payment_terms']);
                                                // -------------------------------------------------------------------------------------------------
                                            ?>
                                                <option value="<?php echo $rows_payment_terms['id']; ?>" <?php if ($action == "edit_$module" && $rows_payment_terms['id'] == $payment_term) { ?>selected <?php } else if ($rows_payment_terms['id'] == $payment_term) { ?>selected <?php } else if (empty($id) && $rows_payment_terms['payment_term'] == 'Due on Receipt') { ?>selected <?php } ?>>
                                                    <?php echo $rows_payment_terms['payment_term']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>

                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">TAX: </label>
                                    <div class="col-lg-8">
                                        <select class="form-select" name="tax_treatment" id="tax_treatment">
                                            <option value='0'></option>
                                            <?php
                                            // -------------------------------------------------------------------------------------------------
                                            $result_tax_treatment = $mysqli->query("SELECT * FROM `" . DB::TAX_TREATMENTS  . "` WHERE is_active=1 ORDER BY id ASC");
                                            while ($rows_tax_treatment = $result_tax_treatment->fetch_array()) {
                                                // $tax_treatment        = s__($rows_tax_treatment['tax_treatment']);
                                                // -------------------------------------------------------------------------------------------------
                                            ?>
                                                <option value="<?php echo $rows_tax_treatment['id']; ?>" <?php if ($action == "edit_$module" && $rows_tax_treatment['id'] == $tax_treatment) { ?>selected <?php } else if ($rows_tax_treatment['id'] == $tax_treatment) { ?>selected <?php } ?>>
                                                    <?php echo $rows_tax_treatment['tax_treatment']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>

                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">TRN #: </label>
                                    <div class="col-lg-8">
                                        <input type="text" name="trn" id="trn" value="<?php echo $trn; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">License #: </label>
                                    <div class="col-lg-8">
                                        <input type="text" name="license_number" id="license_number" value="<?php echo $license_number; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Expiry: </label>
                                    <div class="col-lg-8">
                                        <input type="text" name="license_expiry" id="license_expiry" value="<?php echo $license_expiry; ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Currency: </label>
                                    <div class="col-lg-8">
                                        <select class="form-select" name="currency" id="currency">
                                            <option value='0'></option>
                                            <?php
                                            // -------------------------------------------------------------------------------------------------
                                            $result_currency = $mysqli->query("SELECT * FROM `" . DB::CURRENCIES  . "` WHERE is_active=1 ORDER BY id ASC");
                                            while ($rows_currency = $result_currency->fetch_array()) {
                                                // $currency        = s__($rows_currency['currency']);
                                                // -------------------------------------------------------------------------------------------------
                                            ?>
                                                <option value="<?php echo $rows_currency['id']; ?>" <?php if ($action == "edit_$module" && $rows_currency['id'] == $currency) { ?>selected <?php } else if ($rows_currency['id'] == $currency) { ?>selected <?php } ?>>
                                                    <?php echo $rows_currency['currency']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-lg-4 col-form-label">Exchange Rate: </label>
                                    <div class="col-lg-8">
                                        <input type="text" name="exchange_rate" id="exchange_rate" value="<?php echo $exchange_rate; ?>" class="form-control">
                                    </div>
                                </div>


                            </div>



                        </div>


                    </div>


                    <div class="col-lg-3">

                        <div class="card">

                            <div class="card-header">
                                <span class="fw-semibold">Additional Information</span>
                            </div>


                            <div class="content clearfix">

                                <!-- Lead Category
                                CS Agent [dd sales employees]
                                Rating [None - 1 to 5] -->

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Sales Person:</label>
                                    <div class="col-lg-8">
                                        <select class="form-select" name="sales_person" id="sales_person">
                                            <option value='0'></option>
                                            <?php
                                            // -------------------------------------------------------------------------------------------------
                                            $result_users = $mysqli->query("SELECT * FROM `" . DB::USERS  . "` WHERE is_active=1 ORDER BY full_name");
                                            while ($rows_users = $result_users->fetch_array()) {
                                                // -------------------------------------------------------------------------------------------------
                                            ?>
                                                <option value="<?php echo $rows_users['id']; ?>" <?php if ($action == "edit_$module" && $rows_users['id'] == $sales_person) { ?>selected <?php } else if ($rows_users['id'] == $sales_person) { ?>selected <?php } ?>>
                                                    <?php echo $rows_users['full_name']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Lead Category:</label>
                                    <div class="col-lg-8">
                                        <select class="form-select" name="lead_category" id="lead_category">
                                            <option value='0'></option>

                                            <option value="lead" <?php if ($action == "edit_$module" && $lead_category == 'lead') { ?>selected <?php } else if ($lead_category == 'lead') { ?>selected <?php } ?>>Lead Vendor</option>

                                            <option value="direct" <?php if ($action == "edit_$module" && $lead_category == 'direct') { ?>selected <?php } else if ($lead_category == 'direct') { ?>selected <?php } ?>>Direct Vendor</option>

                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">CS Agent:</label>
                                    <div class="col-lg-8">
                                        <select class="form-select" name="cs_agent" id="cs_agent">
                                            <option value='0'></option>
                                            <?php
                                            // -------------------------------------------------------------------------------------------------
                                            $result_users = $mysqli->query("SELECT * FROM `" . DB::USERS  . "` WHERE is_active=1 ORDER BY full_name");
                                            while ($rows_users = $result_users->fetch_array()) {
                                                // -------------------------------------------------------------------------------------------------
                                            ?>
                                                <option value="<?php echo $rows_users['id']; ?>" <?php if ($action == "edit_$module" && $rows_users['id'] == $cs_agent) { ?>selected <?php } else if ($rows_users['id'] == $cs_agent) { ?>selected <?php } ?>>
                                                    <?php echo $rows_users['full_name']; ?>
                                                </option>

                                            <?php
                                            }  // while
                                            ?>
                                        </select>
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Rating:</label>
                                    <div class="col-lg-8">
                                        <select class="form-select" name="rating" id="rating">
                                            <option value='0'></option>
                                            <?php
                                            // ---------------------------
                                            for ($i = 1; $i <= 5; $i++) {
                                                // ---------------------------
                                            ?>
                                                <option value="<?php echo $i; ?>" <?php if ($action == "edit_$module" && $i == $rating) { ?>selected <?php } else if ($i == $rating) { ?>selected <?php } ?>>
                                                    <?php echo $i; ?>
                                                </option>

                                            <?php
                                            }  // for
                                            ?>
                                        </select>
                                    </div>
                                </div>


                                <div class="row mb-2 divider border-top"></div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Website:</label>
                                    <div class="col-lg-8">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ph-globe"></i></span>
                                            <input type="text" class="form-control" name="website" id="website" value="<?php echo $website; ?>" placeholder="https://www.example.com">
                                        </div>
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Department:</label>
                                    <div class="col-lg-8">
                                        <input type="text" class="form-control" name="department" id="department" value="<?php echo $department; ?>">
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Designation:</label>
                                    <div class="col-lg-8">
                                        <input type="text" class="form-control" name="designation" id="designation" value="<?php echo $designation; ?>">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">X(Twitter):</label>
                                    <div class="col-lg-8">
                                        <input type="text" class="form-control" name="x" id="x" value="<?php echo $x; ?>">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Facebook:</label>
                                    <div class="col-lg-8">
                                        <input type="text" class="form-control" name="facebook" id="facebook" value="<?php echo $facebook; ?>">
                                    </div>
                                </div>


                                <div class="row mb-2">
                                    <label class="col-lg-4 col-form-label">Instagram:</label>
                                    <div class="col-lg-8">
                                        <input type="text" class="form-control" name="instagram" id="instagram" value="<?php echo $instagram; ?>">
                                    </div>
                                </div>




                            </div>

                        </div>



            </form>
        </div></div>
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
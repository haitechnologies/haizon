<?php

/**
 * @db-table erp_customers
 * @db-table erp_contacts (shared: customers + vendors)
 * @db-table erp_addresses (shared: customers + vendors)
 * @org-scoped true
 * @permissions customers
 * @see src/Service/CustomerService.php
 * @see src/DataTable/CustomersDataTable.php
 */

use App\Core\DB;
$dashboardBodyClass = 'page-dashboard-form page-dashboard-customers';
include('admin_elements/admin_header.php');

$module                 = 'customers';
$module_caption         = 'Customer';

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

use App\Core\Container;
use App\Service\CustomerService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

$container = Container::getInstance();
$customerService = $container->get(CustomerService::class);

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in customers.php', 'WARNING', __FILE__, __LINE__);
    }
}

// print_r($_REQUEST);

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

if (isset($_POST['publish'])) {
    $is_active     = 1;
} else {
    $is_active     = 0;
}



$customer_type     = 'business';

if (isset($_POST['customer_type'])) {
    $customer_type     = e_s__($_POST['customer_type']);
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

    $customer_owner             = e_s__($_POST['customer_owner']);
    $payment_term               = e_s__($_POST['payment_term']);

    $customer_status            = e_s__($_POST['customer_status']);
    $customer_source            = e_s__($_POST['customer_source']);
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

    $customer_owner             = '';
    $payment_term               = '';

    $customer_status            = '';
    $customer_source            = '';
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
| 	UPDATE / ADD / EDIT (Modernized)
|--------------------------------------------------------------------------
*/
if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    try {
        $data = [
            'customer_owner' => $customer_owner,
            'payment_term' => $payment_term,
            'customer_status' => $customer_status,
            'customer_source' => $customer_source,
            'assigned_to' => $assigned_to,
            'customer_type' => $customer_type,
            'salutation' => $salutation,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name,
            'address' => $address,
            'email' => $email,
            'phone' => $phone,
            'mobile' => $mobile,
            'tax_treatment' => $tax_treatment,
            'trn' => $trn,
            'license_number' => $license_number,
            'license_expiry' => $license_expiry,
            'currency' => $currency,
            'exchange_rate' => $exchange_rate,
            'sales_person' => $sales_person,
            'cs_agent' => $cs_agent,
            'lead_category' => $lead_category,
            'rating' => $rating,
            'contacted_date' => $contacted_date,
            'description' => $description,
            'tags' => $tags_string,
            'website' => $website,
            'department' => $department,
            'designation' => $designation,
            'x' => $x,
            'facebook' => $facebook,
            'instagram' => $instagram,
            'is_active' => $is_active === 1,
        ];
        $customerService->updateCustomer((int)$id, $data, $activeOrganizationId, (int)$session_user_id);
        $success_message = "The $module_caption has been updated successfully.";
        fp__(DB::CUSTOMERS, $id);
        header("Location:customer_overview.php?customer_id=$id&success_message=$success_message");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    } catch (\Throwable $e) {
        $error_message = "The $module_caption could not be updated. Please try again.";
    }
} else if ($action == "add_$module" && granted('create', $module_id)) {
    try {
        $data = [
            'customer_owner' => $customer_owner,
            'payment_term' => $payment_term,
            'customer_status' => $customer_status,
            'customer_source' => $customer_source,
            'assigned_to' => $assigned_to,
            'customer_type' => $customer_type,
            'salutation' => $salutation,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name,
            'address' => $address,
            'email' => $email,
            'phone' => $phone,
            'mobile' => $mobile,
            'tax_treatment' => $tax_treatment,
            'trn' => $trn,
            'license_number' => $license_number,
            'license_expiry' => $license_expiry,
            'currency' => $currency,
            'exchange_rate' => $exchange_rate,
            'sales_person' => $sales_person,
            'cs_agent' => $cs_agent,
            'lead_category' => $lead_category,
            'rating' => $rating,
            'contacted_date' => $contacted_date,
            'description' => $description,
            'tags' => $tags_string,
            'website' => $website,
            'department' => $department,
            'designation' => $designation,
            'x' => $x,
            'facebook' => $facebook,
            'instagram' => $instagram,
            'is_active' => $is_active === 1,
        ];
        $newCustomer = $customerService->createCustomer($data, $activeOrganizationId, (int)$session_user_id);
        $id = $newCustomer->id;
        $success_message = "The $module_caption has been saved successfully.";
        fp__(DB::CUSTOMERS, $id);
        header("Location:customer_overview.php?customer_id=$id&success_message=$success_message");
        exit;
    } catch (ValidationException $e) {
        $error_message = current($e->getErrors());
    } catch (\Throwable $e) {
        $error_message = "The $module_caption could not be saved. Please try again.";
    }
}

if (!empty($id)) {
    try {
        $customerObj = $customerService->getCustomer((int)$id, $activeOrganizationId);

        $customer_owner         = (string)$customerObj->customerOwner;
        $payment_term           = (string)$customerObj->paymentTerm;
        $customer_status        = (string)$customerObj->customerStatus;
        $customer_source        = (string)$customerObj->customerSource;
        $assigned_to            = (string)$customerObj->assignedTo;
        $customer_type          = $customerObj->customerType;
        $salutation             = (string)$customerObj->salutation;
        $first_name             = (string)$customerObj->firstName;
        $last_name              = (string)$customerObj->lastName;
        $display_name           = $customerObj->displayName;
        $address                = $customerObj->address;
        $email                  = (string)$customerObj->email;
        $phone                  = (string)$customerObj->phone;
        $mobile                 = (string)$customerObj->mobile;
        $tax_treatment          = (string)$customerObj->taxTreatment;
        $trn                    = (string)$customerObj->trn;
        $license_number         = (string)$customerObj->licenseNumber;
        $license_expiry         = $customerObj->licenseExpiry === '1970-01-01' ? '' : processDateYtoD($customerObj->licenseExpiry);
        $currency               = (string)$customerObj->currency;
        $exchange_rate          = (string)$customerObj->exchangeRate;
        $sales_person           = (string)$customerObj->salesPerson;
        $cs_agent               = (string)$customerObj->csAgent;
        $lead_category          = (string)$customerObj->leadCategory;
        $rating                 = (string)$customerObj->rating;
        $contacted_date         = $customerObj->contactedDate ? processDateTimeYtoD($customerObj->contactedDate) : '';
        $description            = (string)$customerObj->description;

        $tags                   = (string)$customerObj->tags;
        $tags_arr               = [];
        if ($tags !== '') {
            $tags_arr           = explode(',', $tags);
        }

        $website                = (string)$customerObj->website;
        $department             = (string)$customerObj->department;
        $designation            = (string)$customerObj->designation;
        $x                      = (string)$customerObj->x;
        $facebook               = (string)$customerObj->facebook;
        $instagram              = (string)$customerObj->instagram;
        $is_active              = $customerObj->isActive ? 1 : 0;
    } catch (NotFoundException $e) {
        $error_message = $e->getMessage();
    }
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
                <?php if (!empty($id)) { ?>
                    <a href="customer_overview.php?customer_id=<?php echo $id; ?>" class="btn btn-light btn-sm">Cancel</a>
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
                <?php echo csrf_field(); ?>

                <div class="row">

                    <?php //include('admin_elements/customer_navbar.php');
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
                                                <input type="radio" class="form-check-input" name="customer_type" id="customer_type" value="business" <?php if ($customer_type == 'business') { ?>checked <?php } ?>>
                                                <label class="form-check-label">Business</label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input type="radio" class="form-check-input" name="customer_type" id="customer_type" value="individual" <?php if ($customer_type == 'individual') { ?>checked <?php } ?>>
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
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Company Name:*</span> <i class="ph-info ms-2" data-bs-popup="tooltip" data-bs-placement="right" data-bs-original-title="This name will be displayed on the transactions you create for this customer"></i> </label>
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
                                            $result_tags = $mysqli->query("SELECT * FROM `" . DB::TAXONOMIES  . "` WHERE is_active=1 AND type='customer_tag' ORDER BY value");
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
                                            <select class="form-select" name="customer_status" id="customer_status">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_statuses = $mysqli->query("SELECT * FROM `" . DB::TAXONOMIES  . "` WHERE is_active=1 AND type='customer_status' ORDER BY value");
                                                while ($rows_statuses = $result_statuses->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_statuses['id']; ?>" <?php if ($action == "edit_$module" && $rows_statuses['id'] == $customer_status) { ?>selected <?php } else if ($rows_statuses['id'] == $customer_status) { ?>selected <?php } ?>>
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
                                            <select class="form-select" name="customer_source" id="customer_source">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_sources = $mysqli->query("SELECT * FROM `" . DB::TAXONOMIES  . "` WHERE is_active=1 AND type='customer_source' ORDER BY value");
                                                while ($rows_sources = $result_sources->fetch_array()) {
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>

                                                    <option value="<?php echo $rows_sources['id']; ?>" <?php if ($action == "edit_$module" && $rows_sources['id'] == $customer_source) { ?>selected <?php } else if ($rows_sources['id'] == $customer_source) { ?>selected <?php } ?>>
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

                                <div class="row mt-3">
                                    <div class="col-lg-12">
                                        <div class="form-check form-switch mt-2">
                                            <input type="checkbox" class="form-check-input form-check-input-success" name="publish" id="publish" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                                            <label class="form-check-label fw-semibold" for="publish">Active Status</label>
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
                                <span class="fw-semibold">Customer Owner</span>
                            </div>

                            <div class="card-body">
                                <div class="row mb-2">
                                    <!-- <label class="col-lg-4 col-form-label">customer Type:</label> -->
                                    <div class="col-lg-12">
                                        <div class="">
                                            <select class="form-select" name="customer_owner" id="customer_owner">
                                                <option value='0'></option>
                                                <?php
                                                // -------------------------------------------------------------------------------------------------
                                                $result_users = $mysqli->query("SELECT * FROM `" . DB::USERS  . "` WHERE is_active=1 ORDER BY full_name");
                                                while ($rows_users = $result_users->fetch_array()) {
                                                    // $assigned_to        = s__($rows_users['full_name']);
                                                    // -------------------------------------------------------------------------------------------------
                                                ?>
                                                    <option value="<?php echo $rows_users['id']; ?>" <?php if ($action == "edit_$module" && $rows_users['id'] == $customer_owner) { ?>selected <?php } else if ($rows_users['id'] == $customer_owner) { ?>selected <?php } ?>>
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
                                <h2 class="mb-0">Terms </h2>
                            </div> -->

                            <div class="card-body">

                                <input type="hidden" name="payment_term" id="payment_term" value="0">


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


                            <div class="card-body">

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

                                            <option value="lead" <?php if ($action == "edit_$module" && $lead_category == 'lead') { ?>selected <?php } else if ($lead_category == 'lead') { ?>selected <?php } ?>>Lead Customer</option>

                                            <option value="direct" <?php if ($action == "edit_$module" && $lead_category == 'direct') { ?>selected <?php } else if ($lead_category == 'direct') { ?>selected <?php } ?>>Direct Customer</option>

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

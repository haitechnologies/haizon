<?php

include('admin_elements/admin_header.php');

use App\Core\Container;
use App\Service\CustomerService;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Core\Roles;

$container = Container::getInstance();
$customerService = $container->get(CustomerService::class);

$module = 'customer_addresses';
$module_caption = 'Shipping Address';
$tbl_name = $tbl_prefix . $module;
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
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in customer_shipping_addresses.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

// ------------------ CHECK IF CUSTOMER EXISTS ----------------
$customer_id = 0;
$id = 0;
if (isset($_REQUEST['customer_id'])) {
    $customer_id = (int)$_REQUEST['customer_id'];
}
if (isset($_POST['customer_id'])) {
    $customer_id = (int)$_POST['customer_id'];
}

if (!empty($customer_id)) {
    $id = $customer_id;
}


if ($id <= 0) {
    header("Location:listing_customers.php");
    exit;
}

try {
    $customerObj = $customerService->getCustomer((int)$id, $activeOrganizationId);
} catch (NotFoundException $e) {
    header("Location:listing_customers.php");
    exit;
}

//---------------
if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
    $id     = (int)$_REQUEST['id'];
}



/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module") {

    $shipping_attention      = e_s__($_POST['shipping_attention']);
    $shipping_country        = e_s__($_POST['shipping_country']);
    $shipping_address_line1  = e_s__($_POST['shipping_address_line1']);
    $shipping_address_line2  = e_s__($_POST['shipping_address_line2']);
    $shipping_city           = e_s__($_POST['shipping_city']);
    $shipping_state          = e_s__($_POST['shipping_state']);
    $shipping_zipcode        = e_s__($_POST['shipping_zipcode']);
    $shipping_phone          = e_s__($_POST['shipping_phone']);
    $shipping_fax            = e_s__($_POST['shipping_fax']);
} else {
    $shipping_attention      = '';
    $shipping_country        = '';
    $shipping_address_line1  = '';
    $shipping_address_line2  = '';
    $shipping_city           = '';
    $shipping_state          = '';
    $shipping_zipcode        = '';
    $shipping_phone          = '';
    $shipping_fax            = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($customer_id) && granted('edit', $module_id)) {
    try {
        $shipping_country = ((empty($shipping_country)) ? '0' : $shipping_country);
        $addresses = $customerService->getAddressesByCustomer((int)$customer_id, $activeOrganizationId);
        
        $existing = null;
        foreach ($addresses as $addr) {
            if ($addr->type === 'shipping') {
                $existing = $addr;
                break;
            }
        }

        if ($existing === null) {
            $customerService->createAddress([
                'customer_id' => $customer_id,
                'type' => 'shipping',
                'attention' => $shipping_attention,
                'country' => $shipping_country,
                'address_line1' => $shipping_address_line1,
                'address_line2' => $shipping_address_line2,
                'city' => $shipping_city,
                'state' => $shipping_state,
                'zipcode' => $shipping_zipcode,
                'phone' => $shipping_phone,
                'fax' => $shipping_fax
            ], $activeOrganizationId, $session_user_id);
        } else {
            $customerService->updateAddress($existing->id, [
                'attention' => $shipping_attention,
                'country' => $shipping_country,
                'address_line1' => $shipping_address_line1,
                'address_line2' => $shipping_address_line2,
                'city' => $shipping_city,
                'state' => $shipping_state,
                'zipcode' => $shipping_zipcode,
                'phone' => $shipping_phone,
                'fax' => $shipping_fax
            ], $activeOrganizationId, $session_user_id);
        }

        $success_message = "The $module_caption has been updated successfully.";
        header("Location:customer_overview.php?customer_id=$customer_id&success_message=" . urlencode($success_message));
        exit;
    } catch (ValidationException $e) {
        $error_message = implode(' ', $e->getErrors());
    } catch (\Throwable $e) {
        $error_message = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/

if ($action == "edit_$module" && !empty($customer_id)) {
    try {
        $addresses = $customerService->getAddressesByCustomer((int)$customer_id, $activeOrganizationId);
        $row_shipping = null;
        foreach ($addresses as $addr) {
            if ($addr->type === 'shipping') {
                $row_shipping = $addr;
                break;
            }
        }

        if ($row_shipping !== null) {
            $shipping_attention      = s__($row_shipping->attention);
            $shipping_country        = (string)$row_shipping->country;
            $shipping_address_line1  = s__($row_shipping->addressLine1);
            $shipping_address_line2  = s__($row_shipping->addressLine2);
            $shipping_city           = s__($row_shipping->city);
            $shipping_state          = s__($row_shipping->state);
            $shipping_zipcode        = s__($row_shipping->zipcode);
            $shipping_phone          = s__($row_shipping->phone);
            $shipping_fax            = s__($row_shipping->fax);
        }
    } catch (\Throwable $e) {
        $error_message = $e->getMessage();
    }
}
?>

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<aside class="sidebar sidebar-secondary sidebar-expand-lg" aria-label="Secondary Navigation">

    <!-- Expand button -->
    <button type="button" class="btn btn-sidebar-expand sidebar-control sidebar-secondary-toggle h-100">
        <i class="ph-caret-right"></i>
    </button>
    <!-- /expand button -->


    <!-- Sidebar content -->
    <?php include('admin_elements/sidebar_customer.php'); ?>
    <!-- /sidebar content -->

</aside>

<div class="content-wrapper">

    <!-- Inner content -->
    <div class="content-inner">

        <!-- Page header -->
        <?php include('admin_elements/page_header_customer.php'); ?>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="row">

                <div class="col-lg-6 col-xl-12">

                    <div class="card">

                        <div class="tab-content card-body">
                            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">

                                <div class="row">

                                    <?php include('admin_elements/sidebar_customer_overview.php'); ?>

                                    <div class="col-lg-8">

                                        <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="customer_shipping_addresses.php" autocomplete="off" enctype="multipart/form-data" novalidate>
                                            <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $customer_id; ?>" />
                                            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                                            <!-- <input type="idden" name="contact_id" id="contact_id" value="<?php echo $contact_id; ?>" /> -->
                                            <?php echo csrf_field(); ?>

                                            <span class="fw-semibold"><?php echo $module_caption; ?></span>

                                            <div class="card-body">

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Attention:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="shipping_attention" id="shipping_attention" value="<?php echo $shipping_attention; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Country:</label>
                                                    <div class="col-lg-9">
                                                        <select required class="form-select" name="shipping_country" id="shipping_country" aria-required="true">
                                                            <option value="0">Please select</option>
                                                            <?php echo getUAECountryDropdown($shipping_country); ?>
                                                        </select>
                                                        <div class="invalid-feedback">
                                                            Please select a country
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Address Line 1:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="shipping_address_line1" id="shipping_address_line1" value="<?php echo $shipping_address_line1; ?>" required aria-required="true">
                                                        <div class="invalid-feedback">
                                                            Address Line 1 is required
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Address Line 2:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="shipping_address_line2" id="shipping_address_line2" value="<?php echo $shipping_address_line2; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">City:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="shipping_city" id="shipping_city" value="<?php echo $shipping_city; ?>" required aria-required="true">
                                                        <div class="invalid-feedback">
                                                            City is required
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">State:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="shipping_state" id="shipping_state" value="<?php echo $shipping_state; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Zip Code:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="shipping_zipcode" id="shipping_zipcode" value="<?php echo $shipping_zipcode; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Phone:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="shipping_phone" id="shipping_phone" value="<?php echo $shipping_phone; ?>">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Fax Number:</label>
                                                    <div class="col-lg-9">
                                                        <input type="text" class="form-control" name="shipping_fax" id="shipping_fax" value="<?php echo $shipping_fax; ?>">
                                                    </div>
                                                </div>


                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">&nbsp;</label>
                                                    <div class="col-lg-9">
                                                        <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                                                            <button type="submit" class="btn btn-primary btn-sm my-1 me-2">Save</button>
                                                        <?php } ?>
                                                    </div>
                                                </div>


                                            </div>
                                        </form>

                                    </div>



                                </div>

                            </div>



                        </div>
                    </div>

                </div>
            </div>

        </div>


    </div>
    <!-- /content area -->

    <?php include('admin_elements/copyright.php'); ?>
</div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
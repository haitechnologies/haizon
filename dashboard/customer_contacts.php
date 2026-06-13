<?php


use App\Security\Roles;
include('admin_elements/admin_header.php');

use App\Core\Container;
use App\Service\CustomerService;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

$container = Container::getInstance();
$customerService = $container->get(CustomerService::class);

$module = 'customer_contacts';
$module_caption = 'Contact Person';
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
        log_error('CSRF token validation failed in customer_contacts.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

$customer_id = '';
if (isset($_REQUEST['customer_id']))        $customer_id     = e_s__($_REQUEST['customer_id']);
if (isset($_POST['customer_id']))           $customer_id     = e_s__($_POST['customer_id']);

try {
    $customerObj = $customerService->getCustomer((int)$customer_id, $activeOrganizationId);
} catch (NotFoundException $e) {
    header("Location:listing_customers.php");
    exit;
}

// IDOR PROTECTION: Verify access permission
$module_id = getModuleIdBySlug('customers', $mysqli);
if (!granted('view', $module_id)) {
    if ($_SESSION['h_role_id'] != Roles::SYSTEM_ADMIN) {
        $isOwner = (int)$customerObj->createdBy === (int)$session_user_id || (int)$customerObj->customerOwner === (int)$session_user_id;
        if (!$isOwner) {
            header("Location:listing_customers.php?error_message=Access denied");
            exit;
        }
    }
}

//---------------
$contact_id = 0;
if (isset($_REQUEST['contact_id']))        $contact_id     = e_s__($_REQUEST['contact_id']);
if (isset($_POST['contact_id']))           $contact_id     = e_s__($_POST['contact_id']);


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $first_name         = e_s__($_POST['first_name']);
    $last_name          = e_s__($_POST['last_name']);
    $position           = e_s__($_POST['position']);
    $email              = e_s__($_POST['email']);
    $phone              = e_s__($_POST['phone']);
    $notes              = e_s__($_POST['notes']);
} else {
    $first_name        = '';
    $last_name         = '';
    $position          = '';
    $email             = '';
    $phone             = '';
    $notes             = '';
}



/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($customer_id)) && granted('delete', $module_id)) {
    try {
        $contacts = $customerService->getContactsByCustomer((int)$customer_id, $activeOrganizationId);
        $targetContact = null;
        foreach ($contacts as $c) {
            if ($c->id === (int)$contact_id) {
                $targetContact = $c;
                break;
            }
        }
        if ($targetContact === null) {
            throw new NotFoundException("Contact not found.");
        }
        
        // Authorization check: Superadmin or owner who created the contact
        if (!Roles::hasFullAccess($session_role_id) && $targetContact->createdBy !== (int)$session_user_id) {
            throw new \Exception("Access denied.");
        }

        $customerService->deleteContact((int)$contact_id, $activeOrganizationId);
        $success_message = "$module_caption Deleted Successfully.";
    } catch (\Throwable $e) {
        $error_message = $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($customer_id) && granted('edit', $module_id)) {
    try {
        $customerService->updateContact((int)$contact_id, [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'position' => $position,
            'email' => $email,
            'phone' => $phone,
            'notes' => $notes
        ], $activeOrganizationId, $session_user_id);

        $success_message = "The $module_caption has been updated successfully.";
        header("Location:customer_overview.php?customer_id=$customer_id&success_message=" . urlencode($success_message));
        exit;
    } catch (ValidationException $e) {
        $error_message = implode(' ', $e->getErrors());
    } catch (\Throwable $e) {
        $error_message = $e->getMessage();
    }
} else if ($action == "add_$module" && granted('create', $module_id)) {
    try {
        $customerService->createContact([
            'customer_id' => $customer_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'position' => $position,
            'email' => $email,
            'phone' => $phone,
            'notes' => $notes,
            'is_primary' => true
        ], $activeOrganizationId, $session_user_id);

        $success_message = "The $module_caption has been saved successfully.";
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

if ($action == "edit_$module" && !empty($contact_id) && !empty($customer_id)) {
    try {
        $contacts = $customerService->getContactsByCustomer((int)$customer_id, $activeOrganizationId);
        $contactObj = null;
        foreach ($contacts as $c) {
            if ($c->id === (int)$contact_id) {
                $contactObj = $c;
                break;
            }
        }
        if ($contactObj === null) {
            throw new NotFoundException("Contact not found.");
        }

        $first_name         = s__($contactObj->firstName);
        $last_name          = s__($contactObj->lastName);
        $position           = s__($contactObj->position);
        $email              = s__($contactObj->email);
        $phone              = s__($contactObj->phone);
        $notes              = s__($contactObj->notes);
        $is_active          = s__($contactObj->isActive);
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

                                        <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
                                            <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $customer_id; ?>" />

                                            <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($customer_id)) { ?>
                                                <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
                                                <input type="hidden" name="contact_id" id="contact_id" value="<?php echo $contact_id; ?>" />
                                            <?php } else { ?>
                                                <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
                                            <?php } ?>
                                            <?php echo csrf_field(); ?>


                                            <span class="fw-semibold"><?php echo $module_caption; ?></span>

                                            <div class="card-body">

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label"><span class="text-danger">First Name:*</span></label>
                                                    <div class="col-lg-9">
                                                        <input required name="first_name" id="first_name" value="<?php echo $first_name; ?>" class="form-control" type="text">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Last Name:*</span></label>
                                                    <div class="col-lg-9">
                                                        <input required name="last_name" id="last_name" value="<?php echo $last_name; ?>" class="form-control" type="text">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Position: </label>
                                                    <div class="col-lg-9">
                                                        <input name="position" id="position" value="<?php echo $position; ?>" class="form-control" type="text">
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label"><span class="text-danger">Email:*</span></label>
                                                    <div class="col-lg-9">
                                                        <input required name="email" id="email" value="<?php echo $email; ?>" class="form-control" type="email">
                                                    </div>
                                                </div>


                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Phone:</label>
                                                    <div class="col-lg-9">
                                                        <input name="phone" id="phone" value="<?php echo $phone; ?>" class="form-control" type="text">
                                                        <div class="form-text text-muted"><small>+971 50 1234567</small></div>
                                                    </div>
                                                </div>


                                                <div class="row mb-2">
                                                    <label class="col-lg-3 col-form-label">Notes: </label>
                                                    <div class="col-lg-9">
                                                        <textarea class="form-control" name="notes" id="notes" style="field-sizing: content;"><?php echo $notes; ?></textarea>
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
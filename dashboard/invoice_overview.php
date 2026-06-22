<?php


use App\Core\DB;
use App\Security\Roles;
use App\Security\InputValidator;
include('admin_elements/admin_header.php');
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/InputValidator.php';
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/Roles.php';

$module = 'invoices';
$module_caption = 'Invoice';
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
        log_error('CSRF token validation failed in invoice_overview.php', 'WARNING', __FILE__, __LINE__);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

$invoice_id = '';
if (isset($_REQUEST['invoice_id']))        $invoice_id     = $_REQUEST['invoice_id'];
if (isset($_POST['invoice_id']))           $invoice_id     = $_POST['invoice_id'];
if (empty($invoice_id) && isset($_REQUEST['id'])) $invoice_id = e_s__($_REQUEST['id']);

// INPUT VALIDATION: Validate invoice_id
$invoiceIdResult = InputValidator::integer($invoice_id, 1);
if (!$invoiceIdResult['valid']) {
    flash_error('Invalid invoice ID: ' . $invoiceIdResult['error']);
    header("Location:listing_invoices.php");
    exit;
}
$invoice_id = $invoiceIdResult['value'];

try {
    $invoiceService = \App\Core\Container::getInstance()->get(\App\Service\InvoiceService::class);
    $invoice = $invoiceService->getInvoice((int)$invoice_id, $activeOrganizationId);

    // IDOR PROTECTION: Verify access permission
    // If user has 'view' permission for invoices module, allow viewing all invoices
    // Otherwise, only allow viewing invoices they own
    $module_id = getModuleIdBySlug('invoices', $mysqli);
    if (!granted('view', $module_id)) {
        if ($_SESSION['h_role_id'] != Roles::SYSTEM_ADMIN) {
            if ($invoice->createdBy !== (int)Session::userId()) {
                flash_error('Access denied');
                header("Location:listing_invoices.php");
                exit;
            }
        }
    }
} catch (\Throwable $e) {
    flash_error($e->getMessage());
    header("Location:listing_invoices.php");
    exit;
}

$is_active = $invoice->isActive ? 1 : 0;

$invoice_status_req = '';
if (isset($_REQUEST['invoice_status']) && !empty($_REQUEST['invoice_status'])) {
    $statusResult = InputValidator::enum($_REQUEST['invoice_status'], ['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled']);
    if ($statusResult['valid']) {
        $invoice_status_req = $statusResult['value'];
    }
}

if ($action == "convert_$module" && !empty($invoice_id)) {
    try {
        $newInvoice = $invoiceService->convertToInvoice((int)$invoice_id, $activeOrganizationId, (int)Session::userId());
        $success_message = 'This Invoice has been Converted to Invoice Successfully. Please click here to view. <a href="invoice_overview.php?invoice_id=' . $newInvoice->id . '"> ' . htmlspecialchars($newInvoice->invoiceNo) . '</a>';
        // Refresh the loaded invoice
        $invoice = $invoiceService->getInvoice((int)$invoice_id, $activeOrganizationId);
    } catch (\Throwable $e) {
        $error_message = $e->getMessage();
    }
} else if ($action == "clone_$module" && !empty($invoice_id)) {
    try {
        $newInvoice = $invoiceService->cloneInvoice((int)$invoice_id, $activeOrganizationId, (int)Session::userId());
        $success_message = 'Invoice has been cloned Successfully. Please click here to view. <a href="invoice_overview.php?invoice_id=' . $newInvoice->id . '"> ' . htmlspecialchars($newInvoice->invoiceNo) . '</a>';
        // Refresh the loaded invoice
        $invoice = $invoiceService->getInvoice((int)$invoice_id, $activeOrganizationId);
    } catch (\Throwable $e) {
        $error_message = $e->getMessage();
    }
} else if ($action == "update_$module" && !empty($invoice_id) && !empty($invoice_status_req)) {
    try {
        if ($invoiceService->updateStatus((int)$invoice_id, $invoice_status_req, $activeOrganizationId)) {
            $success_message = "The $module_caption status has been updated successfully.";
            // Refresh the loaded invoice
            $invoice = $invoiceService->getInvoice((int)$invoice_id, $activeOrganizationId);
        } else {
            $error_message = "Sorry! $module Status Could Not Be Updated.";
        }
    } catch (\Throwable $e) {
        $error_message = $e->getMessage();
    }
}




/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|
*/

$invoice_item_id_arr      = array();
$item_id_arr                = array();
$service_arr                = array();
$description_arr            = array();
$qty_arr                    = array();
$rate_arr                   = array();
$sub_total_arr              = array();
$tax_arr                    = array();
$tax_amount_arr             = array();
$total_arr                  = array();



if (isset($_POST['total_rows']) && !empty($_POST['total_rows'])) {
    $total_rows            = e_s__($_POST['total_rows']);
    // if ($total_rows == 0 || $total_rows == '') $total_rows = 1;
} else {
    $total_rows            = 1;
}



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
    <?php include('admin_elements/sidebar_invoice.php'); ?>
    <!-- /sidebar content -->

</aside>

<div class="content-wrapper">

    <!-- Inner content -->
    <div class="content-inner">

        <!-- Page header -->
        <?php include('admin_elements/page_header_invoice.php'); ?>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <?php
            // $invoice_id = '';
            // if (isset($_REQUEST['invoice_id']))        echo $invoice_id     = e_s__($_REQUEST['invoice_id']);
            // if (isset($_POST['invoice_id']))           echo $invoice_id     = e_s__($_POST['invoice_id']);

            /*
                |--------------------------------------------------------------------------
                | EDIT
                |--------------------------------------------------------------------------
                |
                */
            if (!empty($invoice_id)) {
                $customer_id            = $invoice->customerId;
                $warehouse_id           = $invoice->warehouseId;

                $invoice_no             = $invoice->invoiceNo;
                $invoice_status         = $invoice->invoiceStatus;
                $invoice_date           = $invoice->invoiceDate;
                $expiry_date            = $invoice->expiryDate;

                $reference_no           = $invoice->referenceNo;

                $expected_shipment_date = $invoice->expectedShipmentDate;
                $payment_term           = getTableAttr('payment_term', DB::CUSTOMERS, $customer_id);

                $shipment_type          = $invoice->shipmentType;
                $sales_person           = $invoice->salesPerson;
                $job_reference_no       = $invoice->jobReferenceNo;
                $master_awb_no          = $invoice->masterAwbNo;
                $shipper                = $invoice->shipper;
                $consignee              = $invoice->consignee;
                $origin                 = $invoice->origin;
                $destination            = $invoice->destination;
                $no_of_packs            = $invoice->noOfPacks;
                $gross_weight           = $invoice->grossWeight;
                $chargeable_weight      = $invoice->chargeableWeight;
                $volume                 = $invoice->volume;

                $customer_notes         = $invoice->customerNotes;
                $terms_and_conditions   = $invoice->termsAndConditions;
                // Seprate Line Number on base of Space new line
                $final_terms_and_conditions = '';

                if (!empty($terms_and_conditions)) {
                    $desc = explode("\r", $terms_and_conditions);
                    $d_counter = 1;
                    if (count($desc) > 0) {
                        foreach ($desc as $d) {
                            if (!empty($d)) {
                                // $final_terms_and_conditions .= $d_counter++ . '. ' . $d . '<br />';
                                $final_terms_and_conditions .= $d . '<br />';
                            }
                        }
                    }
                }

                $grand_subtotal             = $invoice->grandSubtotal;
                $grand_discount_type        = $invoice->grandDiscountType;
                $grand_discount_type_value  = $invoice->grandDiscountTypeValue;
                $grand_discount_amount      = $invoice->grandDiscountAmount;
                $grand_after_discount       = $invoice->grandAfterDiscount;
                $grand_tax                  = $invoice->grandTax;
                $grand_total                = $invoice->grandTotal;

                $is_active = $invoice->isActive ? 1 : 0;

                // --- Customer Information
                $customerRepo = \App\Core\Container::getInstance()->get(\App\Repository\CustomerRepository::class);
                $customer = $customerRepo->find($customer_id, $activeOrganizationId);
                if ($customer === null) {
                    flash_error('Customer not found');
                    header("Location:listing_invoices.php");
                    exit;
                }
                $salutation             = s__($customer->salutation);
                $first_name             = s__($customer->firstName);
                $last_name              = s__($customer->lastName);
                $company_name           = s__($customer->companyName);
                $display_name           = s__($customer->displayName);
                $email                  = s__($customer->email);
                $phone                  = s__($customer->phone);
                $mobile                 = s__($customer->mobile);
                $trn                    = s__($customer->trn);

                $db = \App\Core\Container::getInstance()->get(\App\Core\Database::class);
                $row_billing = $db->fetchOne("SELECT * FROM `" . DB::CUSTOMER_ADDRESSES . "` WHERE addressable_type = 'Customer' AND addressable_id = :customer_id AND type = 'billing' AND organization_id = :org_id", [
                    'customer_id' => $customer_id,
                    'org_id' => $activeOrganizationId
                ]);

                $billing_attention      = (!empty($row_billing['attention']) ? s__($row_billing['attention']) : '');
                $billing_country        = (!empty($row_billing['country']) ? s__($row_billing['country']) : '');
                $billing_address_line1  = (!empty($row_billing['address_line1']) ? s__($row_billing['address_line1']) : '');
                $billing_address_line2  = (!empty($row_billing['address_line2']) ? s__($row_billing['address_line2']) : '');
                $billing_city           = (!empty($row_billing['city']) ? s__($row_billing['city']) : '');
                $billing_state          = (!empty($row_billing['state']) ? s__($row_billing['state']) : '');
                $billing_zipcode        = (!empty($row_billing['zipcode']) ? s__($row_billing['zipcode']) : '');
                $billing_phone          = (!empty($row_billing['phone']) ? s__($row_billing['phone']) : '');
                $billing_fax            = (!empty($row_billing['fax']) ? s__($row_billing['fax']) : '');

                $invoice_date         = processDateYtoD($invoice_date);
                $expiry_date            = ($expiry_date === '1970-01-01' || empty($expiry_date)) ? '' : processDateDtoY($expiry_date);
                $expected_shipment_date = ($expected_shipment_date === '1970-01-01' || empty($expected_shipment_date)) ? '' : processDateDtoY($expected_shipment_date);

                // ------------------ TOTAL INVOICES ITEMS ------------------
                $invoice_items = $invoiceService->getInvoiceItems($invoice_id, $activeOrganizationId);
                $total_rows = count($invoice_items);

                if ($total_rows > 0) {
                    foreach ($invoice_items as $item) {
                        array_push($invoice_item_id_arr,      $item->id);
                        array_push($service_arr,                $item->service);
                        array_push($description_arr,            $item->description);
                        array_push($qty_arr,                    $item->qty);
                        array_push($rate_arr,                   $item->rate);
                        array_push($sub_total_arr,              $item->subTotal);
                        array_push($tax_arr,                    $item->tax);
                        array_push($tax_amount_arr,             $item->taxAmount);
                        array_push($total_arr,                  $item->total);
                    }
                }
            }


            if ($total_rows == 0)           $total_rows = 1;

            ?>

            <?php
            // Journal features removed (tables not available)
            $has_void_entry = false;
            $has_writeoff_entry = false;
            
            ?>
            
            <!-- VOID/WRITEOFF STATUS ALERTS -->
            <?php if ($has_void_entry) { ?>
            <div class="row">
                <div class="row p-lg-2">
                    <div class="col-lg-1"></div>
                    <div class="col-lg-10">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <span class="fw-semibold"><i class="ph-warning-circle me-2"></i>VOIDED INVOICE</span>
                            <p class="mb-0">This invoice has been voided. All accounting entries have been reversed. No further transactions can be recorded against this invoice.</p>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
            
            <?php if ($has_writeoff_entry) { ?>
            <div class="row">
                <div class="row p-lg-2">
                    <div class="col-lg-1"></div>
                    <div class="col-lg-10">
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <span class="fw-semibold"><i class="ph-warning-circle me-2"></i>WRITTEN OFF INVOICE</span>
                            <p class="mb-0">This invoice has been written off as bad debt. The outstanding balance has been removed from Accounts Receivable and recorded as Bad Debt Expense. No further payments or transactions can be recorded against this invoice.</p>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="row">

                <div class="row p-lg-2">

                    <div class="col-lg-1">
                    </div>


                    <div class="card col-lg-10">
                        <div class="card-body">
                            <div class="row">

                                <div class="col-sm-6">
                                    <div class="mb-4">

                                        <span class="text-muted">Invoice To:</span>
                                        <ul class="list list-unstyled mb-0">
                                            <li>
                                                <h5 class="my-2"><a href="customer_overview.php?customer_id=<?php echo $customer_id; ?>"><?php echo $display_name; ?></a></h5>
                                            </li>
                                            <li><span class="fw-semibold"><?php echo $company_name; ?></span></li>
                                            <li><?php echo $billing_attention; ?></li>
                                            <li><?php echo $billing_country; ?></li>
                                            <li><?php echo $billing_address_line1; ?></li>
                                            <li><?php echo $billing_address_line2; ?></li>
                                            <li><?php echo $billing_city; ?></li>
                                            <li><?php echo $billing_state; ?></li>
                                            <li><?php echo $billing_zipcode; ?></li>
                                            <li><?php echo $billing_phone; ?></li>
                                            <li><?php echo $billing_fax; ?></li>
                                        </ul>

                                    </div>
                                </div>

                                <?php
                                $warehouse_information = '';
                                $row_warehouse = $db->fetchOne("SELECT * FROM `erp_organizations` WHERE id = :id", ['id' => $warehouse_id]);

                                $warehouse_no       = s__($row_warehouse['warehouse_no'] ?? '');
                                $warehouse_name     = s__($row_warehouse['warehouse_name'] ?? '');
                                $street1            = s__($row_warehouse['street1'] ?? '');
                                $street2            = s__($row_warehouse['street2'] ?? '');

                                $country            = s__($row_warehouse['country'] ?? '');
                                $country            = getTableAttr('country', DB::GEO_COUNTRIES, $country);

                                $state              = s__($row_warehouse['state'] ?? '');
                                $state            = getTableAttr('state_name', DB::GEO_STATES, $state);

                                $phone              = s__($row_warehouse['phone'] ?? '');
                                $email              = s__($row_warehouse['email'] ?? '');
                                $trn                = s__($row_warehouse['trn'] ?? '');

                                $warehouse_information .= (!empty($warehouse_name) ? '<strong>' . $warehouse_name . '</strong><br />' : '');
                                $warehouse_information .= (!empty($warehouse_no) ? $warehouse_no . '<br />' : '');
                                $warehouse_information .= (!empty($street1) ? $street1 . '<br />' : '');
                                $warehouse_information .= (!empty($street2) ? $street2 . '<br />' : '');
                                $warehouse_information .= (!empty($state) ? $state . ', ' : '');
                                $warehouse_information .= (!empty($country) ? $country . '<br />' : '');
                                $warehouse_information .= (!empty($phone) ? $phone . '<br />' : '');
                                $warehouse_information .= (!empty($email) ? $email . '<br />' : '');
                                $warehouse_information .= (!empty($trn) ? $trn : '');
                                ?>
                                <div class="col-sm-6">
                                    <div class="text-sm-end mb-4">
                                        <?php echo $warehouse_information; ?>
                                        <h6 class="text-primary mb-2 mt-lg-2">Invoice #<?php echo $invoice_no; ?></h6>
                                        <ul class="list list-unstyled mb-0">
                                            <li>Date: <span class="fw-semibold"><?php echo $invoice_date; ?></span></li>

                                            <?php
                                            // Calculate Overdue
                                            $payment_term           = getTableAttr('payment_term', DB::CUSTOMERS, $customer_id);
                                            $payment_term_duration  = '';
                                            $display_due_days = '';
                                            if ($invoice_status === 'sent') {
                                                $due_date = calculateInvoiceDueDate($invoice_status, $invoice_date, $payment_term_duration);
                                                if (!empty($due_date)) {
                                                    $today = new DateTime();
                                                    $today->setTime(0, 0, 0);
                                                    $due = new DateTime($due_date);
                                                    $due->setTime(0, 0, 0);

                                                    if ($today > $due) {
                                                        $days_overdue = $due->diff($today)->days;
                                                        $display_due_days = '<li><span class="text-danger">OVERDUE BY ' . $days_overdue . ' DAYS</span></li>';
                                                    } else if ($today == $due) {
                                                        $display_due_days = '<li><span class="text-warning">DUE TODAY</span></li>';
                                                    } else {
                                                        $days_remaining = $today->diff($due)->days;
                                                        $display_due_days = '<li><span class="text-info">DUE IN ' . $days_remaining . ' DAYS</span></li>';
                                                    }
                                                }
                                            }

                                            echo $display_due_days;
                                            ?>

                                            <?php $display_due_date = calculateInvoiceDueDate($invoice_status, $invoice_date, $payment_term_duration); ?>
                                            <li>Due date: <span class="fw-semibold"><?php echo (!empty($display_due_date) ? dd_($display_due_date) : ''); ?></span></li>

                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="d-lg-flex flex-lg-wrap">

                                <div class="col-sm-6">
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Expected Shipment Date:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo $expected_shipment_date; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Delivery Method:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo $shipment_type; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Job Reference No:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo $job_reference_no; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Shipper:</label>
                                        <div class="col-lg-7 mt-2">
                                            <!-- COMMENTED: DB::SHIPPERS table has been deleted -->
                                            <?php echo ''; /* getTableAttr('shipper_name', DB::SHIPPERS, $shipper); */ ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Origin:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo getTableAttr('alpha3_code', DB::GEO_COUNTRIES, $origin); ?> - <?php echo getTableAttr('country', DB::GEO_COUNTRIES, $origin); ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">No of Packs:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo $no_of_packs; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Chargeable Weight:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo $chargeable_weight; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Payment Terms:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo ''; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Sales Person:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo getTableAttr('warehouse_name', DB::ORGANIZATIONS, $sales_person); ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Master AWB No:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo $master_awb_no; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Consignee:</label>
                                        <div class="col-lg-7 mt-2">
                                            <!-- COMMENTED: DB::CONSIGNEES table has been deleted -->
                                            <?php echo ''; /* getTableAttr('consignee_name', DB::CONSIGNEES, $consignee); */ ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Destination:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo getTableAttr('alpha3_code', DB::GEO_COUNTRIES, $destination); ?> - <?php echo getTableAttr('country', DB::GEO_COUNTRIES, $destination); ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Gross Weight:</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo $gross_weight; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-lg-5 col-form-label">Volume (CBM):</label>
                                        <div class="col-lg-7 mt-2">
                                            <?php echo $volume; ?>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-lg">
                                <thead>
                                    <tr>
                                        <th>ITEM DETAILS</th>
                                        <th>DESCRIPTION</th>
                                        <th class="text-center">QUANTITY</th>
                                        <th class="text-center">RATE</th>
                                        <th class="text-center">SUBTOTAL</th>
                                        <th class="text-center">TAX</th>
                                        <th class="text-center">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- <tr>
                                        <td>
                                            <div class="fw-bold">Create UI design model</div>
                                            <span class="text-muted">One morning, when Gregor Samsa woke from troubled.</span>
                                        </td>
                                        <td>$70</td>
                                        <td>57</td>
                                        <td><span class="fw-semibold">$3,990</span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">Support tickets list doesn't support commas</div>
                                            <span class="text-muted">I'd have gone up to the boss and told him just what i think.</span>
                                        </td>
                                        <td>$70</td>
                                        <td>12</td>
                                        <td><span class="fw-semibold">$840</span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">Fix website issues on mobile</div>
                                            <span class="text-muted">I am so happy, my dear friend, so absorbed in the exquisite.</span>
                                        </td>
                                        <td>$70</td>
                                        <td>31</td>
                                        <td><span class="fw-semibold">$2,170</span></td>
                                    </tr> -->

                                    <?php
                                    /*
                                    |--------------------------------------------------------------------------------------------------------------------------------|
                                    |------------------------------------------------------ invoice ITEMS  ----------------------------------------------------------|
                                    |--------------------------------------------------------------------------------------------------------------------------------|
                                    */
                                    // echo $total_rows;

                                    for ($invoice_item = 1; $invoice_item <= $total_rows; $invoice_item++) {
                                        $index = $invoice_item;
                                        $index = $index - 1;

                                        // $fee_included = '';
                                        // if (isset($fee_included_arr[$index]) && !empty($fee_included_arr[$index])) {
                                        //     $fee_included    = $fee_included_arr[$index];
                                        // }

                                        // $requested_date_time = '';
                                        // if (isset($requested_date_time_arr[$index]) && !empty($requested_date_time_arr[$index])) {
                                        //     $requested_date_time    = $requested_date_time_arr[$index];
                                        //     $requested_date_time    = date('j F Y | H:i', strtotime($requested_date_time)); // Remove Seconds
                                        // }


                                        //--------------------------------------------------------------------------------------------------------------------------------|
                                        $invoice_item_id                = $invoice_item_id_arr[$index];
                                        // $invoice_requested_date         = date('j F Y', strtotime($requested_date_arr[$index]));
                                        // $invoice_itn_stops        = process_stops(getTableAttr("stop_name", tbl_stops, $itn_arr[$index]));
                                        // $invoice_itn_stops              = process_stops($itn_arr[$index]);
                                        // $invoice_vehicle_type           = getTableAttr('vehicle_type', tbl_vehicle_types, $vehicle_type_arr[$index]);

                                        //--------------------------------------------------------------------------------------------------------------------------------|
                                    ?>

                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo getTableAttr('item_name', DB::ITEMS, $service_arr[$index]); ?></div>
                                                <span class="text-muted">
                                                    <?php
                                                    // ----------------------------------------------
                                                    // Seprate Line Number on base of Space new line
                                                    // ----------------------------------------------
                                                    $desc = explode("\r", $description_arr[$index]);
                                                    // print_r($desc);
                                                    $d_counter = 1;
                                                    if (count($desc) > 0) {
                                                        foreach ($desc as $d) {
                                                            if (!empty($d)) {
                                                                echo $d_counter++ . '. ' . $d;
                                                                echo '<br />';
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo $description_arr[$index]; ?></td>
                                            <td class="text-center"><?php echo $qty_arr[$index]; ?></td>
                                            <td class="text-end"><?php echo $rate_arr[$index]; ?></td>
                                            <td class="text-end"><?php echo $sub_total_arr[$index]; ?></td>
                                            <td class="text-end"><?php echo $tax_arr[$index]; ?>% (<?php echo $tax_amount_arr[$index]; ?>)</td>
                                            <td class="text-end"><span class="fw-semibold"><?php echo $total_arr[$index]; ?></span></td>
                                        </tr>
                                    <?php
                                    } // for
                                    /*
                                    |--------------------------------------------------------------------------------------------------------------------------------
                                    |--------------------------------------------------------------------------------------------------------------------------------
                                    |--------------------------------------------------------------------------------------------------------------------------------
                                    */
                                    ?>


                                </tbody>
                            </table>
                        </div>

                        <div class="card-body border-top">
                            <div class="d-lg-flex flex-lg-wrap">

                                <div class="pt-2 mb-3">
                                    <ul class="list-unstyled text-muted">
                                        <li class="mb-3">Customer Notes: <br /><?php echo $customer_notes; ?></li>
                                        <li class="mb-3"><span class="fw-semibold">Terms and Conditions: </span> <br /><?php echo $final_terms_and_conditions; ?></li>
                                    </ul>
                                </div>

                                <div class="pt-2 mb-3 wmin-lg-400 ms-auto">
                                    <!-- <h6 class="mb-3">Total due</h6> -->
                                    <div class="table-responsive">
                                        <table class="table">
                                            <tbody>
                                                <tr>
                                                    <td>Grand Subtotal:</td>
                                                    <td class="text-end"><?php echo $grand_subtotal; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Discount Type: <?php echo $grand_discount_type; ?></td>
                                                    <td class="text-end"><?php echo $grand_discount_type_value; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Discount Amount: </td>
                                                    <td class="text-end"><?php echo $grand_discount_amount; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Subtotal: (Discounted): </td>
                                                    <td class="text-end"><?php echo $grand_after_discount; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Total Tax Amount:</td>
                                                    <td class="text-end"><?php echo $grand_tax; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Grand Total:</td>
                                                    <td class="text-end text-primary">
                                                        <h5 class="fw-semibold"><?php echo $grand_total; ?></h5>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- <div class="text-end mt-3">
                                        <button type="button" class="btn btn-primary">
                                            Send invoice
                                            <i class="ph-paper-plane-tilt ms-2"></i>
                                        </button>
                                    </div> -->

                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <span class="text-muted">Thank you for your Business.</span>
                        </div>
                    </div>


                    <div class="col-lg-1">
                    </div>

                </div>


                <?php
                // ---------------------------------------------------------------------------------------------------------------------------------------
                // Journal entries disabled (tables removed)
                $has_journal_entries = false;
                // ---------------------------------------------------------------------------------------------------------------------------------------

                if (false) {
                ?>

                    <p class="mb-0 opacity-50" id="journal">JOURNAL</p>
                    
                    <?php
                    // Loop through each journal entry (original, void, and write-off)
                    while ($journal_row = $journal_result->fetch_assoc()) {
                        $journal_id = $journal_row['id'];
                        $reference_type = $journal_row['reference_type'];
                        $journal_date = $journal_row['journal_date'];
                        $is_void = ($reference_type === 'invoice_void');
                        $is_writeoff = ($reference_type === 'invoice_writeoff');
                    ?>
                    
                    <div class="card <?php echo $is_void ? 'border-danger' : ($is_writeoff ? 'border-warning' : ''); ?>">
                        <div class="card-header d-flex align-items-center <?php echo $is_void ? 'bg-danger bg-opacity-10' : ($is_writeoff ? 'bg-warning bg-opacity-10' : ''); ?>">
                            <?php if ($is_void) { ?>
                                <span class="badge bg-danger me-2">VOID ENTRY</span>
                                <span class="text-muted small">Reversing Entry - <?php echo dd_($journal_date, 'd M Y'); ?></span>
                            <?php } elseif ($is_writeoff) { ?>
                                <span class="badge bg-warning me-2">WRITE-OFF ENTRY</span>
                                <span class="text-muted small">Bad Debt Expense - <?php echo dd_($journal_date, 'd M Y'); ?></span>
                            <?php } else { ?>
                                <span class="text-muted small">Original Entry - <?php echo dd_($journal_date, 'd M Y'); ?></span>
                            <?php } ?>

                            <div class="ms-auto small text-muted">
                                Amount is displayed in your base currency <span class="badge bg-success"><?php echo BASE_CURRENCY['code']; ?></span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="opacity-50">ACCOUNT</th>
                                        <th class="text-end opacity-50">DEBIT</th>
                                        <th class="text-end opacity-50">CREDIT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_debit = 0;
                                    $total_credit = 0;

                                    //-------------------------------------------------------------------
                                    // -------- JOURNAL ENTRIES 
                                    //-------------------------------------------------------------------

                                    $result_journal_items = $mysqli->query("SELECT * FROM `" . $journal_items_table . "` WHERE journal_id=$journal_id");
                                    while ($row_journal_items = $result_journal_items->fetch_array()) {

                                        $account    = $row_journal_items['account'];
                                        $debit      = $row_journal_items['debit'];
                                        $credit     = $row_journal_items['credit'];

                                        $total_debit += $debit;
                                        $total_credit += $credit;
                                    ?>
                                        <tr>
                                            <td><?php echo $account; ?></td>
                                            <td class="text-end"><?php echo number_format($debit, 2); ?></td>
                                            <td class="text-end"><?php echo number_format($credit, 2); ?></td>
                                        </tr>
                                    <?php } ?>
                                    <tr>
                                        <td class="fw-semibold">TOTAL</td>
                                        <td class="text-end fw-semibold"><?php echo number_format($total_debit, 2); ?></td>
                                        <td class="text-end fw-semibold"><?php echo number_format($total_credit, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php } // End loop through journal entries ?>
                    
                <?php }  // JOURNAL 
                ?>

            </div>

        </div>


    </div>
    <!-- /content area -->

    <?php include('admin_elements/copyright.php'); ?>
</div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
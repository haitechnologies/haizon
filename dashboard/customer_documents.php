<?php

include('admin_elements/admin_header.php');

$module             = 'customer_documents';
$module_caption     = 'Customer Document';
$error_message      = '';
$success_message    = '';

$action             = isset($_GET['action']) ? e_s__($_GET['action']) : '';
$id                 = isset($_GET['id']) ? intval($_GET['id']) : '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
    }
}

if (isset($_POST['is_active']))       $is_active = 1;
else                                   $is_active = 0;

if ($action == "update_$module" || $action == "add_$module") {
    $customer_id        = intval($_POST['customer_id'] ?? 0);
    $document_name      = e_s__($_POST['document_name'] ?? '');
    $document_filename  = e_s__($_POST['document_filename'] ?? '');
    $issued_date        = e_s__($_POST['issued_date'] ?? '');
    $expiry_date        = e_s__($_POST['expiry_date'] ?? '');
} else {
    $customer_id        = '';
    $document_name      = '';
    $document_filename  = '';
    $issued_date        = '';
    $expiry_date        = '';
}

if ($action == "update_$module" && !empty($id) && granted('edit', $module_id)) {
    $error_message = 'Customer documents feature has been decommissioned.';

} else if ($action == "add_$module" && granted('create', $module_id)) {
    $error_message = 'Customer documents feature has been decommissioned.';
}

$row = null;
if (!empty($id)) {
    // erp_customer_documents table dropped — no data to load
}

?>

<div class="content-wrapper">
    <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <div class="page-header page-header-light shadow">
            <div class="page-header-content d-lg-flex border-top">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <h5 class="ms-2"><?php echo (($action == "edit_$module" || $action == "update_$module") && !empty($id)) ? 'Edit' : 'New'; ?> <?php echo $module_caption; ?></h5>
                    </div>
                </div>

                <div class="p-3 rounded mt-1">
                    <div class="form-check form-check-inline form-switch me-3">
                        <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>

                <div class="collapse d-lg-block ms-lg-auto">
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

        <div class="content-inner">
            <div class="content">
                <?php include('admin_elements/breadcrumb.php'); ?>

                <div class="card">
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Customer:*</span></label>
                            <div class="col-lg-9">
                                <select class="form-select" name="customer_id" id="customer_id" required>
                                    <option value="">-- Select Customer --</option>
                                    <?php
                                    $cust_result = $mysqli->query("SELECT id, company_name FROM `" . DB::CUSTOMERS . "` WHERE is_active=1 ORDER BY company_name LIMIT 500");
                                    while ($cust_row = $cust_result->fetch_assoc()) {
                                        $selected = ($customer_id == $cust_row['id']) ? 'selected' : '';
                                        echo '<option value="' . $cust_row['id'] . '" ' . $selected . '>' . $cust_row['company_name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">Document Name:*</span></label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="document_name" id="document_name" placeholder="e.g., Passport, License" value="<?php echo $document_name; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Document File:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" name="document_filename" id="document_filename" placeholder="Filename or path" value="<?php echo $document_filename; ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Issued Date:</label>
                            <div class="col-lg-9">
                                <input type="date" class="form-control" name="issued_date" id="issued_date" value="<?php echo $issued_date; ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-3 col-form-label">Expiry Date:</label>
                            <div class="col-lg-9">
                                <input type="date" class="form-control" name="expiry_date" id="expiry_date" value="<?php echo $expiry_date; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

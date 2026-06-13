<?php
include('admin_elements/admin_header.php');

$module = 'customer_statement';
$module_caption = 'Customer Statement';
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed', 'WARNING', __FILE__, __LINE__);
    }
}

include('admin_elements/breadcrumb.php');
?>

<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <h1><?php echo e($module_caption); ?></h1>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>View Customer Statement</h5>
                </div>
                <div class="card-body">
                    <p>Select a customer to view their account statement.</p>
                </div>
            </div>
        </div>
    </div>

    <table id="grid-<?php echo e($module); ?>" class="custom_datatables">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Balance</th>
                <th>Last Transaction</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
    <?php include('admin_elements/copyright.php'); ?>
</div>
<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    $('#grid-<?php echo e($module); ?>').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: {
                ajax_action: 'listing_<?php echo e($module); ?>',
                csrf_token: $('input[name="csrf_token"]').val()
            }
        },
        columns: [
            {data: 'customer'},
            {data: 'balance'},
            {data: 'last_transaction'},
            {data: 'actions', orderable: false, searchable: false}
        ]
    });
});
</script>

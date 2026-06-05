<?php
include('admin_elements/admin_header.php');

$module = 'audit_report';
$module_caption = 'Comprehensive Audit Report';
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
    <div class="page-header">
        <h1><?php echo e($module_caption); ?></h1>
    </div>

    <div class="alert alert-info">
        <p>This page displays comprehensive audit reports and system activity logs.</p>
    </div>

    <table id="grid-audit" class="custom_datatables">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Action</th>
                <th>User</th>
                <th>Details</th>
            </tr>
        </thead>
    </table>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    $('#grid-audit').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: {
                ajax_action: 'listing_audit_logs',
                csrf_token: $('input[name="csrf_token"]').val()
            }
        },
        columns: [
            {data: 'id'},
            {data: 'created_at'},
            {data: 'action'},
            {data: 'user'},
            {data: 'details', orderable: false, searchable: false}
        ]
    });
});
</script>

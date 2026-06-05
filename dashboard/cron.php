<?php
include('admin_elements/admin_header.php');

$module = 'cron';
$module_caption = 'Scheduled Tasks';
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
        <p>This page manages scheduled cron tasks and automated job execution.</p>
    </div>

    <table id="grid-<?php echo e($module); ?>" class="custom_datatables">
        <thead>
            <tr>
                <th>ID</th>
                <th>Task Name</th>
                <th>Frequency</th>
                <th>Last Run</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
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
            {data: 'id'},
            {data: 'task_name'},
            {data: 'frequency'},
            {data: 'last_run'},
            {data: 'status'},
            {data: 'actions', orderable: false, searchable: false}
        ]
    });
});
</script>

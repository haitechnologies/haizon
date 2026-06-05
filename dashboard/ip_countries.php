<?php
include('admin_elements/admin_header.php');

$module = 'ip_countries';
$module_caption = 'IP Country Mappings';
$tbl_name = DB::IP_COUNTRIES ?? 'erp_ip_countries';
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
| DELETE HANDLER
|--------------------------------------------------------------------------
*/
if ($action == "delete_$module" && !empty($id)) {
    $result = DeletionManager::delete(
        $tbl_name,
        $id,
        $session_user_id,
        [
            'verify_field' => 'country_code',
            'item_label' => 'IP Country Mapping',
            'module_slug' => $module
        ]
    );
    
    if ($result['success']) {
        $success_message = $result['message'];
        header("Location: " . $module . ".php?msg=deleted");
        exit;
    } else {
        $error_message = $result['message'];
    }
}

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

    <?php if (granted_('create', $module)): ?>
    <div class="row mb-3">
        <div class="col-md-12">
            <a href="<?php echo e($module); ?>.php?action=add_<?php echo e($module); ?>" class="btn btn-success">
                <i class="fa fa-plus"></i> Add Mapping
            </a>
        </div>
    </div>
    <?php endif; ?>

    <table id="grid-<?php echo e($module); ?>" class="custom_datatables">
        <thead>
            <tr>
                <th>Country Code</th>
                <th>Country Name</th>
                <th>IP Range</th>
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
            {data: 'country_code'},
            {data: 'country_name'},
            {data: 'ip_range'},
            {data: 'actions', orderable: false, searchable: false}
        ]
    });
});
</script>

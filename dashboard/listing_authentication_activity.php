<?php

use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$module = 'authentication_activity';
$module_caption = 'Authentication Activity';
$error_message = '';
$success_message = '';
$hide_add_button = true;

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id))) {
    if (Roles::isSystemAdmin($_SESSION[$project_pre]['DASHBOARD']['role_id'])) {
        $id = intval($id);
        
        $stmt = $mysqli->prepare("DELETE FROM " . DB::AUTHENTICATION_ACTIVITY . " WHERE id = ?");
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
    }
    
    if ($result) {
        $success_message = "$module_caption Record Deleted Successfully.";
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}

?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a>
                    <?php if (!empty($pageHelpData)): ?>
                        <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                            <i class="ph-question"></i>
                        </button>
                    <?php endif; ?>
                </h1>
            </div>

            <div class="my-1">
                <?php if (empty($hide_add_button) && isset($module_id) && isset($module) && granted('create', $module_id)) { ?>
                    <a href="<?php echo $module; ?>.php" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="ph-plus ph-sm me-2 opacity-75"></i>New
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- /page header -->

	<div class="content datatable-enhanced">

		<?php include('admin_elements/breadcrumb.php'); ?>

		<div class="card">
			<div class="card-body">
				<table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
					<thead>
						<tr>
							<th>ID</th>
							<th>User</th>
							<th>Activity Type</th>
							<th>IP Address</th>
							<th>Time</th>
							<th>Actions</th>
						</tr>
					</thead>
				</table>
			</div>
		</div>

	</div>

	<?php include('admin_elements/copyright.php'); ?>
</div>

<!-- Hidden CSRF Token for Form Submissions -->
<?php echo csrf_field(); ?>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        pageLength: 10,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: function(d) {
                d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
                d.session_user_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? ''; ?>';
                d.dt_session_role_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? ''; ?>';
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[<?php echo ucfirst($module); ?>] DataTable AJAX error:', error);
                console.error('[<?php echo ucfirst($module); ?>] Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 0 },  // ID
            { data: 1 },  // User
            { data: 2 },  // Activity Type
            { data: 3 },  // IP Address
            { data: 4 },  // Time
            { data: 5, orderable: false, searchable: false }  // Actions
        ],
        order: [[0, 'desc']]
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>


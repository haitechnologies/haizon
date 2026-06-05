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
	<?php include('admin_elements/page_header.php'); ?>
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


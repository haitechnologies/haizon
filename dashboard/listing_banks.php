<?php
include('admin_elements/admin_header.php');
$module = 'banks';
$module_caption = 'Bank';
$tbl_name = DB::BANKS;
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
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

	if (is_SuperAdmin()) {

		$mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
	} else {

		$mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by ='" . $session_user_id . "'");
	}


	if ($mysqli->affected_rows > 0) {
		$success_message = "Item deleted successfully.";
		header("Location:listing_$module.php?success_message=$success_message");
	} else {
		$error_message = "Action denied. You are not authorized to delete this record.";
	}
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">

	<!-- Page header -->
	<?php include('admin_elements/page_header.php'); ?>
	<!-- /page header -->


	<div class="content datatable-enhanced">

		<?php include('admin_elements/breadcrumb.php'); ?>

		<div class="card">
			<div class="card-body">
				<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
				<table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
					<thead>
						<tr>
							<th width="40">SR.</th>
							<th width="50" class="col-center">PRIMARY</th>
							<th>ACCOUNT NAME</th>
							<th width="150">CURRENCY</th>
							<th>ACCOUNT CODE</th>
							<th>BANK NAME</th>
							<th>ROUTING NUMBER</th>
							<th width="90">CREATED AT</th>
							<th width="80" class="col-center">STATUS</th>
							<th width="90" class="col-center">ACTIONS</th>
						</tr>
					</thead>
				</table>
			</div>
		</div>

	</div>

	<?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        columns: [
            { data: 0, orderable: false, searchable: false },
            { data: 1, orderable: false, searchable: false, className: 'col-center' },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8, className: 'col-center' },
            { data: 9, orderable: false, searchable: false, className: 'col-center' }
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search banks...', lengthMenu: '_MENU_' }
    });
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        var id = $(this).data('id'), module = $(this).data('module');
        if (!confirm('Are you sure you want to delete this record?')) return;
        $('<form method="POST">').append(
            $('<input type="hidden" name="action">').val('delete_' + module),
            $('<input type="hidden" name="id">').val(id)
        ).appendTo('body').submit();
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
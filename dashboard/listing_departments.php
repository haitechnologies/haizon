<?php
include('admin_elements/admin_header.php');
$module = 'departments';
$module_caption = 'Department';
$tbl_name = DB::DEPARTMENTS;
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

        $cascade   = $mysqli->query("SELECT id FROM `" . tbl_users . "` WHERE department_id=$id Limit 1");
        if ($cascade->num_rows > 0) {
            $error_message = "$module_caption is associated with rows in Users Table. ";
        } else {
            $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
        }
    } else {

        $cascade   = $mysqli->query("SELECT id FROM `" . tbl_users . "` WHERE department_id=$id Limit 1");
        if ($cascade->num_rows > 0) {
            $error_message = "$module_caption is associated with rows in Users Table.";
        } else {
            $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by ='" . $session_user_id . "'");
        }
    }

    // success/error message handling
    if (!empty($error_message)) {
        // already set above
    } elseif ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
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
                            <th>DEPARTMENT</th>
                            <th>EMPLOYEES</th>
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
            { data: 1 },
            { data: 2, orderable: false, searchable: false },
            { data: 3 },
            { data: 4, className: 'col-center' },
            { data: 5, orderable: false, searchable: false, className: 'col-center' }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search departments...', lengthMenu: '_MENU_' }
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
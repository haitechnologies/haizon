<?php
include('admin_elements/admin_header.php');

$module = 'frontend_user_searches';
$module_caption = 'User Searches';
$tbl_name = DB::SEARCHES;
$error_message = '';
$success_message = '';
$hide_add_button = true;

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    // Soft delete for unified table
    $result = $tbl_name && $id ? $tbl_name : null;
    if ($tbl_name && $id) {
        $stmt = $conn->prepare("UPDATE `" . $tbl_name . "` SET is_active = 0 WHERE id = ? AND search_type = 'saved'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $success_message = 'Search deleted successfully.';
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    } else {
        $error_message = 'Delete failed.';
    }

    if ($result['success']) {
        $success_message = $result['message'];
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    } else {
        $error_message = $result['message'];
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
                            <th width="60">ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Query</th>
                            <th width="120">Results</th>
                            <th width="160">Created</th>
                            <th width="90">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    var table = window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        pageLength: 10,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            data: function(d) {
                d.module = '<?php echo $module; ?>';
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[User Searches] DataTable AJAX error:', error);
                console.error('[User Searches] Response:', xhr.responseText);
                alert('Error loading data. Please check console for details.');
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();

        var id = $(this).data('id');
        var module = $(this).data('module');

        if (confirm('Are you sure you want to delete this record?')) {
            var form = $('<form>', {
                'method': 'POST',
                'action': 'listing_<?php echo $module; ?>.php'
            }).append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'delete_' + module
            })).append($('<input>', {
                'type': 'hidden',
                'name': 'id',
                'value': id
            }));

            $('body').append(form);
            form.submit();
        }
    });
});
</script>



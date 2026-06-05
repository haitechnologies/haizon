<?php

use App\Core\DB;
/**
 * System Settings Listing Page
 * 
 * Displays a server-side DataTable of system settings
 */

include('admin_elements/admin_header.php');

$module = 'system_settings';
$module_caption = 'System Settings';
$tbl_name = DB::SYSTEM_SETTINGS;
$module_id = getModuleIdBySlug($module, $mysqli);

$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();
$hide_add_button = true;
?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>

    <div class="content datatable-enhanced">
            <?php include('admin_elements/breadcrumb.php'); ?>
<div class="card">
                <div class="card-body">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>SLUG</th>
                                <th>NAME</th>
                                <th>VALUE</th>
                                <th>HINT</th>
                                <th>STATUS</th>
                                <th>UPDATED</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        responsive: true,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            data: function(d) {
                d.module = '<?php echo $module; ?>';
                return d;
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7, orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10
    });
});
</script>



<?php
/**
 * Geo States Listing Page
 */

include('admin_elements/admin_header.php');

$module = 'geo_states';
$module_caption = 'Geo States';
$tbl_name = DB::GEO_STATES;
$module_id = getModuleIdBySlug($module, $mysqli);

$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();
$hide_add_button = true;

if ($action == "delete_$module" && !empty($id)) {
    if (!granted('delete', $module)) {
        $error_message = "You don't have permission to delete states.";
    } else {
        if (delete($tbl_name, $id)) {
            $success_message = "State deleted successfully.";
            header("Location: listing_$module.php?msg=deleted");
            exit;
        } else {
            $error_message = "Could not delete state.";
        }
    }
}
?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>
    <div class="content datatable-enhanced">
            <?php include('admin_elements/breadcrumb.php'); ?>
<div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold mb-0"><?php echo $module_caption; ?></h5>
                </div>
                <div class="card-body">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>SLUG</th>
                                <th>STATE</th>
                                <th>STATE (AR)</th>
                                <th>COUNTRY ID</th>
                                <th>STATUS</th>
                                <th>CREATED</th>
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
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        pageLength: 10,
        responsive: true,
        ajax: {
            url: 'datatables_dispatcher.php?module=<?php echo $module; ?>'
        },
        columns: [
            { data: 'id' },
            { data: 'slug' },
            { data: 'state' },
            { data: 'state_ar' },
            { data: 'country_id' },
            { data: 'is_active' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });
    
});
</script>


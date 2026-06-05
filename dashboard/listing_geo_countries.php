<?php
/**
 * Geo Countries Listing Page
 * 
 * Displays a server-side DataTable of geo countries with search, sort, and pagination
 */

include('admin_elements/admin_header.php');

// Module configuration
$module = 'geo_countries';
$module_caption = 'Geo Countries';
$tbl_name = DB::GEO_COUNTRIES;
$module_id = getModuleIdBySlug($module, $mysqli);

$error_message = '';
$success_message = '';

// Include permissions check
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();
$hide_add_button = true;

// Handle delete action
if ($action == "delete_$module" && !empty($id)) {
    if (!granted('delete', $module)) {
        $error_message = "You don't have permission to delete countries.";
    } else {
        if (delete($tbl_name, $id)) {
            $success_message = "Country deleted successfully.";
            header("Location: listing_$module.php?msg=deleted");
            exit;
        } else {
            $error_message = "Could not delete country.";
        }
    }
}
?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>
    
    <div class="content datatable-enhanced">
            <?php include('admin_elements/breadcrumb.php'); ?>
            
            <!-- Messages -->
<!-- DataTable Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-semibold mb-0"><?php echo $module_caption; ?></h5>
                </div>
                <div class="card-body">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>SLUG</th>
                                <th>COUNTRY</th>
                                <th>COUNTRY (AR)</th>
                                <th>DIALING CODE</th>
                                <th>ABBR</th>
                                <th>STATUS</th>
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
            { data: 'country' },
            { data: 'country_ar' },
            { data: 'dialing_code' },
            { data: 'abbr' },
            { data: 'is_active' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });
    
});
</script>


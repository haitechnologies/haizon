<?php
/**
 * HS Code Texts Listing Page
 */

include('admin_elements/admin_header.php');

$module = 'hs_code_texts';
$module_caption = 'HS Code Texts';
$tbl_name = DB::HS_CODE_TEXTS;
$module_id = getModuleIdBySlug($module, $mysqli);

$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($action == "delete_$module" && !empty($id)) {
    if (!granted('delete', $module)) {
        $error_message = "You don't have permission to delete HS code texts.";
    } else {
        if (delete($tbl_name, $id)) {
            $success_message = "HS Code Text deleted successfully.";
            header("Location: listing_$module.php?msg=deleted");
            exit;
        } else {
            $error_message = "Could not delete HS Code Text.";
        }
    }
}
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
                                <th>HS CODE ID</th>
                                <th>LANGUAGE</th>
                                <th>SHORT DESCRIPTION</th>
                                <th>LONG DESCRIPTION</th>
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
            url: 'datatables_dispatcher.php',
            data: function(d) {
                d.module = '<?php echo $module; ?>';
                return d;
            }
        },
        columns: [
            { data: 'id' },
            { data: 'hs_code_id' },
            { data: 'lang' },
            { data: 'short_desc' },
            { data: 'long_desc' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10
    });
});
</script>



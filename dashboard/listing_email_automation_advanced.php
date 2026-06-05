<?php
/**
 * Email Automation Advanced Listing Page
 * 
 * Displays a combined view of automation rules and queue
 */

include('admin_elements/admin_header.php');

$module = 'email_automation_advanced';
$module_caption = 'Email Automation Advanced';
$tbl_name = DB::EMAIL_AUTOMATION_QUEUE;
$module_id = getModuleIdBySlug($module, $mysqli);

$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');
$activeOrganizationId = dashboardRequireActiveOrganization();
$hide_add_button = true;

// Handle delete action
if ($action == "delete_$module" && !empty($id)) {
    if (!granted('delete', $module)) {
        $error_message = "You don't have permission to delete automation records.";
    } else {
        if (delete($tbl_name, $id)) {
            $success_message = "Automation record deleted successfully.";
            header("Location: listing_$module.php?msg=deleted");
            exit;
        } else {
            $error_message = "Could not delete automation record.";
        }
    }
}
?>

<div class="content-wrapper">
	<?php if (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar')): ?>
		<?php renderEmailQuickbar($visibleEmailLinks, $current_page); ?>
	<?php endif; ?>
    <?php include('admin_elements/page_header.php'); ?>

    <div class="content datatable-enhanced">
            <?php include('admin_elements/breadcrumb.php'); ?>
<div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-semibold mb-0"><?php echo $module_caption; ?></h5>
                </div>
                <div class="card-body">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>RULE</th>
                                <th>TRIGGER</th>
                                <th>COMPANY ID</th>
                                <th>EMAIL</th>
                                <th>SCHEDULED</th>
                                <th>STATUS</th>
                                <th>SENT</th>
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
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        responsive: true,
        pageLength: 10,
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
            { data: 7 },
            { data: 8 },
            { data: 9, orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });
});
</script>




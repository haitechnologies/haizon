<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module             = 'debit_notes';
$module_caption     = 'Debit Note';
$tbl_name = DB::DEBIT_NOTES;
$error_message      = '';
$success_message    = '';

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
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id))) {

    if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1') {

        $mysqli->query("DELETE FROM `" . DB::DEBIT_NOTE_ITEMS . "` WHERE debit_note_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id ");
    } else {

        $mysqli->query("DELETE FROM `" . DB::DEBIT_NOTE_ITEMS . "` WHERE debit_note_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . $session_user_id . "'");
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "$module_caption Deleted Successfully.";
        header("Location:listing_$module.php?page=$page&success_message=$success_message");
    } else {
        $error_message = "Sorry! $module_caption Could Not Be Deleted. Only Super Administrator can delete this record.";
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
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="100">DATE</th>
                            <th width="150">DEBIT NOTE #</th>
                            <th>REFERENCE #</th>
                            <th>VENDOR NAME</th>
                            <th width="100" class="col-center">STATUS</th>
                            <th width="100" class="text-end">AMOUNT</th>
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
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4, className: 'col-center' },
            { data: 5, className: 'text-end' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search debit notes...', lengthMenu: '_MENU_' }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

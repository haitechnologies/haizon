<?php

use App\Core\DB;
include('admin_elements/admin_header.php');
include('admin_elements/only_systemadmin.php');
$module = 'accounts_report_categories';
$module_caption = 'Module';
$tbl_name = DB::ACCOUNTS_REPORT_CATEGORIES;
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/

// // Only me as Super Admin
// if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] != 1) {
//     header("Location:index.php?error_message=Only Super Admin has the rights to access Admnistration Module.");
// }


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
// if (($action == "delete_$module" && !empty($id))) {

//     //SUPERADMIN CAN DELETE ANY DATA
//     if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1') {
//         // echo "DELETE FROM `$tbl_name` WHERE id=$id";
//         // echo "DELETE FROM `" .DB::MODULE_PERMISSIONS. "` WHERE module_id=$id";

//         $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
//         $result = $mysqli->query("DELETE FROM `" . DB::MODULE_PERMISSIONS . "` WHERE module_id=$id");
//     }


//     if ($result) {
//         $success_message = "$module_caption Deleted Successfully.";
//         // header("Location:listing_$module.php?page=$page&success_message=$success_message");
//     } else {
//         $error_message = "Sorry! $module Could Not Be Deleted.";
//     }
// }


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/

$module_id = '';
if (isset($_REQUEST['module_id']) && !empty($_REQUEST['module_id'])) {
    $module_id     = e_s__($_REQUEST['module_id']);
}


if ($action == "delete_module_permissions" && !empty($id) && !empty($module_id)) {

    //SUPERADMIN CAN DELETE ANY DATA
    if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1') {
        $result = $mysqli->query("DELETE FROM `" . DB::MODULE_PERMISSIONS . "` WHERE id=$id AND module_id=$module_id");
    }

    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
        // header("Location:listing_$module.php?page=$page&success_message=$success_message");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted.";
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

        <div class="alert alert-danger border-0 alert-dismissible fade show">
            <strong>Warning</strong> Only Development Teams can edit this.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div class="card">
            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="80">SR.</th>
                            <th width="200">CATEGORY</th>
                            <th>SUBCATEGORIES</th>
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
            { data: 0, orderable: false },
            { data: 1 },
            { data: 2 }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search categories...', lengthMenu: '_MENU_' }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
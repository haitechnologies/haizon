<?php

use App\Core\DB;
include('admin_elements/admin_header.php');
$module = 'alerts';
$module_caption = 'Alert';
$error_message = '';
$success_message = '';
/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

// Mark as Read (CSRF protected and user-scoped)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $user_id = $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0;
    
    $stmt = $mysqli->prepare("UPDATE " . DB::ALERTS . " SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
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

        <div class="card">

            <div class="card-body">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <!-- <th width="40">SR.</th> -->
                            <!-- <th>Alerts</th> -->
                            <th></th>
                            <!-- <th width="90">ACTION</th> -->
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
    var tableSelector = '#grid-<?php echo $module; ?>';

    var table = window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: {
            search: "",
            searchPlaceholder: "Search alerts...",
            lengthMenu: "_MENU_"
        },
        searching: false,
        stateSave: false,
        deferRender: true,
        retrieve: false,
        ajax: {
            data: {
                action: '<?php echo $action; ?>',
                csrf_token: window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || ''
            },
            error: function() {
                $('.grid-error').html('');
                $(tableSelector).append('<tbody class="grid-error"><tr><th colspan="1">No Results Found.</th></tr></tbody>');
                $(tableSelector + '_processing').css('display', 'none');
            }
        },
        columns: [
            { data: 0, orderable: false, searchable: false }
        ]
    });

    var ordering_column = table.column(':contains("")').index();
    table.order([ordering_column, 'desc']).draw();
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

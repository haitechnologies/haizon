<?php
include('admin_elements/admin_header.php');
$module = 'document_categories';
$module_caption = 'Document Category';
$tbl_name = DB::DOCUMENT_CATEGORIES;
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
if (($action == "delete_$module" && !empty($id))) {

    //SUPERADMIN CAN DELETE ANY DATA
    if ($_SESSION[$project_pre]['DASHBOARD']['type'] == 'superadmin') {

        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");

        //ADMIN CAN DELETE ONLY HIS/HER DATA
    } else {

        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . $_SESSION[$project_pre]['admin_id'] . "'");
    }


    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
        header("Location:listing_$module.php?page=$page&success_message=$success_message");
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
                                <th>DOCUMENT CATEGORY</th>
                                <th>MODULES</th>
                                <th width="90">CREATED AT</th>
                                <th width="50">STATUS</th>
                                <th width="90">ACTIONS</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

<?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        columns: [
            { data: 0, orderable: false },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4, className: 'text-center' },
            { data: 5, className: 'text-center' }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search document categories...', lengthMenu: '_MENU_' }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
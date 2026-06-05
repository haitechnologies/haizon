<?php
include('admin_elements/admin_header.php');
$module = 'journals';
$module_caption = 'Journal';
$tbl_name = DB::JOURNALS;
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
// if (($action == "delete_$module" && !empty($id))) {

//     if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1') {

//         $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$id");
//         $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id ");
//     } else {

//         $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$id");
//         $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . $session_user_id . "'");
//     }


//     if ($mysqli->affected_rows > 0) {
//         $success_message = "$module_caption Deleted Successfully.";
//         header("Location:listing_$module.php?page=$page&success_message=$success_message");
//     } else {
//         $error_message = "Sorry! $module Could Not Be Deleted. Only Super Administrator can delete this record.";
//     }
// }


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow ">
        <div class="page-header-content d-lg-flex border-top">
            <div class="d-flex">
                <div class="breadcrumb py-2">
                    <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                    <a href="index.php" class="breadcrumb-item">Home</a>
                    <span class="breadcrumb-item active">Journals</span>
                    <span class="breadcrumb-item active">Listing</span>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <?php if (granted_('create', 'journals')) { ?>
                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="button" onclick="window.location.href='<?php echo $module; ?>.php';" class=" btn btn-info my-1 me-2">Create <?php echo $module_caption; ?></button>
                    </div>
                </div>
            <?php } ?>

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
                            <th width="100">JOURNAL#</th>
                            <th width="100">REFERENCE</th>
                            <th width="100" class="col-center">STATUS</th>
                            <th>NOTES</th>
                            <th width="150" class="text-end">AMOUNT</th>
                            <th width="100">CREATED BY</th>
                            <th width="100">REPORTING METHOD</th>
                            <th width="130" class="col-center">ACTIONS</th>
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
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3, className: 'col-center' },
            { data: 4 },
            { data: 5, className: 'text-end' },
            { data: 6 },
            { data: 7 },
            { data: 8, orderable: false, searchable: false, className: 'col-center' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search journals...', lengthMenu: '_MENU_' }
    });
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        var id = $(this).data('id'), module = $(this).data('module');
        if (!confirm('Are you sure you want to delete this record?')) return;
        $('<form method="POST">').append(
            $('<input type="hidden" name="action">').val('delete_' + module),
            $('<input type="hidden" name="id">').val(id)
        ).appendTo('body').submit();
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
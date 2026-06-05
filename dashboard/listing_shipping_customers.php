<?php
include('admin_elements/admin_header.php');
$module = 'shipping_customers';
$module_caption = 'Shipping Customer';
$tbl_name = DB::SHIPPING_CUSTOMERS;
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
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    if (is_SuperAdmin()) {
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    } else {
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by ='". $session_user_id ."'");
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "Customer deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
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
    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex border-top">
            <div class="row mt-2">
                <div class="col-lg-12">
                    <h5 class="ms-2 mb-0"> <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a></h5>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                <!-- <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="button" class="btn btn-primary btn-sm mt-1 mb-1" onclick="window.location.href='<?php echo $module; ?>.php?action=add_<?php echo $module; ?>';"><i class="ph-plus ph-sm me-2 opacity-75"></i>Add New</button>
                    </div>
                </div> -->
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
                                <th width="100">ID</th>
                                <th>CUSTOMER NAME</th>
                                <th>PHONE</th>
                                <th>CITY</th>
                                <th>COUNTRY</th>
                                <th>TYPE</th>
                                <th>STATUS</th>
                                <th width="130">ACTIONS</th>
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
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, className: 'text-center' },
            { data: 7, className: 'text-center' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search customers...', lengthMenu: '_MENU_' }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

<?php include('admin_elements/admin_footer.php'); ?>

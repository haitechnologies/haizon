<?php

use App\Core\DB;
include('admin_elements/admin_header.php');
$module = 'warehouses';
$module_caption = 'Warehouse';
$tbl_name = DB::WAREHOUSES;
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

    // if (is_SuperAdmin()) {

    //     $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    // } else {

    //     $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by ='" . $session_user_id . "'");
    // }


    // if ($mysqli->affected_rows > 0) {
    //     $success_message = "Item deleted successfully.";
    //     header("Location:listing_$module.php?success_message=$success_message");
    // } else {
    //     $error_message = "Action denied. You are not authorized to delete this record.";
    // }
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
                                <th width="50">SR.</th>
                                <th>LOGO</th>
                                <th>WAREHOUSE NAME</th>
                                <th>COUNTRY</th>
                                <th>CITY</th>
                                <th>EMAIL</th>
                                <th width="90">CREATED AT</th>
                                <th width="60">STATUS</th>
                                <th width="90">ACTIONS</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="alert alert-info border-0 alert-dismissible fade show">
                <span class="fw-semibold">Logo:</span> is to display on PDFs (Quotatations, Sale Orders, Invoices etc)
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

<?php include('admin_elements/copyright.php'); ?>
    </div>
</div>
<?php include('admin_elements/admin_footer.php'); ?>
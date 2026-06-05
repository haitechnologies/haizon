<?php
include('admin_elements/admin_header.php');
$module = 'storage_subtypes';
$module_caption = 'Storage Subtype';
$tbl_name = DB::STORAGE_SUBTYPES;
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

        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by ='" . $session_user_id . "'");
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
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
                                <th width="200">STORAGE SUBTYPE</th>
                                <th>STORAGE TYPE</th>
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

<?php include('admin_elements/admin_footer.php'); ?>
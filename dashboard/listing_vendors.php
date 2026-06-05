<?php


use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'vendors';
$module_caption = 'Vendor';
$tbl_name = DB::VENDORS;
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

        // // --- Delete Quotations
        // $mysqli->query("DELETE FROM `" . DB::QUOTATION_ITEMS . "` WHERE quotation_id=$id");
        // $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id ");


        // --- Delete Vendor Addresses
        $mysqli->query("DELETE FROM `" . tbl_vendor_addresses . "` WHERE vendor_id=$id");

        // --- Delete Vendor Contacts
        $mysqli->query("DELETE FROM `" . DB::VENDOR_CONTACTS . "` WHERE vendor_id=$id");


        // --- Delete Vendor Notes
        $mysqli->query("DELETE FROM `" . tbl_vendor_notes . "` WHERE vendor_id=$id");


        // --- Delete Vendor attachments
        $result = $mysqli->query("SELECT * FROM `" . tbl_vendor_attachments . "` WHERE vendor_id=$id");

        while ($rows = $result->fetch_array()) {

            $file_id          = $rows['id'];
            $filename    = $rows['filename'];

            unlink('../uploads/vendor_attachments/' . $filename);
            $mysqli->query("DELETE FROM `" . tbl_vendor_attachments . "` WHERE id=$file_id");
        } // while


        // --- Delete Vendor Activity Logo
        $mysqli->query("DELETE FROM `" . tbl_vendor_logs . "` WHERE vendor_id=$id");


        // --- Delete Vendor
        $mysqli->query("DELETE FROM `" . DB::VENDORS . "` WHERE id=$id");
    } else {


        // --- Delete Vendor Addresses
        $mysqli->query("DELETE FROM `" . tbl_vendor_addresses . "` WHERE vendor_id=$id AND created_by ='" . $session_user_id . "'");

        // --- Delete Vendor Contacts
        $mysqli->query("DELETE FROM `" . DB::VENDOR_CONTACTS . "` WHERE vendor_id=$id AND created_by ='" . $session_user_id . "'");


        // --- Delete Vendor Notes
        $mysqli->query("DELETE FROM `" . tbl_vendor_notes . "` WHERE vendor_id=$id AND created_by ='" . $session_user_id . "'");


        // --- Delete Vendor attachments
        $result = $mysqli->query("SELECT * FROM `" . tbl_vendor_attachments . "` WHERE vendor_id=$id AND created_by ='" . $session_user_id . "'");

        while ($rows = $result->fetch_array()) {

            $file_id     = $rows['id'];
            $filename    = $rows['filename'];

            unlink('../uploads/vendor_attachments/' . $filename);
            $mysqli->query("DELETE FROM `" . tbl_vendor_attachments . "` WHERE id=$file_id");
        } // while


        // --- Delete Vendor Activity Logo
        $mysqli->query("DELETE FROM `" . tbl_vendor_logs . "` WHERE vendor_id=$id AND created_by ='" . $session_user_id . "'");


        // --- Delete Vendor
        $mysqli->query("DELETE FROM `" . DB::VENDORS . "` WHERE id=$id AND created_by ='" . $session_user_id . "'");
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


    <div class="content">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card">
            <div class="content clearfix">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th>NAME</th>
                            <!-- <th>COMPANY NAME</th> -->
                            <th>EMAIL</th>
                            <th>WORK PHONE</th>
                            <th>PAYABLES (BCY)</th>
                            <th>UNUSED CREDIT (BCY)</th>
                            <!-- <th width="90">ACTION</th> -->
                        </tr>
                    </thead>
                </table>
            </div>
        </div>


    </div>


    <?php include('admin_elements/copyright.php'); ?>
</div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
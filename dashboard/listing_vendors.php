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
        $mysqli->query("DELETE FROM `" . DB::VENDOR_ADDRESSES . "` WHERE addressable_type='Vendor' AND addressable_id=$id");

        // --- Delete Vendor Contacts
        $mysqli->query("DELETE FROM `" . DB::VENDOR_CONTACTS . "` WHERE contactable_type='Vendor' AND contactable_id=$id");


        // --- Delete Vendor Notes
        $mysqli->query("DELETE FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='vendor' AND entity_id=$id");


        // --- Delete Vendor attachments
        $result = $mysqli->query("SELECT * FROM `" . DB::VENDOR_ATTACHMENTS . "` WHERE attachable_type = 'Vendor' AND attachable_id=$id");

        while ($rows = $result->fetch_array()) {

            $file_id          = $rows['id'];
            $filename    = $rows['filename'];

            @unlink('../uploads/vendor_attachments/' . $filename);
            $mysqli->query("DELETE FROM `" . DB::VENDOR_ATTACHMENTS . "` WHERE id=$file_id");
        } // while


        // --- Delete Vendor Activity Logs
        $mysqli->query("DELETE FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='vendor' AND entity_id=$id");


        // --- Delete Vendor
        $mysqli->query("DELETE FROM `" . DB::VENDORS . "` WHERE id=$id");
    } else {


        // --- Delete Vendor Addresses
        $mysqli->query("DELETE FROM `" . DB::VENDOR_ADDRESSES . "` WHERE addressable_type='Vendor' AND addressable_id=$id AND created_by ='" . $session_user_id . "'");

        // --- Delete Vendor Contacts
        $mysqli->query("DELETE FROM `" . DB::VENDOR_CONTACTS . "` WHERE contactable_type='Vendor' AND contactable_id=$id AND created_by ='" . $session_user_id . "'");


        // --- Delete Vendor Notes
        $mysqli->query("DELETE FROM `" . DB::ENTITY_NOTES . "` WHERE entity_type='vendor' AND entity_id=$id AND created_by ='" . $session_user_id . "'");


        // --- Delete Vendor attachments
        $result = $mysqli->query("SELECT * FROM `" . DB::VENDOR_ATTACHMENTS . "` WHERE attachable_type = 'Vendor' AND attachable_id=$id AND created_by ='" . $session_user_id . "'");

        while ($rows = $result->fetch_array()) {

            $file_id     = $rows['id'];
            $filename    = $rows['filename'];

            @unlink('../uploads/vendor_attachments/' . $filename);
            $mysqli->query("DELETE FROM `" . DB::VENDOR_ATTACHMENTS . "` WHERE id=$file_id");
        } // while


        // --- Delete Vendor Activity Logs
        $mysqli->query("DELETE FROM `" . DB::ENTITY_LOGS . "` WHERE entity_type='vendor' AND entity_id=$id AND created_by ='" . $session_user_id . "'");


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

<?php include('admin_elements/admin_footer.php'); ?>
<?php

use App\Core\DB;
include('admin_elements/admin_header.php');
$module = 'payments_made';
$module_caption = 'Payment Made';
$tbl_name = DB::PAYMENTS_MADE;
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

if (isset($_REQUEST['vendor_id']) && !empty($_REQUEST['vendor_id']))
    $vendor_id = e_s__($_REQUEST['vendor_id']);
else
    $vendor_id = '';


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_payments_made" && !empty($id)) && granted('delete', $module_id)) {


    //SUPERADMIN CAN DELETE ANY DATA
    if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1') {

        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");

        // DELETE JOURNAL ENTRY
        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='payment_made' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='payment_made' AND reference_id=$id ");
        }

        //ADMIN CAN DELETE ONLY HIS/HER DATA
    } else {

        $result = $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . $_SESSION[$project_pre]['DASHBOARD']['user_id'] . "'");

        // DELETE JOURNAL ENTRY
        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='payment_made' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='payment_made' AND reference_id=$id AND created_by='" . $_SESSION[$project_pre]['DASHBOARD']['user_id'] . "' ");
        }
    }

    if ($result) {
        $success_message = "$module_caption Deleted Successfully.";
        header("Location:listing_payments_made.php?page=$page&success_message=$success_message");
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

        <div class="card">
            <div class="content clearfix">
                <table id="grid-payments_made" class="custom_datatables display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="100">DATE</th>
                            <th width="100">PAYMENT#</th>
                            <th width="100">REFERENCE NUMBER</th>
                            <th width="100">VENDOR NAME</th>
                            <th width="100">PURCHASE#</th>
                            <th width="100">MODE</th>
                            <th width="100">AMOUNT</th>
                            <th width="100">STATUS</th>
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

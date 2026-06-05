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
    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex border-top">
            <div class="row mt-3">
                <div class="col-lg-12">
                    <h5 class="ms-2"> <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a></h5>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                <div class="d-lg-flex mb-2 mb-lg-0">
                    <div class="mt-2 mb-2">
                        <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                            <a href="payments_made.php" class="btn btn-primary btn-sm">
                                <i class="ph-plus ph-sm me-2 opacity-75"></i>New</button>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- /page header -->


    <div class="content">

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
</div>

<?php include('admin_elements/admin_footer.php'); ?>

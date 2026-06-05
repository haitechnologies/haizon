<?php
include('admin_elements/admin_header.php');
$module = 'payments_received';
$module_caption = 'Payment Received';
$tbl_name = DB::PAYMENTS_RECEIVED;
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
if (($action == "delete_payments_received" && !empty($id)) && granted('delete', $module_id)) {


    if (is_SystemAdmin() || is_SuperAdmin()) {

        // $mysqli->query("DELETE FROM `" . tbl_payment_received_items . "` WHERE payment_received_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id ");

        // DELETE JOURNAL ENTRY
        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='payment_received' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='payment_received' AND reference_id=$id ");
        }
    } else {

        // $mysqli->query("DELETE FROM `" . tbl_invoice_items . "` WHERE invoice_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . $session_user_id . "'");

        // DELETE JOURNAL ENTRY
        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='payment_received' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='payment_received' AND reference_id=$id AND created_by='" . $session_user_id . "' ");
        }
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "$module_caption Deleted Successfully.";
        header("Location:listing_$module.php?page=$page&success_message=$success_message");
    } else {
        // $error_message = "Sorry! $module Could Not Be Deleted. Only Super Administrator can delete this record.";
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
                            <a href="payments_received.php" class="btn btn-primary btn-sm">
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
                <table id="grid-payments_received" class="custom_datatables display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="100">DATE</th>
                            <th width="100">PAYMENT#</th>
                            <th width="100">REFERENCE NUMBER</th>
                            <th width="100">CUSTOMER NAME</th>
                            <th width="100">INVOICE#</th>
                            <th width="100">MODE</th>
                            <th width="100">AMOUNT</th>
                            <th width="100">UNUSED AMOUNT</th>
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
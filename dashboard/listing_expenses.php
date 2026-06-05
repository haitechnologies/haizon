<?php
include('admin_elements/admin_header.php');
$module = 'expenses';
$module_caption = 'Expense';
$tbl_name = DB::EXPENSES;
$error_message = '';
$success_message = '';

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| GENEATE QR CODE AND PDF BOOKING
|--------------------------------------------------------------------------
|
*/
// --- Get From DB where qrcode=''
?>
<!-- <img src="generate_qrcode.php" alt=""> -->
<!-- <img src="generate.php?code=12345" alt=""> -->

<!-- <iframe src="generate_expense_qrcode.php" width="1" height="1"></iframe> -->

<?php

// --- Get From DB where pdf=''
// Generate and Save PDF
// include_once('pdf_expense.php');

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

    if (is_SystemAdmin() || is_SuperAdmin()) {
        $mysqli->query("DELETE FROM `" . tbl_expense_items . "` WHERE expense_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");

        // DELETE JOURNAL ENTRY
        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='expense' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='expense' AND reference_id=$id ");
        }
    } else {
        $mysqli->query("DELETE FROM `" . tbl_expense_items . "` WHERE expense_id=$id AND created_by ='" . $session_user_id . "'");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by ='" . $session_user_id . "'");

        // DELETE JOURNAL ENTRY
        $journal_id = getTableAttrV('id', DB::JOURNALS, " reference_type='expense' AND reference_id=$id ");
        if (!empty($journal_id)) {
            $mysqli->query("DELETE FROM `" . DB::JOURNAL_ITEMS . "` WHERE journal_id=$journal_id ");
            $mysqli->query("DELETE FROM `" . DB::JOURNALS . "` WHERE reference_type='expense' AND reference_id=$id AND created_by='" . $session_user_id . "' ");
        }
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
                            <th width="100">DATE</th>
                            <th>EXPENSE ACCOUNT</th>
                            <th>REFERENCE#</th>
                            <th>VENDOR NAME</th>
                            <th>PAID THROUGH</th>
                            <th>CUSTOMER NAME</th>
                            <th width="80" class="col-center">STATUS</th>
                            <th width="100" class="text-end">AMOUNT</th>
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
            { data: 6, className: 'col-center' },
            { data: 7, className: 'text-end' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search expenses...', lengthMenu: '_MENU_' }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
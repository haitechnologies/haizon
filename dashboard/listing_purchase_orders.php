<?php
include('admin_elements/admin_header.php');
$module = 'purchase_orders';
$module_caption = 'Purchase Order';
$tbl_name = DB::PURCHASE_ORDERS;
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

<!-- <iframe src="generate_purchase_order_qrcode.php" width="1" height="1"></iframe> -->

<?php

// --- Get From DB where pdf=''
// Generate and Save PDF
// include_once('pdf_purchase_order.php');

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

    if ($_SESSION[$project_pre]['DASHBOARD']['role_id'] == '1') {

        $mysqli->query("DELETE FROM `" . DB::PURCHASE_ORDER_ITEMS . "` WHERE purchase_order_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id ");
    } else {

        $mysqli->query("DELETE FROM `" . DB::PURCHASE_ORDER_ITEMS . "` WHERE purchase_order_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by='" . $session_user_id . "'");
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "$module_caption Deleted Successfully.";
        header("Location:listing_$module.php?page=$page&success_message=$success_message");
    } else {
        $error_message = "Sorry! $module Could Not Be Deleted. Only Super Administrator can delete this record.";
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
                            <th width="100">DATE</th>
                            <th>PURCHASE ORDER #</th>
                            <th>REFERENCE #</th>
                            <th>VENDOR NAME</th>
                            <th width="100" class="col-center">STATUS</th>
                            <th width="100" class="text-end">AMOUNT</th>
                            <th width="90" class="col-center">ACTIONS</th>
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
            { data: 0, orderable: false, searchable: false },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5, className: 'col-center' },
            { data: 6, className: 'text-end' },
            { data: 7, orderable: false, searchable: false, className: 'col-center' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: { search: '', searchPlaceholder: 'Search purchase orders...', lengthMenu: '_MENU_' }
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
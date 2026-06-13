<?php

use App\Core\DB;
include('admin_elements/admin_header.php');
$module = 'shipping_stocks';
$module_caption = 'Shipping Stock';
$tbl_name = DB::SHIPPING_STOCKS;
$error_message = '';
$success_message = '';


// print_r($_REQUEST);


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
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    $invoice_date               = e_s__($_POST['invoice_date']);
    $customer_id                = e_s__($_POST['customer_id']);
    $invoice_status             = e_s__($_POST['invoice_status']);
    $invoice_no                 = e_s__($_POST['invoice_no']);
    $warehouse_id               = e_s__($_POST['warehouse_id']);

    $pkgs                       = e_s__($_POST['pkgs']);
    $weight                     = e_s__($_POST['weight']);
    $awb                        = e_s__($_POST['awb']);

    $grand_total                = e_s__($_POST['grand_total']);
} else {
    $invoice_date               = date('d-m-Y', time());
    $invoice_status             = '';
    $invoice_no                 = '';
    $warehouse_id               = '';

    $pkgs                       = '';
    $weight                     = '';
    $awb                        = '';

    $grand_total                = '';
}



/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {

    if (empty($customer_id) || $customer_id == 'Please select') {
        $error_message = 'Please select Customer.';
    } else if (empty($invoice_date)) {
        $error_message = 'Please select Invoice Date.';
    } else if (empty($warehouse_id) || $warehouse_id == 'Please select') {
        $error_message = 'Please select warehouse.';
    } else if (empty($pkgs)) {
        $error_message = 'PLT/BOX/PKGs is mandatory.';
    } else if (empty($weight)) {
        $error_message = 'Weight is mandatory.';
    } else if (empty($awb)) {
        $error_message = 'AWB is mandatory.';
    } else {

        if ($grand_total == '')                         $grand_total = '0.00';

        $invoice_date     = processDateDtoY($invoice_date);

        // ---------------------------------------------
        // UPDATE SHIPPPING INVOICE
        // ---------------------------------------------
        $update_row = $mysqli->query("
                                        UPDATE `$tbl_name` SET
                                            invoice_date		        = '" . $invoice_date . "',
                                            customer_id					= '" . $customer_id . "',
                                            invoice_status		        = '" . $invoice_status . "',
                                            invoice_no		            = '" . $invoice_no . "',
                                            warehouse_id		        = '" . $warehouse_id . "',
                                            
                                            pkgs		                = '" . $pkgs . "',
                                            weight		                = '" . $weight . "',
                                            awb		                    = '" . $awb . "',
                                            
                                            grand_total		            = '" . $grand_total . "',
                                            
                                            is_active 					= '" . $publish . "'
                                        WHERE id=$id");

        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);
            $invoice_id = $id;
            ///////////////////////////////////////////////////////////

            // -- PROCESS SHIPPING INVOICE ITEMS - ITNS
            if ($total_rows > 0) {

                $updated_row    = 0;
                $inserted_row   = 0;

                for ($shipping_invoice_item = 1; $shipping_invoice_item <= $total_rows; $shipping_invoice_item++) {

                    $index = $shipping_invoice_item;
                    $index = $index - 1;

                    $item_id                        = e_s__($_POST['item_id'][$index]);
                    $item_description               = e_s__($_POST['description'][$index]);
                    $item_coo                       = e_s__($_POST['coo'][$index]);
                    $item_declaration_no            = e_s__($_POST['declaration_no'][$index]);
                    $item_hscode                    = e_s__($_POST['hscode'][$index]);
                    $item_qty                       = e_s__($_POST['qty'][$index]);
                    $item_rate                      = e_s__($_POST['rate'][$index]);
                    $item_total                     = e_s__($_POST['total'][$index]);


                    // ---------------------------------------------
                    // UPDATE SHIPPING INVOICE ITEMS
                    // ---------------------------------------------

                    $item_qty           = (($item_qty == '') ? 1 : $item_qty);
                    $item_rate          = (($item_rate == '') ? 0 : $item_rate);
                    $item_total         = (($item_total == '') ? 0 : $item_total);

                    // Process Updated Shipping Invoice Items
                    if (!empty($item_id) && !empty($item_description) && !empty($item_coo) && !empty($item_declaration_no) && !empty($item_hscode) && !empty($item_qty) && !empty($item_rate) && !empty($item_total)) {

                        $update_row = $mysqli->query("UPDATE `" . DB::SHIPPING_INVOICE_ITEMS . "` SET 
                                                            description     = '" . $item_description . "',
                                                            coo             = '" . $item_coo . "',
                                                            declaration_no  = '" . $item_declaration_no . "',
                                                            hscode          = '" . $item_hscode . "',
                                                            qty             = '" . $item_qty . "',
                                                            rate            = '" . $item_rate . "',
                                                            total           = '" . $item_total . "' 
                                                        WHERE id=$item_id");

                        if ($update_row) $updated_row++;
                        fp__(DB::SHIPPING_INVOICE_ITEMS, $item_id);

                        // Process New Shipping Invoice Items
                    } else if (empty($item_id) && !empty($item_description) && !empty($item_coo) && !empty($item_declaration_no) && !empty($item_hscode) && !empty($item_qty) && !empty($item_rate) && !empty($item_total)) {

                        $insert_row = $mysqli->query("INSERT INTO `" . DB::SHIPPING_INVOICE_ITEMS . "`(invoice_id, description, coo, declaration_no, hscode, qty, rate, total) VALUES ('" . $invoice_id . "', '" . $item_description . "', '" . $item_coo . "', '" . $item_declaration_no . "', '" . $item_hscode . "', '" . $item_qty . "', '" . $item_rate . "', '" . $item_total . "'); ");

                        if ($insert_row) $inserted_row++;
                        fp__(DB::SHIPPING_INVOICE_ITEMS, $mysqli->insert_id);

                        // Process Deleted Shipping Invoice Items
                    } else if (!empty($item_id) && empty($item_description) && empty($item_coo) && empty($item_rate) && empty($item_total)) {

                        $mysqli->query("DELETE FROM `" . DB::SHIPPING_INVOICE_ITEMS . "` WHERE id=$item_id");
                    }
                    // ---------------------------------------------

                } //for 

            }
            ///////////////////////////////////////////////////////////

            // CHECK IF AT LEAST ONE SHIPPING INVOICE ITEM IS ADDED
            if ($updated_row == 0 && $inserted_row == 0) {
                $success_message = '';
                $invoice_date = processDateYtoD($invoice_date);
                $error_message = "Please add at least one Invoice Item.";
            } else {
                header("Location:listing_$module.php?success_message=$success_message");
            }
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }

        // CHECK IF AT LEAST ONE SHIPPING INVOICE ITEM IS ADDED
        // if ($inserted_row == 0) {
        //     $success_message = '';
        //     $invoice_date = processDateYtoD($invoice_date);
        //     $error_message = "Please add at least one Shipping Invoice Item.";
        // } else {
        //     header("Location:listing_$module.php?success_message=$success_message");
        // }
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
                    <a href="listing_<?php echo $module; ?>.php" class="text-dark">All Shipping Stocks - History</a>
                    <?php if (!empty($pageHelpData)): ?>
                        <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                            <i class="ph-question"></i>
                        </button>
                    <?php endif; ?>
                </h1>
            </div>

            <div class="my-1">
                <?php if (empty($hide_add_button) && isset($module_id) && isset($module) && granted('create', $module_id)) { ?>
                    <a href="listing_shipping_advice_items.php" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="ph-plus ph-sm me-2 opacity-75"></i>New
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- /page header -->



    <div class="content datatable-enhanced">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="row">

                <div class="card">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                            <thead>
                                <tr>
                                    <th>INVOICE DATE</th>
                                    <th>CONSIGNEE</th>
                                    <th>DESTINATION PORT</th>
                                    <th>COUNTRY</th>
                                    <th>INCOTERM</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

            </div>

    </div>
    <?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        columns: [
            { data: 0, name: 'invoice_date',        title: 'INVOICE DATE' },
            { data: 1, name: 'consignee',           title: 'CONSIGNEE' },
            { data: 2, name: 'destination_port',    title: 'DESTINATION PORT' },
            { data: 3, name: 'destination_country', title: 'COUNTRY' },
            { data: 4, name: 'incoterm',            title: 'INCOTERM' },
            { data: 5, title: 'ACTION', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false
    });

    $(document).on('click', 'a[data-action="delete_record"]', function(e) {
        e.preventDefault();
        var id     = $(this).data('id');
        var module = $(this).data('module');
        if (confirm('Are you sure you want to delete this record?')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_' + module + '"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
<?php
include('admin_elements/admin_header.php');
$module = 'shipping_advices';
$module_caption = 'Shipping Advice';
$tbl_name = DB::SHIPPING_ADVICES;
$error_message = '';
$success_message = '';

//Load Composer's autoloader
require_once '../vendor/autoload.php';
require_once 'helpers/shipping_customer_helper.php';



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

        $mysqli->query("DELETE FROM `" . DB::SHIPPING_ADVICE_ITEMS . "` WHERE advice_id=$id");
        $mysqli->query("DELETE FROM `" . DB::SHIPPING_INVOICE_ITEMS . "` WHERE advice_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    } else {

        $mysqli->query("DELETE FROM `" . DB::SHIPPING_ADVICE_ITEMS . "` WHERE advice_id=$id AND created_by ='" . $session_user_id . "'");
        $mysqli->query("DELETE FROM `" . DB::SHIPPING_INVOICE_ITEMS . "` WHERE advice_id=$id AND created_by ='" . $session_user_id . "'");
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
    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex border-top">
            <div class="row mt-2">
                <div class="col-lg-12">
                    <h5 class="ms-2 mb-0"> <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a></h5>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <?php if (isset($module_id) && granted('create', $module_id)) { ?>
                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="button" class="btn btn-primary btn-sm mt-1 mb-1" onclick="window.location.href='import_<?php echo $module; ?>.php';"><i class="ph-plus ph-sm me-2 opacity-75"></i>Import</button>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
    <!-- /page header -->

    <div class="content datatable-enhanced">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="card">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th width="100">SR.</th>
                                <th>INVOICE #</th>
                                <th>DATE</th>
                                <th>CUSTOMER</th>
                                <th>AWB</th>
                                <th>LICENSE NO</th>
                                <th>MIRSAL II CODE</th>
                                <th></th>
                                <th width="130">ACTIONS</th>
                            </tr>
                        </thead>
                        </table>
                    </div>
                </div>


                <script>
                $(document).ready(function() {
                    window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
                        columns: [
                            { data: 0, orderable: false },
                            { data: 1 },
                            { data: 2 },
                            { data: 3 },
                            { data: 4 },
                            { data: 5 },
                            { data: 6 },
                            { data: 7, visible: false },
                            { data: 8, className: 'text-center' }
                        ],
                        order: [[0, 'desc']],
                        pageLength: 25,
                        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
                        language: { search: '', searchPlaceholder: 'Search shipping advices...', lengthMenu: '_MENU_' }
                    });
                });
                </script>

                <!-- <div class="row">
                    <div class="col-lg-12">
                        Available Quotation Status: &nbsp;
                        <span class="badge bg-primary"> Requested </span>
                        <span class="badge bg-yellow">Waiting</span>
                        <span class="badge bg-success">Confirmed</span>
                        <span class="badge bg-black">Rejected</span>
                        <span class="badge bg-danger">Cancelled</span>
                    </div>
                </div> -->

        </div>


        <!-- <div class="card card-body">
            <h6>Available System Status</h6>

            <div class="dropdown-menu border-secondary border-width-2" style="display: block; position: static; width: 100%; margin-top: 0; float: none; z-index: 2;">

                <span class="badge bg-primary"> Requested </span>
                <span class="badge bg-yellow">Waiting</span>
                <span class="badge bg-success">Confirmed</span>
                <span class="badge bg-black">On Hold</span>
                <span class="badge bg-danger">Rejected</span>
                <span class="badge bg-indigo">Booked</span>
            </div>
        </div> -->


        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        columns: [
            { data: 0, name: 'id',              title: 'SR.' },
            { data: 1, name: 'invoice_no',      title: 'INVOICE #' },
            { data: 2, name: 'invoice_date',    title: 'DATE' },
            { data: 3, name: 'customer_name',   title: 'CUSTOMER' },
            { data: 4, name: 'awb_no',          title: 'AWB' },
            { data: 5, name: 'license_no',      title: 'LICENSE NO' },
            { data: 6, name: 'mirsal_II_code',  title: 'MIRSAL II CODE' },
            { data: 7, name: 'publish',         title: '' },
            { data: 8, title: 'ACTION', orderable: false, searchable: false }
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
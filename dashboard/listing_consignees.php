<?php
require_once __DIR__ . '/bootstrap.php';

use App\Core\DB;

if (($_POST['action'] ?? null) == 'get_consignee_details' && !empty($_POST['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    $id = (int)$_POST['id'];
    $stmt = $mysqli->prepare("SELECT c.*, co.country AS country_name FROM `" . DB::CONSIGNEES . "` c LEFT JOIN `" . DB::GEO_COUNTRIES . "` co ON c.country = co.id WHERE c.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found.']);
    }
    exit;
}

include('admin_elements/admin_header.php');
$module = 'consignees';
$module_caption = 'Consignee';
$tbl_name = DB::CONSIGNEES;
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
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="40">SR.</th>
                            <th>CONSIGNEE NAME</th>
                            <th>STREET ADDRESS1</th>
                            <th width="150">CREATED AT</th>
                            <th width="90" class="col-center">ACTIONS</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Details Modal -->
        <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailsModalLabel">Consignee Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="modal-loading" class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="modal-content" class="d-none">
                            <div class="row g-3">
                                <div class="col-md-6"><strong>Consignee ID:</strong> <span id="det-id">-</span></div>
                                <div class="col-md-6"><strong>Status:</strong> <span id="det-status">-</span></div>
                                <div class="col-md-12"><strong>Consignee Name:</strong> <span id="det-name" class="fw-semibold text-primary">-</span></div>
                                <div class="col-md-6"><strong>Street Address 1:</strong> <div id="det-addr1" class="border rounded p-2 bg-light">-</div></div>
                                <div class="col-md-6"><strong>Street Address 2:</strong> <div id="det-addr2" class="border rounded p-2 bg-light">-</div></div>
                                <div class="col-md-4"><strong>City:</strong> <span id="det-city">-</span></div>
                                <div class="col-md-4"><strong>Province/State:</strong> <span id="det-province">-</span></div>
                                <div class="col-md-4"><strong>Zip/Postal Code:</strong> <span id="det-zipcode">-</span></div>
                                <div class="col-md-6"><strong>Country:</strong> <span id="det-country">-</span></div>
                                <div class="col-md-6"><strong>Email:</strong> <span id="det-email">-</span></div>
                                <div class="col-md-4"><strong>Telephone:</strong> <span id="det-telephone">-</span></div>
                                <div class="col-md-4"><strong>Mobile:</strong> <span id="det-mobile">-</span></div>
                                <div class="col-md-4"><strong>Fax:</strong> <span id="det-fax">-</span></div>
                                <div class="col-md-12 text-muted small mt-3"><strong>Created At:</strong> <span id="det-created">-</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
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
            { data: 0, name: 'id',              title: 'SR.' },
            { data: 1, name: 'consignee_name',  title: 'CONSIGNEE NAME' },
            { data: 2, name: 'address_line1',   title: 'STREET ADDRESS1' },
            { data: 3, name: 'created_at',      title: 'CREATED AT' },
            { data: 4, title: 'ACTION', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false
    });

    var detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

    $(document).on('click', '.view-consignee-details', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#modal-loading').removeClass('d-none');
        $('#modal-content').addClass('d-none');
        detailsModal.show();

        $.ajax({
            url: 'listing_consignees.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_consignee_details',
                id: id,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#det-id').text(data.id);
                    $('#det-name').text(data.consignee_name);
                    $('#det-addr1').text(data.address_line1 || '-');
                    $('#det-addr2').text(data.address_line2 || '-');
                    $('#det-city').text(data.city || '-');
                    $('#det-province').text(data.province || '-');
                    $('#det-zipcode').text(data.zipcode || '-');
                    $('#det-country').text(data.country_name || ('Country #' + data.country));
                    $('#det-email').text(data.email || '-');
                    $('#det-telephone').text(data.telephone || '-');
                    $('#det-mobile').text(data.mobile || '-');
                    $('#det-fax').text(data.fax || '-');
                    $('#det-status').html(data.is_active == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>');
                    $('#det-created').text(data.created_at || '-');
                    
                    $('#modal-loading').addClass('d-none');
                    $('#modal-content').removeClass('d-none');
                } else {
                    alert(response.message || 'Failed to fetch details.');
                    detailsModal.hide();
                }
            },
            error: function() {
                alert('Error fetching details.');
                detailsModal.hide();
            }
        });
    });

    $(document).on('click', 'a[data-action="delete_record"]', function(e) {
        e.preventDefault();
        var id     = $(this).data('id');
        var module = $(this).data('module');
        if (confirm('Are you sure you want to delete this consignee?')) {
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
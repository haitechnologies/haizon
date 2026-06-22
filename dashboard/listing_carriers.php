<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\DB;

if (($_POST['action'] ?? null) == 'get_carrier_details' && !empty($_POST['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    $id = (int)$_POST['id'];
    $stmt = $mysqli->prepare("SELECT * FROM `" . DB::CARRIERS . "` WHERE id = ?");
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

$module = 'carriers';
$module_caption = 'Carrier';
$tbl_name = DB::CARRIERS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$handler_config = ['hard_delete' => true, 'ownership_check' => true, 'redirect_on_success' => true];
include('admin_elements/listing_handler.php');

$detailsModalHtml = '
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Carrier Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-loading" class="text-center py-3">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
                <div id="modal-content" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tr><th width="35%">Carrier ID</th><td id="det-id"></td></tr>
                            <tr><th>Carrier Name</th><td id="det-name"></td></tr>
                            <tr><th>Status</th><td id="det-status"></td></tr>
                            <tr><th>Created At</th><td id="det-created"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>';

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'thead' => '
        <th width="40">SR.</th>
        <th>CARRIER NAME</th>
        <th width="90" class="col-center">ACTIONS</th>
    ',
    'columns' => [
        ['data' => 0, 'name' => 'id', 'title' => 'SR.'],
        ['data' => 1, 'name' => 'carrier_name', 'title' => 'CARRIER NAME'],
        ['data' => 2, 'title' => 'ACTION', 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 25,
    'extra_js' => "
        var detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

        $(document).on('click', '.view-carrier-details', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            $('#modal-loading').removeClass('d-none');
            $('#modal-content').addClass('d-none');
            detailsModal.show();

            $.ajax({
                url: 'listing_carriers.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_carrier_details',
                    id: id,
                    csrf_token: $('input[name=\"csrf_token\"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#det-id').text(data.id);
                        $('#det-name').text(data.carrier_name);
                        $('#det-status').html(data.is_active == 1 ? '<span class=\"badge bg-success\">Active</span>' : '<span class=\"badge bg-danger\">Inactive</span>');
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

        $(document).on('click', 'a[data-action=\"delete_record\"]', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var module = $(this).data('module');
            if (confirm('Are you sure you want to delete this carrier?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type=\"hidden\" name=\"action\" value=\"delete_' + module + '\"><input type=\"hidden\" name=\"id\" value=\"' + id + '\">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    ",
];

include('admin_elements/listing_template.php');

echo $detailsModalHtml;

include('admin_elements/admin_footer.php');

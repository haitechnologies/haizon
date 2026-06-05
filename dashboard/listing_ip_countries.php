<?php
include('admin_elements/admin_header.php');

$module = 'ip_countries';
$module_caption = 'IP Geolocation Database';

// ============================================================================
// PERMISSIONS
// ============================================================================
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

// Set permission flags
$add_permission = false;
$edit_permission = false;
$delete_permission = false;

if (isset($module_id)) {
    $add_permission = granted('create', $module_id);
    $edit_permission = granted('edit', $module_id);
    $delete_permission = granted('delete', $module_id);
}

?>

<!-- Main content -->
<div class="content-wrapper">

    <!-- Page header -->
    <?php include('admin_elements/page_header.php'); ?>
    <!-- /page header -->


    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <!-- IP Lookup Tool -->
        <div class="card mb-4">
            <div class="card-header">
                <span class="fw-semibold">IP Address Lookup</span>
            </div>
            <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Enter IP Address or Range:</label>
                                <input type="text" class="form-control" id="search_ip" placeholder="e.g., 8.8.8.8 or 1.0.1.0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-primary w-100" id="btn_search_ip">
                                    <i class="ph-magnifying-glass"></i> Lookup IP
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="lookup_result" class="alert d-none mt-3" role="alert"></div>
                </div>
            </div>
            <!-- /IP Lookup Tool -->

            <!-- IP Ranges DataTable -->
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <span class="fw-semibold"><?php echo $module_caption; ?> - Browse IP Ranges</span>

                    <?php if ($add_permission) { ?>
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#import_modal">
                            <i class="ph-download"></i> Update Data
                        </button>
                    <?php } ?>

                </div>

                <div class="card-body">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>IP Range Start</th>
                                <th>IP Range End</th>
                                <th>Country Code</th>
                                <th>Country Name</th>
                                <th width="120">Created At</th>
                                <th width="90">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <!-- /IP Ranges DataTable -->

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<!-- Import Data Modal -->
<div class="modal fade" id="import_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update IP Geolocation Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This will download and update the IP-to-Country database from the latest GeoLite2 data source.</p>
                <div id="import_progress" class="progress d-none">
                    <div class="progress-bar progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="import_status" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn_import_data">Start Import</button>
            </div>
        </div>
    </div>
</div>
<!-- /Import Data Modal -->


<script type="text/javascript">
    $(function() {
        // IP Lookup functionality
        $('#btn_search_ip').on('click', function() {
            const ip_addr = $('#search_ip').val().trim();
            if (!ip_addr) {
                alert('Please enter an IP address');
                return;
            }
            
            // Convert IP to numeric
            const parts = ip_addr.split('.');
            if (parts.length !== 4) {
                $('#lookup_result').removeClass('d-none alert-success alert-danger').addClass('alert-danger');
                $('#lookup_result').html('<strong>Invalid IP address format!</strong> Please enter a valid IPv4 address.');
                return;
            }
            
            let ip_numeric = 0;
            for (let i = 0; i < 4; i++) {
                const part = parseInt(parts[i]);
                if (isNaN(part) || part < 0 || part > 255) {
                    $('#lookup_result').removeClass('d-none alert-success alert-danger').addClass('alert-danger');
                    $('#lookup_result').html('<strong>Invalid IP address!</strong> Please enter a valid IPv4 address.');
                    return;
                }
                ip_numeric = ip_numeric * 256 + part;
            }
            
            // AJAX call to datatables.php
            $.ajax({
                url: '<?php echo $admin_base_url; ?>/datatables.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'lookup_ip_country',
                    ip_numeric: ip_numeric
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const data = response.data;
                        $('#lookup_result').removeClass('d-none alert-danger').addClass('alert-success');
                        $('#lookup_result').html(`
                            <strong>IP Address: ${ip_addr}</strong><br>
                            Country: <strong>${data.country_name}</strong> (${data.country_code})<br>
                            Range: ${data.ip_start_addr} - ${data.ip_end_addr}
                        `);
                    } else {
                        $('#lookup_result').removeClass('d-none alert-success alert-danger').addClass('alert-warning');
                        $('#lookup_result').html('<strong>No country found for this IP address.</strong>');
                    }
                },
                error: function() {
                    $('#lookup_result').removeClass('d-none alert-success alert-danger').addClass('alert-danger');
                    $('#lookup_result').html('<strong>Error!</strong> Could not lookup IP address.');
                }
            });
        });
        
        // Allow Enter key to trigger search
        $('#search_ip').on('keypress', function(e) {
            if (e.which == 13) {
                $('#btn_search_ip').click();
                return false;
            }
        });
    });
</script>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        pageLength: 10,
        language: {
            searchPlaceholder: 'country, IP range',
            sLengthMenu: 'Show _MENU_'
        },
        stateSave: false,     // Disable state saving to prevent conflicts
        deferRender: true,    // Defer rendering for performance
        retrieve: false,      // Don't retrieve existing instance
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            data: function(d) {
                d.action = '<?php echo $action; ?>';
                d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
                return d;
            },
            error: function() {
                $('.grid-error').html('');
                $(tableSelector).append('<tbody class="grid-error"><tr><th colspan="7">No Results Found.</th></tr></tbody>');
                $(tableSelector + '_processing').hide();
            }
        },
        columns: [
            { data: 0 },     // ID
            { data: 1 },     // IP RANGE START
            { data: 2 },     // IP RANGE END
            { data: 3 },     // COUNTRY CODE
            { data: 4 },     // COUNTRY NAME
            { data: 5 },     // CREATED AT
            { data: 6, orderable: false, searchable: false }  // ACTIONS
        ],
        order: [[0, 'desc']]
    });

    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        if (confirm('Delete this item?')) {
            var form = $('<form>', { 'method': 'POST', 'action': 'listing_<?php echo $module; ?>.php' })
                .append($('<input>', { 'type': 'hidden', 'name': 'action', 'value': 'delete_<?php echo $module; ?>' }))
                .append($('<input>', { 'type': 'hidden', 'name': 'id', 'value': $(this).data('id') }));
            $('body').append(form);
            form.submit();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>



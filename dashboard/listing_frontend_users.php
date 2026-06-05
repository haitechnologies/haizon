<?php
include('admin_elements/admin_header.php');

$module = 'frontend_users';
$module_caption = 'Frontend Users';
$tbl_name = DB::FRONTEND_USERS;
$error_message = '';
$success_message = '';
$hide_add_button = true;

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {
    $result = DeletionManager::delete(
        $tbl_name,
        $id,
        $session_user_id,
        ['verify_field' => 'full_name', 'item_label' => 'Frontend User', 'module_slug' => 'frontend_users']
    );

    if ($result['success']) {
        $success_message = $result['message'];
        header("Location:listing_$module.php?success_message=$success_message");
        exit;
    } else {
        $error_message = $result['message'];
    }
}

?>

<div class="content-wrapper">

    <!-- Page header -->
    <?php include('admin_elements/page_header.php'); ?>
    <!-- /page header -->

    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card">
            <div class="card-body">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>FULL NAME</th>
                            <th>EMAIL</th>
                            <th>MOBILE</th>
                            <th width="120">EMAIL VERIFIED</th>
                            <th width="90">STATUS</th>
                            <th width="140">LAST LOGIN</th>
                            <th width="140">CREATED</th>
                            <th width="90">ACTIONS</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>

    <!-- Frontend User Details Modal -->
    <div class="modal fade" id="frontendUserDetailsModal" tabindex="-1" role="dialog" aria-labelledby="frontendUserDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="frontendUserDetailsLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Full Name:</strong></label>
                            <div id="userDetailName" class="form-control-plaintext">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>User ID:</strong></label>
                            <div id="userDetailId" class="form-control-plaintext">-</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Email:</strong></label>
                            <div id="userDetailEmail" class="form-control-plaintext">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Mobile:</strong></label>
                            <div id="userDetailMobile" class="form-control-plaintext">-</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Email Verified:</strong></label>
                            <div id="userDetailEmailVerified" class="form-control-plaintext">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Account Status:</strong></label>
                            <div id="userDetailStatus" class="form-control-plaintext">-</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Publish Status:</strong></label>
                            <div id="userDetailPublish" class="form-control-plaintext">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Favorites Count:</strong></label>
                            <div id="userDetailFavorites" class="form-control-plaintext">-</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Searches Count:</strong></label>
                            <div id="userDetailSearches" class="form-control-plaintext">-</div>
                        </div>
                    </div>
                    <hr />
                    <div class="row mb-0">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Last Login:</strong></label>
                            <div id="userDetailLastLogin" class="form-control-plaintext text-muted">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Registered:</strong></label>
                            <div id="userDetailCreated" class="form-control-plaintext text-muted">-</div>
                        </div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Last Updated:</strong></label>
                            <div id="userDetailUpdated" class="form-control-plaintext text-muted">-</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    var table = window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        pageLength: 10,
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            data: function(d) {
                d.module = '<?php echo $module; ?>';
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[Frontend Users] DataTable AJAX error:', error);
                console.error('[Frontend Users] Response:', xhr.responseText);
                alert('Error loading data. Please check console for details.');
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8, orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    $(document).on('click', 'tbody tr', function(e) {
        // Don't trigger if clicking on action buttons or interactive elements
        if ($(e.target).closest('a, button, [role="button"]').length > 0) {
            return;
        }
        
        var rowData = table.row(this).data();
        if (rowData && rowData[0]) {
            var userId = parseInt(rowData[0]);
            showUserDetails(userId);
        }
    });

    function showUserDetails(userId) {
        var csrfToken = $('input[name="csrf_token"]').val();
        
        $.ajax({
            url: 'ajax_frontend_user_details.php',
            type: 'POST',
            dataType: 'json',
            data: {
                id: userId,
                csrf_token: csrfToken
            },
            success: function(response) {
                if (response.success && response.data) {
                    var user = response.data;
                    
                    // Populate modal fields
                    $('#userDetailId').text('#' + user.id);
                    $('#userDetailName').text(user.full_name || '-');
                    $('#userDetailEmail').text(user.email || '-');
                    $('#userDetailMobile').text(user.mobile || '-');
                    
                    // Email verified badge
                    if (user.email_verified === 1) {
                        $('#userDetailEmailVerified').html('<span class="badge bg-success">Verified</span>');
                    } else {
                        $('#userDetailEmailVerified').html('<span class="badge bg-warning">Pending</span>');
                    }
                    
                    // Account status badge
                    if (user.is_active === 1) {
                        $('#userDetailStatus').html('<span class="badge bg-success">Active</span>');
                    } else {
                        $('#userDetailStatus').html('<span class="badge bg-danger">Inactive</span>');
                    }
                    
                    // Publish status badge
                    if (user.publish === 1) {
                        $('#userDetailPublish').html('<span class="badge bg-info">Published</span>');
                    } else {
                        $('#userDetailPublish').html('<span class="badge bg-secondary">Unpublished</span>');
                    }
                    
                    // Counts
                    $('#userDetailFavorites').text(user.favorites_count || '0');
                    $('#userDetailSearches').text(user.searches_count || '0');
                    
                    // Dates
                    if (user.last_login) {
                        $('#userDetailLastLogin').text(new Date(user.last_login).toLocaleString() || '-');
                    } else {
                        $('#userDetailLastLogin').html('<em class="text-muted">Never</em>');
                    }
                    
                    if (user.created_at) {
                        $('#userDetailCreated').text(new Date(user.created_at).toLocaleString() || '-');
                    } else {
                        $('#userDetailCreated').text('-');
                    }
                    
                    if (user.updated_at) {
                        $('#userDetailUpdated').text(new Date(user.updated_at).toLocaleString() || '-');
                    } else {
                        $('#userDetailUpdated').text('-');
                    }
                    
                    // Show modal
                    var modal = new bootstrap.Modal(document.getElementById('frontendUserDetailsModal'));
                    modal.show();
                } else {
                    alert('Error: ' + (response.error || 'Unable to load user details'));
                }
            },
            error: function(xhr, status, error) {
                console.error('[Frontend User Details] AJAX error:', error);
                console.error('[Frontend User Details] Response:', xhr.responseText);
                alert('Error loading user details. Please check console for details.');
            }
        });
    }

});
</script>



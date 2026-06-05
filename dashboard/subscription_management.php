<?php
/**
 * Dashboard: Subscription Management
 * 
 * Admin page for managing customer subscriptions, tiers, and payments.
 */

include('admin_elements/admin_header.php');

$module = 'subscriptions';
$module_caption = 'Subscription Management';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

?>

<div class="content-wrapper">
    <section class="content-header with-border">
        <h1>
            <i class="fa fa-credit-card"></i> <?php echo $module_caption; ?>
        </h1>
        <ol class="breadcrumb">
            <li><a href="dashboard_crm.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li class="active"><?php echo $module_caption; ?></li>
        </ol>
    </section>

    <section class="content">
        <!-- Subscription Statistics -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="fa fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Users</span>
                        <span class="info-box-number" id="stat-total-users">0</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="fa fa-credit-card"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Paid Subscribers</span>
                        <span class="info-box-number" id="stat-paid-subscribers">0</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="fa fa-dollar"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Monthly Revenue</span>
                        <span class="info-box-number" id="stat-monthly-revenue">$0</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-red"><i class="fa fa-warning"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Expiring Soon</span>
                        <span class="info-box-number" id="stat-expiring-soon">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Filters</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Subscription Tier</label>
                            <select id="filter-tier" class="form-control">
                                <option value="">All Tiers</option>
                                <option value="free">Free</option>
                                <option value="registered">Registered</option>
                                <option value="silver">Silver</option>
                                <option value="gold">Gold</option>
                                <option value="platinum">Platinum</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Payment Status</label>
                            <select id="filter-status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="expiring">Expiring Soon</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" id="filter-search" class="form-control" placeholder="Email or name...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button id="btn-filter" class="btn btn-primary btn-block">Apply Filters</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscriptions Table -->
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Subscriptions</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table id="subscriptions-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 5%">ID</th>
                                <th style="width: 20%">Customer</th>
                                <th style="width: 12%">Email</th>
                                <th style="width: 10%">Tier</th>
                                <th style="width: 12%">Status</th>
                                <th style="width: 15%">Expires</th>
                                <th style="width: 10%">Revenue</th>
                                <th style="width: 16%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Subscription History Modal -->
        <div class="modal fade" id="modal-tier-history">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Tier Change History</h4>
                    </div>
                    <div class="modal-body">
                        <div id="history-content">
                            <!-- Will be populated by AJAX -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upgrade User Modal -->
        <div class="modal fade" id="modal-upgrade-user">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Upgrade Subscription</h4>
                    </div>
                    <form id="form-upgrade-user">
                        <div class="modal-body">
                            <input type="hidden" id="upgrade-user-id">
                            
                            <div class="form-group">
                                <label>Current Tier</label>
                                <input type="text" id="upgrade-current-tier" class="form-control" disabled>
                            </div>

                            <div class="form-group">
                                <label>New Tier</label>
                                <select id="upgrade-new-tier" class="form-control" required>
                                    <option value="">Select tier...</option>
                                    <option value="free">Free</option>
                                    <option value="registered">Registered</option>
                                    <option value="silver">Silver (AED 50/month)</option>
                                    <option value="gold">Gold (AED 150/month)</option>
                                    <option value="platinum">Platinum (AED 250/month)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Expiration Date (leave empty for no expiration)</label>
                                <input type="datetime-local" id="upgrade-expires-at" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Reason for Change</label>
                                <textarea id="upgrade-reason" class="form-control" rows="3" placeholder="Admin notes..."></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <small>This change will be logged in the subscription audit trail.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Subscription</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#subscriptions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'datatables_dispatcher.php',
            type: 'POST',
            data: function(d) {
                d.ajax_action = 'subscriptions_list';
                d.tier = $('#filter-tier').val();
                d.status = $('#filter-status').val();
                d.search_text = $('#filter-search').val();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'customer_name' },
            { data: 'email' },
            { data: 'subscription_tier' },
            { data: 'status' },
            { data: 'subscription_expires_at' },
            { data: 'monthly_revenue' },
            { data: 'actions', orderable: false }
        ],
        pageLength: 10,
        order: [[5, 'asc']]
    });

    // Filter button
    $('#btn-filter').click(function() {
        table.draw();
    });

    // Upgrade user
    $('#form-upgrade-user').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'upgrade_subscription',
            user_id: $('#upgrade-user-id').val(),
            new_tier: $('#upgrade-new-tier').val(),
            expires_at: $('#upgrade-expires-at').val(),
            reason: $('#upgrade-reason').val()
        };

        $.post('datatables_dispatcher.php', formData, function(response) {
            if (response.success) {
                alert('Subscription updated successfully');
                $('#modal-upgrade-user').modal('hide');
                table.draw();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    });

    // View tier history
    $(document).on('click', '.btn-view-history', function() {
        var userId = $(this).data('user-id');
        
        $.get('datatables_dispatcher.php', {
            ajax_action: 'subscription_history',
            user_id: userId
        }, function(response) {
            $('#history-content').html(response);
            $('#modal-tier-history').modal('show');
        });
    });

    // Upgrade button
    $(document).on('click', '.btn-upgrade', function() {
        var userId = $(this).data('user-id');
        var currentTier = $(this).data('current-tier');
        
        $('#upgrade-user-id').val(userId);
        $('#upgrade-current-tier').val(currentTier);
        $('#upgrade-new-tier').val('').focus();
        $('#upgrade-expires-at').val('');
        $('#upgrade-reason').val('');
        
        $('#modal-upgrade-user').modal('show');
    });

    // Load statistics
    loadSubscriptionStats();
});

function loadSubscriptionStats() {
    $.get('datatables_dispatcher.php', {
        ajax_action: 'subscription_statistics'
    }, function(data) {
        $('#stat-total-users').text(data.total_users);
        $('#stat-paid-subscribers').text(data.pro_subscribers || data.paid_subscribers || 0);
        $('#stat-monthly-revenue').text('$' + data.monthly_revenue);
        $('#stat-expiring-soon').text(data.expiring_soon);
    }, 'json');
}
</script>

<?php include('admin_elements/admin_footer.php'); ?>

<?php
declare(strict_types=1);

use App\Core\DB;
use App\Security\Roles;

include('admin_elements/admin_header.php');

if (!Roles::hasFullAccess($session_role_id)) {
    echo '<div class="content-wrapper"><div class="content"><div class="alert alert-danger mt-4 mx-3">Access denied. Admin access required.</div></div></div>';
    include('admin_elements/admin_footer.php');
    exit;
}

$module = 'qa_test';
$module_caption = 'QA Test Dashboard';
$hide_add_button = true;

$listingPages = [
    // ── CRM ──
    ['page' => 'listing_leads.php',              'module' => 'leads',              'group' => 'CRM',    'type' => 'ajax'],
    ['page' => 'listing_lead_quotations.php',    'module' => 'lead_quotations',    'group' => 'CRM',    'type' => 'ajax'],
    ['page' => 'listing_projects.php',           'module' => 'projects',           'group' => 'CRM',    'type' => 'ajax'],
    ['page' => 'listing_jobs.php',               'module' => 'jobs',               'group' => 'CRM',    'type' => 'ajax'],
    ['page' => 'listing_job_statuses.php',       'module' => 'job_statuses',       'group' => 'CRM',    'type' => 'ajax'],

    // ── Accounting ──
    ['page' => 'listing_customers.php',          'module' => 'customers',          'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_quotations.php',         'module' => 'quotations',         'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_sale_orders.php',        'module' => 'sale_orders',        'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_invoices.php',           'module' => 'invoices',           'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_payments_received.php',  'module' => 'payments_received',  'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_credit_notes.php',       'module' => 'credit_notes',       'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_vendors.php',            'module' => 'vendors',            'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_expenses.php',           'module' => 'expenses',           'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_purchase_orders.php',    'module' => 'purchase_orders',    'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_purchases.php',          'module' => 'purchases',          'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_payments_made.php',      'module' => 'payments_made',      'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_debit_notes.php',        'module' => 'debit_notes',        'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_banks.php',              'module' => 'banks',              'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_journals.php',           'module' => 'journals',           'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_accounts.php',           'module' => 'accounts',           'group' => 'Accounting', 'type' => 'ajax'],
    ['page' => 'listing_accounts_report_categories.php', 'module' => 'accounts_report_categories', 'group' => 'Accounting', 'type' => 'ajax'],

    // ── HR ──
    ['page' => 'listing_departments.php',        'module' => 'departments',        'group' => 'HR',     'type' => 'ajax'],
    ['page' => 'listing_designations.php',       'module' => 'designations',       'group' => 'HR',     'type' => 'ajax'],
    ['page' => 'listing_user_documents.php',     'module' => 'user_documents',     'group' => 'HR',     'type' => 'ajax'],
    ['page' => 'listing_attendance.php',         'module' => 'attendance',         'group' => 'HR',     'type' => 'server'],
    ['page' => 'listing_leave_requests.php',     'module' => 'leave_requests',     'group' => 'HR',     'type' => 'server'],
    ['page' => 'listing_leave_types.php',        'module' => 'leave_types',        'group' => 'HR',     'type' => 'ajax'],
    ['page' => 'listing_payroll_components.php', 'module' => 'payroll_components', 'group' => 'HR',     'type' => 'ajax'],
    ['page' => 'listing_salary_structures.php',  'module' => 'salary_structures',  'group' => 'HR',     'type' => 'server'],
    ['page' => 'listing_employee_salaries.php',  'module' => 'employee_salaries',  'group' => 'HR',     'type' => 'server'],
    ['page' => 'listing_payroll_runs.php',       'module' => 'payroll_runs',       'group' => 'HR',     'type' => 'server'],
    ['page' => 'listing_payslips.php',           'module' => 'payslips',           'group' => 'HR',     'type' => 'server'],

    // ── Shipping ──
    ['page' => 'listing_shipping_advices.php',   'module' => 'shipping_advices',   'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_shipping_invoices.php',  'module' => 'shipping_invoices',  'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_shipping_stocks.php',    'module' => 'shipping_stocks',    'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_shipping_customers.php', 'module' => 'shipping_customers', 'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_shipping_advice_items.php', 'module' => 'shipping_advice_items', 'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_ports.php',              'module' => 'ports',              'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_carriers.php',           'module' => 'carriers',           'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_consignees.php',         'module' => 'consignees',         'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_shippers.php',           'module' => 'shippers',           'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_container_types.php',    'module' => 'container_types',    'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_storage_types.php',      'module' => 'storage_types',      'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_storage_subtypes.php',   'module' => 'storage_subtypes',   'group' => 'Shipping', 'type' => 'ajax'],
    ['page' => 'listing_exit_points.php',        'module' => 'exit_points',        'group' => 'Shipping', 'type' => 'ajax'],

    // ── Master Data ──
    ['page' => 'listing_categories.php',         'module' => 'categories',         'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_subcategories.php',      'module' => 'subcategories',      'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_items.php',              'module' => 'items',              'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_services.php',           'module' => 'services',           'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_units.php',              'module' => 'units',              'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_currencies.php',         'module' => 'currencies',         'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_incoterms.php',          'module' => 'incoterms',          'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_payment_methods.php',    'module' => 'payment_methods',    'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_payment_terms.php',      'module' => 'payment_terms',      'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_tax_treatments.php',     'module' => 'tax_treatments',     'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_hscodes.php',            'module' => 'hscodes',            'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_category_hs_codes.php',  'module' => 'category_hs_codes',  'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_commodity_types.php',    'module' => 'commodity_types',    'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_document_categories.php','module' => 'document_categories','group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_geo_countries.php',      'module' => 'geo_countries',      'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_geo_states.php',         'module' => 'geo_states',         'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_geo_cities.php',         'module' => 'geo_cities',         'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_warehouses.php',         'module' => 'warehouses',         'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_purchase_types.php',     'module' => 'purchase_types',     'group' => 'Master Data', 'type' => 'ajax'],
    ['page' => 'listing_sale_types.php',         'module' => 'sale_types',         'group' => 'Master Data', 'type' => 'ajax'],

    // ── Admin / System ──
    ['page' => 'listing_users.php',              'module' => 'users',              'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_roles.php',              'module' => 'roles',              'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_modules.php',            'module' => 'modules',            'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_organizations.php',      'module' => 'organizations',      'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_organization_roles.php', 'module' => 'organization_roles', 'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_pages.php',              'module' => 'pages',              'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_system_settings.php',    'module' => 'system_settings',    'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_authentication_activity.php', 'module' => 'authentication_activity', 'group' => 'Admin', 'type' => 'ajax'],
    ['page' => 'listing_email_providers.php',    'module' => 'email_providers',    'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_email_queue.php',        'module' => 'email_queue',        'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_email_history.php',      'module' => 'email_history',      'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_alerts.php',             'module' => 'alerts',             'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_banned_words.php',       'module' => 'banned_words',       'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_disposable_email_domains.php', 'module' => 'disposable_email_domains', 'group' => 'Admin', 'type' => 'ajax'],
    ['page' => 'listing_setup_groups.php',       'module' => 'setup_groups',       'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_setup_sources.php',      'module' => 'setup_sources',      'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_setup_statuses.php',     'module' => 'setup_statuses',     'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_setup_tags.php',         'module' => 'setup_tags',         'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_inquiries.php',          'module' => 'inquiries',          'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_documents.php',          'module' => 'documents',          'group' => 'Admin',  'type' => 'ajax'],
    ['page' => 'listing_cron_jobs.php',          'module' => 'cron_jobs',          'group' => 'Admin',  'type' => 'ajax'],
];

$groups = [];
foreach ($listingPages as $lp) {
    $groups[$lp['group']][] = $lp;
}
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    QA Test Dashboard
                </h1>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <!-- Summary Cards -->
        <div class="row mb-3 mx-1">
            <div class="col-xl-3 col-md-6 mb-2">
                <div class="card border-start border-4 border-primary shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted fs-sm text-uppercase fw-semibold">Total Pages</div>
                            <div class="fs-3 fw-bold text-primary" id="stat-total"><?php echo count($listingPages); ?></div>
                        </div>
                        <i class="ph-list-numbers fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-2">
                <div class="card border-start border-4 border-success shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted fs-sm text-uppercase fw-semibold">Passed</div>
                            <div class="fs-3 fw-bold text-success" id="stat-passed">0</div>
                        </div>
                        <i class="ph-check-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-2">
                <div class="card border-start border-4 border-danger shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted fs-sm text-uppercase fw-semibold">Failed</div>
                            <div class="fs-3 fw-bold text-danger" id="stat-failed">0</div>
                        </div>
                        <i class="ph-x-circle fs-1 text-danger opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-2">
                <div class="card border-start border-4 border-secondary shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted fs-sm text-uppercase fw-semibold">Not Tested</div>
                            <div class="fs-3 fw-bold text-secondary" id="stat-pending"><?php echo count($listingPages); ?></div>
                        </div>
                        <i class="ph-hourglass fs-1 text-secondary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="card mx-1 mb-3">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="fs-sm fw-semibold text-muted">Test Progress</span>
                    <span class="fs-sm fw-semibold" id="progress-label">0 / <?php echo count($listingPages); ?></span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" id="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="<?php echo count($listingPages); ?>"></div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="card mx-1 mb-3">
            <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-primary btn-sm" id="btn-run-all">
                    <i class="ph-play me-1"></i>Run All Tests
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset">
                    <i class="ph-arrow-counter-clockwise me-1"></i>Reset
                </button>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <span class="fs-sm text-muted" id="timer-display">0.0s</span>
                    <select id="filter-status" class="form-select form-select-sm" style="width: 140px;">
                        <option value="all">All Status</option>
                        <option value="pass">Passed Only</option>
                        <option value="fail">Failed Only</option>
                        <option value="pending">Not Tested</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Test Groups -->
        <?php foreach ($groups as $groupName => $pages): ?>
            <div class="card mx-1 mb-3 test-group" data-group="<?php echo htmlspecialchars($groupName); ?>">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ph-folder-open text-primary"></i>
                        <h6 class="mb-0 fw-semibold"><?php echo htmlspecialchars($groupName); ?></h6>
                        <span class="badge bg-secondary"><?php echo count($pages); ?> pages</span>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm btn-run-group" data-group="<?php echo htmlspecialchars($groupName); ?>">
                        <i class="ph-play me-1"></i>Run Group
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr class="table-light">
                                    <th width="40" class="text-center">#</th>
                                    <th>Page</th>
                                    <th>Module</th>
                                    <th width="80" class="text-center">Type</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th width="120" class="text-center">Time</th>
                                    <th>Details</th>
                                    <th width="80" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pages as $idx => $p): ?>
                                    <tr class="test-row" data-page="<?php echo htmlspecialchars($p['page']); ?>" data-module="<?php echo htmlspecialchars($p['module']); ?>" data-type="<?php echo htmlspecialchars($p['type']); ?>" data-status="pending">
                                        <td class="text-center text-muted"><?php echo $idx + 1; ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($p['page']); ?>" target="_blank" class="text-decoration-none fw-medium">
                                                <?php echo htmlspecialchars($p['page']); ?>
                                            </a>
                                        </td>
                                        <td><code class="fs-sm"><?php echo htmlspecialchars($p['module']); ?></code></td>
                                        <td class="text-center">
                                            <?php if ($p['type'] === 'ajax'): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info">AJAX</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning">Server</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center cell-status">
                                            <span class="badge bg-secondary">Pending</span>
                                        </td>
                                        <td class="text-center cell-time text-muted fs-sm">-</td>
                                        <td class="cell-details text-muted fs-sm">-</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-primary btn-sm btn-test-single" title="Run Test">
                                                <i class="ph-play"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<style>
.test-row[data-status="pass"] .cell-status .badge { background-color: #d1e7dd !important; color: #0f5132 !important; }
.test-row[data-status="fail"] .cell-status .badge { background-color: #f8d7da !important; color: #842029 !important; }
.test-row[data-status="running"] .cell-status .badge { background-color: #fff3cd !important; color: #664d03 !important; }
.test-row[data-status="pass"] { background-color: rgba(209, 231, 221, 0.15); }
.test-row[data-status="fail"] { background-color: rgba(248, 215, 218, 0.15); }
.cell-details { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.test-row[data-status="fail"] .cell-details { color: #842029 !important; }
</style>

<script>
$(document).ready(function() {
    var allRows = $('.test-row');
    var totalPages = allRows.length;
    var completed = 0;
    var passed = 0;
    var failed = 0;
    var timerInterval = null;
    var timerStart = 0;
    var isRunning = false;

    function updateStats() {
        passed = allRows.filter('[data-status="pass"]').length;
        failed = allRows.filter('[data-status="fail"]').length;
        var pending = totalPages - passed - failed;
        completed = passed + failed;

        $('#stat-passed').text(passed);
        $('#stat-failed').text(failed);
        $('#stat-pending').text(pending);
        $('#progress-label').text(completed + ' / ' + totalPages);

        var pct = totalPages > 0 ? Math.round((completed / totalPages) * 100) : 0;
        $('#progress-bar').css('width', pct + '%').attr('aria-valuenow', pct);

        if (failed > 0) {
            $('#progress-bar').removeClass('bg-success').addClass('bg-danger');
        } else {
            $('#progress-bar').removeClass('bg-danger').addClass('bg-success');
        }
    }

    function startTimer() {
        timerStart = Date.now();
        timerInterval = setInterval(function() {
            var elapsed = ((Date.now() - timerStart) / 1000).toFixed(1);
            $('#timer-display').text(elapsed + 's');
        }, 100);
    }

    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function setStatus($row, status, timeMs, details) {
        $row.attr('data-status', status);
        var $statusCell = $row.find('.cell-status');
        var $timeCell = $row.find('.cell-time');
        var $detailsCell = $row.find('.cell-details');

        if (status === 'pass') {
            $statusCell.html('<span class="badge bg-success">Pass</span>');
        } else if (status === 'fail') {
            $statusCell.html('<span class="badge bg-danger">Fail</span>');
        } else if (status === 'running') {
            $statusCell.html('<span class="badge bg-warning">Running...</span>');
        } else {
            $statusCell.html('<span class="badge bg-secondary">Pending</span>');
        }

        if (timeMs !== undefined && timeMs !== null) {
            $timeCell.text(timeMs + 'ms');
        }
        if (details !== undefined) {
            $detailsCell.text(details).attr('title', details);
        }

        updateStats();
    }

    function runSingleTest($row, callback) {
        var page = $row.data('page');
        var module = $row.data('module');
        var type = $row.data('type');

        setStatus($row, 'running', null, 'Testing...');

        if (type === 'server') {
            // For server-rendered pages, just check the DataTable handler exists
            $.ajax({
                url: 'api/qa_test_runner.php',
                type: 'POST',
                data: {
                    qa_action: 'test_datatable',
                    module: module,
                    csrf_token: window.HAI_CSRF_TOKEN
                },
                dataType: 'json',
                timeout: 30000,
                success: function(resp) {
                    if (resp.success) {
                        var details = 'Total: ' + resp.records_total + ', Filtered: ' + resp.records_filtered;
                        setStatus($row, 'pass', null, details);
                    } else {
                        setStatus($row, 'fail', null, resp.error || 'Unknown error');
                    }
                    if (callback) callback();
                },
                error: function(xhr, status, err) {
                    setStatus($row, 'fail', null, 'AJAX error: ' + (err || status));
                    if (callback) callback();
                }
            });
        } else {
            // For AJAX pages, run full test suite
            $.ajax({
                url: 'api/qa_test_runner.php',
                type: 'POST',
                data: {
                    qa_action: 'test_page',
                    page: page,
                    module: module,
                    csrf_token: window.HAI_CSRF_TOKEN
                },
                dataType: 'json',
                timeout: 30000,
                success: function(resp) {
                    if (resp.overall_status === 'pass') {
                        var details = resp.passed + '/' + resp.total_tests + ' tests passed';
                        setStatus($row, 'pass', resp.execution_time_ms, details);
                    } else {
                        var failDetails = [];
                        if (resp.tests) {
                            resp.tests.forEach(function(t) {
                                if (t.status === 'fail') failDetails.push(t.name + ': ' + t.detail);
                            });
                        }
                        setStatus($row, 'fail', resp.execution_time_ms, failDetails.join(' | ') || 'Tests failed');
                    }
                    if (callback) callback();
                },
                error: function(xhr, status, err) {
                    setStatus($row, 'fail', null, 'AJAX error: ' + (err || status));
                    if (callback) callback();
                }
            });
        }
    }

    function runAllTests() {
        if (isRunning) return;
        isRunning = true;
        completed = 0;
        passed = 0;
        failed = 0;

        allRows.each(function() {
            setStatus($(this), 'pending', null, '-');
        });
        updateStats();

        $('#btn-run-all').prop('disabled', true).html('<i class="ph-spinner me-1"></i>Running...');
        startTimer();

        var queue = allRows.toArray();
        var concurrency = 3;
        var running = 0;
        var index = 0;

        function next() {
            if (index >= queue.length && running === 0) {
                isRunning = false;
                stopTimer();
                $('#btn-run-all').prop('disabled', false).html('<i class="ph-play me-1"></i>Run All Tests');
                return;
            }

            while (running < concurrency && index < queue.length) {
                (function() {
                    var $row = $(queue[index]);
                    index++;
                    running++;
                    runSingleTest($row, function() {
                        running--;
                        next();
                    });
                })();
            }
        }

        next();
    }

    // Run All
    $('#btn-run-all').on('click', function() {
        runAllTests();
    });

    // Run Group
    $('.btn-run-group').on('click', function() {
        if (isRunning) return;
        var group = $(this).data('group');
        var $groupRows = $('.test-row').filter(function() {
            return $(this).closest('.test-group').data('group') === group;
        });

        isRunning = true;
        $('#btn-run-all').prop('disabled', true);
        startTimer();

        var queue = $groupRows.toArray();
        var running = 0;
        var index = 0;

        function next() {
            if (index >= queue.length && running === 0) {
                isRunning = false;
                stopTimer();
                $('#btn-run-all').prop('disabled', false);
                return;
            }
            while (running < 3 && index < queue.length) {
                (function() {
                    var $row = $(queue[index]);
                    index++;
                    running++;
                    runSingleTest($row, function() {
                        running--;
                        next();
                    });
                })();
            }
        }

        next();
    });

    // Run Single
    $('.btn-test-single').on('click', function() {
        if (isRunning) return;
        var $row = $(this).closest('.test-row');
        isRunning = true;
        runSingleTest($row, function() {
            isRunning = false;
        });
    });

    // Reset
    $('#btn-reset').on('click', function() {
        if (isRunning) return;
        allRows.each(function() {
            setStatus($(this), 'pending', null, '-');
        });
        updateStats();
        stopTimer();
        $('#timer-display').text('0.0s');
    });

    // Filter
    $('#filter-status').on('change', function() {
        var val = $(this).val();
        allRows.each(function() {
            if (val === 'all') {
                $(this).show();
            } else {
                $(this).toggle($(this).data('status') === val);
            }
        });
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

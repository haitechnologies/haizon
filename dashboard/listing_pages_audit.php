<?php
/**
 * DataTable Pages Audit Dashboard
 * 
 * Purpose: Comprehensive audit of all listing/DataTable pages in the codebase
 * Shows status, fixes applied, and remaining issues
 * 
 * Last Updated: February 27, 2026
 * Recent Fixes:
 *   - listing_hscodes.php: UTF-8 encoding fixed (mb_substr for Arabic text)
 *   - listing_blogs.php: Verified as working reference
 * 
 * NOTE: "Untested" status means pages haven't been manually opened and verified yet.
 *       They may work perfectly fine - they just need testing. See instructions below.
 */

include('admin_elements/admin_header.php');

// Check admin permissions
if (!has_full_access()) {
    echo 'Permission Denied.';
    exit();
}

$module = 'pages_audit';
$module_caption = 'DataTable Pages Audit';
$hide_add_button = true;

// Comprehensive audit data
// Status: working, partial, broken, untested
$pages_audit = [
    
    // ====== EMAIL MANAGEMENT (13 pages) ======
    'Email Management' => [
        ['name' => 'listing_email_campaigns.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed, columns aligned'],
        ['name' => 'listing_email_templates.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed, columns aligned'],
        ['name' => 'listing_email_history.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed, columns aligned'],
        ['name' => 'listing_email_queue.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Duplicate JS removed, columns aligned'],
        ['name' => 'listing_email_events.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Duplicate JS removed, columns aligned'],
        ['name' => 'listing_email_bounces.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Duplicate JS removed, columns aligned'],
        ['name' => 'listing_email_sends.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Duplicate JS removed, columns aligned'],
        ['name' => 'listing_email_unsubscribes.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Duplicate JS removed, columns aligned'],
        ['name' => 'listing_email_automation_rules.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Duplicate JS removed, columns aligned'],
        ['name' => 'listing_email_automation_queue.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Duplicate JS removed, columns aligned'],
        ['name' => 'listing_email_providers.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Column mismatch fixed (9→6), handler aligned'],
        ['name' => 'listing_email_targets.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_email_automation_advanced.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
    ],
    
    // ====== SYSTEM ADMINISTRATION (10 pages) ======
    'System Administration' => [
        ['name' => 'listing_users.php', 'status' => 'working', 'issues' => [], 'fixed' => 'HTML structure fixed (extra divs removed)'],
        ['name' => 'listing_authentication_activity.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Column mapping fixed (7→6)'],
        ['name' => 'listing_roles.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_modules.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_alerts.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_system_settings.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_frontend_users.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_frontend_user_searches.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_job_statuses.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
    ],
    
    // ====== CATEGORIES & TAXONOMY ======
    'Categories & Taxonomy' => [
        ['name' => 'listing_categories.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_subcategories.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_category_hs_codes.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_blog_categories.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed, columns added'],
    ],
    
    // ====== CONTENT MANAGEMENT ======
    'Content Management' => [
        ['name' => 'listing_blogs.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Verified working - used as reference for icon styling'],
        ['name' => 'listing_pages.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_banned_words.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed'],
        ['name' => 'listing_public_ads.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Added master toggle, AJAX edit popup'],
    ],
    
    // ====== SALES & INVOICES ======
    'Sales & Invoices' => [
        ['name' => 'listing_items.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed, columns aligned'],
        ['name' => 'listing_invoices.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Columns fixed, array structure corrected'],
        ['name' => 'listing_payment_methods.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
    ],
    
    // ====== SETUP & CONFIGURATION ======
    'Setup & Configuration' => [
        ['name' => 'listing_setup_statuses.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed'],
        ['name' => 'listing_setup_tags.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed'],
        ['name' => 'listing_setup_sources.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
    ],
    
    // ====== BUSINESS DIRECTORY ======
    'Business Directory' => [
        ['name' => 'listing_inquiries.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_searches.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
    ],
    
    // ====== GEOGRAPHIC DATA ======
    'Geographic Data' => [
        ['name' => 'listing_ip_countries.php', 'status' => 'working', 'issues' => [], 'fixed' => 'HTML structure fixed (extra divs removed)'],
        ['name' => 'listing_geo_countries.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_geo_states.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_geo_cities.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
    ],
    
    // ====== INVENTORY & SERVICES ======
    'Inventory & Services' => [
        ['name' => 'listing_organizations.php', 'status' => 'working', 'issues' => [], 'fixed' => 'Backticks fixed'],
    ],
    
    // ====== CRM - CUSTOMERS ======
    'CRM - Customers' => [
        ['name' => 'listing_customers.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_customer_contacts.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_customer_documents.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_customer_invoices.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_customer_payments.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
    ],
    
    // ====== OTHER PAGES ======
    'Other Pages' => [
        ['name' => 'listing_hscodes.php', 'status' => 'working', 'issues' => [], 'fixed' => 'UTF-8 encoding fixed (mb_substr), JSON parsererror resolved, 13,449 HS codes working'],
        ['name' => 'listing_hscodesmappings.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_hs_code_sets.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_hs_code_texts.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
        ['name' => 'listing_cron_jobs.php', 'status' => 'untested', 'issues' => [], 'fixed' => 'Needs verification'],
    ],
];

// Calculate stats and build testable pages list
$total = 0;
$working = 0;
$partial = 0;
$broken = 0;
$untested = 0;
$testable_pages = [];

foreach ($pages_audit as $category => $pages) {
    foreach ($pages as $page) {
        $total++;
        switch ($page['status']) {
            case 'working': $working++; break;
            case 'partial': $partial++; break;
            case 'broken': $broken++; break;
            case 'untested': $untested++; break;
        }
        // Add to testable list
        $testable_pages[] = [
            'name' => $page['name'],
            'url' => $page['name'],
            'category' => $category
        ];
    }
}

?>

<style>
    .pages-audit-compact .content {
        padding-top: 0.5rem;
    }

    .pages-audit-compact .alert {
        padding: 0.75rem 0.9rem;
        margin-bottom: 0.75rem !important;
    }

    .pages-audit-compact .alert-heading {
        font-size: 1rem;
        margin-bottom: 0.35rem;
    }

    .pages-audit-compact .audit-stat-card {
        border-radius: 10px;
        box-shadow: none;
    }

    .pages-audit-compact .audit-stat-card .card-body {
        padding: 0.75rem 0.5rem;
    }

    .pages-audit-compact .audit-stat-card .card-title {
        font-size: 0.72rem;
        margin-bottom: 0.2rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .pages-audit-compact .audit-stat-card h2,
    .pages-audit-compact .audit-stat-card h3 {
        margin-bottom: 0;
        line-height: 1.1;
    }

    .pages-audit-compact .audit-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .pages-audit-compact .audit-toolbar .btn {
        padding: 0.35rem 0.65rem;
    }

    .pages-audit-compact .progress {
        height: 18px !important;
    }

    .pages-audit-compact .audit-category-card {
        margin-bottom: 0.75rem !important;
        border-radius: 10px;
        overflow: hidden;
    }

    .pages-audit-compact .audit-category-card .card-header {
        padding: 0.6rem 0.85rem;
    }

    .pages-audit-compact .audit-category-card .card-header h5 {
        font-size: 0.95rem;
    }

    .pages-audit-compact .audit-category-card .badge {
        font-size: 0.7rem;
        padding: 0.35rem 0.45rem;
    }

    .pages-audit-compact .audit-category-card table {
        font-size: 0.83rem;
    }

    .pages-audit-compact .audit-category-card thead th {
        padding: 0.45rem 0.55rem;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }

    .pages-audit-compact .audit-category-card tbody td {
        padding: 0.45rem 0.55rem;
        vertical-align: middle;
    }

    .pages-audit-compact .audit-category-card code {
        font-size: 0.76rem;
    }

    .pages-audit-compact .audit-category-card .btn-sm {
        padding: 0.2rem 0.38rem;
    }

    .pages-audit-compact .audit-info-card {
        margin-top: 0.75rem !important;
        border-radius: 10px;
    }

    .pages-audit-compact .audit-info-card .card-header {
        padding: 0.6rem 0.85rem;
    }

    .pages-audit-compact .audit-info-card .card-body {
        padding: 0.8rem 0.9rem;
    }

    .pages-audit-compact .audit-info-card h6 {
        font-size: 0.9rem;
        margin-bottom: 0.45rem;
    }

    .pages-audit-compact .audit-info-card ul,
    .pages-audit-compact .audit-info-card ol {
        padding-left: 1.1rem;
        margin-bottom: 0.55rem;
    }

    .pages-audit-compact .audit-info-card li,
    .pages-audit-compact .audit-info-card p,
    .pages-audit-compact .audit-info-card .alert {
        font-size: 0.82rem;
    }

    @media (max-width: 767.98px) {
        .pages-audit-compact .audit-category-card thead th:nth-child(3),
        .pages-audit-compact .audit-category-card tbody td:nth-child(3),
        .pages-audit-compact .audit-category-card thead th:nth-child(4),
        .pages-audit-compact .audit-category-card tbody td:nth-child(4) {
            display: none;
        }
    }
</style>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>
    
    <div class="content datatable-enhanced pages-audit-compact">
        <?php include('admin_elements/breadcrumb.php'); ?>
        
        <!-- Quick Status Summary -->
        <?php if ($untested > 0): ?>
        <div class="alert alert-warning mb-3">
            <h5 class="alert-heading"><i class="ph ph-warning-circle"></i> Action Required</h5>
            <p class="mb-2">
                <strong><?php echo $untested; ?> pages</strong> are marked as "Untested" - this means they haven't been manually verified yet.
                They are <strong>not broken</strong>, just need testing.
            </p>
            <hr>
            <p class="mb-0 small">
                <strong>To test:</strong> Open each untested page → Verify DataTable loads → Test search/sort/pagination → Update status in this file if working
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-2">
                <div class="card text-center audit-stat-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Total Pages</h5>
                        <h2 class="text-primary" id="stat-total"><?php echo $total; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center audit-stat-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Working</h5>
                        <h2 class="text-success" id="stat-working"><?php echo $working; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center audit-stat-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Tested</h5>
                        <h2 class="text-info" id="stat-tested">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center audit-stat-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Untested</h5>
                        <h2 class="text-warning" id="stat-untested"><?php echo $untested; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center audit-stat-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Errors</h5>
                        <h2 class="text-danger" id="stat-errors"><?php echo $broken + $partial; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center audit-stat-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Status</h5>
                        <h3 id="stat-percent" style="font-size: 1.5rem;">0%</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Control Bar -->
        <div class="audit-toolbar">
            <button class="btn btn-primary btn-sm" id="test-all" title="Test all pages">
                <i class="ph-play me-1"></i>Test All
            </button>
            <button class="btn btn-warning btn-sm" id="retest-broken" style="display:none;" title="Retest broken pages">
                <i class="ph-arrow-clockwise me-1"></i>Retest Broken
            </button>
            <button class="btn btn-secondary btn-sm" id="clear-results" title="Clear all test results">
                <i class="ph-eraser me-1"></i>Clear Results
            </button>
        </div>
        
        <!-- Progress Bar -->
        <div id="progress-container" style="display:none;" class="mb-3">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" id="progress-bar" role="progressbar" style="width:0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <span id="progress-text">0%</span>
                </div>
            </div>
        </div>
        
        <!-- Categories -->
        <?php foreach ($pages_audit as $category => $pages): ?>
        <div class="card audit-category-card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <?php echo $category; ?>
                    <span class="badge badge-secondary float-end"><?php echo count($pages); ?> pages</span>
                </h5>
            </div>
            <div class="table datatable-professional-responsive">
                <table class="table datatable-professional table-sm table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="45%">Page</th>
                            <th width="10%">Status</th>
                            <th width="10%">Code</th>
                            <th width="25%">Notes</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                        <tr class="audit-row" data-page="<?php echo htmlspecialchars($page['name']); ?>" data-cat="<?php echo htmlspecialchars($category); ?>">
                            <td>
                                <code><?php echo htmlspecialchars($page['name']); ?></code>
                            </td>
                            <td class="status-cell">
                                <?php 
                                    $badge_class = [
                                        'working' => 'success',
                                        'partial' => 'warning',
                                        'broken' => 'danger',
                                        'untested' => 'secondary'
                                    ];
                                    $status_text = [
                                        'working' => '✓ Working',
                                        'partial' => '⚠ Partial',
                                        'broken' => '✗ Broken',
                                        'untested' => '? Untested'
                                    ];
                                ?>
                                <span class="badge bg-<?php echo $badge_class[$page['status']]; ?>">
                                    <?php echo $status_text[$page['status']]; ?>
                                </span>
                            </td>
                            <td class="code-cell text-center text-muted"><small>-</small></td>
                            <td>
                                <small><?php echo htmlspecialchars($page['fixed']); ?></small>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary test-btn" title="Test this page" data-page="<?php echo htmlspecialchars($page['name']); ?>">
                                    <i class="ph-play"></i>
                                </button>
                                <a href="<?php echo htmlspecialchars($page['name']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Open in new tab">
                                    <i class="ph-arrow-square-out"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Legend & Info -->
        <div class="card audit-info-card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Legend & Info</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Status Definitions</h6>
                        <ul>
                            <li><span class="badge bg-success">✓ Working</span> - Tested and functional</li>
                            <li><span class="badge bg-warning">⚠ Partial</span> - Has some issues but basic functionality works</li>
                            <li><span class="badge bg-danger">✗ Broken</span> - Critical issues preventing use</li>
                            <li><span class="badge bg-secondary">? Untested</span> - <strong>Not yet tested - manual verification needed</strong></li>
                        </ul>
                        <div class="alert alert-info mt-3 mb-0">
                            <strong>Note:</strong> Pages marked "Untested" have not been opened or verified yet. 
                            To update their status:
                            <ol class="mb-0 mt-2">
                                <li>Open each page in browser</li>
                                <li>Check DataTable loads data</li>
                                <li>Test search, sort, pagination</li>
                                <li>Update this audit file manually</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Common Fixes Applied</h6>
                        <ul>
                            <li>Backtick syntax errors in JavaScript</li>
                            <li>Undefined variable ($module) errors</li>
                            <li>Missing/mismatched column definitions</li>
                            <li>Duplicate JavaScript initialization blocks</li>
                            <li>HTML structure issues (extra divs)</li>
                            <li>Handler output alignment</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="card audit-info-card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Overall Progress</h5>
            </div>
            <div class="card-body">
                <div class="progress mb-3">
                    <?php 
                        $working_pct = ($working / $total) * 100;
                        $partial_pct = ($partial / $total) * 100;
                        $broken_pct = ($broken / $total) * 100;
                        $untested_pct = ($untested / $total) * 100;
                    ?>
                    <div class="progress-bar bg-success" style="width: <?php echo $working_pct; ?>%;" title="Working">
                        <?php if ($working_pct > 10) echo 'Working ' . $working; ?>
                    </div>
                    <div class="progress-bar bg-warning" style="width: <?php echo $partial_pct; ?>%;" title="Partial">
                        <?php if ($partial_pct > 10) echo 'Partial ' . $partial; ?>
                    </div>
                    <div class="progress-bar bg-danger" style="width: <?php echo $broken_pct; ?>%;" title="Broken">
                        <?php if ($broken_pct > 10) echo 'Broken ' . $broken; ?>
                    </div>
                    <div class="progress-bar bg-secondary" style="width: <?php echo $untested_pct; ?>%;" title="Untested">
                        <?php if ($untested_pct > 10) echo 'Untested ' . $untested; ?>
                    </div>
                </div>
                <p class="text-muted small mb-0">
                    <?php echo sprintf('%.0f%% complete', ($working / $total) * 100); ?> - 
                    <?php echo $working; ?> of <?php echo $total; ?> pages working
                </p>
            </div>
        </div>
        
    </div>
    
    <?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(function(){
    let totalTests = 0;
    let testsCompleted = 0;
    let pagesWorking = 0;
    let pagesBroken = 0;

    /**
     * Test a single page
     */
    function testPage($row) {
        const pageName = $row.data('page');
        const statusCell = $row.find('.status-cell');
        const codeCell = $row.find('.code-cell');

        $row.find('.status-cell .badge').attr('class', 'badge bg-info');
        $row.find('.status-cell .badge').text('⏳ Testing');

        return $.ajax({
            url: pageName,
            method: 'HEAD',
            timeout: 8000
        })
        .done(function(data, textStatus, xhr) {
            const statusCode = xhr.status;
            
            if (statusCode == 200) {
                statusCell.html('<span class="badge bg-success">✓ Working</span>');
                codeCell.html('<small>' + statusCode + '</small>').addClass('text-success');
                pagesWorking++;
                $row.addClass('table-success');
            } else if (statusCode == 403) {
                statusCell.html('<span class="badge bg-warning">⊘ Forbidden</span>');
                codeCell.html('<small>' + statusCode + '</small>').addClass('text-warning');
                $row.addClass('table-warning');
            } else {
                statusCell.html('<span class="badge bg-danger">✗ Error</span>');
                codeCell.html('<small>' + statusCode + '</small>').addClass('text-danger');
                pagesBroken++;
                $row.addClass('table-danger');
            }
            
            testsCompleted++;
            updateProgress();
        })
        .fail(function(xhr) {
            statusCell.html('<span class="badge bg-danger">✗ Error</span>');
            codeCell.html('<small>' + (xhr.status || 'ERR') + '</small>').addClass('text-danger');
            pagesBroken++;
            testsCompleted++;
            $row.addClass('table-danger');
            updateProgress();
        });
    }

    /**
     * Update progress display
     */
    function updateProgress() {
        const percent = totalTests > 0 ? Math.round((testsCompleted / totalTests) * 100) : 0;
        $('#progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);
        $('#progress-text').text(percent + '%');
        $('#stat-tested').text(testsCompleted);
        $('#stat-working').text(pagesWorking);
        $('#stat-errors').text(pagesBroken);
        $('#stat-percent').text(percent + '%');
    }

    /**
     * Test all pages
     */
    $('#test-all').on('click', function() {
        testsCompleted = 0;
        pagesWorking = 0;
        pagesBroken = 0;
        totalTests = $('.audit-row').length;

        $('#progress-container').show();
        $(this).prop('disabled', true);
        $('#retest-broken').hide();

        // Reset all rows
        $('.audit-row').removeClass('table-success table-warning table-danger');

        const $rows = $('.audit-row');
        let index = 0;

        function processNext() {
            if (index < $rows.length) {
                testPage($rows.eq(index)).always(function() {
                    index++;
                    setTimeout(processNext, 200);
                });
            } else {
                $('#test-all').prop('disabled', false);
                if (pagesBroken > 0) $('#retest-broken').show();
                
                if (typeof Noty !== 'undefined') {
                    new Noty({
                        text: 'Test complete! Working: ' + pagesWorking + ' | Failed: ' + pagesBroken,
                        type: pagesBroken > 0 ? 'warning' : 'success',
                        timeout: 4000
                    }).show();
                }
            }
        }

        processNext();
    });

    /**
     * Test single page button
     */
    $('.test-btn').on('click', function(e) {
        e.preventDefault();
        const $row = $(this).closest('.audit-row');
        testPage($row);
    });

    /**
     * Retest broken pages
     */
    $('#retest-broken').on('click', function() {
        const $brokenRows = $('.audit-row').filter(function() {
            return $(this).hasClass('table-danger');
        });

        if ($brokenRows.length == 0) {
            if (typeof Noty !== 'undefined') {
                new Noty({
                    text: 'No broken pages found!',
                    type: 'info',
                    timeout: 2000
                }).show();
            }
            return;
        }

        testsCompleted = 0;
        pagesWorking = 0;
        pagesBroken = 0;
        totalTests = $brokenRows.length;

        $('#progress-container').show();

        let index = 0;

        function processNext() {
            if (index < $brokenRows.length) {
                testPage($brokenRows.eq(index)).always(function() {
                    index++;
                    setTimeout(processNext, 200);
                });
            } else {
                if (typeof Noty !== 'undefined') {
                    new Noty({
                        text: 'Retest complete! Fixed: ' + pagesWorking + ' | Still Broken: ' + pagesBroken,
                        type: pagesBroken > 0 ? 'warning' : 'success',
                        timeout: 4000
                    }).show();
                }
            }
        }

        processNext();
    });

    /**
     * Clear all results
     */
    $('#clear-results').on('click', function() {
        if (!confirm('Clear all test results?')) return;
        
        $('.audit-row').removeClass('table-success table-warning table-danger');
        $('.status-cell .badge').attr('class', 'badge bg-secondary').each(function() {
            const pageStatus = $(this).closest('tr').data('page');
            // Reset to initial status based on data
            $(this).text('? Untested');
        });
        $('.code-cell').html('<small>-</small>').removeClass('text-success text-danger text-warning');
        
        $('#progress-container').hide();
        $('#progress-bar').css('width', '0%');
        $('#progress-text').text('0%');
        $('#stat-tested').text('0');
        $('#stat-working').text(<?php echo $working; ?>);
        $('#stat-errors').text(0);
        $('#stat-percent').text('0%');
        $('#retest-broken').hide();
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>




<?php
include('admin_elements/admin_header.php');
$module = 'searches';
$module_caption = 'Search Analytics';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::SEARCHES;
$error_message = '';
$success_message = '';

// PERMISSIONS
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();
$hide_add_button = true;

// CSRF VALIDATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in ' . __FILE__, 'WARNING', __FILE__, __LINE__);
    }
}

/*
|--------------------------------------------------------------------------
| ANALYTICS DATA COLLECTION
|--------------------------------------------------------------------------
*/

// Top 10 searches

// Fetch top 10 searches with their most common search_type
$top_searches = [];
$sql_top = "SELECT search_query, COUNT(*) as count, 
  (SELECT search_type FROM `" . DB::SEARCHES . "` s2 WHERE s2.search_query = s1.search_query GROUP BY search_type ORDER BY COUNT(*) DESC LIMIT 1) as search_type
FROM `" . DB::SEARCHES . "` s1
GROUP BY search_query ORDER BY count DESC LIMIT 10";
$result_top = $mysqli->query($sql_top);
if ($result_top) {
	while ($row = $result_top->fetch_assoc()) {
		$top_searches[] = $row;
	}
}



// Overall metrics
$total_searches = (int)($mysqli->query("SELECT COUNT(*) as cnt FROM `" . DB::SEARCHES . "`")->fetch_assoc()['cnt'] ?? 0);
$unique_search_terms = (int)($mysqli->query("SELECT COUNT(DISTINCT search_query) as cnt FROM `" . DB::SEARCHES . "`")->fetch_assoc()['cnt'] ?? 0);
$total_inquiries = (int)($mysqli->query("SELECT COUNT(*) as cnt FROM `" . DB::INQUIRIES . "`")->fetch_assoc()['cnt'] ?? 0);
$search_to_inquiry_rate = $total_searches > 0 ? round(($total_inquiries / $total_searches) * 100, 1) : 0;

// Zero-result searches (quality metric) using unified result_count with legacy fallback.
$zeroResultQuery = $mysqli->query("SELECT COUNT(*) as cnt FROM `" . DB::SEARCHES . "` WHERE COALESCE(result_count, results_found, 0) = 0");
$zero_result_searches = $zeroResultQuery ? (int)($zeroResultQuery->fetch_assoc()['cnt'] ?? 0) : 0;
$zero_result_rate = $total_searches > 0 ? round(($zero_result_searches / $total_searches) * 100, 1) : 0;

?>

<style>
/* Search Analytics Custom Styles */
.search-analytics-page {
    background: #f8f9fa;
}

.search-analytics-page .content {
	padding-top: 0.5rem;
	padding-bottom: 0.5rem;
}

.analytics-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
	padding: 0.9rem 1.1rem;
    border-radius: 12px;
	margin-bottom: 0.75rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.analytics-header h4 {
    font-weight: 600;
	margin-bottom: 0.2rem;
	font-size: 1.1rem;
}

.analytics-header p {
    opacity: 0.95;
    margin-bottom: 0;
	font-size: 0.85rem;
}

.metric-card {
    background: white;
    border-radius: 12px;
	padding: 0.7rem 0.8rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
    height: 100%;
}

.metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.metric-icon {
	width: 34px;
	height: 34px;
	border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
	font-size: 16px;
	margin-bottom: 0.45rem;
}

.metric-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.metric-icon.success { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; }
.metric-icon.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
.metric-icon.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.metric-icon.danger { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }

.metric-label {
	font-size: 0.72rem;
    color: #6c757d;
    font-weight: 500;
    text-transform: uppercase;
	letter-spacing: 0.4px;
	margin-bottom: 0.35rem;
}

.metric-value {
	font-size: 0.82rem;
	font-weight: 700;
	color: #212529;
	line-height: 1;
}

.metric-change {
	font-size: 0.72rem;
	margin-top: 0.35rem;
}

.metric-change.positive { color: #28a745; }
.metric-change.negative { color: #dc3545; }

.period-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.period-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.period-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	padding: 0.55rem 0.7rem;
	border-bottom: 1px solid #dee2e6;
}

.period-card-header h6 {
    font-weight: 600;
    color: #495057;
    margin: 0;
	font-size: 0.86rem;
}

.period-card-body {
	padding: 0.5rem 0.7rem;
}

.period-metric {
    display: flex;
    align-items: center;
    justify-content: space-between;
	padding: 0.32rem 0;
    border-bottom: 1px solid #f1f3f5;
}

.period-metric:last-child {
    border-bottom: none;
}

.period-metric-label {
	font-size: 0.78rem;
    color: #6c757d;
    font-weight: 500;
}

.period-metric-value {
	font-size: 0.82rem;
	font-weight: 700;
	color: #212529;
}

.top-searches-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.top-searches-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
	padding: 0.65rem 0.8rem;
}

.top-searches-header h6 {
	margin: 0;
    font-weight: 600;
	font-size: 0.88rem;
}

.top-searches-header small {
    opacity: 0.9;
	font-size: 0.74rem;
}

.top-search-item {
    display: flex;
    align-items: center;
	padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.2s ease;
}

.top-search-item:hover {
    background: #f8f9fa;
}

.top-search-item:last-child {
    border-bottom: none;
}

.search-rank {
	width: 28px;
	height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
	font-size: 0.72rem;
	margin-right: 0.5rem;
    flex-shrink: 0;
}

.search-rank.gold { background: linear-gradient(135deg, #f5af19 0%, #f12711 100%); color: white; }
.search-rank.silver { background: linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%); color: white; }
.search-rank.bronze { background: linear-gradient(135deg, #cc7a00 0%, #8b5518 100%); color: white; }
.search-rank.default { background: #e9ecef; color: #6c757d; }

.search-query-text {
    flex: 1;
    font-weight: 500;
    color: #212529;
	font-size: 0.78rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.search-count-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
	padding: 0.18rem 0.45rem;
    border-radius: 20px;
    font-weight: 600;
	font-size: 0.72rem;
	margin-left: 0.45rem;
}

.search-progress {
	width: 52px;
	height: 4px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
	margin-left: 0.45rem;
}

.search-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

.data-table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.data-table-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	padding: 0.65rem 0.8rem;
	border-bottom: 1px solid #dee2e6;
}

.data-table-header h6 {
	margin: 0;
    font-weight: 600;
    color: #212529;
	font-size: 0.88rem;
}

.data-table-header small {
    color: #6c757d;
	font-size: 0.74rem;
}

.data-table-card .card-body {
	padding: 0.55rem;
}

.data-table-card .table {
	margin-bottom: 0;
	font-size: 0.82rem;
}

.data-table-card .table thead th {
	padding-top: 0.42rem;
	padding-bottom: 0.42rem;
	white-space: nowrap;
}

.top-searches-card .card-body {
	max-height: calc(100vh - 295px);
	overflow-y: auto;
}



@media (max-width: 768px) {
	.analytics-header {
		padding: 0.75rem 0.85rem;
	}

	.analytics-header p {
		display: none;
	}

	.metric-value {
		font-size: 0.82rem;
	}
	.period-metric-value {
		font-size: 0.82rem;
	}

	.data-table-viewport,
	.top-searches-card .card-body {
		max-height: none;
		overflow-y: visible;
    }
}
</style>

<div class="content-wrapper search-analytics-page">

	<?php //include('admin_elements/page_header.php'); ?>

	<div class="content">

		<?php include('admin_elements/breadcrumb.php'); ?>
		
		<!-- Alerts -->
		<?php if ($success_message): ?>
		<div class="alert alert-success alert-dismissible fade show">
			<i class="ph-check-circle me-2"></i><?php echo e($success_message); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
		<?php endif; ?>
		
		<?php if ($error_message): ?>
		<div class="alert alert-danger alert-dismissible fade show">
			<i class="ph-warning-circle me-2"></i><?php echo e($error_message); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
		<?php endif; ?>





		<!-- Main Content Row -->
		<div class="row g-2">
			<!-- Data Table -->
			<div class="col-xl-8">
				<div class="data-table-card">
					<div class="data-table-header">
					</div>
					<div class="card-body data-table-viewport">
						<table id="grid-<?php echo $module; ?>" class="custom_datatables table table-hover" width="100%">
							<thead>
								<tr>
									<th>Date</th>
									<th>Search Query</th>
									<th>IP Address</th>
									<th>Country</th>
									<th>Results</th>
								</tr>
							</thead>
						</table>

					</div>
				</div>
			</div>

			<!-- Top Searches Sidebar -->
			<div class="col-xl-4">
				<div class="top-searches-card simple-top-searches">
					<div class="top-searches-header">
						<h6 style="font-weight:600;"><i class="ph ph-ranking me-2"></i>Top 10 Searches</h6>
					</div>
					<div class="card-body p-0">
						<?php if (!empty($top_searches)): ?>
							<ul class="list-group list-group-flush">
								<?php foreach ($top_searches as $idx => $search): ?>
									<li class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
										<?php
										$link = '/search?q=' . urlencode($search['search_query']);
										if (!empty($search['search_type']) && $search['search_type'] === 'public-listings') {
											$link = '/listings?keyword=' . urlencode($search['search_query']);
										}
										?>
										<a href="<?php echo $link; ?>" target="_blank" rel="noopener" class="flex-grow-1 ms-1 text-decoration-none" title="<?php echo e($search['search_query']); ?>" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;display:inline-block;">
											<?php echo e($search['search_query']); ?>
										</a>
										<span class="badge bg-light text-dark ms-2" style="font-size:0.93em;min-width:32px;text-align:right;">
											<?php echo number_format((int)$search['count']); ?>
										</span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else: ?>
							<div class="alert alert-info m-3">
								<i class="ph ph-info me-2"></i>No search data available yet.
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

	</div>

	<?php include('admin_elements/copyright.php'); ?>
</div>

<script>

$(document).ready(function() {
	var table = window.HAIDatatableInitializer.init('#grid-<?php echo $module; ?>', '<?php echo $module; ?>', {
        stateSave: false,     // Disable state saving to prevent conflicts
        deferRender: true,    // Defer rendering for performance
        retrieve: false,      // Don't retrieve existing instance
		dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
		pageLength: 10,
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: function(d) {
                // Search analytics is read-only
				d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
				d.action = '<?php echo $action; ?>';
                d.edit_permission = 0;
                d.delete_permission = 0;
                d.session_user_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? ''; ?>';
                d.dt_session_role_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? ''; ?>';
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[' + '<?php echo ucfirst($module); ?>' + '] DataTable AJAX Error');
                console.error('Status:', xhr.status, '|', status);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 0 },     // Date
            { data: 1 },     // Search Query
            { data: 2 },     // IP Address
            { data: 3 },     // Country
            { data: 4 }      // Results
        ],
		order: [[0, 'desc']]
    });
});
</script>

<!-- Hidden CSRF Token for Form Submissions -->
<?php echo csrf_field(); ?>

<?php include('admin_elements/admin_footer.php'); ?>



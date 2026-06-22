<?php

use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');

$module = 'leads';
$module_caption = 'CRM Dashboard';
$tbl_name = $tbl_prefix . $module;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();


/*
|--------------------------------------------------------------------------
| DATA AGGREGATION - Dynamic CRM Metrics
|--------------------------------------------------------------------------
*/

// Date ranges
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));

// CUSTOMERS
$customer_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN created_at BETWEEN '$current_month_start' AND '$current_month_end 23:59:59' THEN 1 ELSE 0 END) as month_count,
        SUM(CASE WHEN created_at BETWEEN '$last_month_start' AND '$last_month_end 23:59:59' THEN 1 ELSE 0 END) as last_month_count,
        SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN approved = 0 THEN 1 ELSE 0 END) as pending_approval
    FROM `" . DB::CUSTOMERS . "`
";
$customer_data = $mysqli->query($customer_query)->fetch_assoc();
$total_customers = $customer_data['total'] ?? 0;
$month_customers = $customer_data['month_count'] ?? 0;
$last_month_customers = $customer_data['last_month_count'] ?? 0;
$approved_customers = $customer_data['approved_count'] ?? 0;
$pending_approval_customers = $customer_data['pending_approval'] ?? 0;

// Customer growth percentage
$customer_growth = $last_month_customers > 0 ? round((($month_customers - $last_month_customers) / $last_month_customers) * 100, 1) : 0;

// LEADS
$lead_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN created_at BETWEEN '$current_month_start' AND '$current_month_end 23:59:59' THEN 1 ELSE 0 END) as month_count,
        SUM(CASE WHEN created_at BETWEEN '$last_month_start' AND '$last_month_end 23:59:59' THEN 1 ELSE 0 END) as last_month_count
    FROM `" . DB::LEADS . "`
";
$lead_data = $mysqli->query($lead_query)->fetch_assoc();
$total_leads = $lead_data['total'] ?? 0;
$month_leads = $lead_data['month_count'] ?? 0;
$last_month_leads = $lead_data['last_month_count'] ?? 0;

// Lead growth percentage
$lead_growth = $last_month_leads > 0 ? round((($month_leads - $last_month_leads) / $last_month_leads) * 100, 1) : 0;

// INVOICES (replacing sale_orders metrics)
$invoice_query = "
    SELECT
        COUNT(*) as total,
        SUM(grand_total) as total_value,
        SUM(CASE WHEN invoice_date BETWEEN '$current_month_start' AND '$current_month_end' THEN grand_total ELSE 0 END) as month_value,
        SUM(CASE WHEN invoice_date BETWEEN '$current_month_start' AND '$current_month_end' THEN 1 ELSE 0 END) as month_count
    FROM `" . DB::INVOICES . "`
";
$invoice_data = $mysqli->query($invoice_query)->fetch_assoc();
$total_invoices = $invoice_data['total'] ?? 0;
$total_invoice_value = $invoice_data['total_value'] ?? 0;
$month_invoice_value = $invoice_data['month_value'] ?? 0;
$month_invoices = $invoice_data['month_count'] ?? 0;

// CONTACTS
$total_contacts = $mysqli->query("SELECT COUNT(*) as count FROM `" . DB::CUSTOMER_CONTACTS . "`")->fetch_assoc()['count'];

// ALERTS (replacing tasks functionality)
$alert_query = "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as closed_count
    FROM `" . DB::ALERTS . "`
";
$alert_data = $mysqli->query($alert_query)->fetch_assoc();
$total_alerts = $alert_data['total'] ?? 0;
$open_alerts = $alert_data['open_count'] ?? 0;
$completed_alerts = $alert_data['closed_count'] ?? 0;

// CUSTOMER DOCUMENTS - table decommissioned
$total_documents = 0;
$expired_documents = 0;
$near_expiry_documents = 0;
$up_to_date_documents = 0;

// CONVERSION RATE (Leads to Customers)
$converted_leads = $mysqli->query("SELECT COUNT(*) as count FROM `" . DB::LEADS . "` WHERE lead_status = 'converted'")->fetch_assoc()['count'];
$conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 1) : 0;

?>

<!-- Main content -->
<div class="content-wrapper">

	<!-- Page header -->
	<div class="page-header page-header-light shadow carriers-page-header">
		<div class="page-header-content d-lg-flex border-top carriers-page-header-content py-2 px-3 carriers-page-header-content">
			<div class="d-flex align-items-center py-3 mb-2 mb-lg-0 flex-fill">
				<div class="me-3 ms-2">
					<div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2">
						<i class="ph ph-users ph-2x"></i>
					</div>
				</div>
				<div class="flex-fill">
					<h4 class="mb-0">CRM Dashboard</h4>
					<span class="text-muted">Customer Relationship Management</span>
				</div>
				<div class="ms-auto">
					<span class="badge bg-primary bg-opacity-20 text-primary px-3 py-2 fs-6">
						<i class="ph ph-calendar-blank me-1"></i> <?php echo date('F Y'); ?>
					</span>
				</div>
			</div>

			<div class="navbar navbar-expand-lg border-bottom-0 py-0 flex-lg-1">
				<div class="container-fluid px-0">
					<div class="navbar-collapse collapse" id="breadcrumb_elements">
						<div class="navbar-nav ms-auto py-2">
							<a href="listing_customers.php" class="btn btn-primary">
								<i class="ph ph-users-three me-2"></i> New Customer
							</a>
						</div>
					</div>
				</div>
			</div>

			<a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
				<i class="ph ph-caret-down collapsible-indicator ph-sm m-1"></i>
			</a>
		</div>
	</div>
	<!-- /page header -->

	<!-- Inner content -->
	<div class="content-inner">

		<!-- Content area -->
		<div class="content">

			<?php include('admin_elements/breadcrumb.php'); ?>

			<!-- KPI Cards -->
			<div class="row">
				<div class="col-sm-6 col-xl-3">
					<div class="card card-body border-start border-success mb-3" style="border-left-width: 3px !important;">
						<div class="d-flex align-items-center mb-2">
							<h6 class="mb-0 text-muted">Total Customers</h6>
							<?php if ($customer_growth > 0): ?>
							<span class="badge bg-success bg-opacity-10 text-success rounded-pill ms-auto">+<?php echo $customer_growth; ?>%</span>
							<?php endif; ?>
						</div>
						<h3 class="mb-1"><?php echo number_format($total_customers); ?></h3>
						<small class="text-muted">Approved: <?php echo number_format($approved_customers); ?></small>
					</div>
				</div>

				<div class="col-sm-6 col-xl-3">
					<a href="listing_customers.php" class="text-decoration-none">
						<div class="card card-body border-start border-primary mb-3" style="border-left-width: 3px !important;">
							<div class="d-flex align-items-center mb-2">
								<h6 class="mb-0 text-muted">New This Month</h6>
								<span class="badge bg-primary bg-opacity-10 text-primary rounded-pill ms-auto"><?php echo number_format($month_customers); ?></span>
							</div>
							<h3 class="mb-1"><?php echo number_format($month_customers); ?></h3>
							<small class="text-muted">Last month: <?php echo number_format($last_month_customers); ?></small>
						</div>
					</a>
				</div>

				<div class="col-sm-6 col-xl-3">
					<a href="listing_leads.php" class="text-decoration-none">
						<div class="card card-body mb-3" style="border-left: 3px solid #6f42c1 !important;">
							<div class="d-flex align-items-center mb-2">
								<h6 class="mb-0 text-muted">Total Leads</h6>
								<?php if ($lead_growth > 0): ?>
								<span class="badge bg-success bg-opacity-10 text-success rounded-pill ms-auto">+<?php echo $lead_growth; ?>%</span>
								<?php endif; ?>
							</div>
							<h3 class="mb-1"><?php echo number_format($total_leads); ?></h3>
							<small class="text-muted">Converted: <?php echo number_format($converted_leads); ?></small>
						</div>
					</a>
				</div>

				<div class="col-sm-6 col-xl-3">
					<div class="card card-body border-start border-warning mb-3" style="border-left-width: 3px !important;">
						<div class="d-flex align-items-center mb-2">
							<h6 class="mb-0 text-muted">Conversion Rate</h6>
						</div>
						<h3 class="mb-1"><?php echo $conversion_rate; ?>%</h3>
						<div class="progress mt-2" style="height: 6px;">
							<div class="progress-bar bg-warning" style="width: <?php echo $conversion_rate; ?>%"></div>
						</div>
					</div>
				</div>
			</div>
			<!-- /KPI Cards -->

			<!-- Stat Cards Row -->
			<div class="row">
				<div class="col-sm-6">
					<a href="listing_customers.php" class="text-decoration-none">
						<div class="card card-body border-start border-warning mb-3" style="border-left-width: 3px !important;">
							<div class="d-flex align-items-center">
								<div class="me-3">
									<div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2">
										<i class="ph ph-hourglass-simple ph-lg"></i>
									</div>
								</div>
								<div class="flex-fill">
									<h5 class="mb-0"><?php echo number_format($pending_approval_customers); ?></h5>
									<span class="text-muted">Pending Approvals</span>
								</div>
							</div>
						</div>
					</a>
				</div>

				<div class="col-sm-6">
					<div class="card card-body border-start border-danger mb-3" style="border-left-width: 3px !important;">
						<div class="d-flex align-items-center">
							<div class="me-3">
								<div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2">
									<i class="ph ph-bell-ringing ph-lg"></i>
								</div>
							</div>
							<div class="flex-fill">
								<h5 class="mb-0"><?php echo number_format($open_alerts); ?></h5>
								<span class="text-muted">Open Alerts</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- /Stat Cards Row -->

			<!-- Dashboard content -->
			<div class="row">
				<div class="col-xl-8">

					<!-- Recent Customers -->
					<div class="card mb-3">
						<div class="card-header d-flex align-items-center">
							<h5 class="mb-0"><i class="ph ph-users me-2"></i>Recent Customers</h5>
							<div class="ms-auto">
								<a href="listing_customers.php" class="btn btn-sm btn-outline-primary">View all</a>
							</div>
						</div>
						<div class="card-body p-0">
							<div class="table-responsive">
								<table class="table table-hover mb-0">
									<thead class="table-light">
										<tr>
											<th>Customer</th>
											<th>Email</th>
											<th>Phone</th>
											<th>Date Added</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$result = $mysqli->query("SELECT * FROM `" . DB::CUSTOMERS . "` ORDER BY id DESC LIMIT 8");
										if ($result->num_rows > 0) {
											while ($row = $result->fetch_array()) {
												$approved = $row['approved'];
												$status_text = $approved == 1 ? 'Approved' : ($approved == 0 ? 'Pending' : 'Not Approved');
												$status_class = $approved == 1 ? 'success' : ($approved == 0 ? 'warning' : 'danger');
										?>
												<tr>
													<td class="py-2">
														<a href="customer_overview.php?customer_id=<?php echo $row['id']; ?>" class="fw-semibold">
															<?php echo $row['display_name']; ?>
														</a>
													</td>
													<td class="py-2 text-muted"><?php echo $row['email']; ?></td>
													<td class="py-2 text-muted"><?php echo $row['phone']; ?></td>
													<td class="py-2 text-muted"><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
													<td class="py-2">
														<span class="badge bg-<?php echo $status_class; ?> bg-opacity-20 text-<?php echo $status_class; ?>">
															<?php echo $status_text; ?>
														</span>
													</td>
												</tr>
										<?php
											}
										} else {
											echo '<tr><td class="text-center text-muted py-3" colspan="5">No customers found</td></tr>';
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<!-- Recent Leads Row -->
					<div class="row">
						<div class="col-lg-6">
							<div class="card mb-3">
								<div class="card-header d-flex align-items-center">
									<h6 class="mb-0"><i class="ph ph-user-plus me-2"></i>Recent Leads</h6>
									<div class="ms-auto"></div>
								</div>
								<div class="card-body">
									<?php
									$result = $mysqli->query("SELECT * FROM `" . DB::LEADS . "` ORDER BY id DESC LIMIT 5");
									if ($result->num_rows > 0) {
										while ($row = $result->fetch_array()) {
											$lead_status = $row['lead_status'] ?? '';
											$status_class = $lead_status == 'converted' ? 'success' : ($lead_status == 'contacted' ? 'info' : 'secondary');
									?>
											<div class="d-sm-flex flex-sm-wrap mb-3 pb-2 border-bottom">
												<div class="flex-fill">
													<a href="lead.php?id=<?php echo $row['id']; ?>" class="fw-semibold">
														<?php echo $row['display_name']; ?>
													</a>
													<div class="text-muted fs-sm">
														<i class="ph ph-envelope me-1"></i><?php echo $row['email']; ?>
													</div>
												</div>
												<div class="ms-sm-auto mt-1 mt-sm-0">
													 
												</div>
											</div>
									<?php
										}
									} else {
										echo '<p class="text-muted mb-0">No leads found</p>';
									}
									?>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="col-xl-4">

					<!-- CRM Statistics -->
					<div class="card mb-3">
						<div class="card-header">
							<h6 class="mb-0"><i class="ph ph-chart-bar me-2"></i>CRM Statistics</h6>
						</div>
						<div class="card-body">
							<div class="mb-3 pb-3 border-bottom">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<span class="text-muted">Contacts</span>
									<span class="fw-semibold"><?php echo number_format($total_contacts); ?></span>
								</div>
								<div class="progress" style="height: 6px;">
									<div class="progress-bar bg-info" style="width: 100%"></div>
								</div>
							</div>
							<div class="mb-3 pb-3 border-bottom">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<span class="text-muted">Pending Approval</span>
									<span class="fw-semibold"><?php echo number_format($pending_approval_customers); ?></span>
								</div>
								<div class="progress" style="height: 6px;">
									<div class="progress-bar bg-warning" style="width: <?php echo $total_customers > 0 ? ($pending_approval_customers / $total_customers) * 100 : 0; ?>%"></div>
								</div>
							</div>
							<div class="mb-3 pb-3 border-bottom">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<span class="text-muted">Open Alerts</span>
									<span class="fw-semibold"><?php echo number_format($open_alerts); ?></span>
								</div>
								<div class="progress" style="height: 6px;">
									<div class="progress-bar bg-danger" style="width: <?php echo $total_alerts > 0 ? ($open_alerts / $total_alerts) * 100 : 0; ?>%"></div>
								</div>
							</div>
						</div>
					</div>

					<!-- Pending Approvals -->
					<div class="card mb-3">
						<div class="card-header d-flex align-items-center">
							<h6 class="mb-0"><i class="ph ph-check-square me-2"></i>Pending Approvals</h6>
							<div class="ms-auto">
								<span class="badge bg-warning"><?php echo $pending_approval_customers; ?></span>
							</div>
						</div>
						<div class="card-body">
							<?php
							$result = $mysqli->query("SELECT * FROM `" . DB::CUSTOMERS . "` WHERE approved != 1 ORDER BY id DESC LIMIT 5");
							if ($result->num_rows > 0) {
								while ($row = $result->fetch_array()) {
									$approved = $row['approved'];
									$status_text = $approved == 0 ? 'Pending' : 'Not Approved';
									$status_class = $approved == 0 ? 'warning' : 'danger';
							?>
									<div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom">
										<div class="flex-fill">
											<a href="customer_overview.php?customer_id=<?php echo $row['id']; ?>" class="fw-semibold">
												<?php echo $row['display_name']; ?>
											</a>
											<div class="text-muted fs-sm"><?php echo date("d M Y", strtotime($row['created_at'])); ?></div>
										</div>
										<span class="badge bg-<?php echo $status_class; ?> bg-opacity-20 text-<?php echo $status_class; ?>">
											<?php echo $status_text; ?>
										</span>
									</div>
							<?php
								}
							} else {
								echo '<p class="text-muted mb-0">All customers approved</p>';
							}
							?>
						</div>
					</div>

				</div>
			</div>
			<!-- /dashboard content -->

		</div>
		<!-- /content area -->

		<?php include('admin_elements/copyright.php'); ?>

	</div>
	<!-- /inner content -->

</div>
<!-- /main content -->

</div>
<!-- /page content -->
<?php include('admin_elements/admin_footer.php'); ?>
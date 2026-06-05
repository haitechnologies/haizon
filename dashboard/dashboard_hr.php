<?php
include('admin_elements/admin_header.php');

$module = 'statistics';
$module_caption = 'Statistics';
$tbl_name = $tbl_prefix . $module;
$error_message = '';
$success_message = '';



/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
$currentRoleName = strtolower(trim((string) Roles::getName(Roles::getCurrentRoleId())));
if (!Roles::currentUserHasFullAccess() && $currentRoleName !== 'hr') {
	echo 'Permission Denied.';
	exit();
}

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();



/*
|--------------------------------------------------------------------------
| DATA AGGREGATION - Dynamic HR Metrics
|--------------------------------------------------------------------------
*/

// Date ranges
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));
$today = date('Y-m-d');

// EMPLOYEES (Exclude superadmin, id > 1)
$employee_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
    FROM `" . tbl_users . "` WHERE id > 1
";
$employee_data = $mysqli->query($employee_query)->fetch_assoc();
$total_employees = $employee_data['total'] ?? 0;
$active_employees = $employee_data['active'] ?? 0;
$inactive_employees = $employee_data['inactive'] ?? 0;

// ATTENDANCE - Today
$today_attendance_query = "
	SELECT
		SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
		SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
		SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as on_leave
	FROM `" . tbl_attendance . "`
	WHERE work_date = '$today'
";
$today_attendance = $mysqli->query($today_attendance_query)->fetch_assoc();
$today_present = $today_attendance['present'] ?? 0;
$today_absent = $today_attendance['absent'] ?? 0;
$today_leave = $today_attendance['on_leave'] ?? 0;

// DEPARTMENTS
$total_departments = $mysqli->query("SELECT COUNT(*) as count FROM `" . tbl_departments . "`")->fetch_assoc()['count'];

// DESIGNATIONS
$total_designations = $mysqli->query("SELECT COUNT(*) as count FROM `" . tbl_designations . "`")->fetch_assoc()['count'];

// LEAVE REQUESTS
$leave_query = "
	SELECT 
		COUNT(*) as total,
		SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
		SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
		SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
	FROM `" . tbl_leave_requests . "`
	WHERE start_date >= '$current_month_start' AND end_date <= '$current_month_end'
";
$leave_data = $mysqli->query($leave_query)->fetch_assoc();
$total_leaves = $leave_data['total'] ?? 0;
$approved_leaves = $leave_data['approved'] ?? 0;
$pending_leaves = $leave_data['pending'] ?? 0;
$rejected_leaves = $leave_data['rejected'] ?? 0;

// EMPLOYEE DOCUMENTS
$doc_query = "
	SELECT 
		COUNT(*) as total,
		SUM(CASE WHEN expiry_date != '1970-01-01' AND expiry_date <= '" . date('Y-m-d') . "' THEN 1 ELSE 0 END) as expired,
		SUM(CASE WHEN expiry_date != '1970-01-01' AND expiry_date BETWEEN '" . date('Y-m-d') . "' AND '" . date('Y-m-d', strtotime('+30 days')) . "' THEN 1 ELSE 0 END) as near_expiry,
		SUM(CASE WHEN expiry_date != '1970-01-01' AND expiry_date > '" . date('Y-m-d', strtotime('+30 days')) . "' THEN 1 ELSE 0 END) as up_to_date
	FROM `" . tbl_user_documents . "`
";
$doc_data = $mysqli->query($doc_query)->fetch_assoc();
$total_documents = $doc_data['total'] ?? 0;
$expired_documents = $doc_data['expired'] ?? 0;
$near_expiry_documents = $doc_data['near_expiry'] ?? 0;
$up_to_date_documents = $doc_data['up_to_date'] ?? 0;

// PAYSLIPS
$payslip_query = "
	SELECT 
		COUNT(*) as total,
		SUM(CASE WHEN status = 'generated' THEN 1 ELSE 0 END) as generated_count,
		SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count
	FROM `" . tbl_payslips . "`
	WHERE YEAR(created_at) = YEAR(NOW())
";
$payslip_data = $mysqli->query($payslip_query)->fetch_assoc();
$total_payslips = $payslip_data['total'] ?? 0;
$generated_payslips = $payslip_data['generated_count'] ?? 0;
$submitted_payslips = $payslip_data['submitted_count'] ?? 0;

// ATTENDANCE PERCENTAGE
$attendance_percentage = $total_employees > 0 ? round(($today_present / $total_employees) * 100, 1) : 0;

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/phosphor-icons@1.4.2/src/css/icons.min.css">

<!-- Main content -->
<div class="content-wrapper">

	<!-- Page header -->
	<div class="page-header page-header-light shadow">
		<div class="page-header-content d-lg-flex border-top">
			<div class="d-flex align-items-center py-3 mb-2 mb-lg-0 flex-fill">
				<div class="me-3 ms-2">
					<div class="bg-info bg-opacity-10 text-info rounded-circle p-2">
						<i class="ph ph-users-three ph-2x"></i>
					</div>
				</div>
				<div class="flex-fill">
					<h4 class="mb-0">HR Dashboard</h4>
					<span class="text-muted">Human Resources Management</span>
				</div>
				<div class="ms-auto">
					<span class="badge bg-info bg-opacity-20 text-info px-3 py-2 fs-6">
						<i class="ph ph-calendar-blank me-1"></i> <?php echo date('F Y'); ?>
					</span>
				</div>
			</div>

			<div class="navbar navbar-expand-lg border-bottom-0 py-0 flex-lg-1">
				<div class="container-fluid px-0">
					<div class="navbar-collapse collapse" id="breadcrumb_elements">
						<div class="navbar-nav ms-auto py-2">
							<a href="listing_users.php" class="btn btn-outline-primary me-2">
								<i class="ph ph-user-plus me-2"></i> New Employee
							</a>
							<a href="listing_attendance.php" class="btn btn-primary">
								<i class="ph ph-sign-in me-2"></i> Mark Attendance
							</a>
						</div>
					</div>
				</div>
			</div>

			<a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
				<i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
			</a>
		</div>
	</div>
	<!-- /page header -->



	<?php //if (granted_('view', '___')) { 
	?>
	<!-- Inner content -->
	<div class="content-inner">

		<!-- Content area -->
		<div class="content">

			<?php include('admin_elements/breadcrumb.php'); ?>

			<!-- KPI Cards -->
			<div class="row mb-3">
				<!-- Total Employees -->
				<div class="col-sm-6 col-xl-3">
					<div class="card card-body">
						<div class="d-flex align-items-center">
							<div class="flex-fill">
								<h3 class="mb-0"><?php echo number_format($total_employees); ?></h3>
								<span class="text-muted">Total Employees</span>
								<div class="mt-2">
									<span class="badge bg-success bg-opacity-20 text-success">
										<i class="ph ph-check-circle"></i> <?php echo $active_employees; ?> Active
									</span>
								</div>
							</div>
							<div class="ms-3">
								<div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
									<i class="ph ph-users-three ph-2x"></i>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Today Attendance -->
				<div class="col-sm-6 col-xl-3">
					<div class="card card-body">
						<div class="d-flex align-items-center">
							<div class="flex-fill">
								<h3 class="mb-0"><?php echo $attendance_percentage; ?>%</h3>
								<span class="text-muted">Attendance Rate</span>
								<div class="mt-2">
									<span class="badge bg-success bg-opacity-20 text-success">
										<?php echo $today_present; ?> Present
									</span>
								</div>
							</div>
							<div class="ms-3">
								<div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
									<i class="ph ph-sign-in ph-2x"></i>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Pending Leave Requests -->
				<div class="col-sm-6 col-xl-3">
					<div class="card card-body">
						<div class="d-flex align-items-center">
							<div class="flex-fill">
								<h3 class="mb-0"><?php echo number_format($pending_leaves); ?></h3>
								<span class="text-muted">Pending Leaves</span>
								<div class="mt-2">
									<span class="badge bg-warning bg-opacity-20 text-warning">
										<?php echo $approved_leaves; ?> Approved
									</span>
								</div>
							</div>
							<div class="ms-3">
								<div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3">
									<i class="ph ph-calendar-x ph-2x"></i>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Document Compliance -->
				<div class="col-sm-6 col-xl-3">
					<div class="card card-body">
						<div class="d-flex align-items-center">
							<div class="flex-fill">
								<h3 class="mb-0"><?php echo number_format($expired_documents); ?></h3>
								<span class="text-muted">Expired Documents</span>
								<div class="mt-2">
									<span class="badge bg-danger bg-opacity-20 text-danger">
										<i class="ph ph-warning"></i> Action Needed
									</span>
								</div>
							</div>
							<div class="ms-3">
								<div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3">
									<i class="ph ph-files ph-2x"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- /KPI Cards -->

			<!-- Dashboard content -->
			<div class="row">
				<div class="col-xl-8">



					<!-- Recent Employees -->
					<div class="card mb-3">
						<div class="card-header d-flex align-items-center">
							<h5 class="mb-0"><i class="ph ph-users me-2"></i>Recent Employees</h5>
							<div class="ms-auto">
								<a href="listing_users.php" class="btn btn-sm btn-outline-primary">View all</a>
							</div>
						</div>
						<div class="card-body p-0">
							<div class="table-responsive">
								<table class="table table-hover table-sm mb-0">
									<thead class="table-light">
										<tr>
											<th>Employee Name</th>
											<th>Email</th>
											<th>Department</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$result = $mysqli->query("SELECT u.*, d.department FROM `" . tbl_users . "` u LEFT JOIN `" . tbl_departments . "` d ON u.department_id = d.id WHERE u.id > 1 ORDER BY u.id DESC LIMIT 8");
										if ($result->num_rows > 0) {
											while ($row = $result->fetch_array()) {
												$is_active = $row['is_active'];
												$status_text = $is_active == 1 ? 'Active' : 'Inactive';
												$status_class = $is_active == 1 ? 'success' : 'danger';
										?>
											<tr>
												<td class="py-2">
													<a href="user.php?id=<?php echo $row['id']; ?>" class="fw-semibold">
														<?php echo $row['full_name']; ?>
													</a>
												</td>
												<td class="py-2 text-muted"><?php echo $row['email']; ?></td>
												<td class="py-2 text-muted"><?php echo $row['department'] ?? 'N/A'; ?></td>
												<td class="py-2">
													<span class="badge bg-<?php echo $status_class; ?> bg-opacity-20 text-<?php echo $status_class; ?>">
														<?php echo $status_text; ?>
													</span>
												</td>
											</tr>
										<?php
											}
										} else {
											echo '<tr><td class="text-center text-muted py-3" colspan="4">No employees found</td></tr>';
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<!-- Today Attendance & Leaves -->
					<div class="row">
						<!-- Today Attendance -->
						<div class="col-lg-6">
							<div class="card mb-3">
								<div class="card-header d-flex align-items-center">
									<h6 class="mb-0"><i class="ph ph-sign-in me-2"></i>Today's Attendance</h6>
									<div class="ms-auto">
										<a href="listing_attendance.php" class="text-primary">View all</a>
									</div>
								</div>
								<div class="card-body">
									<div class="mb-3">
										<div class="d-flex justify-content-between align-items-center mb-2">
											<span class="text-success fw-semibold"><i class="ph ph-check-circle me-1"></i>Present</span>
											<span class="badge bg-success"><?php echo $today_present; ?></span>
										</div>
										<div class="progress" style="height: 8px;">
											<div class="progress-bar bg-success" style="width: <?php echo $attendance_percentage; ?>%"></div>
										</div>
									</div>
									<div class="mb-3">
										<div class="d-flex justify-content-between align-items-center mb-2">
											<span class="text-danger fw-semibold"><i class="ph ph-x-circle me-1"></i>Absent</span>
											<span class="badge bg-danger"><?php echo $today_absent; ?></span>
										</div>
										<div class="progress" style="height: 8px;">
											<div class="progress-bar bg-danger" style="width: <?php echo $total_employees > 0 ? ($today_absent/$total_employees)*100 : 0; ?>%"></div>
										</div>
									</div>
									<div>
										<div class="d-flex justify-content-between align-items-center mb-2">
											<span class="text-warning fw-semibold"><i class="ph ph-calendar-x me-1"></i>On Leave</span>
											<span class="badge bg-warning"><?php echo $today_leave; ?></span>
										</div>
										<div class="progress" style="height: 8px;">
											<div class="progress-bar bg-warning" style="width: <?php echo $total_employees > 0 ? ($today_leave/$total_employees)*100 : 0; ?>%"></div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Leave Requests -->
						<div class="col-lg-6">
							<div class="card mb-3">
								<div class="card-header d-flex align-items-center">
									<h6 class="mb-0"><i class="ph ph-calendar-x me-2"></i>Leave Requests</h6>
									<div class="ms-auto">
										<a href="listing_leave_requests.php" class="text-primary">View all</a>
									</div>
								</div>
								<div class="card-body">
									<div class="mb-3">
										<div class="d-flex justify-content-between align-items-center mb-2">
											<span class="text-success fw-semibold">Approved</span>
											<span class="badge bg-success"><?php echo $approved_leaves; ?></span>
										</div>
										<div class="progress" style="height: 8px;">
											<div class="progress-bar bg-success" style="width: <?php echo $total_leaves > 0 ? ($approved_leaves/$total_leaves)*100 : 0; ?>%"></div>
										</div>
									</div>
									<div class="mb-3">
										<div class="d-flex justify-content-between align-items-center mb-2">
											<span class="text-warning fw-semibold">Pending</span>
											<span class="badge bg-warning"><?php echo $pending_leaves; ?></span>
										</div>
										<div class="progress" style="height: 8px;">
											<div class="progress-bar bg-warning" style="width: <?php echo $total_leaves > 0 ? ($pending_leaves/$total_leaves)*100 : 0; ?>%"></div>
										</div>
									</div>
									<div>
										<div class="d-flex justify-content-between align-items-center mb-2">
											<span class="text-danger fw-semibold">Rejected</span>
											<span class="badge bg-danger"><?php echo $rejected_leaves; ?></span>
										</div>
										<div class="progress" style="height: 8px;">
											<div class="progress-bar bg-danger" style="width: <?php echo $total_leaves > 0 ? ($rejected_leaves/$total_leaves)*100 : 0; ?>%"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>


					<!-- Recent Payslips -->
					<div class="card">
						<div class="card-header d-flex align-items-center">
							<h5 class="mb-0"><i class="ph ph-receipt me-2"></i>Recent Payslips</h5>
							<div class="ms-auto">
								<a href="listing_payslips.php" class="btn btn-sm btn-outline-primary">View all</a>
							</div>
						</div>
						<div class="card-body p-0">
							<div class="table-responsive">
								<table class="table table-hover mb-0">
									<thead class="table-light">
										<tr>
											<th>Employee</th>
											<th>Generated Date</th>
											<th>Month</th>
											<th>Status</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$result = $mysqli->query("SELECT p.*, u.full_name FROM `" . tbl_payslips . "` p LEFT JOIN `" . tbl_users . "` u ON p.employee_id = u.id ORDER BY p.created_at DESC LIMIT 8");
										if ($result->num_rows > 0) {
											while ($row = $result->fetch_array()) {
												$status = $row['status'];
												$status_class = $status == 'generated' ? 'info' : ($status == 'submitted' ? 'success' : 'warning');
										?>
												<tr>
												<td class="py-2 fw-semibold text-truncate" style="max-width: 180px;"><?php echo $row['full_name'] ?? 'N/A'; ?></td>
												<td class="py-2 text-muted text-nowrap"><?php echo date('d M Y', strtotime($row['created_at'] ?? '0000-00-00')); ?></td>
												<td class="py-2 text-muted text-nowrap"><?php echo date('M Y', strtotime($row['created_at'] ?? '0000-00-00')); ?></td>
												<td class="py-2 text-nowrap">
													<span class="badge bg-<?php echo $status_class; ?> bg-opacity-20 text-<?php echo $status_class; ?>">
														<?php echo ucfirst($status); ?>
													</span>
												</td>
												<td class="py-2">
													<a href="view_payslip.php?id=<?php echo $row['id']; ?>" class="btn btn-xs btn-outline-primary">View</a>
												</td>
											</tr>
										<?php
											}
										} else {
											echo '<tr><td class="text-center text-muted py-3" colspan="5">No payslips found</td></tr>';
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<div class="col-lg-4">
					<!-- HR Statistics Sidebar -->
					<div class="card mb-3">
						<div class="card-header">
							<h6 class="mb-0"><i class="ph ph-chart-pie me-2"></i>HR Statistics</h6>
						</div>
						<div class="card-body">
							<div class="mb-3 pb-3 border-bottom">
								<div class="d-flex align-items-center mb-2">
									<i class="ph ph-users text-primary ph-lg me-2"></i>
									<span>Total Employees</span>
									<span class="ms-auto fw-bold h6 mb-0"><?php echo $total_employees; ?></span>
								</div>
								<div class="small text-muted">
									<span class="badge bg-success bg-opacity-20 text-success me-2"><?php echo $active_employees; ?> Active</span>
									<span class="badge bg-danger bg-opacity-20 text-danger"><?php echo $inactive_employees; ?> Inactive</span>
								</div>
							</div>

							<div class="mb-3 pb-3 border-bottom">
								<div class="d-flex align-items-center mb-2">
									<i class="ph ph-briefcase text-info ph-lg me-2"></i>
									<span>Departments</span>
									<span class="ms-auto fw-bold h6 mb-0"><?php echo $total_departments; ?></span>
								</div>
								<div class="progress" style="height: 6px;">
									<div class="progress-bar bg-info" style="width: 100%"></div>
								</div>
							</div>

							<div class="mb-3">
								<div class="d-flex align-items-center mb-2">
									<i class="ph ph-student text-success ph-lg me-2"></i>
									<span>Designations</span>
									<span class="ms-auto fw-bold h6 mb-0"><?php echo $total_designations; ?></span>
								</div>
								<div class="progress" style="height: 6px;">
									<div class="progress-bar bg-success" style="width: 100%"></div>
								</div>
							</div>
						</div>
					</div>

					<!-- Employee Documents -->
					<div class="card mb-3">
						<div class="card-header">
							<h6 class="mb-0"><i class="ph ph-files me-2"></i>Employee Documents</h6>
						</div>
						<div class="card-body">
							<div class="mb-3">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<span class="text-success fw-semibold"><i class="ph ph-check-circle me-1"></i>Up-to-date</span>
									<span class="badge bg-success"><?php echo $up_to_date_documents; ?></span>
								</div>
								<div class="progress" style="height: 8px;">
									<div class="progress-bar bg-success" style="width: <?php echo $total_documents > 0 ? ($up_to_date_documents/$total_documents)*100 : 0; ?>%"></div>
								</div>
							</div>
							<div class="mb-3">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<span class="text-warning fw-semibold"><i class="ph ph-warning me-1"></i>Near Expiry</span>
									<span class="badge bg-warning"><?php echo $near_expiry_documents; ?></span>
								</div>
								<div class="progress" style="height: 8px;">
									<div class="progress-bar bg-warning" style="width: <?php echo $total_documents > 0 ? ($near_expiry_documents/$total_documents)*100 : 0; ?>%"></div>
								</div>
							</div>
							<div>
								<div class="d-flex justify-content-between align-items-center mb-2">
									<span class="text-danger fw-semibold"><i class="ph ph-x-circle me-1"></i>Expired</span>
									<span class="badge bg-danger"><?php echo $expired_documents; ?></span>
								</div>
								<div class="progress" style="height: 8px;">
									<div class="progress-bar bg-danger" style="width: <?php echo $total_documents > 0 ? ($expired_documents/$total_documents)*100 : 0; ?>%"></div>
								</div>
							</div>
						</div>
					</div>

					<!-- Quick Actions -->
					<div class="card">
						<div class="card-header">
							<h6 class="mb-0"><i class="ph ph-lightning me-2"></i>Quick Actions</h6>
						</div>
						<div class="card-body">
							<a href="user.php?action=add" class="d-block btn btn-outline-primary btn-sm mb-2">
								<i class="ph ph-plus me-2"></i>Add New Employee
							</a>
							<a href="listing_attendance.php" class="d-block btn btn-outline-info btn-sm mb-2">
								<i class="ph ph-sign-in me-2"></i>Mark Attendance
							</a>
							<a href="listing_leave_requests.php" class="d-block btn btn-outline-warning btn-sm mb-2">
								<i class="ph ph-calendar-x me-2"></i>Manage Leaves
							</a>
							<a href="listing_payslips.php" class="d-block btn btn-outline-success btn-sm">
								<i class="ph ph-receipt me-2"></i>Payslips
							</a>
						</div>
					</div>
				</div>
			</div>
			<!-- /Dashboard content -->

		</div>
		<!-- /content area -->

		<?php include('admin_elements/copyright.php'); ?>

	</div>
	<!-- /inner content -->

</div>
<!-- /main content -->

<?php include('admin_elements/admin_footer.php'); ?>

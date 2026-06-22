<?php

declare(strict_types=1);
/**
 * @var string $module
 * @var string $moduleCaption
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <?php include 'admin_elements/hr_navbar.php'; ?>
    <div class="page-header page-header-light shadow">
        <div class="page-header-content border-top py-2 px-3">
            <div class="my-1">
                <h5 class="mb-0"><?php echo $moduleCaption; ?></h5>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Welcome to the HR Module Guide</h4>
                    <p class="text-muted">This guide walks you through each HR module step by step.</p>

                    <hr>

                    <!-- Section 1: Users / Employees -->
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-user me-2"></i> Users / Employees</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-user-plus"></i></span>
                                        <div>
                                            <strong>Step 1: Create Employee</strong>
                                            <p class="mb-0 small text-muted">Go to Users / Employees, click New. Fill in role, name, email, password, contact info.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-toggle-left"></i></span>
                                        <div>
                                            <strong>Step 2: System Access</strong>
                                            <p class="mb-0 small text-muted">Toggle <strong>Can Access System</strong> to grant login permissions. Set Date of Joining to trigger entitlements.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-check-circle"></i></span>
                                        <div>
                                            <strong>Step 3: Save</strong>
                                            <p class="mb-0 small text-muted">Click Save. The new employee is now available across all HR modules.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3">
                                        <span class="text-success fs-3 lh-1"><i class="ph-file"></i></span>
                                        <div>
                                            <strong>Manage Documents</strong>
                                            <p class="mb-0 small text-muted">Open employee record, scroll to Documents, select category, upload file, set dates, click Upload.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info d-flex align-items-center gap-2 mt-3 mb-0">
                            <i class="ph-info ph-lg"></i>
                            <div><strong>Tip:</strong> Expired documents are highlighted in red in the User Documents listing.</div>
                        </div>
                    </div>

                    <!-- Section 2: Organization -->
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-users-three me-2"></i> Organization</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-buildings"></i></span>
                                        <div>
                                            <strong>Departments</strong>
                                            <p class="mb-0 small text-muted">Go to Organization &gt; Departments, click New, enter name, and save. Departments group employees for headcount reporting.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-briefcase"></i></span>
                                        <div>
                                            <strong>Designations</strong>
                                            <p class="mb-0 small text-muted">Go to Organization &gt; Designations to add job titles — Manager, Officer, Clerk, etc. Used in payroll and reporting.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Attendance & Leave -->
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-calendar-check me-2"></i> Attendance &amp; Leave</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-tag"></i></span>
                                        <div>
                                            <strong>Leave Types</strong>
                                            <p class="mb-0 small text-muted">Define leave types (Annual, Sick, Emergency). Set Paid Days — first N days are paid, rest unpaid. Default: 3 paid days.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-paper-plane-tilt"></i></span>
                                        <div>
                                            <strong>Leave Requests</strong>
                                            <p class="mb-0 small text-muted">Click New, select employee, leave type, dates. System auto-calculates days and paid/unpaid portions.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-info border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-info fs-3 lh-1"><i class="ph-calendar-star"></i></span>
                                        <div>
                                            <strong>Annual Leave</strong>
                                            <p class="mb-0 small text-muted">After 12 months: 1 month paid leave + AED 1,250 air ticket. At 6 months: HR to-do created to prepare.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Payroll -->
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-calculator me-2"></i> Payroll</h5>
                        <div class="alert alert-info d-flex align-items-center gap-2">
                            <i class="ph-lock-key ph-lg"></i>
                            <div>Payroll is managed by <strong>Accounts Department</strong>. HR has view-only access.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-gear-six"></i></span>
                                        <div>
                                            <strong>Components</strong>
                                            <p class="mb-0 small text-muted">Define earnings (Basic, Housing, Transport) and deductions (Insurance, Loans).</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-stack"></i></span>
                                        <div>
                                            <strong>Salary Structures</strong>
                                            <p class="mb-0 small text-muted">Group components into reusable salary templates for different roles.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-info border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-info fs-3 lh-1"><i class="ph-currency-circle-dollar"></i></span>
                                        <div>
                                            <strong>Payroll Runs</strong>
                                            <p class="mb-0 small text-muted">Accounts creates a Payroll Run. Payslips auto-generate. Mark as Paid once disbursed.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 5: Documents -->
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-file-text me-2"></i> Documents</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-list-magnifying-glass"></i></span>
                                        <div>
                                            <strong>Browse Documents</strong>
                                            <p class="mb-0 small text-muted">Go to <strong>Documents</strong> to view all employee-uploaded documents in one place.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-funnel"></i></span>
                                        <div>
                                            <strong>Filter by Status</strong>
                                            <p class="mb-0 small text-muted">Use expiry filter: <span class="badge bg-danger">Expired</span>, <span class="badge bg-warning">Expiring Soon</span>, <span class="badge bg-success">Valid</span>.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-info border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-info fs-3 lh-1"><i class="ph-download"></i></span>
                                        <div>
                                            <strong>Download</strong>
                                            <p class="mb-0 small text-muted">Click any document name to download or preview it in your browser.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 6: Air Tickets -->
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-airplane me-2"></i> Air Tickets</h5>
                        <div class="alert alert-success d-flex align-items-center gap-2">
                            <i class="ph-airplane-takeoff ph-lg"></i>
                            <div>Employees are eligible for <strong>AED 1,250</strong> air ticket after <strong>12 months</strong> of service.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-eye"></i></span>
                                        <div>
                                            <strong>HR Role</strong>
                                            <p class="mb-0 small text-muted">View air ticket records and eligibility. Receive to-do alerts at 6-month milestone.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-warning border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-warning fs-3 lh-1"><i class="ph-currency-circle-dollar"></i></span>
                                        <div>
                                            <strong>Accounts Role</strong>
                                            <p class="mb-0 small text-muted">Update ticket status: <span class="badge bg-warning">Payable</span> → <span class="badge bg-success">Paid</span>. Record payment date and reference.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 7: Gratuity -->
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-currency-circle-dollar me-2"></i> Gratuity</h5>
                        <div class="alert alert-info d-flex align-items-center gap-2">
                            <i class="ph-info ph-lg"></i>
                            <div>End-of-service gratuity calculated per <strong>UAE Labour Law</strong>, based on <strong>last basic salary</strong> at time of exit.</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr><th>Service Duration</th><th>Entitlement</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td>Less than 1 year</td><td><span class="badge bg-secondary">No gratuity</span></td></tr>
                                    <tr><td>1 – 5 years</td><td>21 days' basic salary per year</td></tr>
                                    <tr><td>More than 5 years</td><td>30 days' basic salary per year</td></tr>
                                    <tr><td colspan="2" class="text-muted"><i class="ph-info me-1"></i>Maximum: 2 years' total salary</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Section 8: Reports -->
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-chart-line-up me-2"></i> Reports</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-primary border-3 text-center">
                                    <span class="text-primary fs-2 lh-1 mb-2"><i class="ph-users-three"></i></span>
                                    <strong class="small">Headcount</strong>
                                    <p class="mb-0 small text-muted">By department</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-success border-3 text-center">
                                    <span class="text-success fs-2 lh-1 mb-2"><i class="ph-currency-circle-dollar"></i></span>
                                    <strong class="small">Payroll Summary</strong>
                                    <p class="mb-0 small text-muted">Gross, deductions, net</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-info border-3 text-center">
                                    <span class="text-info fs-2 lh-1 mb-2"><i class="ph-calendar-check"></i></span>
                                    <strong class="small">Leave Utilization</strong>
                                    <p class="mb-0 small text-muted">Summary by type</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-warning border-3 text-center">
                                    <span class="text-warning fs-2 lh-1 mb-2"><i class="ph-chart-line-up"></i></span>
                                    <strong class="small">Gratuity</strong>
                                    <p class="mb-0 small text-muted">Projections</p>
                                </div>
                            </div>
                        </div>
                        <p class="small text-muted mt-2 mb-0"><i class="ph-arrow-right me-1"></i>Go to <strong>Reports</strong> to view all HR analytics.</p>
                    </div>
                </div>
            </div>

            <?php include 'admin_elements/copyright.php'; ?>
        </div>
    </div>
</div>
<?php include 'admin_elements/admin_footer.php'; ?>

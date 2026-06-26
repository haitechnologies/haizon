<?php

declare(strict_types=1);
/**
 * @var string $module
 * @var string $moduleCaption
 */
include 'admin_elements/admin_header.php';
?>
<div class="content-wrapper">
    <div class="content-inner">
        <div class="content">
            <?php include 'admin_elements/breadcrumb.php'; ?>

            <!-- Quick Nav - outside card -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="#section-employees" class="btn btn-sm btn-outline-primary"><i class="ph-user me-1"></i>Employees</a>
                <a href="#section-org" class="btn btn-sm btn-outline-primary"><i class="ph-buildings me-1"></i>Organization</a>
                <a href="#section-doccat" class="btn btn-sm btn-outline-primary"><i class="ph-folder me-1"></i>Document Categories</a>
                <a href="#section-attendance" class="btn btn-sm btn-outline-primary"><i class="ph-calendar-check me-1"></i>Attendance &amp; Leave</a>
                <a href="#section-payroll" class="btn btn-sm btn-outline-primary"><i class="ph-calculator me-1"></i>Payroll</a>
                <a href="#section-airtickets" class="btn btn-sm btn-outline-primary"><i class="ph-airplane me-1"></i>Air Tickets</a>
                <a href="#section-gratuity" class="btn btn-sm btn-outline-primary"><i class="ph-currency-circle-dollar me-1"></i>Gratuity</a>
                <a href="#section-dashboard" class="btn btn-sm btn-outline-primary"><i class="ph-gauge me-1"></i>HR Dashboard</a>
            </div>

            <div class="card">
                <div class="card-body">

                    <!-- ============================================ -->
                    <!-- Section 1: Users / Employees -->
                    <!-- ============================================ -->
                    <div class="mb-4" id="section-employees">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-user me-2"></i> Users / Employees</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-user-plus"></i></span>
                                        <div>
                                            <strong>Step 1: Create Employee</strong>
                                            <p class="mb-0 small text-muted">Go to <strong>Users</strong>, click New. Fill in role, name, email, password, and contact info (UAE +971 / PAK +92).</p>
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
                                            <p class="mb-0 small text-muted">Toggle <strong>Can Access System</strong> to grant login access. Set Date of Joining (mandatory) and Date of Birth.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-shield-check"></i></span>
                                        <div>
                                            <strong>System Role</strong>
                                            <p class="mb-0 small text-muted">System Role controls permissions. Non-admin users see a read-only bold <span class="badge bg-dark">ROLE NAME</span> label instead of the dropdown.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-semibold mt-4 mb-2"><i class="ph-file-text me-1"></i> Employee Documents</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-upload"></i></span>
                                        <div>
                                            <strong>Add Document</strong>
                                            <p class="mb-0 small text-muted">Select category, choose file, set Issue/Expiry dates (<span class="text-nowrap">dd-mm-yyyy</span>), click Upload.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-warning border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-warning fs-3 lh-1"><i class="ph-warning-circle"></i></span>
                                        <div>
                                            <strong>Mandatory Documents</strong>
                                            <p class="mb-0 small text-muted">Categories marked <span class="badge bg-warning">Required</span> are mandatory. A warning banner lists any missing required documents.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-info border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-info fs-3 lh-1"><i class="ph-pencil-simple"></i></span>
                                        <div>
                                            <strong>Edit Dates</strong>
                                            <p class="mb-0 small text-muted">Click the <i class="ph-pencil-simple"></i> icon to edit Issue / Expiry dates inline via a popup modal.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-danger border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-danger fs-3 lh-1"><i class="ph-trash"></i></span>
                                        <div>
                                            <strong>Delete</strong>
                                            <p class="mb-0 small text-muted">Click the <i class="ph-trash"></i> icon to remove a document. Confirmation required.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- Section 2: Organization -->
                    <!-- ============================================ -->
                    <div class="mb-4" id="section-org">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-buildings me-2"></i> Organization</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-buildings"></i></span>
                                        <div>
                                            <strong>Departments</strong>
                                            <p class="mb-0 small text-muted">Go to <strong>Departments</strong>, click New, enter name, and save. Each department shows employee count in the listing.</p>
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
                                            <p class="mb-0 small text-muted">Go to <strong>Designations</strong> to add job titles — Manager, Officer, Clerk, etc. Each shows employee count in the listing.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- Section 3: Document Categories -->
                    <!-- ============================================ -->
                    <div class="mb-4" id="section-doccat">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-folder me-2"></i> Document Categories</h5>
                        <div class="alert alert-info d-flex align-items-center gap-2">
                            <i class="ph-info ph-lg"></i>
                            <div>Document Categories define what types of documents employees must upload. Categories marked <strong>Mandatory</strong> trigger a warning banner if missing.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-plus-circle"></i></span>
                                        <div>
                                            <strong>Create Category</strong>
                                            <p class="mb-0 small text-muted">Go to <strong>Document Categories</strong>, click New. Enter name, select type (Employees / Company), and check Mandatory if required.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-warning border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-warning fs-3 lh-1"><i class="ph-star"></i></span>
                                        <div>
                                            <strong>Mandatory Categories</strong>
                                            <p class="mb-0 small text-muted">Emirates ID, Passport, Visa, Labor Card, Photo, and Contract are mandatory. Marked with <span class="badge bg-warning">Required</span> badge.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-sort-ascending"></i></span>
                                        <div>
                                            <strong>Smart Sorting</strong>
                                            <p class="mb-0 small text-muted">Categories are listed Employee-types first, then Company. In the employee form, Mandatory categories appear at the top.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- Section 4: Attendance & Leave -->
                    <!-- ============================================ -->
                    <div class="mb-4" id="section-attendance">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-calendar-check me-2"></i> Attendance &amp; Leave</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-fingerprint"></i></span>
                                        <div>
                                            <strong>Attendance Devices</strong>
                                            <p class="mb-0 small text-muted">Employees clock in/out via biometric devices. Data syncs to the system automatically.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-tag"></i></span>
                                        <div>
                                            <strong>Leave Types</strong>
                                            <p class="mb-0 small text-muted">3 types only: Annual Leave (12 months DOJ, 1 month paid + ticket), Sick Leave (paid per medical certificate), Urgent Leave (3 days paid, 1x/year from DOJ).</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-paper-plane-tilt"></i></span>
                                        <div>
                                            <strong>Leave Requests</strong>
                                            <p class="mb-0 small text-muted">Click New, select employee, leave type, and dates. System auto-calculates days and paid/unpaid portions.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-info border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-info fs-3 lh-1"><i class="ph-calendar-star"></i></span>
                                        <div>
                                            <strong>Annual Leave</strong>
                                            <p class="mb-0 small text-muted">After 12 months: 1 month paid leave + AED 1,250 air ticket. At 6 months a to-do reminder is created.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- Section 5: Payroll -->
                    <!-- ============================================ -->
                    <div class="mb-4" id="section-payroll">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-calculator me-2"></i> Payroll</h5>
                        <div class="alert alert-info d-flex align-items-center gap-2">
                            <i class="ph-info ph-lg"></i>
                            <div>Payroll is managed by <strong>Accounts Department</strong>. HR has view-only access to payslips and reports.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-gear-six"></i></span>
                                        <div>
                                            <strong>Payroll Components</strong>
                                            <p class="mb-0 small text-muted">Define earnings (Basic, Housing, Transport) and deductions (Insurance, Loans) that make up a salary.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-stack"></i></span>
                                        <div>
                                            <strong>Salary Structures</strong>
                                            <p class="mb-0 small text-muted">Group components into reusable salary templates assigned to employees.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-warning border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-warning fs-3 lh-1"><i class="ph-currency-circle-dollar"></i></span>
                                        <div>
                                            <strong>Payroll Runs</strong>
                                            <p class="mb-0 small text-muted">Accounts creates a Payroll Run (draft). Click <strong>Generate Payslips</strong> to auto-compute earnings/deductions for all employees with salary structures.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-body h-100 border-start border-info border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-info fs-3 lh-1"><i class="ph-file-text"></i></span>
                                        <div>
                                            <strong>Payslips</strong>
                                            <p class="mb-0 small text-muted">View individual payslips with full earnings/deductions breakdown. <strong>Mark as Paid</strong> once salary is disbursed.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- Section 6: Air Tickets -->
                    <!-- ============================================ -->
                    <div class="mb-4" id="section-airtickets">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-airplane me-2"></i> Air Tickets</h5>
                        <div class="alert alert-success d-flex align-items-center gap-2">
                            <i class="ph-airplane-takeoff ph-lg"></i>
                            <div>Employees are eligible for <strong>AED 1,250</strong> air ticket after <strong>12 months</strong> of service.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-eye"></i></span>
                                        <div>
                                            <strong>View Records</strong>
                                            <p class="mb-0 small text-muted">HR can view air ticket eligibility and status per employee. Pending requests appear on the HR Dashboard.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-warning border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-warning fs-3 lh-1"><i class="ph-timer"></i></span>
                                        <div>
                                            <strong>6-Month Alert</strong>
                                            <p class="mb-0 small text-muted">A to-do reminder is generated at 6 months of service so HR can prepare for the upcoming entitlement.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-check-circle"></i></span>
                                        <div>
                                            <strong>Payment</strong>
                                            <p class="mb-0 small text-muted">Accounts updates the status to <span class="badge bg-success">Paid</span> after disbursement, recording payment reference and date.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- Section 7: Gratuity -->
                    <!-- ============================================ -->
                    <div class="mb-4" id="section-gratuity">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-currency-circle-dollar me-2"></i> Gratuity (End of Service)</h5>
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
                                    <tr><td>1 – 5 years</td><td>21 days' basic salary per year of service</td></tr>
                                    <tr><td>More than 5 years</td><td>30 days' basic salary per year of service</td></tr>
                                    <tr><td colspan="2" class="text-muted"><i class="ph-info me-1"></i>Maximum cap: 2 years' total basic salary</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- Section 8: HR Dashboard -->
                    <!-- ============================================ -->
                    <div class="mb-4" id="section-dashboard">
                        <h5 class="fw-semibold mb-3 pb-2 border-bottom"><i class="ph-gauge me-2"></i> HR Dashboard</h5>
                        <p class="text-muted small mb-3">The HR Dashboard (<code>dashboard_hr.php</code>) is the first page HR users see after login. It shows a complete daily overview:</p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-gauge"></i></span>
                                        <div>
                                            <strong>KPI Cards</strong>
                                            <p class="mb-0 small text-muted">Present Today, Pending Leaves, and Pending Air Tickets counts at a glance.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-primary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-primary fs-3 lh-1"><i class="ph-clock"></i></span>
                                        <div>
                                            <strong>Live Clocks</strong>
                                            <p class="mb-0 small text-muted">UAE (UTC+4) and Pakistan (UTC+5) clocks with date displayed in the page header, updated every second.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-body h-100 border-start border-info border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-info fs-3 lh-1"><i class="ph-calendar-star"></i></span>
                                        <div>
                                            <strong>UAE Public Holidays</strong>
                                            <p class="mb-0 small text-muted">Interactive table with all 2026 UAE holidays. Click any row to copy the holiday announcement.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-warning border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-warning fs-3 lh-1"><i class="ph-airplane"></i></span>
                                        <div>
                                            <strong>Pending Air Tickets</strong>
                                            <p class="mb-0 small text-muted">List of pending/payable air tickets with employee, amount, eligibility date, and direct link to manage.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-danger border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-danger fs-3 lh-1"><i class="ph-calendar-x"></i></span>
                                        <div>
                                            <strong>Pending Leave Requests</strong>
                                            <p class="mb-0 small text-muted">Pending leave requests with employee, type, dates, days, and a Review button to approve/decline.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-success border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-success fs-3 lh-1"><i class="ph-sign-in"></i></span>
                                        <div>
                                            <strong>Today's Attendance</strong>
                                            <p class="mb-0 small text-muted">Shows all employees who clocked in today with check-in/out times, hours, and status.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-body h-100 border-start border-secondary border-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <span class="text-secondary fs-3 lh-1"><i class="ph-chart-line-up"></i></span>
                                        <div>
                                            <strong>HR Reports</strong>
                                            <p class="mb-0 small text-muted">Access headcount, leave utilization, and payroll summary reports from the HR Report page.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php include 'admin_elements/copyright.php'; ?>
    </div>
</div>
<?php include 'admin_elements/admin_footer.php'; ?>

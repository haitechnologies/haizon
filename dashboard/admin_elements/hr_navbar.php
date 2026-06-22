<?php
if (!dashboardHasSystemAccess('hr')) {
    return;
}

$hrCurrentFile = basename((string)($_SERVER['PHP_SELF'] ?? ''));

$hrGroups = [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => 'ph-chart-bar',
        'link' => 'dashboard_hr.php',
        'files' => ['dashboard_hr.php'],
    ],
    'users' => [
        'label' => 'Users / Employees',
        'icon' => 'ph-user',
        'link' => 'listing_users.php',
        'files' => ['listing_users.php', 'users.php'],
    ],
    'departments' => [
        'label' => 'Departments',
        'icon' => 'ph-buildings',
        'link' => 'listing_departments.php',
        'files' => ['listing_departments.php', 'departments.php'],
    ],
    'designations' => [
        'label' => 'Designations',
        'icon' => 'ph-briefcase',
        'link' => 'listing_designations.php',
        'files' => ['listing_designations.php', 'designations.php'],
    ],
    'attendance_leave' => [
        'label' => 'Attendance & Leave',
        'icon' => 'ph-calendar-check',
        'link' => 'listing_attendance.php',
        'files' => ['listing_attendance.php', 'attendance.php', 'listing_leave_requests.php', 'leave_requests.php', 'listing_leave_types.php', 'leave_types.php', 'listing_annual_leave_entitlements.php', 'annual_leave_entitlements.php', 'listing_attendance_devices.php', 'attendance_devices.php'],
        'children' => [
            'listing_attendance.php' => 'Attendance',
            'listing_leave_requests.php' => 'Leave Requests',
            'listing_leave_types.php' => 'Leave Types',
            'listing_annual_leave_entitlements.php' => 'Annual Leave',
            'listing_attendance_devices.php' => 'Device Sync',
        ],
    ],
    'payroll' => [
        'label' => 'Payroll',
        'icon' => 'ph-calculator',
        'link' => 'listing_payroll_runs.php',
        'files' => ['listing_payroll_components.php', 'payroll_components.php', 'listing_salary_structures.php', 'salary_structures.php', 'listing_employee_salaries.php', 'listing_payroll_runs.php', 'payroll_runs.php', 'view_payroll_run.php', 'listing_payslips.php', 'payslips.php', 'view_payslip.php'],
        'children' => [
            'listing_payroll_components.php' => 'Payroll Components',
            'listing_salary_structures.php' => 'Salary Structures',
            'listing_employee_salaries.php' => 'Employee Salaries',
            'listing_payroll_runs.php' => 'Payroll Runs',
            'listing_payslips.php' => 'Payslips',
        ],
    ],
    'documents' => [
        'label' => 'Documents',
        'icon' => 'ph-file-text',
        'link' => 'listing_user_documents.php',
        'files' => ['listing_user_documents.php', 'user_documents.php'],
    ],
    'hr_todo' => [
        'label' => 'To-Do Tasks',
        'icon' => 'ph-check-square',
        'link' => 'listing_hr_todo_tasks.php',
        'files' => ['listing_hr_todo_tasks.php', 'hr_todo_tasks.php'],
    ],
    'reports' => [
        'label' => 'Reports',
        'icon' => 'ph-chart-line-up',
        'link' => 'report_hr.php',
        'files' => ['report_hr.php'],
    ],
    'guide' => [
        'label' => 'Help / Guide',
        'icon' => 'ph-question',
        'link' => 'hr_guide.php',
        'files' => ['hr_guide.php'],
    ],
];

$hrActiveGroup = null;
foreach ($hrGroups as $key => $group) {
    if (in_array($hrCurrentFile, $group['files'])) {
        $hrActiveGroup = $key;
        break;
    }
}
?>
<div class="card mb-3">
    <div class="card-body p-0">
        <ul class="nav nav-tabs nav-tabs-bottom border-bottom-0">
            <?php foreach ($hrGroups as $key => $group): ?>
                <?php
                $isActive = ($key === $hrActiveGroup);
                $hasChildren = !empty($group['children']);
                ?>
                <?php if ($hasChildren): ?>
                    <li class="nav-item dropdown">
                        <a href="<?php echo $group['link']; ?>"
                           class="nav-link dropdown-toggle <?php echo $isActive ? 'active' : ''; ?>"
                           data-bs-toggle="dropdown"
                           aria-expanded="false">
                            <i class="<?php echo $group['icon']; ?> me-2"></i><?php echo $group['label']; ?>
                        </a>
                        <div class="dropdown-menu">
                            <?php foreach ($group['children'] as $childFile => $childLabel): ?>
                                <?php
                                $isChildActive = ($childFile === $hrCurrentFile);
                                ?>
                                <a href="<?php echo $childFile; ?>"
                                   class="dropdown-item <?php echo $isChildActive ? 'active' : ''; ?>">
                                    <?php echo $childLabel; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?php echo $group['link']; ?>"
                           class="nav-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="<?php echo $group['icon']; ?> me-2"></i><?php echo $group['label']; ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

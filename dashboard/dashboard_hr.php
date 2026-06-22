<?php

use App\Core\DB;
use App\Security\Roles;

include('admin_elements/admin_header.php');

$module = 'report_hr';
$module_caption = 'HR Dashboard';
$tbl_name = $tbl_prefix . $module;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$today = date('Y-m-d');

$employeeFilter = " AND u.organization_id = " . (int)$activeOrganizationId . " AND u.id > 1";

// Today's Attendance with employee details
$attendanceQuery = "
    SELECT a.*, u.full_name
    FROM `" . DB::ATTENDANCE . "` a
    JOIN `" . DB::USERS . "` u ON u.id = a.employee_id
    WHERE a.work_date = '$today' $employeeFilter
    ORDER BY a.check_in ASC
";
$attendanceResult = $mysqli->query($attendanceQuery);
$todayAttendance = $attendanceResult ? $attendanceResult->fetch_all(MYSQLI_ASSOC) : [];
$todayPresentCount = 0;
$todayAbsentCount = 0;
$todayLeaveCount = 0;
foreach ($todayAttendance as $a) {
    $s = $a['status'] ?? '';
    if ($s === 'present') {
        $todayPresentCount++;
    } elseif ($s === 'absent') {
        $todayAbsentCount++;
    } elseif ($s === 'on_leave' || $s === 'leave') {
        $todayLeaveCount++;
    }
}
$totalEmployeesToday = count($todayAttendance);

// Pending Leave Requests
$pendingLeavesQuery = "
    SELECT lr.*, u.full_name, lt.leave_type
    FROM `" . DB::LEAVE_REQUESTS . "` lr
    JOIN `" . DB::USERS . "` u ON u.id = lr.employee_id
    LEFT JOIN `" . DB::LEAVE_TYPES . "` lt ON lt.id = lr.leave_type_id
    WHERE lr.status = 'pending' $employeeFilter
    ORDER BY lr.created_at DESC LIMIT 10
";
$pendingLeavesResult = $mysqli->query($pendingLeavesQuery);
$pendingLeaves = $pendingLeavesResult ? $pendingLeavesResult->fetch_all(MYSQLI_ASSOC) : [];

$pendingLeavesCount = $mysqli->query("
    SELECT COUNT(*) as cnt FROM `" . DB::LEAVE_REQUESTS . "` lr
    JOIN `" . DB::USERS . "` u ON u.id = lr.employee_id
    WHERE lr.status = 'pending' $employeeFilter
")->fetch_assoc()['cnt'] ?? 0;

// Pending Air Tickets
$pendingAirTicketsQuery = "
    SELECT at.*, u.full_name
    FROM `" . DB::AIR_TICKETS . "` at
    JOIN `" . DB::USERS . "` u ON u.id = at.employee_id
    WHERE at.status IN ('pending', 'payable') $employeeFilter
    ORDER BY at.eligibility_date ASC LIMIT 10
";
$pendingAirTicketsResult = $mysqli->query($pendingAirTicketsQuery);
$pendingAirTickets = $pendingAirTicketsResult ? $pendingAirTicketsResult->fetch_all(MYSQLI_ASSOC) : [];

$pendingAirTicketsCount = $mysqli->query("
    SELECT COUNT(*) as cnt FROM `" . DB::AIR_TICKETS . "` at
    JOIN `" . DB::USERS . "` u ON u.id = at.employee_id
    WHERE at.status IN ('pending', 'payable') $employeeFilter
")->fetch_assoc()['cnt'] ?? 0;

// Expiring & Expired Employee Documents (next 30 days or already expired)
$documentsQuery = "
    SELECT ud.*, u.full_name, dc.document_category
    FROM `" . DB::USER_DOCUMENTS . "` ud
    JOIN `" . DB::USERS . "` u ON u.id = ud.attachable_id
    LEFT JOIN `" . DB::DOCUMENT_CATEGORIES . "` dc ON dc.id = ud.document_category
    WHERE ud.attachable_type = 'EmployeeDoc'
    AND ud.expiry_date != '1970-01-01' AND ud.expiry_date IS NOT NULL
    AND ud.expiry_date <= '" . date('Y-m-d', strtotime('+30 days')) . "'
    $employeeFilter
    ORDER BY ud.expiry_date ASC LIMIT 15
";
$documentsResult = $mysqli->query($documentsQuery);
$expiringDocuments = $documentsResult ? $documentsResult->fetch_all(MYSQLI_ASSOC) : [];

$expiringDocsCount = $mysqli->query("
    SELECT COUNT(*) as cnt FROM `" . DB::USER_DOCUMENTS . "` ud
    JOIN `" . DB::USERS . "` u ON u.id = ud.attachable_id
    WHERE ud.attachable_type = 'EmployeeDoc'
    AND ud.expiry_date != '1970-01-01' AND ud.expiry_date IS NOT NULL
    AND ud.expiry_date <= '" . date('Y-m-d', strtotime('+30 days')) . "'
    $employeeFilter
")->fetch_assoc()['cnt'] ?? 0;

?>
<div class="content-wrapper">
    <?php include('admin_elements/hr_navbar.php'); ?>

    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex border-top py-2 px-3">
            <div class="d-flex align-items-center py-2 mb-2 mb-lg-0 flex-fill">
                <div class="me-3 ms-2">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2">
                        <i class="ph ph-users-three ph-2x"></i>
                    </div>
                </div>
                <div class="flex-fill">
                    <h4 class="mb-0">HR Dashboard</h4>
                    <span class="text-muted">Daily overview — <?php echo date('l, j F Y'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <!-- KPI Cards -->
            <div class="row mb-3 g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-body border-start border-success border-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <h3 class="mb-0"><?php echo $todayPresentCount; ?></h3>
                                <span class="text-muted small">Present Today</span>
                                <div class="mt-1">
                                    <span class="badge bg-success bg-opacity-20 text-success">
                                        <i class="ph-check-circle"></i> <?php echo $todayPresentCount; ?>
                                    </span>
                                    <span class="badge bg-danger bg-opacity-20 text-danger ms-1">
                                        <?php echo $todayAbsentCount; ?> Absent
                                    </span>
                                    <span class="badge bg-warning bg-opacity-20 text-warning ms-1">
                                        <?php echo $todayLeaveCount; ?> Leave
                                    </span>
                                </div>
                            </div>
                            <div class="ms-3 text-success">
                                <i class="ph-sign-in ph-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-body border-start border-warning border-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <h3 class="mb-0"><?php echo $pendingLeavesCount; ?></h3>
                                <span class="text-muted small">Pending Leaves</span>
                                <div class="mt-1">
                                    <a href="listing_leave_requests.php" class="small">Manage <i class="ph-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                            <div class="ms-3 text-warning">
                                <i class="ph-calendar-x ph-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-body border-start border-info border-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <h3 class="mb-0"><?php echo $pendingAirTicketsCount; ?></h3>
                                <span class="text-muted small">Pending Air Tickets</span>
                                <div class="mt-1">
                                    <a href="listing_air_tickets.php" class="small">Manage <i class="ph-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                            <div class="ms-3 text-info">
                                <i class="ph-airplane ph-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-body border-start border-danger border-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <h3 class="mb-0"><?php echo $expiringDocsCount; ?></h3>
                                <span class="text-muted small">Expiring/Expired Documents</span>
                                <div class="mt-1">
                                    <a href="listing_users.php" class="small">View employees <i class="ph-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                            <div class="ms-3 text-danger">
                                <i class="ph-files ph-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Today's Attendance + Holidays Calendar -->
            <div class="row g-3 mb-3">
                <div class="col-xl-8">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <h6 class="mb-0"><i class="ph-sign-in me-2"></i>Today's Attendance</h6>
                            <div class="ms-auto">
                                <a href="listing_attendance.php" class="btn btn-sm btn-outline-primary">View All</a>
                                <a href="attendance_devices.php" class="btn btn-sm btn-outline-info ms-1">Sync Device</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Hours</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($todayAttendance)) : ?>
                                            <?php foreach ($todayAttendance as $a) :
                                                $status = $a['status'] ?? 'present';
                                                $statusClass = match ($status) {
                                                    'present' => 'success',
                                                    'absent' => 'danger',
                                                    'late' => 'warning',
                                                    'half_day' => 'info',
                                                    'on_leave' => 'secondary',
                                                    default => 'secondary'
                                                };
                                                $checkIn = $a['check_in'] ? htmlspecialchars($a['check_in']) : '<span class="text-muted">--</span>';
                                                $checkOut = $a['check_out'] ? htmlspecialchars($a['check_out']) : '<span class="text-muted">--</span>';
                                                $hoursDisplay = $a['total_hours'] > 0 ? number_format((float)$a['total_hours'], 1) . 'h' : '<span class="text-muted">--</span>';
    ?>
                                            <tr>
                                                <td class="py-2 fw-semibold"><?php echo htmlspecialchars($a['full_name'] ?? ''); ?></td>
                                                <td class="py-2"><?php echo $checkIn; ?></td>
                                                <td class="py-2"><?php echo $checkOut; ?></td>
                                                <td class="py-2"><?php echo $hoursDisplay; ?></td>
                                                <td class="py-2">
                                                    <span class="badge bg-<?php echo $statusClass; ?> bg-opacity-20 text-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr><td class="text-center text-muted py-4" colspan="5">No attendance records for today</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <h6 class="mb-0"><i class="ph-calendar me-2"></i>UAE Public Holidays <?php echo date('Y'); ?></h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="px-3 py-2">Holiday</th>
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $uaeHolidays = [
                                            ['New Year\'s Day', date('Y') . '-01-01'],
                                            ['Eid Al Fitr', date('Y') . '-03-19'],
                                            ['Eid Al Fitr Holiday', date('Y') . '-03-20'],
                                            ['Eid Al Fitr Holiday', date('Y') . '-03-21'],
                                            ['Eid Al Fitr Holiday', date('Y') . '-03-22'],
                                            ['Arafat Day', date('Y') . '-05-26'],
                                            ['Eid Al Adha', date('Y') . '-05-27'],
                                            ['Eid Al Adha Holiday', date('Y') . '-05-28'],
                                            ['Eid Al Adha Holiday', date('Y') . '-05-29'],
                                            ['Islamic New Year', date('Y') . '-06-15'],
                                            ['Prophet Muhammad\'s Birthday', date('Y') . '-08-24'],
                                            ['National Day', date('Y') . '-12-02'],
                                            ['National Day Holiday', date('Y') . '-12-03'],
                                        ];
                                        $todayDate = date('Y-m-d');
                                        foreach ($uaeHolidays as $h) :
                                            $hDate = $h[1];
                                            $isPast = $hDate < $todayDate;
                                            $isToday = $hDate === $todayDate;
                                            $rowClass = $isToday ? 'table-primary' : ($isPast ? 'text-muted' : '');
                                            $badgeBg = $isToday ? 'bg-primary' : ($isPast ? 'bg-secondary' : 'bg-success');
                                            $badgeText = $isToday ? 'Today' : ($isPast ? 'Passed' : 'Upcoming');
                                            $badgeTextClass = $isToday ? 'text-primary' : ($isPast ? 'text-secondary' : 'text-success');
                                            ?>
                                        <tr class="<?php echo $rowClass; ?>">
                                            <td class="px-3 py-1"><?php echo $h[0]; ?></td>
                                            <td class="px-3 py-1 text-nowrap"><?php echo date('d M Y', strtotime($hDate)); ?></td>
                                            <td class="px-3 py-1"><span class="badge <?php echo $badgeBg; ?> bg-opacity-20 <?php echo $badgeTextClass; ?>"><?php echo $badgeText; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Pending Leaves + Pending Air Tickets -->
            <div class="row g-3 mb-3">
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <h6 class="mb-0"><i class="ph-calendar-x me-2"></i>Pending Leave Requests</h6>
                            <div class="ms-auto">
                                <a href="listing_leave_requests.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee</th>
                                            <th>Type</th>
                                            <th>Dates</th>
                                            <th>Days</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($pendingLeaves)) : ?>
                                            <?php foreach ($pendingLeaves as $lr) :
                                                $startDate = $lr['start_date'] ?? '';
                                                $endDate = $lr['end_date'] ?? '';
                                                $dateRange = $startDate;
                                                if ($endDate && $endDate !== $startDate) {
                                                    $dateRange .= ' &rarr; ' . $endDate;
                                                }
                                                ?>
                                            <tr>
                                                <td class="py-2 fw-semibold"><?php echo htmlspecialchars($lr['full_name'] ?? ''); ?></td>
                                                <td class="py-2"><?php echo htmlspecialchars($lr['leave_type'] ?? 'N/A'); ?></td>
                                                <td class="py-2 text-nowrap"><?php echo $dateRange; ?></td>
                                                <td class="py-2"><?php echo (int)($lr['total_days'] ?? 1); ?></td>
                                                <td class="py-2">
                                                    <a href="leave_requests.php?id=<?php echo (int)$lr['id']; ?>" class="btn btn-xs btn-outline-primary">Review</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr><td class="text-center text-muted py-4" colspan="5">No pending leave requests</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <h6 class="mb-0"><i class="ph-airplane me-2"></i>Pending Air Tickets</h6>
                            <div class="ms-auto">
                                <a href="listing_air_tickets.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee</th>
                                            <th>Amount</th>
                                            <th>Eligibility</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($pendingAirTickets)) : ?>
                                            <?php foreach ($pendingAirTickets as $at) :
                                                $atStatus = $at['status'] ?? 'pending';
                                                $atStatusClass = $atStatus === 'payable' ? 'warning' : 'secondary';
                                                $eligDate = $at['eligibility_date'] ?? '';
                                                $eligDisplay = (!empty($eligDate) && $eligDate !== '0000-00-00') ? htmlspecialchars($eligDate) : '-';
                                                ?>
                                            <tr>
                                                <td class="py-2 fw-semibold"><?php echo htmlspecialchars($at['full_name'] ?? ''); ?></td>
                                                <td class="py-2">AED <?php echo number_format((float)($at['entitlement_amount'] ?? 0), 2); ?></td>
                                                <td class="py-2 text-nowrap"><?php echo $eligDisplay; ?></td>
                                                <td class="py-2">
                                                    <span class="badge bg-<?php echo $atStatusClass; ?> bg-opacity-20 text-<?php echo $atStatusClass; ?>">
                                                        <?php echo ucfirst($atStatus); ?>
                                                    </span>
                                                </td>
                                                <td class="py-2">
                                                    <a href="air_tickets.php?id=<?php echo (int)$at['id']; ?>" class="btn btn-xs btn-outline-primary">View</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr><td class="text-center text-muted py-4" colspan="5">No pending air tickets</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 4: Expiring/Expired Employee Documents -->
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h6 class="mb-0"><i class="ph-files me-2"></i>Expiring & Expired Employee Documents</h6>
                    <div class="ms-auto">
                        <span class="badge bg-danger bg-opacity-20 text-danger me-2"><?php echo $expiringDocsCount; ?> need attention</span>
                        <a href="listing_users.php" class="btn btn-sm btn-outline-primary">Manage Employees</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Document</th>
                                    <th>Category</th>
                                    <th>Issue Date</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($expiringDocuments)) : ?>
                                    <?php foreach ($expiringDocuments as $doc) :
                                        $expiry = $doc['expiry_date'] ?? '';
                                        $daysLeft = $expiry ? floor((strtotime($expiry) - strtotime($today)) / 86400) : 999;
                                        if ($daysLeft < 0) {
                                            $badgeClass = 'danger';
                                            $statusText = 'Expired';
                                        } elseif ($daysLeft <= 7) {
                                            $badgeClass = 'warning';
                                            $statusText = $daysLeft . ' days';
                                        } elseif ($daysLeft <= 30) {
                                            $badgeClass = 'info';
                                            $statusText = $daysLeft . ' days';
                                        } else {
                                            $badgeClass = 'success';
                                            $statusText = $daysLeft . ' days';
                                        }
                                        $issuedDate = $doc['issued_date'] ?? '';
                                        $issuedDisplay = (!empty($issuedDate) && $issuedDate !== '1970-01-01') ? htmlspecialchars($issuedDate) : '-';
                                        ?>
                                    <tr>
                                        <td class="py-2 fw-semibold"><?php echo htmlspecialchars($doc['full_name'] ?? ''); ?></td>
                                        <td class="py-2">
                                            <a href="../uploads/user_documents/<?php echo rawurlencode($doc['filename'] ?? ''); ?>" target="_blank">
                                                <?php echo htmlspecialchars($doc['display_name'] ?? $doc['original_filename'] ?? 'View'); ?>
                                            </a>
                                        </td>
                                        <td class="py-2 text-muted"><?php echo htmlspecialchars($doc['document_category'] ?? '-'); ?></td>
                                        <td class="py-2 text-nowrap"><?php echo $issuedDisplay; ?></td>
                                        <td class="py-2 text-nowrap"><?php echo htmlspecialchars($expiry); ?></td>
                                        <td class="py-2">
                                            <span class="badge bg-<?php echo $badgeClass; ?> bg-opacity-20 text-<?php echo $badgeClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td class="text-center text-muted py-4" colspan="6">All employee documents are up to date</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>

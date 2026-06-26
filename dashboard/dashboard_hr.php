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

$holidays = [
    ['name' => "New Year's Day",           'date' => '01-01-2026',              'period' => '01-01-2026',                                   'days' => 1],
    ['name' => 'Eid Al Fitr',              'date' => '20-03-2026 to 23-03-2026','period' => '20-03-2026 to 23-03-2026',                     'days' => 4],
    ['name' => 'Arafat Day',               'date' => '27-05-2026',              'period' => '27-05-2026',                                   'days' => 1],
    ['name' => 'Eid Al Adha',              'date' => '28-05-2026 to 31-05-2026','period' => '28-05-2026 to 31-05-2026',                     'days' => 4],
    ['name' => 'Islamic New Year',         'date' => '16-06-2026',              'period' => '16-06-2026',                                   'days' => 1],
    ['name' => "Prophet Muhammad's Birthday",'date'=> '26-08-2026',             'period' => '26-08-2026',                                   'days' => 1],
    ['name' => 'Commemoration Day',        'date' => '01-12-2026',              'period' => '01-12-2026',                                   'days' => 1],
    ['name' => 'National Day',             'date' => '02-12-2026 to 03-12-2026','period' => '02-12-2026 to 03-12-2026',                     'days' => 2],
];

?>
<div class="content-wrapper">
    <?php  ?>

    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content d-flex flex-wrap align-items-center">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    <a href="dashboard_hr.php" class="text-dark">HR Dashboard</a>
                    <span class="text-muted fw-normal fs-6">— Daily overview — <?php echo date('l, j F Y'); ?></span>
                </h1>
            </div>
            <div class="d-flex gap-4 align-items-center ms-auto py-1">
                <div class="text-end">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary bg-opacity-20 text-primary" style="font-size:.7rem">UAE</span>
                        <span class="fw-bold fs-5" id="uae-clock">--:--:--</span>
                    </div>
                    <div class="text-muted small" id="uae-date"></div>
                </div>
                <div class="text-end">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success bg-opacity-20 text-success" style="font-size:.7rem">PK</span>
                        <span class="fw-bold fs-5" id="pk-clock">--:--:--</span>
                    </div>
                    <div class="text-muted small" id="pk-date"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-inner">
        <div class="content">
            <?php include('admin_elements/breadcrumb.php'); ?>

            <!-- KPI Cards -->
            <div class="row mb-3 g-3">
                <div class="col-sm-4 col-xl-4">
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
                <div class="col-sm-4 col-xl-4">
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
                <div class="col-sm-4 col-xl-4">
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

            </div>

            <!-- Row 2: Pending Requests + Holidays -->
            <div class="row g-3 mb-3">
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
            </div>

            <!-- Row 3: Today's Attendance + UAE Public Holidays -->
            <div class="row g-3 mb-3">
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <h6 class="mb-0"><i class="ph-sign-in me-2"></i>Today's Attendance</h6>
                            <div class="ms-auto">
                                <a href="listing_attendance.php" class="btn btn-sm btn-outline-primary">View All</a>
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
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="mb-0"><i class="ph-calendar me-2"></i>UAE Public Holidays 2026</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">#</th>
                                            <th>Holiday</th>
                                            <th>Date</th>
                                            <th width="80">Days</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; foreach ($holidays as $h): ?>
                                        <tr class="holiday-row" style="cursor:pointer" data-name="<?php echo htmlspecialchars($h['name'], ENT_QUOTES); ?>" data-period="<?php echo htmlspecialchars($h['period'], ENT_QUOTES); ?>" data-days="<?php echo $h['days']; ?>">
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo htmlspecialchars($h['name']); ?></td>
                                            <td><?php echo htmlspecialchars($h['date']); ?></td>
                                            <td><?php echo $h['days']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-info fw-bold">
                                            <td></td>
                                            <td>Total Public Holidays</td>
                                            <td></td>
                                            <td><?php echo array_sum(array_column($holidays, 'days')); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Holiday Detail Modal -->
            <div class="modal fade" id="holidayModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="holidayModalTitle">Holiday</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label class="fw-semibold text-muted small">Announcement:</label>
                                <textarea class="form-control" id="holidayAnnouncement" rows="6" readonly style="background:#f8f9fa;resize:none;font-size:.875rem;" onclick="this.select()"></textarea>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm w-100 mt-2" id="btn-copy-announcement">
                                <i class="ph-copy-simple me-1"></i>Copy Announcement
                            </button>
                            <div id="copy-feedback" class="text-success small mt-1 text-center" style="display:none;">Copied!</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<script>
jQuery(function($) {
    $('#holidayModal').on('hidden.bs.modal', function() {
        $('#copy-feedback').hide().stop(true);
    });

    $(document).on('click', '.holiday-row', function() {
        var name = $(this).attr('data-name');
        var period = $(this).attr('data-period');
        var days = $(this).attr('data-days');
        var dayLabel = parseInt(days) === 1 ? 'day' : 'days';
        $('#holidayModalTitle').text(name);
        $('#holidayAnnouncement').val(
            'Dear Team,\n\n'
            + 'Please be informed that there will be ' + name + ' on ' + period + ' for ' + days + ' ' + dayLabel + '.\n\n'
            + 'Regards,\nHR Department'
        );
        $('#copy-feedback').hide();
        $('#holidayModal').modal('show');
    });

    $('#btn-copy-announcement').on('click', function() {
        var text = $('#holidayAnnouncement').val();
        navigator.clipboard.writeText(text).then(function() {
            $('#copy-feedback').show().fadeOut(2000);
        }).catch(function() {
            $('#holidayAnnouncement').select();
            document.execCommand('copy');
            $('#copy-feedback').show().fadeOut(2000);
        });
    });

    function updateClocks() {
        var now = new Date();
        var uaeOffset = 4 * 60;
        var pkOffset = 5 * 60;
        var localOffset = now.getTimezoneOffset();
        var uaeTime = new Date(now.getTime() + (uaeOffset + localOffset) * 60000);
        var pkTime = new Date(now.getTime() + (pkOffset + localOffset) * 60000);

        function pad(n) { return n < 10 ? '0' + n : n; }
        function formatTime(d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()); }
        function formatDate(d) {
            var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        }

        $('#uae-clock').text(formatTime(uaeTime));
        $('#uae-date').text(formatDate(uaeTime));
        $('#pk-clock').text(formatTime(pkTime));
        $('#pk-date').text(formatDate(pkTime));
    }

    updateClocks();
    setInterval(updateClocks, 1000);
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>

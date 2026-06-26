<?php

declare(strict_types=1);

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'payroll_runs';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$action = $_GET['action'] ?? '';
$payslipId = (int)($_GET['id'] ?? 0);
$payrollRunId = (int)($_GET['payroll_run_id'] ?? 0);

if (!granted_('edit', $module)) {
    $_SESSION['error_message'] = 'You do not have permission to update payslip status.';
    header('Location: listing_payroll_runs.php');
    exit;
}

if ($action === 'mark_paid' && $payslipId > 0) {
    $result = $mysqli->query("
        UPDATE `" . DB::PAYSLIPS . "`
        SET status = 'paid', paid_at = NOW()
        WHERE id = $payslipId
    ");

    if ($result) {
        $_SESSION['success_message'] = 'Payslip marked as paid successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to mark payslip as paid.';
    }

    header("Location: view_payslip.php?id=$payslipId");

} elseif ($action === 'mark_unpaid' && $payslipId > 0) {
    $result = $mysqli->query("
        UPDATE `" . DB::PAYSLIPS . "`
        SET status = 'generated', paid_at = NULL
        WHERE id = $payslipId
    ");

    if ($result) {
        $_SESSION['success_message'] = 'Payslip reverted to unpaid successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to revert payslip status.';
    }

    header("Location: view_payslip.php?id=$payslipId");

} elseif ($action === 'mark_all_paid' && $payrollRunId > 0) {
    $result = $mysqli->query("
        UPDATE `" . DB::PAYSLIPS . "`
        SET status = 'paid', paid_at = NOW()
        WHERE payroll_run_id = $payrollRunId AND status != 'paid'
    ");

    $affected = $mysqli->affected_rows;

    if ($result) {
        $_SESSION['success_message'] = "All $affected unpaid payslip(s) marked as paid successfully.";
    } else {
        $_SESSION['error_message'] = 'Failed to mark payslips as paid.';
    }

    header("Location: view_payroll_run.php?id=$payrollRunId");

} else {
    $_SESSION['error_message'] = 'Invalid request.';
    header('Location: listing_payroll_runs.php');
}

exit;

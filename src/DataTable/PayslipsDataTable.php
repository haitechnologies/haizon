<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;

class PayslipsDataTable extends BaseDataTable
{
    protected $table = DB::PAYSLIPS;
    protected $searchFields = ['status']; // Limited search as names are in other tables, unless we do joins
    protected $sortableColumns = [
        0 => 'id',
        1 => 'employee_id',
        2 => 'employee_id', // department
        3 => 'payroll_run_id',
        4 => 'gross',
        5 => 'deductions',
        6 => 'net',
        7 => 'status',
        8 => 'id'
    ];

    protected function getOrgIdWhereClause(): string
    {
        return '';
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)$row['id'];
        
        // Employee and Department
        $user_data = $this->db->fetchOne("
            SELECT u.full_name, d.department 
            FROM `" . DB::USERS . "` u 
            LEFT JOIN `" . DB::DEPARTMENTS . "` d ON u.department_id = d.id 
            WHERE u.id = " . (int)$row['employee_id']
        );
        $employee_name = $user_data ? s__($user_data['full_name']) : '-';
        $department = ($user_data && $user_data['department']) ? s__($user_data['department']) : '-';

        // Payroll Run
        $run_data = $this->db->fetchOne("SELECT period_start, period_end FROM `" . DB::PAYROLL_RUNS . "` WHERE id = " . (int)$row['payroll_run_id']);
        $payroll_period = $run_data ? processDateYtoD($run_data['period_start']) . ' - ' . processDateYtoD($run_data['period_end']) : '-';

        // Status
        $status = $row['status'];
        if ($status == 'generated') {
            $statusHtml = '<span class="badge bg-info">Generated</span>';
        } elseif ($status == 'submitted') {
            $statusHtml = '<span class="badge bg-success">Submitted</span>';
        } elseif ($status == 'paid') {
            $statusHtml = '<span class="badge bg-primary">Paid</span>';
        } else {
            $statusHtml = '<span class="badge bg-secondary">' . ucfirst(htmlspecialchars($status)) . '</span>';
        }

        $actionBtn = '<a href="view_payslip.php?id=' . $id . '" class="action-btn action-view" title="View payslip"><i class="ph-eye"></i></a>';

        return [
            $id,
            '<span class="fw-semibold">' . htmlspecialchars($employee_name) . '</span>',
            htmlspecialchars($department),
            $payroll_period,
            '<span class="text-success fw-semibold">AED ' . number_format((float)$row['gross'], 2) . '</span>',
            '<span class="text-danger">AED ' . number_format((float)$row['deductions'], 2) . '</span>',
            '<span class="text-primary fw-bold">AED ' . number_format((float)$row['net'], 2) . '</span>',
            $statusHtml,
            $actionBtn,
        ];
    }
}

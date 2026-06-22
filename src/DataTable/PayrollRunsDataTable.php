<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\ActionButtonHelper;

class PayrollRunsDataTable extends BaseDataTable
{
    protected $table = DB::PAYROLL_RUNS;
    protected $searchFields = ['status'];
    protected $sortableColumns = [
        0 => 'id',
        1 => 'period_start',
        2 => 'period_end',
        3 => 'status',
        4 => 'total_gross',
        5 => 'total_deductions',
        6 => 'total_net',
        7 => 'id', // employee count (custom logic)
        8 => 'id'
    ];

    protected function getOrgIdWhereClause(): string
    {
        return '';
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)$row['id'];
        
        // Count employees
        $employee_count_data = $this->db->fetchOne("SELECT COUNT(*) as count FROM `" . DB::PAYSLIPS . "` WHERE payroll_run_id = $id");
        $employee_count = $employee_count_data ? (int)($employee_count_data['count'] ?? 0) : 0;
        
        // Status formatting
        $status = $row['status'];
        if ($status == 'draft') {
            $statusHtml = '<span class="badge bg-secondary">Draft</span>';
        } elseif ($status == 'approved') {
            $statusHtml = '<span class="badge bg-success">Approved</span>';
        } elseif ($status == 'posted') {
            $statusHtml = '<span class="badge bg-primary">Posted</span>';
        } else {
            $statusHtml = '<span class="badge bg-warning">' . ucfirst(htmlspecialchars($status)) . '</span>';
        }

        // Employee badge
        if ($employee_count > 0) {
            $empHtml = '<span class="badge bg-info">' . $employee_count . ' employee' . ($employee_count > 1 ? 's' : '') . '</span>';
        } else {
            $empHtml = '<span class="text-muted">-</span>';
        }

        return [
            $this->rowNumber,
            processDateYtoD($row['period_start']),
            processDateYtoD($row['period_end']),
            $statusHtml,
            '<span class="text-success fw-semibold">AED ' . number_format((float)$row['total_gross'], 2) . '</span>',
            '<span class="text-danger">AED ' . number_format((float)$row['total_deductions'], 2) . '</span>',
            '<span class="text-primary fw-bold">AED ' . number_format((float)$row['total_net'], 2) . '</span>',
            $empHtml,
            $this->getActionButtons($id, 'payroll_runs', $status, (float)$row['total_gross']),
        ];
    }

    protected function getActionButtons($id, $module, $status = '', $totalGross = 0)
    {
        $a = '';
        
        // Generate Payslips
        if ($status == 'draft' && $totalGross == 0) {
            $a .= '<a href="process_payroll_run.php?id=' . $id . '" class="action-btn action-view" title="Generate payslips" onclick="return confirm(\'Generate payslips for this payroll run?\');"><i class="ph-play"></i></a> ';
        }
        
        // View details
        $a .= '<a href="view_payroll_run.php?id=' . $id . '" class="action-btn action-view" title="View details"><i class="ph-eye"></i></a> ';
        
        // Edit
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'payroll_runs.php', $module, 'Edit payroll run', false) . ' ';
        }
        
        // Delete
        if ($this->isGranted('delete', $module) && $status == 'draft') {
            $a .= '<a href="listing_' . htmlspecialchars($module) . '.php?action=delete_' . htmlspecialchars($module) . '&id=' . $id . '" class="action-btn action-delete" onclick="return confirm(\'Delete this payroll run?\\n\\nThis will also delete all associated payslips and payroll items.\');" title="Delete"><i class="ph-trash"></i></a>';
        }
        
        return ActionButtonHelper::group($a);
    }
}

<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;

class EmployeeSalariesDataTable extends BaseDataTable
{
    protected $table = DB::USERS;
    protected $searchFields = ['full_name'];
    protected $sortableColumns = [
        0 => 'id',
        1 => 'full_name',
        2 => 'department_id',
        // these are calculated, so they shouldn't be sortable easily without subqueries
        // but since we only have a few, we can just disable sorting on them or map them
        3 => 'id', 
        4 => 'id',
        5 => 'id',
        6 => 'id',
        7 => 'id'
    ];

    protected function getBaseQuery(): string
    {
        return parent::getBaseQuery() . " AND is_active = 1 AND id > 1";
    }

    protected function getOrgIdWhereClause(): string
    {
        // employees belong to organization via users table? If multi-tenant, yes.
        return ''; 
    }

    protected function formatRow($row, $requestData = [])
    {
        $employee_id = (int)$row['id'];
        $employee_name = s__($row['full_name']);
        $department = $row['department_id'] ? getTableAttr('department', DB::DEPARTMENTS, $row['department_id']) : '-';

        // Calculate salary breakdown for this employee
        $salary_data = $this->db->fetchAll("
            SELECT
                ss.component_id,
                pc.component_name,
                pc.component_type,
                ss.amount
            FROM `" . DB::SALARY_STRUCTURES . "` ss
            INNER JOIN `" . DB::PAYROLL_COMPONENTS . "` pc ON ss.component_id = pc.id
            WHERE ss.employee_id = $employee_id
            AND (ss.effective_to IS NULL OR ss.effective_to >= CURDATE())
            AND (ss.effective_from IS NULL OR ss.effective_from <= CURDATE())
        ");

        $gross_salary = 0;
        $total_deductions = 0;
        $component_count = 0;
        $components_list = [];

        if ($salary_data) {
            foreach ($salary_data as $sal) {
                $component_count++;
                $amount = (float)$sal['amount'];

                if ($sal['component_type'] == 'earning') {
                    $gross_salary += $amount;
                    $components_list[] = [
                        'name' => s__($sal['component_name']),
                        'type' => 'earning',
                        'amount' => $amount
                    ];
                } else {
                    $total_deductions += $amount;
                    $components_list[] = [
                        'name' => s__($sal['component_name']),
                        'type' => 'deduction',
                        'amount' => $amount
                    ];
                }
            }
        }

        $net_salary = $gross_salary - $total_deductions;

        // Render modal HTML directly into the column
        $modalHtml = $this->renderComponentsModal($employee_id, $employee_name, $component_count, $components_list, $net_salary);

        $actionBtn = '<a href="salary_structures.php?employee_id=' . $employee_id . '" class="action-btn action-edit" title="Edit salary"><i class="ph-pencil"></i></a>';

        return [
            $this->rowNumber,
            '<span class="fw-semibold">' . htmlspecialchars($employee_name) . '</span>',
            htmlspecialchars((string)$department),
            '<span class="text-success fw-semibold">AED ' . number_format($gross_salary, 2) . '</span>',
            '<span class="text-danger">AED ' . number_format($total_deductions, 2) . '</span>',
            '<span class="text-primary fw-bold">AED ' . number_format($net_salary, 2) . '</span>',
            $modalHtml,
            $actionBtn,
        ];
    }

    private function renderComponentsModal($employee_id, $employee_name, $component_count, $components_list, $net_salary)
    {
        $btn = '<button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#componentsModal' . $employee_id . '"><i class="ph-list"></i> View ' . $component_count . ' Component(s)</button>';

        $earnings_html = '';
        $earnings_total = 0;
        $deductions_html = '';
        $deductions_total = 0;

        foreach ($components_list as $comp) {
            if ($comp['type'] == 'earning') {
                $earnings_total += $comp['amount'];
                $earnings_html .= '<li class="d-flex justify-content-between mb-1"><span>' . htmlspecialchars($comp['name']) . '</span><span class="fw-semibold">AED ' . number_format($comp['amount'], 2) . '</span></li>';
            } else {
                $deductions_total += $comp['amount'];
                $deductions_html .= '<li class="d-flex justify-content-between mb-1"><span>' . htmlspecialchars($comp['name']) . '</span><span class="fw-semibold">AED ' . number_format($comp['amount'], 2) . '</span></li>';
            }
        }

        if ($earnings_total == 0) $earnings_html = '<li class="text-muted">No earnings</li>';
        if ($deductions_total == 0) $deductions_html = '<li class="text-muted">No deductions</li>';

        $modal = '
        <div class="modal fade" id="componentsModal' . $employee_id . '" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title">Salary Components - ' . htmlspecialchars($employee_name) . '</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 class="text-success"><i class="ph-plus-circle me-1"></i>Earnings</h6>
                            <ul class="list-unstyled">' . $earnings_html . '</ul>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold text-success">
                                <span>Total Earnings:</span>
                                <span>AED ' . number_format($earnings_total, 2) . '</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-danger"><i class="ph-minus-circle me-1"></i>Deductions</h6>
                            <ul class="list-unstyled">' . $deductions_html . '</ul>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold text-danger">
                                <span>Total Deductions:</span>
                                <span>AED ' . number_format($deductions_total, 2) . '</span>
                            </div>
                        </div>
                        <div class="alert alert-primary mb-0">
                            <div class="d-flex justify-content-between">
                                <strong>Net Salary:</strong>
                                <strong>AED ' . number_format($net_salary, 2) . '</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        return $btn . $modal;
    }
}

<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\ActionButtonHelper;

class SalaryStructuresDataTable extends BaseDataTable
{
    protected $table = DB::SALARY_STRUCTURES;
    protected $searchFields = []; // Can add if needed
    protected $sortableColumns = [
        0 => 'id',
        1 => 'employee_id',
        2 => 'component_id',
        3 => 'amount',
        4 => 'effective_from',
        5 => 'effective_to',
        6 => 'id'
    ];

    protected function getOrgIdWhereClause(): string
    {
        return '';
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)$row['id'];
        $effFrom = ($row['effective_from']) ? processDateYtoD($row['effective_from']) : '-';
        $effTo = ($row['effective_to']) ? processDateYtoD($row['effective_to']) : '-';

        return [
            $this->rowNumber,
            getTableAttr('full_name', DB::USERS, $row['employee_id']),
            getTableAttr('component_name', DB::PAYROLL_COMPONENTS, $row['component_id']),
            number_format((float)$row['amount'], 2),
            $effFrom,
            $effTo,
            $this->getActionButtons($id, 'salary_structures'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'salary_structures.php', $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

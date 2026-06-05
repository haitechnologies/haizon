<?php
/**
 * HS Code Sets DataTable Handler
 * 
 * Server-side DataTables processing for hai_hs_code_sets table
 * Returns 7 columns: id, country_code, version_label, effective_from, effective_to, is_active, actions
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class HSCodeSetsDataTable extends BaseDataTable {
    
    protected $table = DB::HS_CODE_SETS;
    
    protected $searchFields = ['country_code', 'version_label'];
    
    protected $sortableColumns = [
        0 => 'id',
        1 => 'country_code',
        2 => 'version_label',
        3 => 'effective_from',
        4 => 'effective_to',
        5 => 'is_active',
        6 => 'id'
    ];
    
    protected function formatRow($row, $requestData = []) {
        $activeStatus = $row['is_active'] ? 
            BadgeHelper::success('Active') : 
            BadgeHelper::danger('Inactive');
        
        // Build action buttons
        $actions = '';
        $actions .= ActionButtonHelper::editButton($row['id'], 'hs_code_sets.php', 'hs_code_sets', 'Edit', false);
        $actions .= ' ' . ActionButtonHelper::deleteButton($row['id'], 'hs_code_sets');
        
        return [
            'id' => $row['id'],
            'country_code' => htmlspecialchars($row['country_code'] ?? '-'),
            'version_label' => htmlspecialchars($row['version_label'] ?? ''),
            'effective_from' => $row['effective_from'] ? date('M j, Y', strtotime($row['effective_from'])) : '-',
            'effective_to' => $row['effective_to'] ? date('M j, Y', strtotime($row['effective_to'])) : 'Current',
            'is_active' => $activeStatus,
            'actions' => $actions
        ];
    }
}

<?php
/**
 * HS Code Texts DataTable Handler
 * 
 * Server-side DataTables processing for hai_hs_code_texts table
 * Returns 6 columns: id, hs_code_id, lang, short_desc, long_desc, actions
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class HSCodeTextsDataTable extends BaseDataTable {
    
    protected $table = DB::HS_CODE_TEXTS;
    
    protected $searchFields = ['lang', 'short_desc', 'long_desc'];
    
    protected $sortableColumns = [
        0 => 'id',
        1 => 'hs_code_id',
        2 => 'lang',
        3 => 'short_desc',
        4 => 'long_desc',
        5 => 'id'
    ];
    
    protected function formatRow($row, $requestData = []) {
        $langBadge = match($row['lang']) {
            'en' => BadgeHelper::info('English'),
            'ar' => BadgeHelper::info('العربية'),
            default => BadgeHelper::secondary($row['lang'])
        };
        
        // Build action buttons
        $actions = '';
        $actions .= ActionButtonHelper::editButton($row['id'], 'hs_code_texts.php', 'hs_code_texts', 'Edit', false);
        $actions .= ' ' . ActionButtonHelper::deleteButton($row['id'], 'hs_code_texts');
        
        return [
            'id' => $row['id'],
            'hs_code_id' => $row['hs_code_id'],
            'lang' => $langBadge,
            'short_desc' => htmlspecialchars(substr($row['short_desc'] ?? '', 0, 50)) . (strlen($row['short_desc'] ?? '') > 50 ? '...' : ''),
            'long_desc' => htmlspecialchars(substr($row['long_desc'] ?? '', 0, 50)) . (strlen($row['long_desc'] ?? '') > 50 ? '...' : ''),
            'actions' => $actions
        ];
    }
}

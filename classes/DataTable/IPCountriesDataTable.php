<?php
/**
 * IPCountriesDataTable Handler
 * 
 * Maps IP ranges to countries
 * Handles conversion from numeric IP to dotted notation
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class IPCountriesDataTable extends BaseDataTable {
    protected $table = DB::IP_COUNTRIES;
    protected $searchFields = ['country_code', 'country_name'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'ip_start', 2 => 'ip_end', 3 => 'country_code', 
        4 => 'country_name', 5 => 'created_at', 6 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $ipStart = (int)$row['ip_start'];
        $ipEnd = (int)$row['ip_end'];
        $countryCode = s__($row['country_code'] ?? '');
        $countryName = s__($row['country_name'] ?? '');
        $createdAt = $row['created_at'] ?? '';
        
        // Convert numeric IP to dotted format
        $ipStartAddr = long2ip($ipStart);
        $ipEndAddr = long2ip($ipEnd);
        
        return [
            $id,
            '<code>' . htmlspecialchars($ipStartAddr) . '</code>',
            '<code>' . htmlspecialchars($ipEndAddr) . '</code>',
            '<span class="badge bg-primary bg-opacity-20 text-primary">' . htmlspecialchars($countryCode) . '</span>',
            htmlspecialchars($countryName),
            timeAgo($createdAt),
            $this->getActionButtons($id, 'ip_countries')
        ];
    }
    
    protected function getActionButtons($id, $module) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton($id, 'ip_countries.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton($id, $module);
        }
        return $actions;
    }
}


<?php
/**
 * Geo Countries DataTable Handler
 * 
 * Server-side DataTables processing for hai_geo_countries table
 * Returns 8 columns: id, slug, country, country_ar, dialing_code, abbr, is_active, actions
 */

require_once __DIR__ . '/BaseDataTable.php';

class GeoCountriesDataTable extends BaseDataTable {
    
    protected $table = '';

    public function __construct($mysqli, $userId = null, $roleId = null) {
        parent::__construct($mysqli, $userId, $roleId);
        $this->table = defined('DB::GEO_COUNTRIES') && DB::GEO_COUNTRIES !== ''
            ? DB::GEO_COUNTRIES
            : 'erp_geo_countries';
    }
    
    protected function getColumns() {
        return ['id', 'slug', 'country', 'country_ar', 'dialing_code', 'abbr', 'is_active'];
    }
    
    protected function getDefaultOrder() {
        return 'id DESC';
    }
    
    protected function formatRow($row, $requestData = []) {
        return [
            'id' => $row['id'],
            'slug' => htmlspecialchars($row['slug']),
            'country' => htmlspecialchars($row['country']),
            'country_ar' => htmlspecialchars($row['country_ar'] ?? ''),
            'dialing_code' => $row['dialing_code'] ?? '-',
            'abbr' => htmlspecialchars($row['abbr'] ?? ''),
            'is_active' => $row['is_active'] ? '<span class="badge bg-success bg-opacity-20 text-success">Active</span>' : '<span class="badge bg-danger bg-opacity-20 text-danger">Inactive</span>',
            'actions' => $this->getActionButtons($row['id'], 'geo_countries')
        ];
    }

    protected function getActionButtons($id, $module) {
        if (!class_exists('ActionButtonHelper')) {
            return '';
        }

        $buttons = [];
        if (method_exists('ActionButtonHelper', 'editButton')) {
            $buttons[] = ActionButtonHelper::editButton($id, $module . '.php', $module, 'Edit', false);
        }
        if (method_exists('ActionButtonHelper', 'deleteButton')) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }

        return implode(' ', $buttons);
    }
}

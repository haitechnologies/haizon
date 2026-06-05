<?php
/**
 * Geo States DataTable Handler
 * 
 * Server-side DataTables processing for hai_geo_states table
 * Returns 9 columns: id, slug, state, state_ar, country_id, is_active, created_at, actions
 */


require_once __DIR__ . '/BaseDataTable.php';

class GeoStatesDataTable extends BaseDataTable {
    
    protected $table = '';

    public function __construct($mysqli, $userId = null, $roleId = null) {
        parent::__construct($mysqli, $userId, $roleId);
        $this->table = defined('DB::GEO_STATES') && DB::GEO_STATES !== ''
            ? DB::GEO_STATES
            : 'erp_geo_states';
    }
    
    protected function getColumns() {
        return ['id', 'slug', 'state', 'state_ar', 'country_id', 'is_active', 'created_at'];
    }
    
    protected function getDefaultOrder() {
        return 'id DESC';
    }
    
    protected function formatRow($row, $requestData = []) {
        return [
            'id' => $row['id'],
            'slug' => htmlspecialchars($row['slug']),
            'state' => htmlspecialchars($row['state']),
            'state_ar' => htmlspecialchars($row['state_ar'] ?? ''),
            'country_id' => $row['country_id'],
            'is_active' => $row['is_active'] ? '<span class="badge bg-success bg-opacity-20 text-success">Active</span>' : '<span class="badge bg-danger bg-opacity-20 text-danger">Inactive</span>',
            'created_at' => date('M j, Y', strtotime($row['created_at'])),
            'actions' => $this->getActionButtons($row['id'], 'geo_states')
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

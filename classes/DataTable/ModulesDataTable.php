<?php
/**
 * ModulesDataTable Handler
 * Handles DataTable requests for system modules listing
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class ModulesDataTable extends BaseDataTable {
    protected $table = DB::MODULES;
    protected $searchFields = ['module_name', 'slug', 'systems'];
    protected $sortableColumns = [
        0 => 'id',
        1 => 'module_name',
        2 => 'module_name',  // Permissions column - not sortable but needs index
        3 => 'systems',
        4 => 'id'
    ];

    protected function buildBaseQuery($requestData) {
        return "SELECT * FROM `" . $this->table . "` WHERE 1=1";
    }

    protected function buildSearchClause($requestData) {
        $searchValue = $requestData['search']['value'] ?? '';
        if (empty($searchValue)) {
            return '';
        }

        $searchValue = $this->mysqli->real_escape_string($searchValue);
        return "AND (module_name LIKE '%{$searchValue}%' 
            OR slug LIKE '%{$searchValue}%' 
            OR systems LIKE '%{$searchValue}%')";
    }

    protected function buildOrderClause($requestData) {
        $orderColumn = isset($requestData['order'][0]['column']) ? (int)$requestData['order'][0]['column'] : 0;
        $orderDir = isset($requestData['order'][0]['dir']) ? strtoupper($requestData['order'][0]['dir']) : 'ASC';

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'ASC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $moduleName = htmlspecialchars($row['module_name'] ?? '');
        $moduleSlug = htmlspecialchars($row['slug'] ?? '');
        $systems = htmlspecialchars($row['systems'] ?? '');
        $moduleIcon = 'ph-cube';

        // Get permissions count for this module
        $permissionsCount = $this->getPermissionsCount($id);
        
        // Build permissions display - single line to avoid JSON issues
        $permissionsDisplay = '<span class="badge bg-info bg-opacity-20 text-info">' . $permissionsCount . ' permissions</span>';
        if (granted_('edit', 'modules')) {
            $permissionsDisplay .= ' <a href="module_permissions.php?module_id=' . $id . '" class="btn btn-sm btn-light"><i class="ph-shield-check"></i> Manage</a>';
        }

        // Systems display
        $systemsDisplay = $systems !== ''
            ? BadgeHelper::primary($systems)
            : BadgeHelper::secondary('N/A');

        // Action buttons
        $actions = '';
        if (granted_('edit', 'modules')) {
            $actions .= ActionButtonHelper::editButton($id, 'modules.php', 'modules', 'Edit', false);
        }
        if (granted_('delete', 'modules')) {
            $actions .= ' ' . ActionButtonHelper::deleteButton($id, 'modules');
        }
        
        if (empty($actions)) {
            $actions = '&mdash;';
        }

        // Build module info - single line to avoid JSON encoding issues
        $moduleInfo = '<div><i class="' . $moduleIcon . ' me-2"></i><strong>' . $moduleName . '</strong></div><div class="text-muted small">' . $moduleSlug . '</div>';

        // Return indexed array format (position-based)
        return [
            0 => $id,
            1 => $moduleInfo,
            2 => $permissionsDisplay,
            3 => $systemsDisplay,
            4 => $actions
        ];
    }

    /**
     * Get count of roles with permissions for this module
     */
    private function getPermissionsCount($moduleId) {
        // Use COUNT(*) to avoid schema-specific column assumptions (e.g. permission_name vs slug).
        $stmt = $this->mysqli->prepare("SELECT COUNT(*) as count FROM " . DB::MODULE_PERMISSIONS . " WHERE module_id = ?");

        if (!$stmt) {
            error_log('[ModulesDataTable] Failed to prepare permission count query: ' . $this->mysqli->error);
            return 0;
        }

        $stmt->bind_param('i', $moduleId);
        if (!$stmt->execute()) {
            error_log('[ModulesDataTable] Failed to execute permission count query: ' . $stmt->error);
            $stmt->close();
            return 0;
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : [];
        $stmt->close();

        return (int)($row['count'] ?? 0);
    }
}

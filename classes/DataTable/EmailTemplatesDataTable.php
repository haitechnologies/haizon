<?php
/**
 * EmailTemplatesDataTable Handler
 * 
 * Email template management with system template protection
 * Uses BadgeHelper and ActionButtonHelper for consistent styling
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class EmailTemplatesDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_TEMPLATES;
    protected $searchFields = ['name', 'subject_default'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'name', 2 => 'subject_default', 3 => 'is_default', 
        4 => 'is_system', 5 => 'created_at', 6 => 'id'
    ];

    protected function buildBaseQuery($requestData) {
        return "SELECT * FROM `" . $this->table . "` WHERE id > 0";
    }

    protected function buildOrderClause($requestData) {
        $orderColumn = isset($requestData['order'][0]['column']) ? (int)$requestData['order'][0]['column'] : 0;
        $orderDir = isset($requestData['order'][0]['dir']) ? strtoupper($requestData['order'][0]['dir']) : 'DESC';
        $column = isset($this->sortableColumns[$orderColumn]) ? $this->sortableColumns[$orderColumn] : 'id';
        return 'ORDER BY is_default DESC, ' . $column . ' ' . $orderDir;
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $name = htmlspecialchars($row['name'] ?? '');
        $subjectDefault = htmlspecialchars($row['subject_default'] ?? '');
        $isDefault = (int)$row['is_default'];
        $isSystem = (int)$row['is_system'];
        $createdAt = $row['created_at'] ?? '';
        
        // Use BadgeHelper for consistent styling
        $defaultBadge = $isDefault == 1 
            ? BadgeHelper::success('Yes')
            : BadgeHelper::secondary('No');
        
        $systemBadge = $isSystem == 1
            ? BadgeHelper::info('Yes')
            : BadgeHelper::secondary('No');
        
        $subjectDisplay = strlen($subjectDefault) > 60 ? substr($subjectDefault, 0, 60) . '...' : $subjectDefault;
        
        return [
            $id,
            '<span class="fw-semibold">' . $name . '</span>',
            $subjectDisplay,
            $defaultBadge,
            $systemBadge,
            !empty($createdAt) ? timeAgo($createdAt) : '-',
            $this->getActionButtons($id, 'email_templates', $isSystem)
        ];
    }
    
    protected function getActionButtons($id, $module, $isSystem) {
        $buttons = [];
        
        // View button
        if (granted_('view', $module)) {
            $buttons[] = ActionButtonHelper::viewButton($id, 'template');
        }
        
        // Edit button
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'email_templates.php', $module, 'Edit', false);
        }
        
        // Delete button - cannot delete system templates
        if (granted_('delete', $module) && $isSystem == 0) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        
        // Return buttons with proper spacing
        return implode(' ', array_filter($buttons));
    }
}


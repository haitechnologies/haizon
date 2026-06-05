<?php
/**
 * EmailTargetsDataTable Handler
 * 
 * Campaign recipient segment management
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class EmailTargetsDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_TARGETS;
    protected $searchFields = ['name', 'segment_type'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'name', 2 => 'segment_type', 3 => 'estimated_count', 
        4 => 'is_active', 5 => 'created_at', 6 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $name = s__($row['name'] ?? '');
        $segmentType = s__($row['segment_type'] ?? '');
        $estimatedCount = (int)$row['estimated_count'];
        $isActive = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';
        
        $activeBadge = $isActive == 1 ? BadgeHelper::success('Yes') : BadgeHelper::secondary('No');
        
        return [
            $id,
            '<span class="fw-semibold">' . htmlspecialchars($name) . '</span>',
            ucfirst(htmlspecialchars($segmentType)),
            BadgeHelper::info(number_format($estimatedCount)),
            $activeBadge,
            timeAgo($createdAt),
            $this->getActionButtons($id, 'email_targets')
        ];
    }
    
    protected function getActionButtons($id, $module) {
        $buttons = [];
        
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'email_targets.php', $module, 'Edit', false);
        }
        
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        
        return implode(' ', array_filter($buttons));
    }
}


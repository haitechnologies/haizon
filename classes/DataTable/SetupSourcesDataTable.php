<?php
/**
 * SetupSourcesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class SetupSourcesDataTable extends BaseDataTable {
    protected $table = DB::SETUP_SOURCES;
    protected $searchFields = ['source'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'source', 2 => 'source_type', 3 => 'created_at', 4 => 'is_active', 5 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $source = $row['source'] ?? '';
        $sourceType = $row['source_type'] ?? '';
        $publish = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';
        
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            $id,
            htmlspecialchars($source),
            ucwords($sourceType),
            timeAgo($createdAt),
            $publishBadge,
            $this->getActionButtons($id, 'setup_sources', $publish)
        ];
    }
    
    protected function getActionButtons($id, $module, $publish) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton($id, 'setup_sources.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton($id, $module);
        }
        return $actions;
    }
}


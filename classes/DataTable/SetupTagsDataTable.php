<?php
/**
 * SetupTagsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class SetupTagsDataTable extends BaseDataTable {
    protected $table = DB::SETUP_TAGS;
    protected $searchFields = ['tag'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'tag', 2 => 'tag_type', 3 => 'created_at', 4 => 'id', 5 => 'is_active'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $tag = $row['tag'] ?? '';
        $tagType = $row['tag_type'] ?? '';
        $publish = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';
        
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            $id,
            htmlspecialchars($tag),
            ucwords($tagType),
            timeAgo($createdAt),
            $publishBadge,
            $this->getActionButtons($id, 'setup_tags', $publish)
        ];
    }
    
    protected function getActionButtons($id, $module, $publish) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton($id, 'setup_tags.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton($id, $module);
        }
        return $actions;
    }
}


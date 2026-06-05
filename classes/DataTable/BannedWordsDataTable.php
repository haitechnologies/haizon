<?php
/**
 * BannedWordsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class BannedWordsDataTable extends BaseDataTable {
    protected $table = DB::BANNED_WORDS;
    protected $searchFields = ['banned_word'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'banned_word', 2 => 'created_at', 3 => 'id', 4 => 'is_active'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $bannedWord = s__($row['banned_word'] ?? '');
        $publish = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';
        
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            $id,
            '<code>' . htmlspecialchars($bannedWord) . '</code>',
            !empty($createdAt) ? timeAgo($createdAt) : '',
            $publishBadge,
            $this->getActionButtons($id, 'banned_words', $publish)
        ];
    }
    
    protected function getActionButtons($id, $module, $publish) {
        $buttons = [];
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'banned_words.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}


<?php
/**
 * BlogCategoriesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class BlogCategoriesDataTable extends BaseDataTable {
    protected $table = DB::BLOG_CATEGORIES;
    protected $searchFields = ['name', 'slug'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'name', 2 => 'slug', 3 => 'created_at', 4 => 'status', 5 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $name = $row['name'] ?? '';
        $slug = $row['slug'] ?? '';
        $status = (int)($row['status'] ?? 0);
        $createdAt = $row['created_at'] ?? '';
        
        $statusBadge = $status == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            'id' => $id,
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'slug' => htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'),
            'created_at' => timeAgo($createdAt),
            'status' => $statusBadge,
            'actions' => $this->getActionButtons($id, 'blog_categories', $status, $slug)
        ];
    }
    
    protected function getActionButtons($id, $module, $status, $slug = '') {
        $buttons = [];
        if (!empty($slug)) {
            $buttons[] = ActionButtonHelper::publicLinkButton('/blog/category/' . rawurlencode($slug), 'Open Public Blog Category Page');
        }
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'blog_categories.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}


<?php
/**
 * CompanyCategoriesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class CompanyCategoriesDataTable extends BaseDataTable {
    protected $table = DB::CATEGORIES;
    protected $searchFields = ['category', 'slug'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'category', 2 => 'slug', 3 => 'total_companies', 4 => 'main', 5 => 'created_at', 6 => 'is_active', 7 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $category = $row['category'] ?? '';
        $slug = $row['slug'] ?? '';
        $totalCompanies = (int)($row['total_companies'] ?? 0);
        $main = (int)($row['main'] ?? 0);
        $publish = (int)($row['is_active'] ?? 0);
        $createdAt = $row['created_at'] ?? '';
        
        $categoryDisplay = substr($category, 0, 50) . (strlen($category) > 50 ? '...' : '');
        $mainBadge = $main == 1 ? BadgeHelper::info('Main') : BadgeHelper::secondary('Sub');
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');

        return [
            $id,
            htmlspecialchars($categoryDisplay),
            $slug,
            number_format($totalCompanies),
            $mainBadge,
            timeAgo($createdAt),
            $publishBadge,
            $this->getActionButtons($id, 'company_categories', $publish)
        ];
    }
    
    protected function getActionButtons($id, $module, $publish) {
        $buttons = [];
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'company_categories.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}



<?php
/**
 * PagesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class PagesDataTable extends BaseDataTable {
    protected $table = DB::PAGES;
    protected $searchFields = ['title', 'slug', 'menu_caption'];
    protected $sortableColumns = [
        0 => 'id', 
        1 => 'title', 
        2 => 'template_type', 
        3 => 'is_main_menu', 
        4 => 'views', 
        5 => 'updated_at', 
        6 => 'status', 
        7 => 'id'
    ];

    protected function buildBaseQuery($requestData) {
        $query = "SELECT p.*, u.full_name as author_name 
                  FROM `" . $this->table . "` p 
                  LEFT JOIN `" . DB::USERS . "` u ON p.created_by = u.id 
                  WHERE p.id > 0";
        
        // Filter for compliance pages if requested
        if (!empty($requestData['compliance_only'])) {
            $complianceSlugs = ['privacy-policy', 'terms-of-use', 'cookies-policy', 'gdpr', 'uae-pdpl', 'ccpa', 'refund-policy', 'accessibility', 'security'];
            $escapedSlugs = array_map(fn($s) => "'" . addslashes($s) . "'", $complianceSlugs);
            $query .= " AND p.slug IN (" . implode(',', $escapedSlugs) . ")";
        }
        
        return $query;
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $title = $row['title'] ?? '';
        $slug = $row['slug'] ?? '';
        $templateType = $row['template_type'] ?? 'default';
        $isMainMenu = (int)($row['is_main_menu'] ?? 0);
        $views = (int)$row['views'];
        $status = (int)$row['status'];
        $isFeatured = (int)($row['is_featured'] ?? 0);
        $updatedAt = $row['updated_at'] ?? '';
        
        // Truncate title
        $titleDisplay = strlen($title) > 60 ? substr($title, 0, 60) . '...' : $title;
        
        // Template badge
        $templateBadge = match($templateType) {
            'default' => '<span class="badge bg-light bg-opacity-20 text-dark">Default</span>',
            'landing' => '<span class="badge bg-info bg-opacity-20 text-info">Landing</span>',
            'sidebar' => '<span class="badge bg-primary bg-opacity-20 text-primary">Sidebar</span>',
            'fullwidth' => '<span class="badge bg-secondary bg-opacity-20 text-secondary">Full Width</span>',
            default => '<span class="badge bg-light bg-opacity-20 text-dark">' . htmlspecialchars($templateType) . '</span>'
        };
        
        // Menu visibility
        $menuBadge = $isMainMenu == 1 
            ? '<span class="badge bg-success">In Menu</span>' 
            : '<span class="badge bg-light text-muted">Hidden</span>';
        
        // Status badges
        $statusBadges = '';
        if ($status == 1) {
            $statusBadges .= BadgeHelper::success('Active') . ' ';
        } else {
            $statusBadges .= BadgeHelper::danger('Inactive') . ' ';
        }
        
        if ($isFeatured == 1) {
            $statusBadges .= BadgeHelper::primary('Featured');
        }
        
        return [
            $id, 
            htmlspecialchars($titleDisplay), 
            $templateBadge,
            $menuBadge,
            number_format($views), 
            date('M j, Y', strtotime($updatedAt)),
            $statusBadges,
            $this->getActionButtons($id, 'pages', $status, $slug)
        ];
    }
    
    protected function getActionButtons($id, $module, $status, $slug = '') {
        $buttons = [];
        if (!empty($slug)) {
            $buttons[] = ActionButtonHelper::publicLinkButton('/page/' . rawurlencode($slug), 'Open Public Page');
        }
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'pages.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}


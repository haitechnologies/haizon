<?php
/**
 * SitemapsDataTable Handler
 * 
 * Manages sitemap generation tracking and metadata
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class SitemapsDataTable extends BaseDataTable {
    protected $table = 'erp_sitemaps';
    protected $searchFields = ['name', 'type'];
    protected $sortableColumns = [
        0 => 'name',
        1 => 'type',
        2 => 'last_generated',
        3 => 'total_entries',
        4 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $name = s__($row['name'] ?? '');
        $type = s__($row['type'] ?? '');
        $lastGenerated = $row['last_generated'] ?? '';
        $entries = (int)($row['total_entries'] ?? 0);
        
        // Type badge formatting
        $typeBadge = match(strtolower($type)) {
            'pages' => BadgeHelper::primary('Pages'),
            'companies' => BadgeHelper::success('Companies'),
            'products' => BadgeHelper::info('Products'),
            'blogs' => BadgeHelper::warning('Blogs'),
            default => BadgeHelper::secondary(ucfirst($type))
        };
        
        // Last generated time
        $generatedDisplay = $lastGenerated 
            ? '<span title="' . htmlspecialchars($lastGenerated) . '">' . timeAgo($lastGenerated) . '</span>'
            : '<span class="text-muted">Never</span>';
        
        return [
            htmlspecialchars($name),
            $typeBadge,
            $generatedDisplay,
            BadgeHelper::info(number_format($entries)),
            $this->getActionButtons($id, 'sitemaps')
        ];
    }
    
    protected function getActionButtons($id, $module) {
        $buttons = [];
        
        // Regenerate button
        $buttons[] = '<a href="' . $module . '.php?action=generate&id=' . $id . '" 
                         class="btn btn-sm btn-success" 
                         title="Regenerate Sitemap">
                         <i class="ph-arrows-clockwise"></i>
                      </a>';
        
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        
        return implode(' ', array_filter($buttons));
    }
}


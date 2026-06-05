<?php
/**
 * BlogsDataTable Handler
 * 
 * Manages server-side DataTable processing for the Blogs module
 * Handles: search by title/slug, category join, sort, is_active status, pagination
 * 
 * @package DataTable
 * @subpackage Handlers
 * @version 1.0
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class BlogsDataTable extends BaseDataTable {
    
    /**
     * Table name
     */
    protected $table = DB::BLOGS;
    
    /**
     * Search fields
     */
    protected $searchFields = [
        'title',
        'slug',
        'guest_author_name',
        'guest_author_email'
    ];
    
    /**
     * Sortable columns
     */
    protected $sortableColumns = [
        0 => 'id',
        1 => 'title',
        2 => 'category_id',
        3 => 'created_by',
        4 => 'views',
        5 => 'created_at',
        6 => 'updated_at',
        7 => 'is_active',
        8 => 'id'
    ];
    
    /**
     * Build base query with category join
     * 
     * @param array $requestData Request data
     * @return string Base SQL query
     */
    protected function buildBaseQuery($requestData) {
        $filters = [];
        $sourceFilter = strtolower(trim((string)($requestData['source_filter'] ?? '')));
        $statusFilter = strtolower(trim((string)($requestData['status_filter'] ?? '')));

        if (in_array($sourceFilter, ['admin', 'guest'], true)) {
            $filters[] = "b.source = '" . $this->mysqli->real_escape_string($sourceFilter) . "'";
        }

        if (in_array($statusFilter, ['pending', 'approved', 'rejected', 'admin'], true)) {
            $filters[] = "b.submission_status = '" . $this->mysqli->real_escape_string($statusFilter) . "'";
        }

        $whereClause = 'WHERE b.id > 0';
        if (!empty($filters)) {
            $whereClause .= ' AND ' . implode(' AND ', $filters);
        }

        return "SELECT b.*, 
                bc.name as category_name,
                u.full_name as author_name
                FROM `" . $this->table . "` b 
                LEFT JOIN `" . DB::BLOG_CATEGORIES . "` bc ON b.category_id = bc.id
                LEFT JOIN `" . DB::USERS . "` u ON b.created_by = u.id
                " . $whereClause;
    }
    
    /**
     * Build search clause for title/slug
     * 
     * @param array $requestData Request data
     * @return string WHERE clause
     */
    protected function buildSearchClause($requestData) {
        global $mysqli;
        
        if (empty($requestData['search']['value'])) {
            return '';
        }
        
        $search = $mysqli->real_escape_string($requestData['search']['value']);
        return " AND (b.title LIKE '%{$search}%' OR b.slug LIKE '%{$search}%' OR b.guest_author_name LIKE '%{$search}%' OR b.guest_author_email LIKE '%{$search}%')";
    }
    
    /**
     * Override buildOrderClause to use table alias
     * 
     * @param array $requestData Request data
     * @return string ORDER BY clause
     */
    protected function buildOrderClause($requestData) {
        if (empty($requestData['order'])) {
            return 'ORDER BY b.title DESC';
        }
        
        $orderColumn = isset($requestData['order'][0]['column']) 
            ? (int)$requestData['order'][0]['column'] 
            : 1;
        
        $orderDir = isset($requestData['order'][0]['dir']) 
            ? strtoupper($requestData['order'][0]['dir']) 
            : 'DESC';
        
        // Validate order direction
        if (!in_array($orderDir, ['ASC', 'DESC'])) {
            $orderDir = 'DESC';
        }
        
        $column = isset($this->sortableColumns[$orderColumn]) 
            ? $this->sortableColumns[$orderColumn] 
            : 'title';
        
        // Add table alias for clarity
        $column = 'b.' . $column;
        
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }
    
    /**
     * Format row data with category info
     * 
     * @param array $row Database row
     * @param array $requestData Request data
     * @return array Formatted row
     */
    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $title = $row['title'] ?? '';
        $slug = $row['slug'] ?? '';
        $categoryName = $row['category_name'] ?? null;
        $authorName = $row['author_name'] ?? 'Unknown';
        $source = strtolower((string)($row['source'] ?? 'admin'));
        $submissionStatus = strtolower((string)($row['submission_status'] ?? 'admin'));
        $guestAuthorName = trim((string)($row['guest_author_name'] ?? ''));
        $views = (int)($row['views'] ?? 0);
        $publish = (int)$row['is_active'];
        $isFeatured = (int)($row['is_homepage'] ?? 0);
        $createdAt = $row['created_at'] ?? '';
        $updatedAt = $row['updated_at'] ?? '';
        $featuredImage = $row['featured_image'] ?? '';
        
        // Build title with thumbnail
        $titleDisplay = strlen($title) > 60 ? substr($title, 0, 60) . '...' : $title;
        if (!empty($featuredImage)) {
            $imgPath = '/uploads/blogs/' . $featuredImage;
            $titleHtml = '<div class="d-flex align-items-center gap-2">';
            $titleHtml .= '<img src="' . htmlspecialchars($imgPath) . '" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover;">';
            $titleHtml .= '<div><span>' . htmlspecialchars($titleDisplay) . '</span>';
            if ($source === 'guest') {
                $titleHtml .= '<div class="small mt-1">' . BadgeHelper::info('Guest') . ' ';
                $titleHtml .= match ($submissionStatus) {
                    'pending' => BadgeHelper::warning('Pending'),
                    'approved' => BadgeHelper::success('Approved'),
                    'rejected' => BadgeHelper::danger('Rejected'),
                    default => BadgeHelper::secondary(ucfirst($submissionStatus !== '' ? $submissionStatus : 'admin')),
                };
                if ($guestAuthorName !== '') {
                    $titleHtml .= '<div class="text-muted mt-1">By ' . htmlspecialchars($guestAuthorName) . '</div>';
                }
                $titleHtml .= '</div>';
            }
            $titleHtml .= '</div>';
            $titleHtml .= '</div>';
        } else {
            $titleHtml = htmlspecialchars($titleDisplay);
            if ($source === 'guest') {
                $titleHtml .= '<div class="small mt-1">' . BadgeHelper::info('Guest') . ' ';
                $titleHtml .= match ($submissionStatus) {
                    'pending' => BadgeHelper::warning('Pending'),
                    'approved' => BadgeHelper::success('Approved'),
                    'rejected' => BadgeHelper::danger('Rejected'),
                    default => BadgeHelper::secondary(ucfirst($submissionStatus !== '' ? $submissionStatus : 'admin')),
                };
                if ($guestAuthorName !== '') {
                    $titleHtml .= '<div class="text-muted mt-1">By ' . htmlspecialchars($guestAuthorName) . '</div>';
                }
                $titleHtml .= '</div>';
            }
        }
        
        // Build category display
        $categoryDisplay = !empty($categoryName)
            ? '<span class="badge bg-light text-dark border">' . htmlspecialchars($categoryName) . '</span>'
            : BadgeHelper::secondary('Uncategorized');
        
        // Build status badges
        $statusBadges = '';
        if ($publish == 1) {
            $statusBadges .= BadgeHelper::success('Active') . ' ';
        } else {
            $statusBadges .= BadgeHelper::danger('Inactive') . ' ';
        }
        
        if ($isFeatured == 1) {
            $statusBadges .= BadgeHelper::primary('Featured');
        }

        if ($source === 'guest' && $submissionStatus === 'pending') {
            $statusBadges .= ' ' . BadgeHelper::warning('Needs Review');
        }
        
        return [
            $id,
            $titleHtml,
            $categoryDisplay,
            htmlspecialchars($source === 'guest' && $guestAuthorName !== '' ? $guestAuthorName : $authorName),
            number_format($views),
            date('M j, Y', strtotime($createdAt)),
            date('M j, Y', strtotime($updatedAt)),
            $statusBadges,
            $this->getActionButtons($id, 'blogs', $publish, $slug)
        ];
    }
    
    /**
     * Get action buttons
     * 
     * @param int $id Record ID
     * @param string $module Module name
     * @param int $publish Publish status
     * @return string HTML action buttons
     */
    protected function getActionButtons($id, $module, $publish, $slug = '') {
        $buttons = [];

        if (!empty($slug)) {
            $buttons[] = ActionButtonHelper::publicLinkButton('/blog/' . rawurlencode($slug), 'Open Public Blog Page');
            $buttons[] = ActionButtonHelper::ampLinkButton('/blog/' . rawurlencode($slug) . '/amp', 'Open Blog AMP Page');
        }
        
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'blogs.php', $module, 'Edit', false);
        }
        
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        
        return implode(' ', array_filter($buttons));
    }
}


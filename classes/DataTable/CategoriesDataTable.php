<?php
/**
 * CategoriesDataTable Handler
 * 
 * Manages server-side DataTable for product categories
 * Includes subcategory, item, and company counts
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class CategoriesDataTable extends BaseDataTable {
    protected $table = DB::CATEGORIES;
    protected $searchFields = ['name'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'name', 2 => 'id',
        3 => 'id', 4 => 'id', 5 => 'is_active', 6 => 'id'
    ];

    /**
     * OPTIMIZATION: Pre-fetch all counts for categories in bulk
     * Prevents 3 N+1 queries per category
     */
    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $categoryIds = array_filter(array_map(fn($r) => (int)($r['id'] ?? 0), $rows));
        
        if (empty($categoryIds)) {
            return;
        }

        $idList = implode(',', $categoryIds);

        // OPTIMIZATION 1: Fetch subcategory counts in ONE query
        $subQuery = "
            SELECT category_id, COUNT(*) as cnt 
            FROM " . DB::SUBCATEGORIES . " 
            WHERE category_id IN ({$idList})
            GROUP BY category_id
        ";
        
        $this->relatedDataCache['subcategories'] = [];
        $result = $this->mysqli->query($subQuery);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['subcategories'][(int)$row['category_id']] = (int)$row['cnt'];
            }
        }

        $this->relatedDataCache['items'] = [];
        if ($this->tableExists(DB::CATEGORY_ITEMS)) {
            // OPTIMIZATION 2: Fetch item counts in ONE query
            $itemQuery = "
                SELECT category_id, COUNT(*) as cnt 
                FROM " . DB::CATEGORY_ITEMS . " 
                WHERE category_id IN ({$idList})
                GROUP BY category_id
            ";

            $result = $this->mysqli->query($itemQuery);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $this->relatedDataCache['items'][(int)$row['category_id']] = (int)$row['cnt'];
                }
            }
        }

        $this->relatedDataCache['companies'] = [];
        if ($this->tableExists(DB::COMPANIES)) {
            // OPTIMIZATION 3: Fetch company counts in ONE query
            $companyQuery = "
                SELECT primary_category_id, COUNT(*) as cnt 
                FROM " . DB::COMPANIES . " 
                WHERE primary_category_id IN ({$idList}) AND is_active = 1
                GROUP BY primary_category_id
            ";

            $result = $this->mysqli->query($companyQuery);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $this->relatedDataCache['companies'][(int)$row['primary_category_id']] = (int)$row['cnt'];
                }
            }
        }
    }

    private function tableExists(string $tableName): bool
    {
        static $cache = [];
        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }

        $stmt = $this->mysqli->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
        if (!$stmt) {
            $cache[$tableName] = false;
            return false;
        }

        $stmt->bind_param('s', $tableName);
        $stmt->execute();
        $stmt->store_result();
        $cache[$tableName] = $stmt->num_rows > 0;
        $stmt->close();

        return $cache[$tableName];
    }

    protected function formatRow($row, $requestData = []) {
        global $mysqli;
        
        $id = (int)$row['id'];
        $name = $row['name'] ?? '';
        $slug = $row['slug'] ?? '';
        $publish = (int)$row['is_active'];
        
        // OPTIMIZATION: Use pre-fetched counts instead of per-row queries
        $subCount = $this->relatedDataCache['subcategories'][$id] ?? 0;
        $itemCount = $this->relatedDataCache['items'][$id] ?? 0;
        $companyCount = $this->relatedDataCache['companies'][$id] ?? 0;
        
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            $id, $name,
            BadgeHelper::info($subCount),
            BadgeHelper::primary($itemCount),
            BadgeHelper::success($companyCount),
            $publishBadge,
            $this->getActionButtons($id, 'categories', $publish, $slug)
        ];
    }
    
    protected function getActionButtons($id, $module, $publish, $slug = '') {
        $buttons = [];
        if (!empty($slug)) {
            $buttons[] = ActionButtonHelper::publicLinkButton('/category/' . rawurlencode($slug), 'Open Public Category Page');
        }
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'categories.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}


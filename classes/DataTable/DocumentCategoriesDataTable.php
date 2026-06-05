<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class DocumentCategoriesDataTable extends BaseDataTable {
    protected $table = DB::DOCUMENT_CATEGORIES;
    protected $searchFields = ['document_category'];
    protected $sortableColumns = [0 => 'id', 1 => 'document_category', 2 => 'document_category_type', 3 => 'created_at', 4 => 'publish', 5 => 'id'];

    protected function formatRow($row, $requestData = []) {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['document_category'] ?? '');
        $type    = (string)($row['document_category_type'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars($type),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'document_categories'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $a = '';
        if (granted_('edit', $module))   $a .= ActionButtonHelper::editButton((int)$id, 'document_categories.php', $module, 'Edit', false);
        if (granted_('delete', $module)) $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        return $a;
    }
}

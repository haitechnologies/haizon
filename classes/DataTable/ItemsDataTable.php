<?php
/**
 * ItemsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class ItemsDataTable extends BaseDataTable {
    protected $table = DB::ITEMS;
    protected $searchFields = ['item_name'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'item_name', 2 => 'unit_price', 3 => 'created_at', 4 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $itemName = $row['item_name'] ?? '';
        $unitPrice = (float)($row['unit_price'] ?? 0);
        $createdAt = $row['created_at'] ?? '';
        
        return [
            $id,
            htmlspecialchars($itemName),
            number_format($unitPrice, 2),
            date('d M Y', strtotime($createdAt)),
            $this->getActionButtons($id, 'items')
        ];
    }
    
    protected function getActionButtons($id, $module) {
        $buttons = [];
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'items.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}


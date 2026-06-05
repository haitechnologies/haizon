<?php
/**
 * CustomerDocumentsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class CustomerDocumentsDataTable extends BaseDataTable {
    protected $table = 'erp_customer_documents'; // table decommissioned
    protected $searchFields = ['document_name', 'document_filename'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'document_name', 2 => 'issued_date', 3 => 'expiry_date', 4 => 'created_at', 5 => 'is_active', 6 => 'id'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $documentName = htmlspecialchars($row['document_name'] ?? '');
        $issuedDate = date('M j, Y', strtotime($row['issued_date'] ?? 'now'));
        $expiryDate = !empty($row['expiry_date']) ? date('M j, Y', strtotime($row['expiry_date'])) : '-';
        $publish = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';
        
        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');
        
        return [
            $id,
            $documentName,
            $issuedDate,
            $expiryDate,
            timeAgo($createdAt),
            $publishBadge,
            $this->getActionButtons($id, 'customer_documents', $publish)
        ];
    }    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? count($this->sortableColumns) - 2);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }


    
    protected function getActionButtons($id, $module, $publish) {
        $buttons = [];
        if (granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'customer_documents.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}
?>



<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class CustomerDocumentsDataTable extends BaseDataTable
{
    protected $table = 'erp_customer_documents';
    protected $searchFields = ['document_name', 'document_filename'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'document_name', 2 => 'issued_date', 3 => 'expiry_date', 4 => 'created_at', 5 => 'is_active', 6 => 'id'
    ];

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)$row['id'];
        $documentName = htmlspecialchars($row['document_name'] ?? '');
        $issuedDate = date('M j, Y', strtotime($row['issued_date'] ?? 'now'));
        $expiryDate = !empty($row['expiry_date']) ? date('M j, Y', strtotime($row['expiry_date'])) : '-';
        $publish = (int)$row['is_active'];
        $createdAt = $row['created_at'] ?? '';

        $publishBadge = $publish == 0 ? BadgeHelper::danger('Inactive') : BadgeHelper::success('Active');

        $timeAgoStr = '';
        if (!empty($createdAt)) {
            $timeAgoStr = function_exists('timeAgo') ? timeAgo($createdAt) : $createdAt;
        }

        return [
            $id,
            $documentName,
            $issuedDate,
            $expiryDate,
            $timeAgoStr,
            $publishBadge,
            $this->getActionButtons($id, 'customer_documents', $publish)
        ];
    }

    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? count($this->sortableColumns) - 2);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }

    protected function getActionButtons($id, $module, $publish)
    {
        $buttons = [];
        if (function_exists('granted_') && granted_('edit', $module)) {
            $buttons[] = ActionButtonHelper::editButton($id, 'customer_documents.php', $module, 'Edit', false);
        }
        if (function_exists('granted_') && granted_('delete', $module)) {
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}

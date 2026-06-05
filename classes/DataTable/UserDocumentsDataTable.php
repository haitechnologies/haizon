<?php
require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class UserDocumentsDataTable extends BaseDataTable {
    protected $table = DB::USER_DOCUMENTS;
    protected $searchFields = ['document_name', 'document_filename'];
    protected $sortableColumns = [0 => 'ud.id', 1 => 'ud.document_name', 2 => 'ud.document_category', 3 => 'ud.user', 4 => 'ud.document_filename', 5 => 'ud.issued_date', 6 => 'ud.expiry_date', 7 => 'ud.created_at', 8 => 'ud.id'];

    protected function buildBaseQuery($requestData)
    {
        return "SELECT ud.*, dc.document_category, u.full_name "
            . "FROM `" . DB::USER_DOCUMENTS . "` ud "
            . "LEFT JOIN `" . DB::DOCUMENT_CATEGORIES . "` dc ON dc.id = ud.document_category "
            . "LEFT JOIN `" . DB::USERS . "` u ON u.id = ud.user "
            . "WHERE ud.id > 0" . $this->getOrgIdWhereClause();
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $fileName = trim((string)($row['document_filename'] ?? ''));
        $fileLink = '';
        if ($fileName !== '') {
            $fileLink = '<a href="../uploads/user_documents/' . rawurlencode($fileName) . '" target="_blank" rel="noopener">View</a>';
        }

        return [
            $id,
            htmlspecialchars((string)($row['document_name'] ?? '')),
            htmlspecialchars((string)($row['document_category'] ?? '')),
            htmlspecialchars((string)($row['full_name'] ?? '')),
            $fileLink,
            htmlspecialchars((string)($row['issued_date'] ?? '')),
            htmlspecialchars((string)($row['expiry_date'] ?? '')),
            htmlspecialchars((string)($row['created_at'] ?? '')),
            $this->getActionButtons($id, 'user_documents'),
        ];
    }

    protected function getActionButtons($id, $module) {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton((int)$id, 'user_documents.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return trim($actions);
    }
}
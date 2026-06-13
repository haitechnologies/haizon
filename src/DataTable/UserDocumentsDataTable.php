<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class UserDocumentsDataTable extends BaseDataTable
{
    protected $table = DB::USER_DOCUMENTS;
    protected $searchFields = ['display_name', 'filename'];
    protected $sortableColumns = [0 => 'ud.id', 1 => 'ud.display_name', 2 => 'ud.document_category', 3 => 'ud.attachable_id', 4 => 'ud.filename', 5 => 'ud.issued_date', 6 => 'ud.expiry_date', 7 => 'ud.created_at', 8 => 'ud.id'];

    protected function buildBaseQuery($requestData)
    {
        return "SELECT ud.*, ud.display_name AS document_name, ud.filename AS document_filename, dc.document_category AS category_name, u.full_name "
            . "FROM `" . DB::USER_DOCUMENTS . "` ud "
            . "LEFT JOIN `" . DB::DOCUMENT_CATEGORIES . "` dc ON dc.id = ud.document_category "
            . "LEFT JOIN `" . DB::USERS . "` u ON u.id = ud.attachable_id "
            . "WHERE ud.attachable_type = 'UserDoc' AND ud.id > 0" . $this->getOrgIdWhereClause();
    }

    protected function getOrgIdWhereClause(): string
    {
        if ($this->organizationId === null) {
            return '';
        }
        $this->params['active_org_id'] = (int)$this->organizationId;
        return " AND ud.organization_id = :active_org_id";
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $fileName = trim((string)($row['document_filename'] ?? ''));
        $fileLink = '';
        if ($fileName !== '') {
            $fileLink = '<a href="../uploads/user_documents/' . rawurlencode($fileName) . '" target="_blank" rel="noopener">View</a>';
        }

        return [
            $id,
            htmlspecialchars((string)($row['document_name'] ?? '')),
            htmlspecialchars((string)($row['category_name'] ?? '')),
            htmlspecialchars((string)($row['full_name'] ?? '')),
            $fileLink,
            htmlspecialchars((string)($row['issued_date'] ?? '')),
            htmlspecialchars((string)($row['expiry_date'] ?? '')),
            htmlspecialchars((string)($row['created_at'] ?? '')),
            $this->getActionButtons($id, 'user_documents'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
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

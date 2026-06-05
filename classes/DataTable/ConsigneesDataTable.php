<?php

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class ConsigneesDataTable extends BaseDataTable
{
    protected $table = DB::CONSIGNEES;

    protected $searchFields = [
        'consignee_name',
        'address_line1',
    ];

    protected $sortableColumns = [
        0 => 'id',
        1 => 'consignee_name',
        2 => 'address_line1',
        3 => 'created_at',
        4 => 'id',
    ];

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $name = (string)($row['consignee_name'] ?? '');
        $address1 = (string)($row['address_line1'] ?? '');
        $createdAt = (string)($row['created_at'] ?? '');

        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars($address1),
            htmlspecialchars(timeAgo($createdAt)),
            $this->getActionButtons($id, 'consignees'),
        ];
    }

    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? 0);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }

    protected function getActionButtons($id, $module)
    {
        $actions = '';

        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton((int)$id, 'consignees.php', $module, 'Edit', false);
        }

        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }

        return $actions;
    }
}

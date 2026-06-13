<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class LeadsDataTable extends BaseDataTable
{
    protected $table = DB::LEADS;
    protected $searchFields = ['company_name', 'display_name', 'email', 'phone', 'tags'];
    protected $sortableColumns = [0 => 'id', 1 => 'company_name', 2 => 'address', 3 => 'email', 4 => 'phone', 5 => 'tags', 6 => 'assigned_to', 7 => 'lead_status', 8 => 'contacted_date', 9 => 'created_at'];

    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $userIds = [];
        $statusIds = [];

        foreach ($rows as $row) {
            $userIds[] = (int)($row['assigned_to'] ?? 0);
            $statusIds[] = (int)($row['lead_status'] ?? 0);
        }

        $this->relatedDataCache['users'] = [];
        $this->relatedDataCache['statuses'] = [];

        $userIds = array_values(array_filter(array_unique(array_map('intval', $userIds))));
        if ($userIds) {
            $sql = "SELECT id, full_name FROM `" . DB::USERS . "` WHERE id IN (" . implode(',', $userIds) . ")";
            $result = $this->mysqli->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $this->relatedDataCache['users'][(int)$row['id']] = (string)($row['full_name'] ?? '');
                }
                $result->free();
            }
        }

        $statusIds = array_values(array_filter(array_unique(array_map('intval', $statusIds))));
        if ($statusIds) {
            $result = $this->mysqli->query("SELECT id, value as status FROM `" . DB::TAXONOMIES . "` WHERE id IN (" . implode(',', $statusIds) . ")");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $this->relatedDataCache['statuses'][(int)$row['id']] = (string)($row['status'] ?? '');
                }
                $result->free();
            }
        }
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $assignedTo = (int)($row['assigned_to'] ?? 0);
        $statusId = (int)($row['lead_status'] ?? 0);
        $companyName = trim((string)($row['company_name'] ?? ''));
        if ($companyName === '') {
            $companyName = (string)($row['display_name'] ?? '');
        }
        $statusLabel = (string)($this->relatedDataCache['statuses'][$statusId] ?? '');

        return [
            $id,
            '<a href="lead.php?id=' . $id . '">' . htmlspecialchars($companyName) . '</a>',
            htmlspecialchars((string)($row['address'] ?? '')),
            htmlspecialchars((string)($row['email'] ?? '')),
            htmlspecialchars((string)($row['phone'] ?? '')),
            htmlspecialchars(rtrim((string)($row['tags'] ?? ''), ', ')),
            htmlspecialchars((string)($this->relatedDataCache['users'][$assignedTo] ?? '')),
            $statusLabel !== '' ? BadgeHelper::info(htmlspecialchars($statusLabel)) : '',
            htmlspecialchars((string)($row['contacted_date'] ?? '')),
            htmlspecialchars((string)($row['created_at'] ?? '')),
            $this->getActionButtons($id, 'leads'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $actions = '';
        if (granted_('edit', $module)) {
            $actions .= ActionButtonHelper::editButton((int)$id, 'leads.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return trim($actions);
    }
}

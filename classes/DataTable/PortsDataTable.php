<?php

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class PortsDataTable extends BaseDataTable
{
    protected $table = DB::PORTS;

    protected $searchFields = [
        'port_name',
        'port_code',
    ];

    protected $sortableColumns = [
        0 => 'id',
        1 => 'port_name',
        2 => 'port_code',
        3 => 'country_id',
        4 => 'created_at',
        5 => 'publish',
        6 => 'id',
    ];

    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $countryIds = array_filter(array_map(static function ($row) {
            return (int)($row['country_id'] ?? 0);
        }, $rows));

        if (empty($countryIds)) {
            return;
        }

        $idList = implode(',', array_unique($countryIds));
        $this->relatedDataCache['countries'] = [];

        $query = "SELECT id, country AS country_name FROM `" . DB::GEO_COUNTRIES . "` WHERE id IN ({$idList})";
        $result = $this->mysqli->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['countries'][(int)$row['id']] = (string)($row['country_name'] ?? '');
            }
            $result->free();
        }
    }

    protected function formatRow($row, $requestData = [])
    {
        $id = (int)($row['id'] ?? 0);
        $portName = (string)($row['port_name'] ?? '');
        $portCode = (string)($row['port_code'] ?? '');
        $countryId = (int)($row['country_id'] ?? 0);
        $createdAt = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);

        $countryName = $this->relatedDataCache['countries'][$countryId] ?? ('Country #' . $countryId);
        $publishBadge = ($publish === 1) ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');

        return [
            $id,
            htmlspecialchars($portName),
            htmlspecialchars($portCode),
            htmlspecialchars($countryName),
            htmlspecialchars(timeAgo($createdAt)),
            $publishBadge,
            $this->getActionButtons($id, 'ports'),
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
            $actions .= ActionButtonHelper::editButton((int)$id, 'ports.php', $module, 'Edit', false);
        }

        if (granted_('delete', $module)) {
            $actions .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }

        return $actions;
    }
}

<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

final class GenericDataTable extends BaseDataTable
{
    private array $config;

    public function __construct(
        array $config,
        mixed $db,
        ?int $userId = null,
        ?int $roleId = null,
        ?int $organizationId = null
    ) {
        $this->config = $config;
        $this->table = $config['table'] ?? '';
        $this->searchFields = $config['search'] ?? [];
        $this->sortableColumns = $config['sort'] ?? [0 => 'id'];
        parent::__construct($db, $userId, $roleId, $organizationId);
    }

    protected function formatRow($row, $requestData = []): array
    {
        $id = (int)($row['id'] ?? 0);
        $labelCol = $this->config['label_col'] ?? 'name';
        $label = (string)($row[$labelCol] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $module = $this->config['module'] ?? '';
        $hasPublish = $this->config['publish'] ?? false;

        $cols = [
            $this->rowNumber,
            htmlspecialchars($label),
            $this->formatTimeAgo($created),
        ];

        if ($hasPublish) {
            $publish = (int)($row['is_active'] ?? 0);
            $cols[] = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        }

        $cols[] = $this->buildActionButtons($id, $module);

        return $cols;
    }

    private function buildActionButtons(int $id, string $module): string
    {
        $a = '';
        if ($this->isGranted('edit', $module)) {
            $editUrl = $module . '.php';
            $a .= ActionButtonHelper::editButton($id, $editUrl, $module, 'Edit', false);
        }
        if ($this->isGranted('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton($id, $module);
        }
        return $a;
    }
}

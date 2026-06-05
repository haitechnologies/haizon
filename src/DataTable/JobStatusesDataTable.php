<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class JobStatusesDataTable extends BaseDataTable
{
    protected $table = DB::JOB_STATUSES;
    protected $searchFields = ['job_status'];
    protected $sortableColumns = [0 => 'id', 1 => 'job_status', 2 => 'created_at', 3 => 'publish', 4 => 'id'];

    protected function formatRow($row, $requestData = [])
    {
        $id      = (int)($row['id'] ?? 0);
        $name    = (string)($row['job_status'] ?? '');
        $created = (string)($row['created_at'] ?? '');
        $publish = (int)($row['publish'] ?? 0);
        $badge   = $publish ? BadgeHelper::success('Active') : BadgeHelper::danger('Inactive');
        return [
            $id,
            htmlspecialchars($name),
            htmlspecialchars(timeAgo($created)),
            $badge,
            $this->getActionButtons($id, 'job_statuses'),
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $a = '';
        if (granted_('edit', $module)) {
            $a .= ActionButtonHelper::editButton((int)$id, 'job_statuses.php', $module, 'Edit', false);
        }
        if (granted_('delete', $module)) {
            $a .= ' ' . ActionButtonHelper::deleteButton((int)$id, $module);
        }
        return $a;
    }
}

<?php

/**
 * Category HS Codes DataTable Handler
 *
 * Server-side DataTables processing for hai_category_hs_codes table
 * Returns 6 columns: id, category_id, hs_code_id, relevance, notes, actions
 */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class CategoryHSCodesDataTable extends BaseDataTable
{
    protected $table = DB::CATEGORY_HS_CODES;

    protected $searchFields = ['notes'];

    protected $sortableColumns = [
        0 => 'id',
        1 => 'category_id',
        2 => 'hs_code_id',
        3 => 'relevance',
        4 => 'notes',
        5 => 'id'
    ];

    protected function formatRow($row, $requestData = [])
    {
        $relevanceBadge = match (intval($row['relevance'] ?? 0)) {
            1 => BadgeHelper::success('High'),
            2 => BadgeHelper::warning('Medium'),
            3 => BadgeHelper::secondary('Low'),
            default => BadgeHelper::secondary('-')
        };

        // Build action buttons
        $buttons = [];
        $buttons[] = ActionButtonHelper::editButton($row['id'], 'category_hs_codes.php', 'category_hs_codes', 'Edit', false);
        $buttons[] = ActionButtonHelper::deleteButton($row['id'], 'category_hs_codes');

        return [
            'id' => $row['id'],
            'category_id' => $row['category_id'],
            'hs_code_id' => $row['hs_code_id'],
            'relevance' => $relevanceBadge,
            'notes' => htmlspecialchars(substr($row['notes'] ?? '', 0, 50)) . (strlen($row['notes'] ?? '') > 50 ? '...' : ''),
            'actions' => implode(' ', array_filter($buttons))
        ];
    }
}

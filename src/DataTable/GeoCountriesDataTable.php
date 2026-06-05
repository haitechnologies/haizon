<?php

/**
 * Geo Countries DataTable Handler
 *
 * Server-side DataTables processing for hai_geo_countries table
 * Returns 8 columns: id, slug, country, country_ar, dialing_code, abbr, is_active, actions
 */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class GeoCountriesDataTable extends BaseDataTable
{
    protected $table = DB::GEO_COUNTRIES;

    protected function getColumns()
    {
        return ['id', 'slug', 'country', 'country_ar', 'dialing_code', 'abbr', 'is_active'];
    }

    protected function getDefaultOrder()
    {
        return 'id DESC';
    }

    protected function formatRow($row, $requestData = [])
    {
        return [
            'id' => $row['id'],
            'slug' => htmlspecialchars($row['slug']),
            'country' => htmlspecialchars($row['country']),
            'country_ar' => htmlspecialchars($row['country_ar'] ?? ''),
            'dialing_code' => $row['dialing_code'] ?? '-',
            'abbr' => htmlspecialchars($row['abbr'] ?? ''),
            'is_active' => $row['is_active'] ? '<span class="badge bg-success bg-opacity-20 text-success">Active</span>' : '<span class="badge bg-danger bg-opacity-20 text-danger">Inactive</span>',
            'actions' => $this->getActionButtons($row['id'], 'geo_countries')
        ];
    }

    protected function getActionButtons($id, $module)
    {
        $buttons = [];
        if (class_exists('ActionButtonHelper')) {
            $buttons[] = ActionButtonHelper::editButton($id, $module . '.php', $module, 'Edit', false);
            $buttons[] = ActionButtonHelper::deleteButton($id, $module);
        }
        return implode(' ', array_filter($buttons));
    }
}

<?php

/**
 * Geo Cities DataTable Handler
 *
 * Server-side DataTables processing for hai_geo_cities table
 * Returns 8 columns: id, slug, city, city_ar, state_id, country_id, is_active, actions
 */

declare(strict_types=1);

namespace App\DataTable;

use App\Core\DB;
use App\Helper\BadgeHelper;
use App\Helper\ActionButtonHelper;

class GeoCitiesDataTable extends BaseDataTable
{
    protected $table = DB::GEO_CITIES;

    protected function getColumns()
    {
        return ['id', 'slug', 'city', 'city_ar', 'state_id', 'country_id', 'is_active'];
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
            'city' => htmlspecialchars($row['city']),
            'city_ar' => htmlspecialchars($row['city_ar'] ?? ''),
            'state_id' => $row['state_id'],
            'country_id' => $row['country_id'],
            'is_active' => $row['is_active'] ? '<span class="badge bg-success bg-opacity-20 text-success">Active</span>' : '<span class="badge bg-danger bg-opacity-20 text-danger">Inactive</span>',
            'actions' => $this->getActionButtons($row['id'], 'geo_cities')
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

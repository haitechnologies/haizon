<?php

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'geo_cities';
$module_caption = 'Geo Cities';
$tbl_name = DB::GEO_CITIES;
$module_id = getModuleIdBySlug($module, $mysqli);
$error_message = '';
$success_message = '';
$hide_add_button = true;

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($action == "delete_$module" && !empty($id)) {
    if (!granted('delete', $module)) {
        $error_message = "You don't have permission to delete cities.";
    } else {
        if (delete($tbl_name, $id)) {
            $success_message = "City deleted successfully.";
            header("Location: listing_$module.php?msg=deleted");
            exit;
        } else {
            $error_message = "Could not delete city.";
        }
    }
}

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => true,
    'thead' => '
        <th>ID</th>
        <th>SLUG</th>
        <th>CITY</th>
        <th>CITY (AR)</th>
        <th>STATE ID</th>
        <th>COUNTRY ID</th>
        <th>STATUS</th>
        <th>ACTIONS</th>
    ',
    'columns' => [
        ['data' => 'id'],
        ['data' => 'slug'],
        ['data' => 'city'],
        ['data' => 'city_ar'],
        ['data' => 'state_id'],
        ['data' => 'country_id'],
        ['data' => 'is_active'],
        ['data' => 'actions', 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');

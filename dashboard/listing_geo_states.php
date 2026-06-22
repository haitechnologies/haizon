<?php

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'geo_states';
$module_caption = 'Geo States';
$tbl_name = DB::GEO_STATES;
$module_id = getModuleIdBySlug($module, $mysqli);
$error_message = '';
$success_message = '';
$hide_add_button = true;

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($action == "delete_$module" && !empty($id)) {
    if (!granted('delete', $module)) {
        $error_message = "You don't have permission to delete states.";
    } else {
        if (delete($tbl_name, $id)) {
            $success_message = "State deleted successfully.";
            header("Location: listing_$module.php?msg=deleted");
            exit;
        } else {
            $error_message = "Could not delete state.";
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
        <th>STATE</th>
        <th>STATE (AR)</th>
        <th>COUNTRY ID</th>
        <th>STATUS</th>
        <th>CREATED</th>
        <th>ACTIONS</th>
    ',
    'columns' => [
        ['data' => 'id'],
        ['data' => 'slug'],
        ['data' => 'state'],
        ['data' => 'state_ar'],
        ['data' => 'country_id'],
        ['data' => 'is_active'],
        ['data' => 'created_at'],
        ['data' => 'actions', 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');

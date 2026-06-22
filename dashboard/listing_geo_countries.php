<?php

use App\Core\DB;

include('admin_elements/admin_header.php');

$module = 'geo_countries';
$module_caption = 'Geo Countries';
$tbl_name = DB::GEO_COUNTRIES;
$module_id = getModuleIdBySlug($module, $mysqli);
$error_message = '';
$success_message = '';
$hide_add_button = true;

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

// Handle delete action
if ($action == "delete_$module" && !empty($id)) {
    if (!granted('delete', $module)) {
        $error_message = "You don't have permission to delete countries.";
    } else {
        if (delete($tbl_name, $id)) {
            $success_message = "Country deleted successfully.";
            header("Location: listing_$module.php?msg=deleted");
            exit;
        } else {
            $error_message = "Could not delete country.";
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
        <th>COUNTRY</th>
        <th>COUNTRY (AR)</th>
        <th>DIALING CODE</th>
        <th>ABBR</th>
        <th>STATUS</th>
        <th>ACTIONS</th>
    ',
    'columns' => [
        ['data' => 'id'],
        ['data' => 'slug'],
        ['data' => 'country'],
        ['data' => 'country_ar'],
        ['data' => 'dialing_code'],
        ['data' => 'abbr'],
        ['data' => 'is_active'],
        ['data' => 'actions', 'orderable' => false, 'searchable' => false],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'extra_js' => "
        // Override ajax url for named-column DataTable
        var tableKey = '#grid-" . $module . "';
        if (window.HAI_DATATABLES && window.HAI_DATATABLES[tableKey]) {
            // Table already initialized by template — override ajax url if needed
        }
    ",
];

include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');

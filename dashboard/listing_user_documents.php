<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');

$module = 'user_documents';
$module_caption = 'Employee Document';
$tbl_name = DB::USER_DOCUMENTS;
$error_message = '';
$success_message = '';

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$document_expiry_type = 'ALL';

if (isset($_REQUEST['document_expiry_type']) && !empty($_REQUEST['document_expiry_type'])) {
    $document_expiry_type = e_s__($_REQUEST['document_expiry_type']);
    $document_expiry_type = strtoupper(str_ireplace('_', ' ', $document_expiry_type));
}

$handler_config = ['hard_delete' => true, 'ownership_check' => true, 'redirect_on_success' => true];
include('admin_elements/listing_handler.php');

$expiryTabsHtml = '
<div class="row mt-3 mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">&nbsp;</h5>
        <ul class="nav nav-tabs nav-tabs-solid nav-justified rounded col-lg-6">
            <li class="nav-item">
                <a href="listing_' . $module . '.php?document_expiry_type=all" class="nav-link rounded-start active">All</a>
            </li>
            <li class="nav-item bg-success">
                <a href="listing_' . $module . '.php?document_expiry_type=up_to_date" class="nav-link text-white">UP-TO-DATE</a>
            </li>
            <li class="nav-item bg-warning">
                <a href="listing_' . $module . '.php?document_expiry_type=near_expiry" class="nav-link text-white">NEAR EXPIRY</a>
            </li>
            <li class="nav-item bg-danger">
                <a href="listing_' . $module . '.php?document_expiry_type=expired" class="nav-link text-white">EXPIRED</a>
            </li>
        </ul>
    </div>
</div>';

$categoryBadgesHtml = '<div class="row mb-2 mt-2"><div class="col-lg-12">';
$result = $mysqli->query("SELECT * FROM `" . DB::DOCUMENT_CATEGORIES . "` WHERE is_active=1 AND document_category_type='employees' ORDER BY CASE document_category WHEN 'Emirates ID' THEN 1 WHEN 'Visa' THEN 2 WHEN 'Labor Card' THEN 3 WHEN 'Passport' THEN 4 WHEN 'Photo' THEN 5 WHEN 'Contract' THEN 6 ELSE 7 END, document_category LIMIT 50");
while ($rows = $result->fetch_array()) {
    $document_category = $rows['id'];
    $rs = $mysqli->query("SELECT id FROM `" . DB::USER_DOCUMENTS . "` WHERE attachable_type = 'UserDoc' AND document_category=$document_category");
    $categoryBadgesHtml .= '<span class="badge bg-light text-dark fw-normal">' . htmlspecialchars($rows['document_category']) . ' (' . $rs->num_rows . ')</span> ';
}
$categoryBadgesHtml .= '</div></div>';

$listingConfig = [
    'module' => $module,
    'module_caption' => 'Employee Documents',
    'hide_add_button' => true,
    'thead' => '
        <th width="40">SR.</th>
        <th>CATEGORY</th>
        <th>EMPLOYEE NAME</th>
        <th>DOCUMENT</th>
        <th>ISSUE DATE</th>
        <th>EXPIRY DATE</th>
        <th width="90">STATUS</th>
        <th width="90">CREATED AT</th>
    ',
    'columns' => [
        ['data' => 0, 'orderable' => false],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6],
        ['data' => 7],
    ],
    'order' => [[7, 'desc']],
    'page_length' => 25,
    'search_placeholder' => 'Search documents...',
    'before_table' => $expiryTabsHtml . $categoryBadgesHtml,
    'extra_js' => "
        $('#grid-user_documents tbody').on('click', 'tr', function() {
            var employeeId = $(this).find('[data-employee-id]').data('employee-id');
            if (employeeId) {
                window.location.href = 'users.php?id=' + employeeId;
            }
        });
    ",
];



include('admin_elements/listing_template.php');
include('admin_elements/admin_footer.php');

<?php
require_once __DIR__ . '/bootstrap.php';

use App\Core\DB;

if (($_POST['action'] ?? null) == 'get_port_details' && !empty($_POST['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    $id = (int)$_POST['id'];
    $stmt = $mysqli->prepare("SELECT p.*, c.country AS country_name FROM `" . DB::PORTS . "` p LEFT JOIN `" . DB::GEO_COUNTRIES . "` c ON p.country_id = c.id WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found.']);
    }
    exit;
}

include('admin_elements/admin_header.php');
$module = 'ports';
$module_caption = 'Port';
$tbl_name = DB::PORTS;
$error_message = '';
$success_message = '';


require_once '../vendor/autoload.php';


/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/



/*
|--------------------------------------------------------------------------
| 
|--------------------------------------------------------------------------
|
*/


// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// // use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// $spreadsheet = new Spreadsheet();
// $activeWorksheet = $spreadsheet->getActiveSheet();
// $activeWorksheet->setCellValue('A1', 'Hello World !');

// $inputFileType = 'Xlsx';
// $inputFileName = 'ccra.xlsx';

// /**  Create a new Reader of the type defined in $inputFileType  **/
// $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
// /**  Advise the Reader that we only want to load cell data  **/
// $reader->setReadDataOnly(true);

// $counter = 0;

// $worksheetData = $reader->listWorksheetInfo($inputFileName);


// foreach ($worksheetData as $worksheet) {

// 	$sheetName = $worksheet['worksheetName'];

// 	// echo "<h4>$sheetName</h4>";
// 	/**  Load $inputFileName to a Spreadsheet Object  **/
// 	$reader->setLoadSheetsOnly($sheetName);
// 	$spreadsheet = $reader->load($inputFileName);

// 	$worksheet = $spreadsheet->getActiveSheet();
// 	// print_r($worksheet->toArray());

// 	// Get the highest row and column numbers referenced in the worksheet
// 	$highestRow = $worksheet->getHighestDataRow(); // e.g. 10
// 	$highestColumn = $worksheet->getHighestDataColumn(); // e.g 'F'
// 	$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // e.g. 5


//     		$column1 		= '';
//     		$column2 		= '';
//     		$column3 		= '';


//     		echo '<table>' . "\n";
//     		for ($row = 1; $row <= $highestRow; ++$row) { 
//     			$counter++;
//     			echo '<tr>' . PHP_EOL;

//     			for ($col = 1; $col <= $highestColumnIndex; ++$col) {
//     				$value = $worksheet->getCell([$col, $row])->getValue();
//     				// echo '<td>' . $value . '</td>' . PHP_EOL;


//                     // -- Port Name
//     				if ($col == '1') { 
//     					echo $column1 = $value;
//                         echo '<br />'; 
//     				}


//                     // -- Country
//     				if ($col == '2') { 
//     					echo $column2 = $value;
//                         echo '<br />'; 
//     				}


//                     // -- Port Code 				
//     				if ($col == '3') { 
//     					echo $column3 = $value;
//                         echo '<br /><br /><br />'; 
//     				}

//     			} // for

//     			echo '</tr>' . PHP_EOL;


//     			// SKiP INSERTION OF COLUMNS HEADINGS
//     			// if ($counter > 1) {
//     			// 	$port_name 		    = e_s__($column1);
//     			// 	$country 		    = e_s__($column2);
//     			// 	$port_code 		= e_s__($column3);

//                 //     // $mysqli->query("INSERT INTO `" .DB::PORTS. "`(column1, column2, column3) VALUES ('" . $port_name . "', '" . $country . "',  '" . $port_code . "'); ");

//                 // }

//             } // for

//             echo '</table>';
// }



/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    if (is_SystemAdmin() || is_SuperAdmin()) {
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    }

    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a>
                    <?php if (!empty($pageHelpData)): ?>
                        <button type="button" class="page-help-trigger-btn" data-bs-toggle="offcanvas" data-bs-target="#pageHelpPanel" title="How to use this page" aria-label="Page help">
                            <i class="ph-question"></i>
                        </button>
                    <?php endif; ?>
                </h1>
            </div>

            <div class="my-1">
                <?php if (empty($hide_add_button) && isset($module_id) && isset($module) && granted('create', $module_id)) { ?>
                    <a href="<?php echo $module; ?>.php" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="ph-plus ph-sm me-2 opacity-75"></i>New
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- /page header -->


    <div class="content datatable-enhanced">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="card">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                        <thead>
                            <tr>
                                <th width="40">SR.</th>
                                <th>PORT NAME</th>
                                <th>PORT CODE</th>
                                <th>PORT COUNTRY</th>
                                <th>CREATED AT</th>
                                <th>STATUS</th>
                                <th width="90">ACTION</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- Details Modal -->
            <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="detailsModalLabel">Port Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="modal-loading" class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <div id="modal-content" class="d-none">
                                <table class="table table-bordered table-striped">
                                    <tr>
                                        <th width="35%">Port ID</th>
                                        <td id="det-id"></td>
                                    </tr>
                                    <tr>
                                        <th>Port Name</th>
                                        <td id="det-name"></td>
                                    </tr>
                                    <tr>
                                        <th>Port Code</th>
                                        <td id="det-code"></td>
                                    </tr>
                                    <tr>
                                        <th>Country</th>
                                        <td id="det-country"></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td id="det-status"></td>
                                    </tr>
                                    <tr>
                                        <th>Created At</th>
                                        <td id="det-created"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

    </div>
    <?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        columns: [
            { data: 0, name: 'id',         title: 'SR.' },
            { data: 1, name: 'port_name',  title: 'PORT NAME' },
            { data: 2, name: 'port_code',  title: 'PORT CODE' },
            { data: 3, name: 'country',    title: 'PORT COUNTRY' },
            { data: 4, name: 'created_at', title: 'CREATED AT' },
            { data: 5, name: 'is_active',    title: 'STATUS' },
            { data: 6, title: 'ACTION', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false
    });

    var detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

    $(document).on('click', '.view-port-details', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#modal-loading').removeClass('d-none');
        $('#modal-content').addClass('d-none');
        detailsModal.show();

        $.ajax({
            url: 'listing_ports.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_port_details',
                id: id,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#det-id').text(data.id);
                    $('#det-name').text(data.port_name);
                    $('#det-code').text(data.port_code);
                    $('#det-country').text(data.country_name || ('Country #' + data.country_id));
                    $('#det-status').html(data.is_active == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>');
                    $('#det-created').text(data.created_at || '-');
                    
                    $('#modal-loading').addClass('d-none');
                    $('#modal-content').removeClass('d-none');
                } else {
                    alert(response.message || 'Failed to fetch details.');
                    detailsModal.hide();
                }
            },
            error: function() {
                alert('Error fetching details.');
                detailsModal.hide();
            }
        });
    });

    $(document).on('click', 'a[data-action="delete_record"]', function(e) {
        e.preventDefault();
        var id     = $(this).data('id');
        var module = $(this).data('module');
        if (confirm('Are you sure you want to delete this port?')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_' + module + '"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
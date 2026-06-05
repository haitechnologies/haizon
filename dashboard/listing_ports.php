<?php

use App\Core\DB;
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
    <?php include('admin_elements/page_header.php'); ?>
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

        <?php include('admin_elements/copyright.php'); ?>
    </div>
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
            { data: 5, name: 'publish',    title: 'STATUS' },
            { data: 6, title: 'ACTION', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false
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
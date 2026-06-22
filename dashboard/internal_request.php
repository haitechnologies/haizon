<?php

require_once __DIR__ . '/admin_elements/error_handler_init.php';

use App\Core\DB;
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

include_once('../config/globals.php');
include_once('../config/database.php');
include_once('admin_elements/error_logger.php');

// Register custom error/exception/shutdown handlers for AJAX (returning JSON on exceptions/fatals)
if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}

set_exception_handler(function (\Throwable $exception) {
    log_error('[AJAX:internal_request] Exception: ' . $exception->getMessage(), 'ERROR', $exception->getFile(), $exception->getLine(), [
        'module' => 'internal_request',
        'module_slug' => 'ajax',
        'stack_trace' => $exception->getTraceAsString(),
    ]);
    
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        log_error('[AJAX:internal_request] Fatal Error: ' . $error['message'], 'CRITICAL', $error['file'], $error['line'], [
            'module' => 'internal_request',
            'module_slug' => 'ajax',
        ]);
        
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
        exit;
    }
});


/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

$ajax_action = '';
if (isset($_REQUEST['ajax_action']) && !empty($_REQUEST['ajax_action'])) {
	$ajax_action = e_s__($_REQUEST['ajax_action']);
}


/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

switch ($ajax_action) {

 

		/*
		|--------------------------------------------------------------------------
		| 	Populate Services Drop Downs
		|--------------------------------------------------------------------------
		|
		*/
	case 'populate_services':

		$response = array();

		$result		= $mysqli->query("SELECT * FROM `" . $GLOBALS['TBL']['PREFIX'] . "items` WHERE is_active=1 AND item_type='services' ORDER BY item_name");

		// IF ROW EXISTS
		////////////////////////////////
		if (($result->num_rows >= 1)) {

			while ($row		= $result->fetch_array()) {
				if (!empty($row[0])) {

					$id				= s__($row['id']);
					$service_name	= s__($row['item_name']);

					$subArray['id'] 				= $id;
					$subArray['service_name'] 		= $service_name;

					$response[] =  $subArray;
				}
			} // while


		}

		echo json_encode($response);

		break;


 


	/*
	|--------------------------------------------------------------------------
	| 	Populate Item Rate
	|--------------------------------------------------------------------------
	|
	*/
	case 'populate_item_rate':

		$response = array();

		$item_id 	= '0';
		$row_no 	= '0';

		if (isset($_POST['item_id'])) {
			$item_id 	= e_s__($_POST['item_id']);
		}		
		if (isset($_POST['row_no'])) {
			$row_no 	= e_s__($_POST['row_no']);
		}		

			$result		= $mysqli->query("SELECT * FROM `" . tbl_items . "`  WHERE id=$item_id LIMIT 1");
			// --------------------------------
			// IF ROW EXISTS
			if (($result->num_rows >= 1)) {
	
				$row		= $result->fetch_array();
				if (!empty($row[0])) {
	
						$unit_price			= s__($row['unit_price']);
						
						$subArray['item_rate']		= $unit_price;
						$subArray['row_no'] 		= $row_no;
	
						$response =  $subArray;
					}
	
			}

			echo json_encode($response);

		break;
 

 


		/*
	|--------------------------------------------------------------------------
	| 	Populate Customers
	|--------------------------------------------------------------------------
	|
	*/
	case 'populate_customers':

		$new_pax 	= '';
		$old_pax 	= '';

		if (isset($_POST['new_pax'])) {
			$new_pax 	= e_s__($_POST['new_pax']);
		}

		if (isset($_POST['old_pax'])) {
			$old_pax 	= e_s__($_POST['old_pax']);
		}

		$arr = array('new_pax' => $new_pax, 'old_pax' => $old_pax);

		echo json_encode($arr);

		break;


	/*
	|--------------------------------------------------------------------------
	| 	Get Subcategories by Category ID
	|--------------------------------------------------------------------------
	|
	*/
	case 'get_subcategories':

		$response = array('success' => false, 'data' => array());
		$category_id = 0;

		if (isset($_POST['category_id'])) {
			$category_id = (int)$_POST['category_id'];
		}

		if ($category_id > 0) {
			$result = $mysqli->query("SELECT id, subcategory FROM `" . DB::SUBCATEGORIES . "` WHERE is_active=1 AND category_id=" . $category_id . " ORDER BY subcategory");

			if ($result && $result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$response['data'][] = array(
						'id' => $row['id'],
						'subcategory' => $row['subcategory']
					);
				}
				$response['success'] = true;
			}
		}

		echo json_encode($response);

		break;


}//switch

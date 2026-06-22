<?php
require_once dirname(__DIR__) . '/admin_elements/error_handler_init.php';

/**
 * CustomerAPI.php - API Handler for Customer Operations
 * 
 * Endpoints:
 * - populateCustomers: Get customer information
 * 
 * @package API
 * @version 1.0.0
 */

require_once('BaseAPI.php');

class CustomerAPI extends BaseAPI {
    
    public function __construct() {
        $this->action = 'CustomerAPI';
        parent::__construct();
    }
    
    /**
     * Populate customer data
     * 
     * @return void (sends JSON response)
     */
    public function populateCustomers() {
        try {
            $this->logRequest('POPULATE_CUSTOMERS_START');
            
            $new_pax = $this->validateParam('new_pax', 'int');
            $old_pax = $this->validateParam('old_pax', 'int');
            
            $data = [
                'new_pax' => $new_pax ?? 0,
                'old_pax' => $old_pax ?? 0
            ];
            
            $this->setSuccess($data, 'Customer data retrieved');
            $this->logRequest('POPULATE_CUSTOMERS_SUCCESS', $data);
            
        } catch (Exception $e) {
            $this->setError($e->getMessage(), 500);
            $this->logRequest('POPULATE_CUSTOMERS_ERROR', ['error' => $e->getMessage()]);
        }
        
        $this->sendResponse();
    }
}

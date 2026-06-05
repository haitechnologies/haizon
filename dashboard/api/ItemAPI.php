<?php
/**
 * ItemAPI.php - API Handler for Item/Service Operations
 * 
 * Endpoints:
 * - getServices: Retrieve all published services
 * - getItemRate: Get unit price for a specific item
 * 
 * @package API
 * @version 1.0.0
 */

require_once('BaseAPI.php');

class ItemAPI extends BaseAPI {
    
    public function __construct() {
        $this->action = 'ItemAPI';
        parent::__construct();
    }
    
    /**
     * Get all published services
     * 
     * @return void (sends JSON response)
     */
    public function getServices() {
        try {
            $this->logRequest('GET_SERVICES_START');
            
            // Prepare query with parameterized statement
            $sql = "SELECT id, item_name FROM ? WHERE is_active=1 AND item_type='services' ORDER BY item_name";
            $table = tbl_items;
            
            // Direct query (table names cannot be parameterized in prepared statements)
            $result = $this->mysqli->query(
                "SELECT id, item_name FROM `" . addslashes($table) . "` WHERE is_active=1 AND item_type='services' ORDER BY item_name"
            );
            
            if (!$result) {
                throw new Exception("Database query failed: " . $this->mysqli->error);
            }
            
            $services = [];
            
            while ($row = $result->fetch_assoc()) {
                $services[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['item_name']
                ];
            }
            
            $this->setSuccess($services, 'Services retrieved successfully');
            $this->logRequest('GET_SERVICES_SUCCESS', ['count' => count($services)]);
            
        } catch (Exception $e) {
            $this->setError($e->getMessage(), 500);
            $this->logRequest('GET_SERVICES_ERROR', ['error' => $e->getMessage()]);
        }
        
        $this->sendResponse();
    }
    
    /**
     * Get item rate (unit price)
     * 
     * @return void (sends JSON response)
     */
    public function getItemRate() {
        try {
            $this->logRequest('GET_ITEM_RATE_START');
            
            // Validate required parameters
            $item_id = $this->validateParam('item_id', 'int', true);
            $row_no = $this->validateParam('row_no', 'int', true);
            
            if ($item_id === false || $row_no === false) {
                throw new InvalidArgumentException("Invalid parameters: item_id and row_no must be integers");
            }
            
            // Use prepared statement to prevent SQL injection
            $stmt = $this->executeQuery(
                "SELECT id, unit_price FROM `" . tbl_items . "` WHERE id = ? LIMIT 1",
                [$item_id],
                'i'
            );
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Item not found: ID $item_id");
            }
            
            $row = $result->fetch_assoc();
            $stmt->close();
            
            $data = [
                'item_id' => (int)$row['id'],
                'rate' => (float)$row['unit_price'],
                'row_no' => (int)$row_no
            ];
            
            $this->setSuccess($data, 'Item rate retrieved successfully');
            $this->logRequest('GET_ITEM_RATE_SUCCESS', ['item_id' => $item_id, 'rate' => $row['unit_price']]);
            
        } catch (InvalidArgumentException $e) {
            $this->setError($e->getMessage(), 400);
            $this->logRequest('GET_ITEM_RATE_VALIDATION_ERROR', ['error' => $e->getMessage()]);
        } catch (Exception $e) {
            $this->setError($e->getMessage(), 500);
            $this->logRequest('GET_ITEM_RATE_ERROR', ['error' => $e->getMessage()]);
        }
        
        $this->sendResponse();
    }
}

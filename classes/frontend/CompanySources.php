<?php
/**
 * Company Sources Data Access Class
 * 
 * Handles all database operations for company sources (data providers) on the frontend.
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class CompanySources {
    
    private $mysqli;
    private $table = DB::COMPANY_SOURCES;
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }
    
    /**
     * Get all published sources
     * 
     * @param array $options Filter options (order_by)
     * @return array Array of source records
     */
    public function getAll($options = []) {
        $orderBy = $options['order_by'] ?? 'source ASC';
        
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 ORDER BY {$orderBy}";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("CompanySources::getAll - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $sources = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $sources;
    }
    
    /**
     * Get a single source by slug
     * 
     * @param string $slug Source sitemap_slug
     * @return array|null Source record or null if not found
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM `{$this->table}` WHERE sitemap_slug = ? AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("CompanySources::getBySlug - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $source = $result->fetch_assoc();
        $stmt->close();
        
        return $source;
    }
    
    /**
     * Get a single source by ID
     * 
     * @param int $id Source ID
     * @return array|null Source record or null if not found
     */
    public function getById($id) {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = ? AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("CompanySources::getById - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $source = $result->fetch_assoc();
        $stmt->close();
        
        return $source;
    }
    
    /**
     * Get sources with company counts, ordered by total
     * 
     * @param int $limit Maximum number of sources
     * @return array Array of source records
     */
    public function getPopular($limit = 10) {
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 AND total_companies > 0 ORDER BY total_companies DESC LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("CompanySources::getPopular - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $sources = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $sources;
    }
}

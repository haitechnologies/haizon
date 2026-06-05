<?php
/**
 * CSV Exporter Utility Class
 * 
 * Provides simple CSV export functionality for dashboard listing pages
 * Handles secure export with permission checks and memory-efficient streaming
 * 
 * Usage:
 *   $exporter = new CSVExporter($mysqli);
 *   $exporter->exportFromQuery($query, $filename, $columnsToExport);
 * 
 * @package HAI_UAE
 * @version 1.0
 */

class CSVExporter {
    
    /**
     * Database connection
     * @var mysqli
     */
    private $mysqli;
    
    /**
     * Maximum rows to export (security limit)
     * @var int
     */
    private $maxRows = 50000;
    
    /**
     * CSV delimiter
     * @var string
     */
    private $delimiter = ',';
    
    /**
     * CSV enclosure character
     * @var string
     */
    private $enclosure = '"';
    
    /**
     * Constructor
     * 
     * @param mysqli $mysqli Database connection
     */
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Export data array to CSV file
     * 
     * @param array $data Array of associative arrays (rows)
     * @param string $filename Filename without extension
     * @param array $columns Optional: specific columns to export (in order)
     * @return bool Success status
     */
    public function exportArray($data, $filename, $columns = []) {
        if (empty($data)) {
            return false;
        }
        
        // Sanitize filename
        $filename = $this->sanitizeFilename($filename);
        
        // Set CSV headers
        $this->setHeaders($filename);
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Determine columns to export
        if (empty($columns)) {
            $columns = array_keys(reset($data));
        }
        
        // Write header row
        fputcsv($output, $columns, $this->delimiter, $this->enclosure);
        
        // Write data rows
        $rowCount = 0;
        foreach ($data as $row) {
            if ($rowCount >= $this->maxRows) {
                break;
            }
            
            $csvRow = [];
            foreach ($columns as $col) {
                $csvRow[] = $row[$col] ?? '';
            }
            
            fputcsv($output, $csvRow, $this->delimiter, $this->enclosure);
            $rowCount++;
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export data from SQL query directly (memory efficient)
     * 
     * @param string $query SQL SELECT query
     * @param string $filename Filename without extension
     * @param array $columns Optional: specific columns to export
     * @param array $columnHeaders Optional: custom header names (maps column => header)
     * @return bool Success status
     */
    public function exportFromQuery($query, $filename, $columns = [], $columnHeaders = []) {
        // Execute query
        $result = $this->mysqli->query($query);
        
        if (!$result) {
            error_log("CSV Export Query Failed: " . $this->mysqli->error);
            return false;
        }
        
        // Check if results exist
        if ($result->num_rows === 0) {
            $result->close();
            return false;
        }
        
        // Sanitize filename
        $filename = $this->sanitizeFilename($filename);
        
        // Set CSV headers
        $this->setHeaders($filename);
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Get first row to determine columns
        $firstRow = $result->fetch_assoc();
        
        // Determine columns to export
        if (empty($columns)) {
            $columns = array_keys($firstRow);
        }
        
        // Build header row with custom labels if provided
        $headerRow = [];
        foreach ($columns as $col) {
            $headerRow[] = $columnHeaders[$col] ?? ucwords(str_replace('_', ' ', $col));
        }
        
        // Write header row
        fputcsv($output, $headerRow, $this->delimiter, $this->enclosure);
        
        // Write first row
        $csvRow = [];
        foreach ($columns as $col) {
            $csvRow[] = $this->formatValue($firstRow[$col] ?? '');
        }
        fputcsv($output, $csvRow, $this->delimiter, $this->enclosure);
        
        // Write remaining rows
        $rowCount = 1;
        while (($row = $result->fetch_assoc()) && $rowCount < $this->maxRows) {
            $csvRow = [];
            foreach ($columns as $col) {
                $csvRow[] = $this->formatValue($row[$col] ?? '');
            }
            fputcsv($output, $csvRow, $this->delimiter, $this->enclosure);
            $rowCount++;
        }
        
        $result->close();
        fclose($output);
        exit;
    }
    
    /**
     * Export from DataTable class (using server-side query)
     * 
     * @param string $dataTableClass DataTable class name (e.g., 'CompaniesDataTable')
     * @param array $requestData Request parameters for filtering
     * @param string $filename Filename without extension
     * @param array $columns Columns to export
     * @return bool Success status
     */
    public function exportFromDataTable($dataTableClass, $requestData, $filename, $columns = []) {
        if (!class_exists($dataTableClass)) {
            return false;
        }
        
        // Instantiate DataTable
        $dataTable = new $dataTableClass($this->mysqli);
        
        // Get query (without limit)
        $query = $dataTable->buildExportQuery($requestData);
        
        // Export
        return $this->exportFromQuery($query, $filename, $columns);
    }
    
    /**
     * Set HTTP headers for CSV download
     * 
     * @param string $filename Filename
     * @return void
     */
    private function setHeaders($filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        header('Pragma: no-cache');
    }
    
    /**
     * Sanitize filename to prevent path traversal
     * 
     * @param string $filename Raw filename
     * @return string Safe filename
     */
    private function sanitizeFilename($filename) {
        // Remove extension if provided
        $filename = preg_replace('/\.csv$/i', '', $filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        
        // Limit length
        $filename = substr($filename, 0, 100);
        
        // Add timestamp
        $filename .= '_' . date('Y-m-d_His');
        
        return $filename;
    }
    
    /**
     * Format cell value for CSV output
     * 
     * @param mixed $value Cell value
     * @return string Formatted value
     */
    private function formatValue($value) {
        if ($value === null) {
            return '';
        }
        
        // Strip HTML tags
        $value = strip_tags($value);
        
        // Decode HTML entities
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Trim whitespace
        $value = trim($value);
        
        return $value;
    }
    
    /**
     * Set maximum rows to export
     * 
     * @param int $max Maximum rows
     * @return self
     */
    public function setMaxRows($max) {
        $this->maxRows = (int)$max;
        return $this;
    }
    
    /**
     * Set CSV delimiter
     * 
     * @param string $delimiter Delimiter character
     * @return self
     */
    public function setDelimiter($delimiter) {
        $this->delimiter = $delimiter;
        return $this;
    }
}

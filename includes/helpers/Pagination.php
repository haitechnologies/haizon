<?php
/**
 * Pagination - Utility class for handling pagination logic
 * Centralizes pagination calculation, validation, and query building
 * 
 * Replaces repeated code patterns:
 *   $currentPage = intval($_GET['page'] ?? 1);
 *   $offset = ($currentPage - 1) * $perPage;
 *   $totalPages = ceil($totalResults / $perPage);
 * 
 * Usage:
 *   $pagination = new Pagination($_GET['page'] ?? 1, $totalResults, 12);
 *   if (!$pagination->isValid()) header('Location: ' . $pagination->getFirstPageUrl());
 *   $result = $query_stmt->execute();
 *   $nextPageUrl = $pagination->getNextPageUrl('/listings');
 */

class Pagination {
    
    private $currentPage;
    private $totalItems;
    private $perPage;
    private $totalPages;
    private $offset;
    private $isValid;
    
    /**
     * Initialize pagination
     * 
     * @param int|string $currentPage Current page number (1-indexed)
     * @param int $totalItems Total number of items
     * @param int $perPage Items per page (default: 12)
     */
    public function __construct($currentPage = 1, $totalItems = 0, $perPage = 12) {
        $this->currentPage = max(1, intval($currentPage));
        $this->totalItems = max(0, intval($totalItems));
        $this->perPage = max(1, intval($perPage));
        
        // Calculate pagination values
        $this->totalPages = $this->totalItems > 0 ? ceil($this->totalItems / $this->perPage) : 1;
        $this->offset = ($this->currentPage - 1) * $this->perPage;
        
        // Validate current page
        $this->isValid = $this->currentPage <= $this->totalPages;
    }
    
    /**
     * Check if current page is valid
     * Use to 404 or redirect invalid page requests
     * 
     * @return bool True if page is valid
     */
    public function isValid() {
        return $this->isValid;
    }
    
    /**
     * Get SQL LIMIT clause
     * Use in database queries
     * 
     * @return string SQL LIMIT clause (e.g., "LIMIT 12 OFFSET 0")
     */
    public function getLimit() {
        return "LIMIT {$this->perPage} OFFSET {$this->offset}";
    }
    
    /**
     * Get offset for query
     * 
     * @return int Offset value
     */
    public function getOffset() {
        return $this->offset;
    }
    
    /**
     * Get current page number
     * 
     * @return int
     */
    public function getCurrentPage() {
        return $this->currentPage;
    }
    
    /**
     * Get total number of pages
     * 
     * @return int
     */
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    /**
     * Get total number of items
     * 
     * @return int
     */
    public function getTotalItems() {
        return $this->totalItems;
    }
    
    /**
     * Check if there's a next page
     * 
     * @return bool
     */
    public function hasNextPage() {
        return $this->currentPage < $this->totalPages;
    }
    
    /**
     * Check if there's a previous page
     * 
     * @return bool
     */
    public function hasPreviousPage() {
        return $this->currentPage > 1;
    }
    
    /**
     * Get next page number
     * 
     * @return int|null Next page number or null if no next page
     */
    public function getNextPage() {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }
    
    /**
     * Get previous page number
     * 
     * @return int|null Previous page number or null if no previous page
     */
    public function getPreviousPage() {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }
    
    /**
     * Get URL for specific page
     * 
     * @param string $baseUrl Base URL (e.g., '/listings')
     * @param int $page Page number
     * @param array $queryParams Additional query parameters
     * @return string URL with page parameter
     */
    public function getPageUrl($baseUrl, $page = null, $queryParams = []) {
        if ($page === null) {
            $page = $this->currentPage;
        }
        
        $params = ['page' => $page];
        $params = array_merge($params, $queryParams);
        
        $query = http_build_query($params);
        return $page === 1 ? $baseUrl : $baseUrl . '?' . $query;
    }
    
    /**
     * Get URL for first page
     * 
     * @param string $baseUrl Base URL
     * @param array $queryParams Additional query parameters
     * @return string
     */
    public function getFirstPageUrl($baseUrl, $queryParams = []) {
        return $this->getPageUrl($baseUrl, 1, $queryParams);
    }
    
    /**
     * Get URL for last page
     * 
     * @param string $baseUrl Base URL
     * @param array $queryParams Additional query parameters
     * @return string
     */
    public function getLastPageUrl($baseUrl, $queryParams = []) {
        return $this->getPageUrl($baseUrl, $this->totalPages, $queryParams);
    }
    
    /**
     * Get URL for next page
     * 
     * @param string $baseUrl Base URL
     * @param array $queryParams Additional query parameters
     * @return string|null
     */
    public function getNextPageUrl($baseUrl, $queryParams = []) {
        return $this->hasNextPage() ? $this->getPageUrl($baseUrl, $this->getNextPage(), $queryParams) : null;
    }
    
    /**
     * Get URL for previous page
     * 
     * @param string $baseUrl Base URL
     * @param array $queryParams Additional query parameters
     * @return string|null
     */
    public function getPreviousPageUrl($baseUrl, $queryParams = []) {
        return $this->hasPreviousPage() ? $this->getPageUrl($baseUrl, $this->getPreviousPage(), $queryParams) : null;
    }
    
    /**
     * Get array of page numbers for rendering pagination controls
     * Useful for displaying numbered page buttons
     * 
     * @param int $maxVisible Maximum number of page links to show (default: 7)
     * @return array Array of page numbers to display
     */
    public function getVisiblePages($maxVisible = 7) {
        if ($this->totalPages <= $maxVisible) {
            return range(1, $this->totalPages);
        }
        
        $half = floor($maxVisible / 2);
        $start = max(1, $this->currentPage - $half);
        $end = min($this->totalPages, $start + $maxVisible - 1);
        
        if ($end - $start < $maxVisible - 1) {
            $start = max(1, $end - $maxVisible + 1);
        }
        
        $pages = range($start, $end);
        
        // Add first/last page indicators if gap exists
        if ($start > 1) {
            array_unshift($pages, 1);
            if ($start > 2) {
                array_splice($pages, 1, 0, ['...']);
            }
        }
        
        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $pages[] = '...';
            }
            $pages[] = $this->totalPages;
        }
        
        return $pages;
    }
    
}

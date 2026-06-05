<?php
/**
 * Blogs Data Access Class
 * 
 * Handles all database operations for blog posts on the frontend.
 * Supports filtering by category, search, pagination, and view tracking.
 * 
 * @package Classes\Frontend
 */

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class Blogs {
    
    private $mysqli;
    private $table = DB::BLOGS;
    
    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }
    
    /**
     * Get all published blogs with optional filters
     * 
     * @param array $options Filter options (category_id, search, limit, offset, order_by)
     * @return array Array of blog records
     */
    public function getAll($options = []) {
        $where = ["publish = 1"];
        $params = [];
        $types = "";
        
        // Filter by category
        if (!empty($options['category_id'])) {
            $where[] = "category_id = ?";
            $params[] = $options['category_id'];
            $types .= "i";
        }
        
        // Search in title, excerpt, content
        if (!empty($options['search'])) {
            $searchTerm = '%' . $options['search'] . '%';
            $where[] = "(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            $types .= "sss";
        }
        
        // Filter by homepage feature
        if (isset($options['is_homepage'])) {
            $where[] = "is_homepage = ?";
            $params[] = $options['is_homepage'];
            $types .= "i";
        }
        
        // Build WHERE clause
        $whereClause = implode(' AND ', $where);
        
        // Order by
        $orderBy = $options['order_by'] ?? 'created_at DESC';
        
        // Pagination
        $limit = $options['limit'] ?? 10;
        $offset = $options['offset'] ?? 0;
        
        $sql = "SELECT * FROM `{$this->table}` WHERE {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getAll - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $blogs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $blogs;
    }
    
    /**
     * Get total count of published blogs with optional filters
     * 
     * @param array $options Filter options (category_id, search, is_homepage)
     * @return int Total count
     */
    public function getCount($options = []) {
        $where = ["publish = 1"];
        $params = [];
        $types = "";
        
        // Filter by category
        if (!empty($options['category_id'])) {
            $where[] = "category_id = ?";
            $params[] = $options['category_id'];
            $types .= "i";
        }
        
        // Search filter
        if (!empty($options['search'])) {
            $searchTerm = '%' . $options['search'] . '%';
            $where[] = "(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            $types .= "sss";
        }
        
        // Filter by homepage feature
        if (isset($options['is_homepage'])) {
            $where[] = "is_homepage = ?";
            $params[] = $options['is_homepage'];
            $types .= "i";
        }
        
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` WHERE {$whereClause}";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getCount - Prepare failed: " . $this->mysqli->error);
            return 0;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total'] ?? 0);
    }
    
    /**
     * Get a single blog by slug
     * 
     * @param string $slug Blog slug
     * @param bool $incrementViews Whether to increment view count
     * @return array|null Blog record or null if not found
     */
    public function getBySlug($slug, $incrementViews = true) {
        $sql = "SELECT * FROM `{$this->table}` WHERE slug = ? AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getBySlug - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $blog = $result->fetch_assoc();
        $stmt->close();
        
        // Increment view count
        if ($blog && $incrementViews) {
            $this->incrementViews($blog['id']);
        }
        
        return $blog;
    }
    
    /**
     * Get a single blog by ID
     * 
     * @param int $id Blog ID
     * @return array|null Blog record or null if not found
     */
    public function getById($id) {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = ? AND publish = 1 LIMIT 1";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getById - Prepare failed: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $blog = $result->fetch_assoc();
        $stmt->close();
        
        return $blog;
    }
    
    /**
     * Increment view count for a blog
     * 
     * @param int $id Blog ID
     * @return bool Success status
     */
    public function incrementViews($id) {
        $sql = "UPDATE `{$this->table}` SET views = views + 1 WHERE id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::incrementViews - Prepare failed: " . $this->mysqli->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get featured blogs (for homepage)
     * 
     * @param int $limit Maximum number of blogs to return
     * @return array Array of blog records
     */
    public function getFeatured($limit = 3) {
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 AND is_homepage = 1 ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getFeatured - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $blogs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $blogs;
    }
    
    /**
     * Get recent blog posts
     * 
     * @param int $limit Maximum number of blogs to return
     * @return array Array of blog records
     */
    public function getRecent($limit = 5) {
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getRecent - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $blogs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $blogs;
    }
    
    /**
     * Get most viewed blogs
     * 
     * @param int $limit Maximum number of blogs to return
     * @return array Array of blog records
     */
    public function getMostViewed($limit = 5) {
        $sql = "SELECT * FROM `{$this->table}` WHERE publish = 1 ORDER BY views DESC LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getMostViewed - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $blogs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $blogs;
    }

    /**
     * Get most viewed blogs with only homepage-required columns.
     * Reduces payload by avoiding large content fields.
     *
     * @param int $limit Maximum number of blogs to return
     * @return array Array of blog summary records
     */
    public function getMostViewedSummary($limit = 5) {
        $sql = "SELECT id, slug, title, views, created_at, excerpt, LEFT(content, 1200) AS content
                FROM `{$this->table}`
                WHERE publish = 1
                ORDER BY views DESC
                LIMIT ?";

        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getMostViewedSummary - Prepare failed: " . $this->mysqli->error);
            return [];
        }

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $blogs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $blogs;
    }

    /**
     * Get a random set of blogs from the latest posts window.
     * Keeps homepage content fresh while staying recent.
     *
     * @param int $limit Number of posts to return
     * @param int $latestPool Number of latest posts to randomize from
     * @return array Array of blog summary records
     */
    public function getLatestRandomSummary($limit = 3, $latestPool = 15) {
        $limit = max(1, (int)$limit);
        $latestPool = max($limit, (int)$latestPool);

        $sql = "SELECT id, slug, title, views, created_at, excerpt, LEFT(content, 1200) AS content
                FROM `{$this->table}`
                WHERE publish = 1
                ORDER BY created_at DESC
                LIMIT ?";

        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getLatestRandomSummary - Prepare failed: " . $this->mysqli->error);
            return [];
        }

        $stmt->bind_param("i", $latestPool);
        $stmt->execute();
        $result = $stmt->get_result();
        $blogs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (count($blogs) <= $limit) {
            return $blogs;
        }

        shuffle($blogs);
        return array_slice($blogs, 0, $limit);
    }
    
    /**
     * Get related blogs (same category, excluding current blog)
     * 
     * @param int $categoryId Category ID
     * @param int $excludeId Blog ID to exclude
     * @param int $limit Maximum number of blogs
     * @return array Array of blog records
     */
    public function getRelated($categoryId, $excludeId, $limit = 3) {
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE publish = 1 
                AND category_id = ? 
                AND id != ?
                ORDER BY created_at DESC
                LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::getRelated - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("iii", $categoryId, $excludeId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $blogs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $blogs;
    }
    
    /**
     * Search blogs by keyword
     * 
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array Array of blog records
     */
    public function search($keyword, $limit = 10, $offset = 0) {
        $searchTerm = '%' . $keyword . '%';
        
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE publish = 1 
                AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)
                ORDER BY 
                    CASE 
                        WHEN title LIKE ? THEN 1
                        WHEN excerpt LIKE ? THEN 2
                        ELSE 3
                    END,
                    created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::search - Prepare failed: " . $this->mysqli->error);
            return [];
        }
        
        $stmt->bind_param("sssssii", 
            $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm,
            $limit, $offset
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $blogs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $blogs;
    }
    
    /**
     * Get total count of search results
     * 
     * @param string $keyword Search keyword
     * @return int Total count
     */
    public function searchCount($keyword) {
        $searchTerm = '%' . $keyword . '%';
        
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` 
                WHERE publish = 1 
                AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Blogs::searchCount - Prepare failed: " . $this->mysqli->error);
            return 0;
        }
        
        $stmt->bind_param("sss", 
            $searchTerm, $searchTerm, $searchTerm
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total'] ?? 0);
    }
}

<?php
require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class Favorites {
    private $mysqli;
    private $favoritesTable = DB::FRONTEND_USER_FAVORITES;
    private $companiesTable = DB::COMPANIES;
    private $categoriesTable = DB::CATEGORIES;

    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }

    private function hasColumn($table, $column) {
        $sql = "SELECT COUNT(*) AS cnt
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ((int)($row['cnt'] ?? 0)) > 0;
    }

    public function getUserFavorites($userId, array $filters = []) {
        $hasNotes = $this->hasColumn($this->favoritesTable, 'user_notes');
        $hasCategoryName = $this->hasColumn($this->categoriesTable, 'name');
        $hasCategoryLegacy = $this->hasColumn($this->categoriesTable, 'category');

        $categorySelect = "'' AS category_name";
        if ($hasCategoryName && $hasCategoryLegacy) {
            $categorySelect = "COALESCE(cat.name, cat.category) AS category_name";
        } elseif ($hasCategoryName) {
            $categorySelect = "cat.name AS category_name";
        } elseif ($hasCategoryLegacy) {
            $categorySelect = "cat.category AS category_name";
        }

        $sql = "SELECT f.company_id, f.created_at AS saved_at,";
        if ($hasNotes) {
            $sql .= " f.user_notes,";
        } else {
            $sql .= " '' AS user_notes,";
        }
        $sql .= " c.company_name, c.slug, c.city, c.state, c.telephone AS phone, c.email, c.website,
                  c.verified, {$categorySelect}
                 FROM `{$this->favoritesTable}` f
                 INNER JOIN `{$this->companiesTable}` c ON c.id = f.company_id
                 LEFT JOIN `{$this->categoriesTable}` cat ON cat.id = c.primary_category_id
                 WHERE f.user_id = ? AND c.publish = 1";

        $params = [$userId];
        $types = 'i';

        if (!empty($filters['verified_only'])) {
            $sql .= " AND c.verified = 1";
        }

        $emirate = trim((string)($filters['emirate'] ?? ''));
        if ($emirate !== '') {
            $sql .= " AND (LOWER(c.state) = LOWER(?) OR LOWER(c.city) LIKE LOWER(?))";
            $params[] = $emirate;
            $params[] = '%' . $emirate . '%';
            $types .= 'ss';
        }

        $sql .= " ORDER BY f.created_at DESC";

        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function addFavorite($userId, $companyId) {
        $sql = "INSERT IGNORE INTO `{$this->favoritesTable}` (user_id, company_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $userId, $companyId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function isFavorite($userId, $companyId) {
        $sql = "SELECT 1 FROM `{$this->favoritesTable}` WHERE user_id = ? AND company_id = ? LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $userId, $companyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function removeFavorite($userId, $companyId) {
        $sql = "DELETE FROM `{$this->favoritesTable}` WHERE user_id = ? AND company_id = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $userId, $companyId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateNote($userId, $companyId, $note) {
        if (!$this->hasColumn($this->favoritesTable, 'user_notes')) {
            return false;
        }

        $sql = "UPDATE `{$this->favoritesTable}` SET user_notes = ? WHERE user_id = ? AND company_id = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sii', $note, $userId, $companyId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/**
 * User Repository
 * 
 * Handles PDO-based data access for erp_users table.
 * Adheres strictly to PSR.md rules.
 */
class UserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Check if any users are associated with a department
     *
     * @param int $departmentId
     * @return bool
     */
    public function hasUsersInDepartment(int $departmentId): bool
    {
        $sql = "SELECT id FROM `erp_users` WHERE department_id = :department_id LIMIT 1";
        $row = $this->db->fetchOne($sql, ['department_id' => $departmentId]);
        return $row !== null;
    }
}

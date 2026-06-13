<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\User;

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
     */
    public function hasUsersInDepartment(int $departmentId): bool
    {
        $sql = "SELECT id FROM DB::USERS WHERE department_id = :department_id LIMIT 1";
        $row = $this->db->fetchOne($sql, ['department_id' => $departmentId]);
        return $row !== null;
    }

    /**
     * Find user by ID
     */
    public function find(int $id): ?User
    {
        $sql = "SELECT id, can_access_system, is_active, role_id, email, password, 
                       full_name, mobile, contact1, contact2, address, dob, 
                       department_id, last_login, photo, created_at, updated_at, created_by 
                FROM DB::USERS 
                WHERE id = :id";

        $row = $this->db->fetchOne($sql, ['id' => $id]);
        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT id, can_access_system, is_active, role_id, email, password, 
                       full_name, mobile, contact1, contact2, address, dob, 
                       department_id, last_login, photo, created_at, updated_at, created_by 
                FROM DB::USERS 
                WHERE email = :email";

        $row = $this->db->fetchOne($sql, ['email' => trim($email)]);
        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    /**
     * Check if email exists (excluding a specific user ID)
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $email = trim($email);
        if ($excludeId !== null) {
            $sql = "SELECT id FROM DB::USERS WHERE email = :email AND id != :exclude_id LIMIT 1";
            $params = ['email' => $email, 'exclude_id' => $excludeId];
        } else {
            $sql = "SELECT id FROM DB::USERS WHERE email = :email LIMIT 1";
            $params = ['email' => $email];
        }

        $row = $this->db->fetchOne($sql, $params);
        return $row !== null;
    }

    /**
     * Save user record (insert or update)
     */
    public function save(User $user): User
    {
        if ($user->id === null) {
            return $this->insert($user);
        }
        return $this->update($user);
    }

    private function insert(User $user): User
    {
        $sql = "INSERT INTO DB::USERS (
                    can_access_system, is_active, role_id, email, password, 
                    full_name, mobile, contact1, contact2, address, dob, 
                    department_id, photo, created_by, created_at
                ) VALUES (
                    :can_access_system, :is_active, :role_id, :email, :password, 
                    :full_name, :mobile, :contact1, :contact2, :address, :dob, 
                    :department_id, :photo, :created_by, NOW()
                )";

        $params = [
            'can_access_system' => $user->canAccessSystem ? 1 : 0,
            'is_active' => $user->isActive ? 1 : 0,
            'role_id' => $user->roleId,
            'email' => $user->email,
            'password' => $user->password,
            'full_name' => $user->fullName,
            'mobile' => $user->mobile,
            'contact1' => $user->contact1,
            'contact2' => $user->contact2,
            'address' => $user->address,
            'dob' => $user->dob,
            'department_id' => $user->departmentId,
            'photo' => $user->photo,
            'created_by' => $user->createdBy,
        ];

        $insertId = (int)$this->db->insert($sql, $params);

        $inserted = $this->find($insertId);
        if ($inserted === null) {
            throw new \RuntimeException("Failed to retrieve inserted user.");
        }

        return $inserted;
    }

    private function update(User $user): User
    {
        $sql = "UPDATE DB::USERS 
                SET can_access_system = :can_access_system, 
                    is_active = :is_active, 
                    role_id = :role_id, 
                    email = :email, 
                    password = :password, 
                    full_name = :full_name, 
                    mobile = :mobile, 
                    contact1 = :contact1, 
                    contact2 = :contact2, 
                    address = :address, 
                    dob = :dob, 
                    department_id = :department_id, 
                    photo = :photo, 
                    updated_at = NOW()
                WHERE id = :id";

        $params = [
            'can_access_system' => $user->canAccessSystem ? 1 : 0,
            'is_active' => $user->isActive ? 1 : 0,
            'role_id' => $user->roleId,
            'email' => $user->email,
            'password' => $user->password,
            'full_name' => $user->fullName,
            'mobile' => $user->mobile,
            'contact1' => $user->contact1,
            'contact2' => $user->contact2,
            'address' => $user->address,
            'dob' => $user->dob,
            'department_id' => $user->departmentId,
            'photo' => $user->photo,
            'id' => $user->id,
        ];

        $this->db->execute($sql, $params);

        $updated = $this->find((int)$user->id);
        if ($updated === null) {
            throw new \RuntimeException("Failed to retrieve updated user.");
        }

        return $updated;
    }

    /**
     * Delete user by ID
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM DB::USERS WHERE id = :id";
        $stmt = $this->db->execute($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Map database row to User DTO
     */
    private function mapRowToDto(array $row): User
    {
        return new User(
            id: (int)$row['id'],
            canAccessSystem: (bool)$row['can_access_system'],
            isActive: (bool)$row['is_active'],
            roleId: (int)$row['role_id'],
            email: (string)$row['email'],
            password: $row['password'] !== null ? (string)$row['password'] : null,
            fullName: (string)$row['full_name'],
            mobile: $row['mobile'] !== null ? (string)$row['mobile'] : null,
            contact1: $row['contact1'] !== null ? (string)$row['contact1'] : null,
            contact2: $row['contact2'] !== null ? (string)$row['contact2'] : null,
            address: $row['address'] !== null ? (string)$row['address'] : null,
            dob: $row['dob'] !== null ? (string)$row['dob'] : null,
            departmentId: $row['department_id'] !== null ? (int)$row['department_id'] : null,
            lastLogin: $row['last_login'] !== null ? (string)$row['last_login'] : null,
            photo: $row['photo'] !== null ? (string)$row['photo'] : null,
            publish: (bool)$row['publish'],
            createdAt: $row['created_at'] !== null ? (string)$row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            createdBy: (int)($row['created_by'] ?? 0)
        );
    }
}

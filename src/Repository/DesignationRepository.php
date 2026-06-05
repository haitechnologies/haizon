<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Model\Designation;
use App\Core\DB;

/**
 * Designation Repository
 *
 * Handles PDO-based data access for erp_designations table.
 * Adheres strictly to PSR-12 and PSR.md rules.
 */
class DesignationRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a designation by ID
     *
     * @param int $id
     * @return Designation|null
     */
    public function find(int $id): ?Designation
    {
        $sql = "SELECT id, organization_id, designation, publish, created_at, updated_at, created_by 
                FROM `erp_designations` 
                WHERE id = :id";

        $row = $this->db->fetchOne($sql, ['id' => $id]);
        if ($row === null) {
            return null;
        }

        return $this->mapRowToDto($row);
    }

    /**
     * Find all designations for an organization
     *
     * @param int $organizationId
     * @return Designation[]
     */
    public function findAll(int $organizationId): array
    {
        $sql = "SELECT id, organization_id, designation, publish, created_at, updated_at, created_by 
                FROM `erp_designations` 
                WHERE organization_id = :organization_id 
                ORDER BY designation ASC";

        $rows = $this->db->fetchAll($sql, ['organization_id' => $organizationId]);
        $designations = [];
        foreach ($rows as $row) {
            $designations[] = $this->mapRowToDto($row);
        }

        return $designations;
    }

    /**
     * Check if a designation name exists, optionally excluding an ID
     *
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $sql = "SELECT id FROM `erp_designations` WHERE designation = :name AND id != :exclude_id LIMIT 1";
            $params = ['name' => $name, 'exclude_id' => $excludeId];
        } else {
            $sql = "SELECT id FROM `erp_designations` WHERE designation = :name LIMIT 1";
            $params = ['name' => $name];
        }

        $row = $this->db->fetchOne($sql, $params);
        return $row !== null;
    }

    /**
     * Save a designation record (Insert or Update)
     *
     * @param Designation $designation
     * @return Designation
     */
    public function save(Designation $designation): Designation
    {
        if ($designation->id === null) {
            return $this->insert($designation);
        }
        return $this->update($designation);
    }

    /**
     * Insert a new designation
     */
    private function insert(Designation $designation): Designation
    {
        $sql = "INSERT INTO `erp_designations` (organization_id, designation, publish, created_by) 
                VALUES (:organization_id, :designation, :publish, :created_by)";

        $params = [
            'organization_id' => $designation->organizationId,
            'designation' => $designation->designation,
            'publish' => $designation->publish ? 1 : 0,
            'created_by' => $designation->createdBy,
        ];

        $insertId = (int)$this->db->insert($sql, $params);
        return $this->find($insertId);
    }

    /**
     * Update an existing designation
     */
    private function update(Designation $designation): Designation
    {
        $sql = "UPDATE `erp_designations` 
                SET organization_id = :organization_id, 
                    designation = :designation, 
                    publish = :publish, 
                    created_by = :created_by 
                WHERE id = :id";

        $params = [
            'organization_id' => $designation->organizationId,
            'designation' => $designation->designation,
            'publish' => $designation->publish ? 1 : 0,
            'created_by' => $designation->createdBy,
            'id' => $designation->id,
        ];

        $this->db->execute($sql, $params);
        return $this->find((int)$designation->id);
    }

    /**
     * Delete a designation by ID
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM `erp_designations` WHERE id = :id";
        $stmt = $this->db->execute($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Map database row to Designation DTO
     */
    private function mapRowToDto(array $row): Designation
    {
        return new Designation(
            id: (int)$row['id'],
            organizationId: $row['organization_id'] !== null ? (int)$row['organization_id'] : null,
            designation: (string)$row['designation'],
            publish: (bool)$row['publish'],
            createdAt: (string)($row['created_at'] ?? ''),
            updatedAt: (string)($row['updated_at'] ?? ''),
            createdBy: (int)($row['created_by'] ?? 0)
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\UserDocument;

class UserDocumentRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByUser(int $userId, int $orgId): array
    {
        $sql = "SELECT * FROM `{DB::USER_DOCUMENTS}` WHERE attachable_type = 'UserDoc' AND attachable_id = :user_id AND organization_id = :org_id ORDER BY created_at DESC";
        $rows = $this->db->fetchAll($sql, ['user_id' => $userId, 'org_id' => $orgId]);
        return array_map($this->mapRowToUserDocument(...), $rows);
    }

    public function find(int $id, int $orgId): ?UserDocument
    {
        $sql = "SELECT * FROM `{DB::USER_DOCUMENTS}` WHERE id = :id AND organization_id = :org_id AND attachable_type = 'UserDoc'";
        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);
        if ($row === null) {
            return null;
        }
        return $this->mapRowToUserDocument($row);
    }

    public function save(UserDocument $document): UserDocument
    {
        if ($document->id === null) {
            return $this->insert($document);
        }
        return $this->update($document);
    }

    private function insert(UserDocument $document): UserDocument
    {
        $sql = "INSERT INTO `{DB::USER_DOCUMENTS}` (
                    organization_id, attachable_type, attachable_id, document_category,
                    display_name, filename, original_filename, file_size,
                    description, issued_date, expiry_date,
                    created_at, updated_at, created_by
                ) VALUES (
                    :organization_id, 'UserDoc', :user_id, :document_type,
                    :display_name, :filename, :original_filename, :file_size,
                    :description, :issued_date, :expiry_date,
                    NOW(), NOW(), :created_by
                )";

        $params = [
            'organization_id' => $document->organizationId,
            'user_id' => $document->userId,
            'document_type' => $document->documentType,
            'display_name' => $document->originalFilename,
            'filename' => $document->filename,
            'original_filename' => $document->originalFilename,
            'file_size' => $document->fileSize,
            'description' => $document->description,
            'issued_date' => $document->issuedDate,
            'expiry_date' => $document->expiryDate,
            'created_by' => $document->createdBy,
        ];

        $insertId = (int)$this->db->insert($sql, $params);

        $inserted = $this->find($insertId, $document->organizationId);
        if ($inserted === null) {
            throw new \RuntimeException("Failed to retrieve inserted user document.");
        }

        return $inserted;
    }

    private function update(UserDocument $document): UserDocument
    {
        $sql = "UPDATE `{DB::USER_DOCUMENTS}` SET
                    attachable_id = :user_id,
                    document_category = :document_type,
                    display_name = :display_name,
                    filename = COALESCE(:filename, filename),
                    original_filename = COALESCE(:original_filename, original_filename),
                    file_size = COALESCE(:file_size, file_size),
                    description = :description,
                    issued_date = :issued_date,
                    expiry_date = :expiry_date,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :organization_id";

        $params = [
            'id' => $document->id,
            'organization_id' => $document->organizationId,
            'user_id' => $document->userId,
            'document_type' => $document->documentType,
            'display_name' => $document->originalFilename,
            'filename' => $document->filename,
            'original_filename' => $document->originalFilename,
            'file_size' => $document->fileSize,
            'description' => $document->description,
            'issued_date' => $document->issuedDate,
            'expiry_date' => $document->expiryDate,
        ];

        $this->db->execute($sql, $params);

        $updated = $this->find((int)$document->id, $document->organizationId);
        if ($updated === null) {
            throw new \RuntimeException("Failed to retrieve updated user document.");
        }

        return $updated;
    }

    public function delete(int $id, int $orgId): bool
    {
        $sql = "DELETE FROM `{DB::USER_DOCUMENTS}` WHERE id = :id AND organization_id = :org_id";
        $stmt = $this->db->execute($sql, ['id' => $id, 'org_id' => $orgId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteByUserAndOrg(int $id, int $orgId, int $userId): bool
    {
        $sql = "DELETE FROM `{DB::USER_DOCUMENTS}` WHERE id = :id AND organization_id = :org_id AND created_by = :created_by";
        $stmt = $this->db->execute($sql, ['id' => $id, 'org_id' => $orgId, 'created_by' => $userId]);
        return $stmt->rowCount() > 0;
    }

    private function mapRowToUserDocument(array $row): UserDocument
    {
        return new UserDocument(
            id: (int)$row['id'],
            organizationId: (int)($row['organization_id'] ?? 0),
            userId: (int)($row['attachable_id'] ?? 0),
            documentType: isset($row['document_category']) ? (int)$row['document_category'] : null,
            filename: isset($row['filename']) ? (string)$row['filename'] : null,
            originalFilename: isset($row['original_filename']) ? (string)$row['original_filename'] : (isset($row['filename']) ? (string)$row['filename'] : null),
            fileSize: isset($row['file_size']) ? (int)$row['file_size'] : null,
            description: $row['description'] !== null ? (string)$row['description'] : null,
            issuedDate: isset($row['issued_date']) && $row['issued_date'] !== '1970-01-01' ? (string)$row['issued_date'] : null,
            expiryDate: isset($row['expiry_date']) && $row['expiry_date'] !== '1970-01-01' ? (string)$row['expiry_date'] : null,
            createdAt: $row['created_at'] !== null ? (string)$row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            createdBy: (int)($row['created_by'] ?? 0),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\LeadAttachment;

class LeadAttachmentRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id, int $orgId): ?LeadAttachment
    {
        $sql = "SELECT * FROM `{DB::LEAD_ATTACHMENTS}` WHERE id = :id AND organization_id = :org_id AND attachable_type = 'Lead'";
        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);
        if ($row === null) {
            return null;
        }
        return $this->mapRowToLeadAttachment($row);
    }

    public function findByLead(int $leadId, int $orgId): array
    {
        $sql = "SELECT * FROM `{DB::LEAD_ATTACHMENTS}` WHERE attachable_type = 'Lead' AND attachable_id = :lead_id AND organization_id = :org_id ORDER BY id DESC";
        $rows = $this->db->fetchAll($sql, ['lead_id' => $leadId, 'org_id' => $orgId]);
        $attachments = [];
        foreach ($rows as $row) {
            $attachments[] = $this->mapRowToLeadAttachment($row);
        }
        return $attachments;
    }

    public function save(LeadAttachment $attachment): LeadAttachment
    {
        if ($attachment->id === null) {
            return $this->insert($attachment);
        }
        return $this->update($attachment);
    }

    private function insert(LeadAttachment $attachment): LeadAttachment
    {
        $sql = "INSERT INTO `{DB::LEAD_ATTACHMENTS}` (
                    organization_id, attachable_type, attachable_id,
                    display_name, filename, original_filename, file_size,
                    description, created_at, updated_at, created_by
                ) VALUES (
                    :organization_id, 'Lead', :lead_id,
                    :display_name, :filename, :original_filename, :file_size,
                    :description, NOW(), NOW(), :created_by
                )";

        $params = [
            'organization_id' => $attachment->organizationId,
            'lead_id' => $attachment->leadId,
            'display_name' => $attachment->originalFilename,
            'filename' => $attachment->filename,
            'original_filename' => $attachment->originalFilename,
            'file_size' => $attachment->fileSize,
            'description' => $attachment->description,
            'created_by' => $attachment->createdBy,
        ];

        $insertId = (int)$this->db->insert($sql, $params);

        $inserted = $this->find($insertId, $attachment->organizationId);
        if ($inserted === null) {
            throw new \RuntimeException("Failed to retrieve inserted lead attachment.");
        }

        return $inserted;
    }

    private function update(LeadAttachment $attachment): LeadAttachment
    {
        $sql = "UPDATE `{DB::LEAD_ATTACHMENTS}` SET
                    display_name = :display_name,
                    filename = COALESCE(:filename, filename),
                    original_filename = COALESCE(:original_filename, original_filename),
                    file_size = COALESCE(:file_size, file_size),
                    description = :description,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :organization_id";

        $params = [
            'id' => $attachment->id,
            'organization_id' => $attachment->organizationId,
            'display_name' => $attachment->originalFilename,
            'filename' => $attachment->filename,
            'original_filename' => $attachment->originalFilename,
            'file_size' => $attachment->fileSize,
            'description' => $attachment->description,
        ];

        $this->db->execute($sql, $params);

        $updated = $this->find((int)$attachment->id, $attachment->organizationId);
        if ($updated === null) {
            throw new \RuntimeException("Failed to retrieve updated lead attachment.");
        }

        return $updated;
    }

    public function delete(int $id, int $orgId): bool
    {
        $sql = "DELETE FROM `{DB::LEAD_ATTACHMENTS}` WHERE id = :id AND organization_id = :org_id";
        $stmt = $this->db->execute($sql, ['id' => $id, 'org_id' => $orgId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteByUserAndOrg(int $id, int $orgId, int $userId): bool
    {
        $sql = "DELETE FROM `{DB::LEAD_ATTACHMENTS}` WHERE id = :id AND organization_id = :org_id AND created_by = :created_by";
        $stmt = $this->db->execute($sql, ['id' => $id, 'org_id' => $orgId, 'created_by' => $userId]);
        return $stmt->rowCount() > 0;
    }

    private function mapRowToLeadAttachment(array $row): LeadAttachment
    {
        return new LeadAttachment(
            id: (int)$row['id'],
            organizationId: (int)($row['organization_id'] ?? 0),
            leadId: (int)($row['attachable_id'] ?? 0),
            filename: isset($row['filename']) ? (string)$row['filename'] : null,
            originalFilename: isset($row['original_filename']) ? (string)$row['original_filename'] : (isset($row['filename']) ? (string)$row['filename'] : null),
            fileSize: isset($row['file_size']) ? (int)$row['file_size'] : null,
            description: $row['description'] !== null ? (string)$row['description'] : null,
            createdAt: $row['created_at'] !== null ? (string)$row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            createdBy: (int)($row['created_by'] ?? 0),
        );
    }
}

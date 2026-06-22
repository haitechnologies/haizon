<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Model\LeadAttachment;
use App\Repository\LeadAttachmentRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

class LeadAttachmentService
{
    private LeadAttachmentRepository $repo;
    private Database $db;

    private const UPLOAD_DIR = 'uploads/lead_attachments/';
    private const ALLOWED_EXTENSIONS = ['doc', 'docx', 'pdf', 'txt', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx', 'jpeg', 'jpg', 'png'];
    private const MAX_FILE_SIZE = 5242880;

    public function __construct(LeadAttachmentRepository $repo, Database $db)
    {
        $this->repo = $repo;
        $this->db = $db;
    }

    public function getAttachment(int $id, int $orgId): LeadAttachment
    {
        $attachment = $this->repo->find($id, $orgId);
        if ($attachment === null) {
            throw new NotFoundException("Lead Attachment with ID {$id} not found.");
        }
        return $attachment;
    }

    public function getAttachmentsByLead(int $leadId, int $orgId): array
    {
        return $this->repo->findByLead($leadId, $orgId);
    }

    public function createAttachment(array $data, ?array $file, int $orgId, int $userId): LeadAttachment
    {
        $this->validateCreate($data, $file);

        $uploadedFilename = $this->handleUpload($file, $userId);
        $originalFilename = $file['name'] ?? '';

        $this->db->beginTransaction();
        try {
            $attachment = new LeadAttachment(
                id: null,
                organizationId: $orgId,
                leadId: (int)($data['lead_id'] ?? 0),
                filename: $uploadedFilename,
                originalFilename: $originalFilename,
                fileSize: $file['size'] ?? null,
                description: !empty($data['description']) ? trim((string)$data['description']) : null,
                createdBy: $userId,
            );

            $saved = $this->repo->save($attachment);
            $this->db->commit();
            return $saved;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->deleteFile($uploadedFilename);
            throw $e;
        }
    }

    public function updateAttachment(int $id, array $data, ?array $file, int $orgId, int $userId): LeadAttachment
    {
        $existing = $this->getAttachment($id, $orgId);

        $uploadedFilename = null;
        $originalFilename = null;
        $fileSize = null;

        if ($file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $this->validateFile($file);
            $uploadedFilename = $this->handleUpload($file, $userId);
            $originalFilename = $file['name'] ?? '';
            $fileSize = $file['size'] ?? null;
        }

        $this->db->beginTransaction();
        try {
            $attachment = new LeadAttachment(
                id: $existing->id,
                organizationId: $existing->organizationId,
                leadId: $existing->leadId,
                filename: $uploadedFilename,
                originalFilename: $originalFilename,
                fileSize: $fileSize,
                description: isset($data['description']) ? (!empty($data['description']) ? trim((string)$data['description']) : null) : $existing->description,
                createdAt: $existing->createdAt,
                updatedAt: $existing->updatedAt,
                createdBy: $existing->createdBy,
            );

            $saved = $this->repo->save($attachment);

            if ($uploadedFilename !== null && $existing->filename !== null) {
                $this->deleteFile($existing->filename);
            }

            $this->db->commit();
            return $saved;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            if ($uploadedFilename !== null) {
                $this->deleteFile($uploadedFilename);
            }
            throw $e;
        }
    }

    public function deleteAttachment(int $id, int $orgId, int $userId, bool $isSuperAdmin): bool
    {
        $attachment = $this->getAttachment($id, $orgId);

        $this->db->beginTransaction();
        try {
            if ($isSuperAdmin) {
                $result = $this->repo->delete($id, $orgId);
            } else {
                $result = $this->repo->deleteByUserAndOrg($id, $orgId, $userId);
            }

            if ($result && $attachment->filename !== null) {
                $this->deleteFile($attachment->filename);
            }

            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateCreate(array $data, ?array $file): void
    {
        if (empty($data['lead_id']) || (int)$data['lead_id'] <= 0) {
            throw new ValidationException(['lead_id' => 'Lead ID is required.']);
        }
        if ($file === null || empty($file['tmp_name'])) {
            throw new ValidationException(['file' => 'Document is mandatory.']);
        }
        $this->validateFile($file);
    }

    private function validateFile(array $file): void
    {
        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new ValidationException(['file' => 'Invalid file extension. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)]);
        }
        if (($file['size'] ?? 0) > self::MAX_FILE_SIZE) {
            throw new ValidationException(['file' => 'File is too large. Maximum size: ' . (self::MAX_FILE_SIZE / 1048576) . 'MB']);
        }
    }

    private function handleUpload(array $file, int $userId): string
    {
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $filename = 'att_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        $uploadDir = dirname(__DIR__, 2) . '/' . self::UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException("Failed to move uploaded file.");
        }

        return $filename;
    }

    private function deleteFile(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }
        $path = dirname(__DIR__, 2) . '/' . self::UPLOAD_DIR . $filename;
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}

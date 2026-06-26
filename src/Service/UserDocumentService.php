<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Model\UserDocument;
use App\Repository\UserDocumentRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

class UserDocumentService
{
    private UserDocumentRepository $repo;
    private Database $db;

    private const UPLOAD_DIR = 'uploads/user_documents/';
    private const ALLOWED_EXTENSIONS = ['doc', 'docx', 'pdf', 'txt', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx', 'jpeg', 'jpg', 'png'];
    private const MAX_FILE_SIZE = 5242880;

    public function __construct(UserDocumentRepository $repo, Database $db)
    {
        $this->repo = $repo;
        $this->db = $db;
    }

    public function getDocumentsByUser(int $userId, int $orgId): array
    {
        return $this->repo->findByUser($userId, $orgId);
    }

    public function getDocument(int $id, int $orgId): UserDocument
    {
        $document = $this->repo->find($id, $orgId);
        if ($document === null) {
            throw new NotFoundException("User Document with ID {$id} not found.");
        }
        return $document;
    }

    public function createDocument(array $data, ?array $file, int $orgId, int $userId): UserDocument
    {
        $this->validateCreate($data, $file, $orgId);

        $uploadedFilename = $this->handleUpload($file, $userId);
        $originalFilename = $file['name'] ?? '';

        $this->db->beginTransaction();
        try {
            $document = new UserDocument(
                id: null,
                organizationId: $orgId,
                userId: (int)($data['user_id'] ?? 0),
                documentType: !empty($data['document_type']) ? (int)$data['document_type'] : null,
                filename: $uploadedFilename,
                originalFilename: $originalFilename,
                fileSize: $file['size'] ?? null,
                description: !empty($data['description']) ? trim((string)$data['description']) : null,
                issuedDate: $this->parseDate($data['issued_date'] ?? ''),
                expiryDate: $this->parseDate($data['expiry_date'] ?? ''),
                createdBy: $userId,
            );

            $saved = $this->repo->save($document);
            $this->db->commit();
            return $saved;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->deleteFile($uploadedFilename);
            throw $e;
        }
    }

    public function updateDocument(int $id, array $data, ?array $file, int $orgId, int $userId): UserDocument
    {
        $existing = $this->getDocument($id, $orgId);
        $this->validateUpdate($data);

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
            $document = new UserDocument(
                id: $existing->id,
                organizationId: $existing->organizationId,
                userId: isset($data['user_id']) ? (int)$data['user_id'] : $existing->userId,
                documentType: isset($data['document_type']) ? (!empty($data['document_type']) ? (int)$data['document_type'] : null) : $existing->documentType,
                filename: $uploadedFilename,
                originalFilename: $originalFilename,
                fileSize: $fileSize,
                description: isset($data['description']) ? (!empty($data['description']) ? trim((string)$data['description']) : null) : $existing->description,
                issuedDate: isset($data['issued_date']) ? $this->parseDate($data['issued_date']) : $existing->issuedDate,
                expiryDate: isset($data['expiry_date']) ? $this->parseDate($data['expiry_date']) : $existing->expiryDate,
                createdAt: $existing->createdAt,
                updatedAt: $existing->updatedAt,
                createdBy: $existing->createdBy,
            );

            $saved = $this->repo->save($document);

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

    public function updateDocumentDates(int $id, array $data, int $orgId): UserDocument
    {
        $existing = $this->getDocument($id, $orgId);

        $document = new UserDocument(
            id: $existing->id,
            organizationId: $existing->organizationId,
            userId: $existing->userId,
            documentType: $existing->documentType,
            filename: $existing->filename,
            originalFilename: $existing->originalFilename,
            fileSize: $existing->fileSize,
            description: $existing->description,
            issuedDate: $this->parseDate($data['issued_date'] ?? ''),
            expiryDate: $this->parseDate($data['expiry_date'] ?? ''),
            createdAt: $existing->createdAt,
            updatedAt: $existing->updatedAt,
            createdBy: $existing->createdBy,
        );

        $this->db->beginTransaction();
        try {
            $saved = $this->repo->save($document);
            $this->db->commit();
            return $saved;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteDocument(int $id, int $orgId, int $userId, bool $isSuperAdmin): bool
    {
        $document = $this->getDocument($id, $orgId);

        $this->db->beginTransaction();
        try {
            if ($isSuperAdmin) {
                $result = $this->repo->delete($id, $orgId);
            } else {
                $result = $this->repo->deleteByUserAndOrg($id, $orgId, $userId);
            }

            if ($result && $document->filename !== null) {
                $this->deleteFile($document->filename);
            }

            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateCreate(array $data, ?array $file, int $orgId): void
    {
        if (empty($data['user_id']) || (int)$data['user_id'] <= 0) {
            throw new ValidationException(['user_id' => 'Please select an Employee.']);
        }
        if ($file === null || empty($file['tmp_name'])) {
            throw new ValidationException(['file' => 'Document is mandatory.']);
        }
        $this->validateFile($file);
        $this->validateDates($data);

        if (!empty($data['document_type'])) {
            $userId = (int)$data['user_id'];
            $categoryId = (int)$data['document_type'];
            $issuedDate = $this->parseDate($data['issued_date'] ?? '');
            $expiryDate = $this->parseDate($data['expiry_date'] ?? '');
            if ($this->repo->existsByUserCategoryAndDates($userId, $orgId, $categoryId, $issuedDate, $expiryDate)) {
                throw new ValidationException(['document' => 'A document with the same category, issue date, and expiry date already exists for this employee.']);
            }
        }
    }

    private function validateUpdate(array $data): void
    {
        if (isset($data['user_id']) && ((int)$data['user_id'] <= 0)) {
            throw new ValidationException(['user_id' => 'Please select an Employee.']);
        }
        $this->validateDates($data);
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

    private function validateDates(array $data): void
    {
        $issuedDate = $data['issued_date'] ?? '';
        $expiryDate = $data['expiry_date'] ?? '';
        if (!empty($issuedDate) && $issuedDate !== '1970-01-01' && !empty($expiryDate) && $expiryDate !== '1970-01-01') {
            $issuedDb = $this->parseDate($issuedDate);
            $expiryDb = $this->parseDate($expiryDate);
            if ($issuedDb !== null && $expiryDb !== null && $expiryDb < $issuedDb) {
                throw new ValidationException(['expiry_date' => 'Expiry date should always be later than the Issued Date.']);
            }
        }
    }

    private function handleUpload(array $file, int $userId): string
    {
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $filename = 'doc_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

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

    private function parseDate(string $date): ?string
    {
        if (empty($date) || $date === '1970-01-01') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        $timestamp = strtotime($date);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
}

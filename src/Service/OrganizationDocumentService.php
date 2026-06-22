<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;
use App\Model\UserDocument;
use App\Exception\ValidationException;

class OrganizationDocumentService
{
    private Database $db;

    private const ALLOWED_EXTENSIONS = ['doc', 'docx', 'pdf', 'txt', 'xls', 'xlsx', 'ppt', 'pptx', 'jpeg', 'jpg', 'png'];
    private const MAX_FILE_SIZE = 5_242_880;
    private const UPLOAD_DIR = '/uploads/organization_documents/';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getDocuments(int $orgId): array
    {
        $sql = "SELECT a.id, a.attachable_id, a.document_category, a.display_name,
                       a.filename, a.original_filename, a.file_size,
                       a.description, a.issued_date, a.expiry_date,
                       a.created_at, a.created_by,
                       dc.document_category AS category_name
                FROM `{DB::USER_DOCUMENTS}` a
                LEFT JOIN `{DB::DOCUMENT_CATEGORIES}` dc ON a.document_category = dc.id
                WHERE a.attachable_type = 'OrganizationDoc'
                  AND a.attachable_id = :org_id
                ORDER BY a.created_at DESC";

        return $this->db->fetchAll($sql, ['org_id' => $orgId]);
    }

    public function createDocument(array $data, ?array $file, int $orgId, int $userId): UserDocument
    {
        $errors = [];

        if (empty($data['document_category'])) {
            $errors['document_category'] = 'Document category is required.';
        }

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            $errors['document'] = 'Please select a file to upload.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $displayName = !empty($data['display_name'])
            ? trim($data['display_name'])
            : $file['name'];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new ValidationException(['document' => 'File type not allowed. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)]);
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new ValidationException(['document' => 'File size must be under 5 MB.']);
        }

        $uploadDir = dirname(__DIR__, 2) . self::UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = 'org_doc_' . $orgId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \RuntimeException('Failed to upload document.');
        }

        $issuedDate = !empty($data['issued_date']) ? $data['issued_date'] : null;
        $expiryDate = !empty($data['expiry_date']) ? $data['expiry_date'] : null;

        if ($issuedDate && $expiryDate && $expiryDate < $issuedDate) {
            @unlink($destPath);
            throw new ValidationException(['expiry_date' => 'Expiry date cannot be before issue date.']);
        }

        $sql = "INSERT INTO `{DB::USER_DOCUMENTS}` 
                (organization_id, attachable_type, attachable_id, document_category, display_name,
                 filename, original_filename, file_size, description, issued_date, expiry_date,
                 created_at, updated_at, created_by)
                VALUES (:org_id, 'OrganizationDoc', :org_id2, :category, :display_name,
                        :filename, :original_name, :file_size, :description, :issued_date, :expiry_date,
                        NOW(), NOW(), :created_by)";

        $params = [
            'org_id' => $orgId,
            'org_id2' => $orgId,
            'category' => (int)$data['document_category'],
            'display_name' => $displayName,
            'filename' => $filename,
            'original_name' => $file['name'],
            'file_size' => $file['size'],
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'issued_date' => $issuedDate,
            'expiry_date' => $expiryDate,
            'created_by' => $userId,
        ];

        $insertId = (int)$this->db->insert($sql, $params);

        return new UserDocument(
            id: $insertId,
            organizationId: $orgId,
            userId: $orgId,
            documentType: (int)$data['document_category'],
            filename: $filename,
            originalFilename: $file['name'],
            fileSize: $file['size'],
            description: !empty($data['description']) ? trim($data['description']) : null,
            issuedDate: $issuedDate,
            expiryDate: $expiryDate,
            createdBy: $userId,
        );
    }

    public function deleteDocument(int $id, int $orgId, int $userId): void
    {
        $sql = "SELECT filename FROM `{DB::USER_DOCUMENTS}` 
                WHERE id = :id AND attachable_type = 'OrganizationDoc' 
                  AND attachable_id = :org_id";
        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);

        if ($row === null) {
            throw new ValidationException(['document' => 'Document not found.']);
        }

        $this->db->execute(
            "DELETE FROM `{DB::USER_DOCUMENTS}` WHERE id = :id AND attachable_type = 'OrganizationDoc' AND attachable_id = :org_id",
            ['id' => $id, 'org_id' => $orgId]
        );

        $filename = $row['filename'] ?? '';
        if ($filename !== '') {
            $path = dirname(__DIR__, 2) . self::UPLOAD_DIR . $filename;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }
}

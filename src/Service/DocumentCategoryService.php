<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\DocumentCategory;
use App\Repository\DocumentCategoryRepository;
use App\Exception\ValidationException;

class DocumentCategoryService
{
    private DocumentCategoryRepository $repo;

    public function __construct(DocumentCategoryRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?DocumentCategory
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['document_category'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['document_category' => 'Document category is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['document_category' => 'Document category already exists. Please enter a different one.']);
        }

        $item = new DocumentCategory(
            id: 0,
            documentCategory: $name,
            documentCategoryType: (string)($data['document_category_type'] ?? 'employees'),
            isActive: true,
            createdBy: $createdBy,
        );

        return $this->repo->insert($item);
    }

    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        $name = trim((string)($data['document_category'] ?? $existing->documentCategory));
        if ($name === '') {
            throw new ValidationException(['document_category' => 'Document category is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['document_category' => 'Document category already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'document_category' => $name,
            'document_category_type' => (string)($data['document_category_type'] ?? $existing->documentCategoryType),
            'is_active' => $existing->isActive ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\StorageType;
use App\Repository\StorageTypeRepository;
use App\Exception\ValidationException;

class StorageTypeService
{
    private StorageTypeRepository $repo;

    public function __construct(StorageTypeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?StorageType
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['storage_type'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['storage_type' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['storage_type' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new StorageType(
            id: 0,
            storageType: $name,
            description: (string)($data['description'] ?? ''),
            isActive: (bool)($data['is_active'] ?? true),
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

        $name = trim((string)($data['storage_type'] ?? $existing->storageType));
        if ($name === '') {
            throw new ValidationException(['storage_type' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['storage_type' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'storage_type' => $name,
            'description' => (string)($data['description'] ?? $existing->description),
            'is_active' => (bool)($data['is_active'] ?? $existing->isActive) ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

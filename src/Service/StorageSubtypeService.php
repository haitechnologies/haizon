<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\StorageSubtype;
use App\Repository\StorageSubtypeRepository;
use App\Exception\ValidationException;

class StorageSubtypeService
{
    private StorageSubtypeRepository $repo;

    public function __construct(StorageSubtypeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?StorageSubtype
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['storage_subtype'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['storage_subtype' => 'Storage subtype is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['storage_subtype' => 'Storage subtype already exists.']);
        }

        $item = new StorageSubtype(
            id: 0,
            storageSubtype: $name,
            storageTypeId: (int)($data['storage_type_id'] ?? 0),
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

        $name = trim((string)($data['storage_subtype'] ?? $existing->storageSubtype));
        if ($name === '') {
            throw new ValidationException(['storage_subtype' => 'Storage subtype is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['storage_subtype' => 'Storage subtype already exists.']);
        }

        return $this->repo->update($id, [
            'storage_subtype' => $name,
            'storage_type_id' => (int)($data['storage_type_id'] ?? $existing->storageTypeId),
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

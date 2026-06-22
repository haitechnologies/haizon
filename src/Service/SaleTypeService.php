<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\SaleType;
use App\Repository\SaleTypeRepository;
use App\Exception\ValidationException;

class SaleTypeService
{
    private SaleTypeRepository $repo;

    public function __construct(SaleTypeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?SaleType
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['sale_type'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['sale_type' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['sale_type' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new SaleType(
            id: 0,
            saleType: $name,
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

        $name = trim((string)($data['sale_type'] ?? $existing->saleType));
        if ($name === '') {
            throw new ValidationException(['sale_type' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['sale_type' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'sale_type' => $name,
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

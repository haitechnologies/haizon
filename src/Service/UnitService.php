<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Unit;
use App\Repository\UnitRepository;
use App\Exception\ValidationException;

class UnitService
{
    private UnitRepository $repo;

    public function __construct(UnitRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Unit
    {
        return $this->repo->find($id);
    }

    public function create(array $data, int $createdBy): int
    {
        $unitName = trim((string)($data['unit_name'] ?? ''));
        if ($unitName === '') {
            throw new ValidationException(['unit_name' => 'Unit is mandatory.']);
        }

        if ($this->repo->exists($unitName)) {
            throw new ValidationException(['unit_name' => 'Unit already exists. Please enter a different one.']);
        }

        $unit = new Unit(
            id: 0,
            unitName: $unitName,
            isActive: (bool)($data['publish'] ?? true),
            createdBy: $createdBy,
        );

        return $this->repo->insert($unit);
    }

    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        $unitName = trim((string)($data['unit_name'] ?? $existing->unitName));
        if ($unitName === '') {
            throw new ValidationException(['unit_name' => 'Unit is mandatory.']);
        }

        if ($this->repo->exists($unitName, $id)) {
            throw new ValidationException(['unit_name' => 'Duplicate Unit. Please enter different.']);
        }

        $updateData = [
            'unit' => $unitName,
            'publish' => (int)($data['publish'] ?? ($existing->isActive ? 1 : 0)),
            'is_active' => (int)($data['publish'] ?? ($existing->isActive ? 1 : 0)),
            'updated_by' => $updatedBy,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->repo->update($id, $updateData);
    }

    public function delete(int $id): bool
    {
        if ($this->repo->find($id) === null) {
            return false;
        }
        return $this->repo->delete($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function exists(string $value, ?int $excludeId = null): bool
    {
        return $this->repo->exists($value, $excludeId);
    }
}

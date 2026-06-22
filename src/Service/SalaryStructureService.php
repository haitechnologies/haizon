<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\SalaryStructure;
use App\Repository\SalaryStructureRepository;
use App\Exception\ValidationException;

class SalaryStructureService
{
    private SalaryStructureRepository $repo;

    public function __construct(SalaryStructureRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?SalaryStructure
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['effective_from'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['effective_from' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['effective_from' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new SalaryStructure(
            id: 0,
            effectiveFrom: $name,
            description: (string)($data['description'] ?? ''),
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

        $name = trim((string)($data['effective_from'] ?? $existing->effectiveFrom));
        if ($name === '') {
            throw new ValidationException(['effective_from' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['effective_from' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'effective_from' => $name,
            'description' => (string)($data['description'] ?? $existing->description),
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\PayrollComponent;
use App\Repository\PayrollComponentRepository;
use App\Exception\ValidationException;

class PayrollComponentService
{
    private PayrollComponentRepository $repo;

    public function __construct(PayrollComponentRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?PayrollComponent
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['component_name'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['component_name' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['component_name' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new PayrollComponent(
            id: 0,
            componentName: $name,
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

        $name = trim((string)($data['component_name'] ?? $existing->componentName));
        if ($name === '') {
            throw new ValidationException(['component_name' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['component_name' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'component_name' => $name,
            'description' => (string)($data['description'] ?? $existing->description),
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

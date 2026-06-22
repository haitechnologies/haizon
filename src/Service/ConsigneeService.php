<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Consignee;
use App\Repository\ConsigneeRepository;
use App\Exception\ValidationException;

class ConsigneeService
{
    private ConsigneeRepository $repo;

    public function __construct(ConsigneeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Consignee
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['consignee_name'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['consignee_name' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['consignee_name' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new Consignee(
            id: 0,
            consigneeName: $name,
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

        $name = trim((string)($data['consignee_name'] ?? $existing->consigneeName));
        if ($name === '') {
            throw new ValidationException(['consignee_name' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['consignee_name' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'consignee_name' => $name,
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

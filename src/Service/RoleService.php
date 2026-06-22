<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Role;
use App\Repository\RoleRepository;
use App\Exception\ValidationException;

class RoleService
{
    private RoleRepository $repo;

    public function __construct(RoleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Role
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['role_name'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['role_name' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['role_name' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new Role(
            id: 0,
            roleName: $name,
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

        $name = trim((string)($data['role_name'] ?? $existing->roleName));
        if ($name === '') {
            throw new ValidationException(['role_name' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['role_name' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'role_name' => $name,
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

<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\SetupGroup;
use App\Repository\SetupGroupRepository;
use App\Exception\ValidationException;
use App\Helper\SlugHelper;

class SetupGroupService
{
    private SetupGroupRepository $repo;

    public function __construct(SetupGroupRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?SetupGroup
    {
        return $this->repo->find($id);
    }

    public function create(array $data, int $createdBy): int
    {
        $groupName = trim((string)($data['group_name'] ?? ''));
        if ($groupName === '') {
            throw new ValidationException(['group_name' => 'Group Name is mandatory.']);
        }

        if ($this->repo->exists($groupName)) {
            throw new ValidationException(['group_name' => 'Group Name already exists. Please enter a different one.']);
        }

        $group = new SetupGroup(
            id: 0,
            groupName: $groupName,
            description: (string)($data['description'] ?? ''),
            isActive: (bool)($data['publish'] ?? true),
            createdBy: $createdBy,
        );

        return $this->repo->insert($group);
    }

    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        $groupName = trim((string)($data['group_name'] ?? $existing->groupName));
        if ($groupName === '') {
            throw new ValidationException(['group_name' => 'Group Name is mandatory.']);
        }

        if ($this->repo->exists($groupName, $id)) {
            throw new ValidationException(['group_name' => 'Duplicate Group Name. Please enter different.']);
        }

        $updateData = [
            'value' => $groupName,
            'key' => SlugHelper::slugify($groupName),
            'description' => $data['description'] ?? $existing->description,
            'is_active' => (int)($data['publish'] ?? ($existing->isActive ? 1 : 0)),
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

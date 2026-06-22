<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\ExitPoint;
use App\Repository\ExitPointRepository;
use App\Exception\ValidationException;

class ExitPointService
{
    private ExitPointRepository $repo;

    public function __construct(ExitPointRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?ExitPoint
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['exit_point'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['exit_point' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['exit_point' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new ExitPoint(
            id: 0,
            exitPoint: $name,
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

        $name = trim((string)($data['exit_point'] ?? $existing->exitPoint));
        if ($name === '') {
            throw new ValidationException(['exit_point' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['exit_point' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'exit_point' => $name,
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

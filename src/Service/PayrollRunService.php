<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\PayrollRun;
use App\Repository\PayrollRunRepository;
use App\Exception\ValidationException;

class PayrollRunService
{
    private PayrollRunRepository $repo;

    public function __construct(PayrollRunRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?PayrollRun
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['period_start'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['period_start' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['period_start' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new PayrollRun(
            id: 0,
            periodStart: $name,
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

        $name = trim((string)($data['period_start'] ?? $existing->periodStart));
        if ($name === '') {
            throw new ValidationException(['period_start' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['period_start' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'period_start' => $name,
            'description' => (string)($data['description'] ?? $existing->description),
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

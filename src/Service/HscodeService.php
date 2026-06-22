<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Hscode;
use App\Repository\HscodeRepository;
use App\Exception\ValidationException;

class HscodeService
{
    private HscodeRepository $repo;

    public function __construct(HscodeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Hscode
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $code = trim((string)($data['code'] ?? ''));
        if ($code === '') {
            throw new ValidationException(['code' => 'HS Code is mandatory.']);
        }
        if ($this->repo->exists($code)) {
            throw new ValidationException(['code' => 'HS Code already exists.']);
        }

        $item = new Hscode(
            id: 0,
            code: $code,
            oldCode: (string)($data['old_code'] ?? ''),
            level: (int)($data['level'] ?? 0),
            dutyRate: (string)($data['duty_rate'] ?? ''),
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

        $code = trim((string)($data['code'] ?? $existing->code));
        if ($code === '') {
            throw new ValidationException(['code' => 'HS Code is mandatory.']);
        }
        if ($this->repo->exists($code, $id)) {
            throw new ValidationException(['code' => 'HS Code already exists.']);
        }

        return $this->repo->update($id, [
            'code' => $code,
            'old_code' => (string)($data['old_code'] ?? $existing->oldCode),
            'level' => (int)($data['level'] ?? $existing->level),
            'duty_rate' => (string)($data['duty_rate'] ?? $existing->dutyRate),
            'is_active' => (bool)($data['is_active'] ?? $existing->isActive) ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

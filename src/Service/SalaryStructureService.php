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

    public function getById(int $id, int $orgId): ?SalaryStructure
    {
        return $this->repo->find($id, $orgId);
    }

    public function getByEmployee(int $employeeId, int $orgId): array
    {
        return $this->repo->findByEmployee($employeeId, $orgId);
    }

    public function getByEmployeeIndexed(int $employeeId, int $orgId): array
    {
        return $this->repo->findByEmployeeIndexed($employeeId, $orgId);
    }

    public function list(int $orgId): array
    {
        return $this->repo->findAll($orgId);
    }

    public function saveBatch(int $employeeId, array $components, int $orgId, int $createdBy): void
    {
        if ($employeeId <= 0) {
            throw new ValidationException(['employee_id' => 'Employee is mandatory.']);
        }

        $valid = false;
        $records = [];
        foreach ($components as $componentId => $data) {
            $componentId = (int)$componentId;
            $amount = (float)($data['amount'] ?? 0);
            if ($componentId <= 0 || $amount <= 0) {
                continue;
            }
            $valid = true;
            $records[] = new SalaryStructure(
                id: 0,
                organizationId: $orgId,
                employeeId: $employeeId,
                componentId: $componentId,
                amount: $amount,
                effectiveFrom: !empty($data['effective_from']) ? trim((string)$data['effective_from']) : null,
                effectiveTo: !empty($data['effective_to']) ? trim((string)$data['effective_to']) : null,
                isBasic: $componentId === 1,
                createdBy: $createdBy,
            );
        }

        if (!$valid) {
            throw new ValidationException(['amount' => 'At least one component must have an amount greater than zero.']);
        }

        $this->repo->deleteByEmployee($employeeId, $orgId);
        foreach ($records as $record) {
            $this->repo->insert($record);
        }
    }

    public function update(int $id, array $data): bool
    {
        return $this->repo->update($id, $data);
    }

    public function delete(int $id, int $orgId): bool
    {
        return $this->repo->delete($id, $orgId);
    }
}

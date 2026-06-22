<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Port;
use App\Repository\PortRepository;
use App\Exception\ValidationException;

class PortService
{
    private PortRepository $repo;

    public function __construct(PortRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Port
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['port_name'] ?? ''));
        $code = trim((string)($data['port_code'] ?? ''));
        $countryId = (int)($data['country_id'] ?? 0);

        if ($name === '') {
            throw new ValidationException(['port_name' => 'Port name is mandatory.']);
        }
        if ($code === '') {
            throw new ValidationException(['port_code' => 'Port code is mandatory.']);
        }
        if ($countryId <= 0) {
            throw new ValidationException(['country_id' => 'Country is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['port_name' => 'Port name already exists.']);
        }

        $item = new Port(
            id: 0,
            portName: $name,
            portCode: $code,
            countryId: $countryId,
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

        $name = trim((string)($data['port_name'] ?? $existing->portName));
        $code = trim((string)($data['port_code'] ?? $existing->portCode));
        $countryId = (int)($data['country_id'] ?? $existing->countryId);

        if ($name === '') {
            throw new ValidationException(['port_name' => 'Port name is mandatory.']);
        }
        if ($code === '') {
            throw new ValidationException(['port_code' => 'Port code is mandatory.']);
        }
        if ($countryId <= 0) {
            throw new ValidationException(['country_id' => 'Country is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['port_name' => 'Port name already exists.']);
        }

        return $this->repo->update($id, [
            'port_name' => $name,
            'port_code' => $code,
            'country_id' => $countryId,
            'is_active' => (bool)($data['is_active'] ?? $existing->isActive) ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

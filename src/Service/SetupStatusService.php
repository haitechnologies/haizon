<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\SetupStatus;
use App\Repository\SetupStatusRepository;
use App\Exception\ValidationException;
use App\Helper\SlugHelper;

class SetupStatusService
{
    private SetupStatusRepository $repo;

    public function __construct(SetupStatusRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?SetupStatus
    {
        return $this->repo->find($id);
    }

    public function create(array $data, int $createdBy): int
    {
        $statusName = trim((string)($data['status_name'] ?? ''));
        $statusType = trim((string)($data['status_type'] ?? ''));

        if ($statusType === '' || $statusType === '0') {
            throw new ValidationException(['status_type' => 'Please select Status type.']);
        }
        if ($statusName === '') {
            throw new ValidationException(['status_name' => 'Status name is mandatory.']);
        }

        $type = ($statusType === 'leads') ? 'lead_status' : (($statusType === 'vendors') ? 'vendor_status' : 'customer_status');

        if ($this->repo->exists($statusName, $type)) {
            throw new ValidationException(['status_name' => 'Status name already exists.']);
        }

        $status = new SetupStatus(
            id: 0,
            statusName: $statusName,
            statusType: $type,
            isActive: (bool)($data['publish'] ?? true),
            createdBy: $createdBy,
        );

        return $this->repo->insert($status);
    }

    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        $statusName = trim((string)($data['status_name'] ?? $existing->statusName));
        $statusType = $data['status_type'] ?? '';

        if ($statusType === '' || $statusType === '0') {
            throw new ValidationException(['status_type' => 'Please select Status type.']);
        }
        if ($statusName === '') {
            throw new ValidationException(['status_name' => 'Status name is mandatory.']);
        }

        $type = ($statusType === 'leads') ? 'lead_status' : (($statusType === 'vendors') ? 'vendor_status' : 'customer_status');

        if ($this->repo->exists($statusName, $type, $id)) {
            throw new ValidationException(['status_name' => 'Status name already exists.']);
        }

        $updateData = [
            'value' => $statusName,
            'key' => SlugHelper::slugify($statusName),
            'type' => $type,
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

    public function list(?string $type = null): array
    {
        return $this->repo->findAll($type);
    }

    public function exists(string $value, string $type, ?int $excludeId = null): bool
    {
        return $this->repo->exists($value, $type, $excludeId);
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\AttendanceDevice;
use App\Repository\AttendanceDeviceRepository;
use App\Exception\ValidationException;

class AttendanceDeviceService
{
    private AttendanceDeviceRepository $repo;

    public function __construct(AttendanceDeviceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?AttendanceDevice
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function getDevicesByOrg(int $orgId): array
    {
        return $this->repo->findByOrg($orgId);
    }

    public function create(array $data, int $createdBy): int
    {
        $deviceName = trim((string)($data['device_name'] ?? ''));
        $ipAddress = trim((string)($data['ip_address'] ?? ''));

        if ($deviceName === '') {
            throw new ValidationException(['device_name' => 'Device name is mandatory.']);
        }
        if ($ipAddress === '') {
            throw new ValidationException(['ip_address' => 'IP address is mandatory.']);
        }

        $item = new AttendanceDevice(
            id: 0,
            organizationId: (int)($data['organization_id'] ?? 0),
            deviceName: $deviceName,
            ipAddress: $ipAddress,
            port: (int)($data['port'] ?? 4370),
            serialNumber: trim((string)($data['serial_number'] ?? '')),
            devicePassword: (string)($data['device_password'] ?? '0'),
            deviceModel: trim((string)($data['device_model'] ?? '')),
            location: trim((string)($data['location'] ?? '')),
            lastSyncAt: null,
            lastPunchAt: null,
            isActive: isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1,
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

        $deviceName = trim((string)($data['device_name'] ?? $existing->deviceName));
        $ipAddress = trim((string)($data['ip_address'] ?? $existing->ipAddress));

        if ($deviceName === '') {
            throw new ValidationException(['device_name' => 'Device name is mandatory.']);
        }
        if ($ipAddress === '') {
            throw new ValidationException(['ip_address' => 'IP address is mandatory.']);
        }

        return $this->repo->update($id, [
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'port' => (int)($data['port'] ?? $existing->port),
            'serial_number' => trim((string)($data['serial_number'] ?? $existing->serialNumber)),
            'device_password' => (string)($data['device_password'] ?? $existing->devicePassword),
            'device_model' => trim((string)($data['device_model'] ?? $existing->deviceModel)),
            'location' => trim((string)($data['location'] ?? $existing->location)),
            'is_active' => isset($data['is_active']) ? (int)(bool)$data['is_active'] : $existing->isActive,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

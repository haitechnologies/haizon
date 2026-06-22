<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Alert;
use App\Repository\AlertRepository;
use App\Exception\ValidationException;

class AlertService
{
    private AlertRepository $repo;

    public function __construct(AlertRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Alert
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['alert_name'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['alert_name' => 'Alert name is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['alert_name' => 'Alert name already exists. Please enter a different one.']);
        }

        $item = new Alert(
            id: 0,
            alertName: $name,
            description: (string)($data['description'] ?? ''),
            type: (string)($data['type'] ?? 'general'),
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

        $name = trim((string)($data['alert_name'] ?? $existing->alertName));
        if ($name === '') {
            throw new ValidationException(['alert_name' => 'Alert name is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['alert_name' => 'Alert name already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'alert_name' => $name,
            'description' => (string)($data['description'] ?? $existing->description),
            'type' => (string)($data['type'] ?? $existing->type),
            'is_active' => (bool)($data['is_active'] ?? $existing->isActive) ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    /**
     * Create a system-generated notification alert
     *
     * Used by cron jobs and automated processes to create alerts
     * that don't require manual creation (type = 'system').
     */
    public function createSystemNotification(string $name, string $description, int $createdBy = 0): int
    {
        $item = new Alert(
            id: 0,
            alertName: $name,
            description: $description,
            type: 'system',
            isActive: true,
            createdBy: $createdBy,
        );

        return $this->repo->insert($item);
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\SetupSource;
use App\Repository\SetupSourceRepository;
use App\Exception\ValidationException;
use App\Helper\SlugHelper;

class SetupSourceService
{
    private SetupSourceRepository $repo;

    public function __construct(SetupSourceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?SetupSource
    {
        return $this->repo->find($id);
    }

    public function create(array $data, int $createdBy): int
    {
        $sourceName = trim((string)($data['source_name'] ?? ''));
        $sourceType = trim((string)($data['source_type'] ?? ''));

        if ($sourceType === '' || $sourceType === '0') {
            throw new ValidationException(['source_type' => 'Please select Source type.']);
        }
        if ($sourceName === '') {
            throw new ValidationException(['source_name' => 'Source is mandatory.']);
        }

        $type = ($sourceType === 'leads') ? 'lead_source' : 'customer_source';

        if ($this->repo->exists($sourceName, $type)) {
            throw new ValidationException(['source_name' => 'Source already exists. Please enter a different one.']);
        }

        $source = new SetupSource(
            id: 0,
            sourceName: $sourceName,
            sourceType: $type,
            isActive: (bool)($data['publish'] ?? true),
            createdBy: $createdBy,
        );

        return $this->repo->insert($source);
    }

    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        $sourceName = trim((string)($data['source_name'] ?? $existing->sourceName));
        $sourceType = $data['source_type'] ?? '';

        if ($sourceType === '' || $sourceType === '0') {
            throw new ValidationException(['source_type' => 'Please select Source type.']);
        }
        if ($sourceName === '') {
            throw new ValidationException(['source_name' => 'Source is mandatory.']);
        }

        $type = ($sourceType === 'leads') ? 'lead_source' : 'customer_source';

        if ($this->repo->exists($sourceName, $type, $id)) {
            throw new ValidationException(['source_name' => 'Duplicate Source. Please enter different.']);
        }

        $updateData = [
            'value' => $sourceName,
            'key' => SlugHelper::slugify($sourceName),
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

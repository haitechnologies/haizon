<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\SetupTag;
use App\Repository\SetupTagRepository;
use App\Exception\ValidationException;
use App\Helper\SlugHelper;

class SetupTagService
{
    private SetupTagRepository $repo;

    public function __construct(SetupTagRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?SetupTag
    {
        return $this->repo->find($id);
    }

    public function create(array $data, int $createdBy): int
    {
        $tagName = trim((string)($data['tag_name'] ?? ''));
        $tagType = trim((string)($data['tag_type'] ?? ''));

        if ($tagType === '' || $tagType === '0') {
            throw new ValidationException(['tag_type' => 'Please select Tag type.']);
        }
        if ($tagName === '') {
            throw new ValidationException(['tag_name' => 'Tag is mandatory.']);
        }

        $type = ($tagType === 'leads') ? 'lead_tag' : (($tagType === 'jobs') ? 'job_tag' : 'customer_tag');

        if ($this->repo->exists($tagName, $type)) {
            throw new ValidationException(['tag_name' => 'Tag already exists. Please enter a different one.']);
        }

        $tag = new SetupTag(
            id: 0,
            tagName: $tagName,
            tagType: $type,
            isActive: (bool)($data['publish'] ?? true),
            createdBy: $createdBy,
        );

        return $this->repo->insert($tag);
    }

    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        $tagName = trim((string)($data['tag_name'] ?? $existing->tagName));
        $tagType = $data['tag_type'] ?? '';

        if ($tagType === '' || $tagType === '0') {
            throw new ValidationException(['tag_type' => 'Please select Tag type.']);
        }
        if ($tagName === '') {
            throw new ValidationException(['tag_name' => 'Tag is mandatory.']);
        }

        $type = ($tagType === 'leads') ? 'lead_tag' : (($tagType === 'jobs') ? 'job_tag' : 'customer_tag');

        if ($this->repo->exists($tagName, $type, $id)) {
            throw new ValidationException(['tag_name' => 'Duplicate Tag. Please enter different.']);
        }

        $updateData = [
            'value' => $tagName,
            'key' => SlugHelper::slugify($tagName),
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

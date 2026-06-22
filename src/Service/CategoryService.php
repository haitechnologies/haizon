<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Category;
use App\Repository\CategoryRepository;
use App\Exception\ValidationException;

class CategoryService
{
    private CategoryRepository $repo;

    public function __construct(CategoryRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Category
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));

        if ($name === '') {
            throw new ValidationException(['name' => 'Category name is mandatory.']);
        }
        if ($slug === '') {
            throw new ValidationException(['slug' => 'Slug is mandatory.']);
        }
        if ($this->repo->exists($slug)) {
            throw new ValidationException(['slug' => 'Slug already exists. Please enter a different one.']);
        }

        $category = new Category(
            id: 0,
            name: $name,
            slug: $slug,
            description: (string)($data['description'] ?? ''),
            icon: (string)($data['icon'] ?? ''),
            metaTitle: (string)($data['meta_title'] ?? ''),
            metaDescription: (string)($data['meta_description'] ?? ''),
            isActive: (bool)($data['is_active'] ?? true),
            createdBy: $createdBy,
        );

        return $this->repo->insert($category);
    }

    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        $name = trim((string)($data['name'] ?? $existing->name));
        $slug = trim((string)($data['slug'] ?? $existing->slug));

        if ($name === '') {
            throw new ValidationException(['name' => 'Category name is mandatory.']);
        }
        if ($slug === '') {
            throw new ValidationException(['slug' => 'Slug is mandatory.']);
        }
        if ($this->repo->exists($slug, $id)) {
            throw new ValidationException(['slug' => 'Slug already exists. Please enter a different one.']);
        }

        $fields = [
            'name' => $name,
            'slug' => $slug,
            'description' => (string)($data['description'] ?? $existing->description),
            'icon' => (string)($data['icon'] ?? $existing->icon),
            'meta_title' => (string)($data['meta_title'] ?? $existing->metaTitle),
            'meta_description' => (string)($data['meta_description'] ?? $existing->metaDescription),
            'is_active' => (bool)($data['is_active'] ?? $existing->isActive) ? 1 : 0,
            'updated_by' => $updatedBy,
        ];

        return $this->repo->update($id, $fields);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

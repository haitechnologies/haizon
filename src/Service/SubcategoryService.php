<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Subcategory;
use App\Repository\SubcategoryRepository;
use App\Repository\CategoryRepository;
use App\Exception\ValidationException;

class SubcategoryService
{
    private SubcategoryRepository $repo;
    private CategoryRepository $categoryRepo;

    public function __construct(SubcategoryRepository $repo, CategoryRepository $categoryRepo)
    {
        $this->repo = $repo;
        $this->categoryRepo = $categoryRepo;
    }

    public function getById(int $id): ?Subcategory
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $categoryId = (int)($data['category_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));

        if ($categoryId <= 0) {
            throw new ValidationException(['category_id' => 'Parent category is mandatory.']);
        }
        if ($this->categoryRepo->find($categoryId) === null) {
            throw new ValidationException(['category_id' => 'Parent category not found.']);
        }
        if ($name === '') {
            throw new ValidationException(['name' => 'Subcategory name is mandatory.']);
        }
        if ($slug === '') {
            throw new ValidationException(['slug' => 'Slug is mandatory.']);
        }
        if ($this->repo->exists($slug)) {
            throw new ValidationException(['slug' => 'Slug already exists. Please enter a different one.']);
        }

        $item = new Subcategory(
            id: 0,
            categoryId: $categoryId,
            name: $name,
            slug: $slug,
            description: (string)($data['description'] ?? ''),
            icon: (string)($data['icon'] ?? ''),
            metaTitle: (string)($data['meta_title'] ?? ''),
            metaDescription: (string)($data['meta_description'] ?? ''),
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

        $categoryId = (int)($data['category_id'] ?? $existing->categoryId);
        $name = trim((string)($data['name'] ?? $existing->name));
        $slug = trim((string)($data['slug'] ?? $existing->slug));

        if ($categoryId <= 0) {
            throw new ValidationException(['category_id' => 'Parent category is mandatory.']);
        }
        if ($this->categoryRepo->find($categoryId) === null) {
            throw new ValidationException(['category_id' => 'Parent category not found.']);
        }
        if ($name === '') {
            throw new ValidationException(['name' => 'Subcategory name is mandatory.']);
        }
        if ($slug === '') {
            throw new ValidationException(['slug' => 'Slug is mandatory.']);
        }
        if ($this->repo->exists($slug, $id)) {
            throw new ValidationException(['slug' => 'Slug already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => (string)($data['description'] ?? $existing->description),
            'icon' => (string)($data['icon'] ?? $existing->icon),
            'meta_title' => (string)($data['meta_title'] ?? $existing->metaTitle),
            'meta_description' => (string)($data['meta_description'] ?? $existing->metaDescription),
            'is_active' => (bool)($data['is_active'] ?? $existing->isActive) ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

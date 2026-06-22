<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Item;
use App\Repository\ItemRepository;
use App\Exception\ValidationException;

class ItemService
{
    private ItemRepository $repo;

    public function __construct(ItemRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Item
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['item_name'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['item_name' => 'Item name is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['item_name' => 'Item name already exists.']);
        }

        $item = new Item(
            id: 0,
            itemType: (string)($data['item_type'] ?? 'services'),
            itemName: $name,
            unitPrice: (string)($data['unit_price'] ?? '0'),
            isExcise: (bool)($data['is_excise'] ?? false),
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

        $name = trim((string)($data['item_name'] ?? $existing->itemName));
        if ($name === '') {
            throw new ValidationException(['item_name' => 'Item name is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['item_name' => 'Item name already exists.']);
        }

        return $this->repo->update($id, [
            'item_type' => (string)($data['item_type'] ?? $existing->itemType),
            'item_name' => $name,
            'unit_price' => (string)($data['unit_price'] ?? $existing->unitPrice),
            'is_excise' => (bool)($data['is_excise'] ?? $existing->isExcise) ? 1 : 0,
            'is_active' => (bool)($data['is_active'] ?? $existing->isActive) ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\BannedWord;
use App\Repository\BannedWordRepository;
use App\Exception\ValidationException;

class BannedWordService
{
    private BannedWordRepository $repo;

    public function __construct(BannedWordRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?BannedWord
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['word'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['word' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['word' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new BannedWord(
            id: 0,
            word: $name,
            description: (string)($data['description'] ?? ''),
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

        $name = trim((string)($data['word'] ?? $existing->word));
        if ($name === '') {
            throw new ValidationException(['word' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['word' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'word' => $name,
            'description' => (string)($data['description'] ?? $existing->description),
            'is_active' => (bool)($data['is_active'] ?? $existing->isActive) ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}

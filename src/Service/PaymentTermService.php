<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\PaymentTerm;
use App\Repository\PaymentTermRepository;
use App\Exception\ValidationException;

class PaymentTermService
{
    private PaymentTermRepository $repo;

    public function __construct(PaymentTermRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?PaymentTerm
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $name = trim((string)($data['payment_term'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['payment_term' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name)) {
            throw new ValidationException(['payment_term' => 'Commodity type already exists. Please enter a different one.']);
        }

        $item = new PaymentTerm(
            id: 0,
            paymentTerm: $name,
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

        $name = trim((string)($data['payment_term'] ?? $existing->paymentTerm));
        if ($name === '') {
            throw new ValidationException(['payment_term' => 'Commodity type is mandatory.']);
        }
        if ($this->repo->exists($name, $id)) {
            throw new ValidationException(['payment_term' => 'Commodity type already exists. Please enter a different one.']);
        }

        return $this->repo->update($id, [
            'payment_term' => $name,
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

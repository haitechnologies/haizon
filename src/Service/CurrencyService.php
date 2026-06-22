<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Currency;
use App\Repository\CurrencyRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

class CurrencyService
{
    private CurrencyRepository $currencyRepo;

    public function __construct(CurrencyRepository $currencyRepo)
    {
        $this->currencyRepo = $currencyRepo;
    }

    public function getById(int $id, int $orgId): Currency
    {
        $currency = $this->currencyRepo->find($id, $orgId);
        if ($currency === null) {
            throw new NotFoundException("Currency with ID {$id} not found.");
        }
        return $currency;
    }

    public function create(array $data, int $orgId, int $userId): Currency
    {
        $name = trim((string)($data['currency'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['currency' => 'Currency is mandatory.']);
        }

        if ($this->currencyRepo->existsByName($name, $orgId)) {
            throw new ValidationException(['currency' => 'Currency already exists. Please enter a different one.']);
        }

        $currency = new Currency(
            id: null,
            organizationId: $orgId,
            currency: $name,
            isActive: !empty($data['publish'] ?? $data['is_active'] ?? true),
            createdBy: $userId
        );

        return $this->currencyRepo->save($currency);
    }

    public function update(int $id, array $data, int $orgId): Currency
    {
        $currency = $this->getById($id, $orgId);

        $name = trim((string)($data['currency'] ?? $currency->currency));
        if ($name === '') {
            throw new ValidationException(['currency' => 'Currency is mandatory.']);
        }

        if ($name !== $currency->currency && $this->currencyRepo->existsByName($name, $orgId, $id)) {
            throw new ValidationException(['currency' => 'Duplicate Currency. Please enter different.']);
        }

        $updatedCurrency = new Currency(
            id: $currency->id,
            organizationId: $orgId,
            currency: $name,
            isActive: isset($data['publish']) ? !empty($data['publish']) : (isset($data['is_active']) ? !empty($data['is_active']) : $currency->isActive),
            createdAt: $currency->createdAt,
            updatedAt: null,
            createdBy: $currency->createdBy
        );

        return $this->currencyRepo->save($updatedCurrency);
    }

    public function delete(int $id, int $orgId): void
    {
        $this->getById($id, $orgId);
        $this->currencyRepo->delete($id, $orgId);
    }

    public function list(int $orgId): array
    {
        return $this->currencyRepo->findAll($orgId);
    }

    public function listActive(int $orgId): array
    {
        return $this->currencyRepo->findAllActive($orgId);
    }
}

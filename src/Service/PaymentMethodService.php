<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\PaymentMethod;
use App\Repository\PaymentMethodRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

class PaymentMethodService
{
    private PaymentMethodRepository $paymentMethodRepo;

    public function __construct(PaymentMethodRepository $paymentMethodRepo)
    {
        $this->paymentMethodRepo = $paymentMethodRepo;
    }

    public function getById(int $id, int $orgId): PaymentMethod
    {
        $method = $this->paymentMethodRepo->find($id, $orgId);
        if ($method === null) {
            throw new NotFoundException("Payment method with ID {$id} not found.");
        }
        return $method;
    }

    public function create(array $data, int $orgId, int $userId): PaymentMethod
    {
        $name = trim((string)($data['payment_method'] ?? ''));
        if ($name === '') {
            throw new ValidationException(['payment_method' => 'Payment method is mandatory.']);
        }

        if ($this->paymentMethodRepo->existsByName($name, $orgId)) {
            throw new ValidationException(['payment_method' => 'Payment method already exists. Please enter a different one.']);
        }

        $method = new PaymentMethod(
            id: null,
            organizationId: $orgId,
            paymentMethod: $name,
            isActive: !empty($data['is_active'] ?? true),
            createdBy: $userId
        );

        return $this->paymentMethodRepo->save($method);
    }

    public function update(int $id, array $data, int $orgId): PaymentMethod
    {
        $method = $this->getById($id, $orgId);

        $name = trim((string)($data['payment_method'] ?? $method->paymentMethod));
        if ($name === '') {
            throw new ValidationException(['payment_method' => 'Payment method is mandatory.']);
        }

        if ($name !== $method->paymentMethod && $this->paymentMethodRepo->existsByName($name, $orgId, $id)) {
            throw new ValidationException(['payment_method' => 'Duplicate Payment method. Please enter different.']);
        }

        $updatedMethod = new PaymentMethod(
            id: $method->id,
            organizationId: $orgId,
            paymentMethod: $name,
            isActive: isset($data['is_active']) ? !empty($data['is_active']) : $method->isActive,
            createdAt: $method->createdAt,
            updatedAt: null,
            createdBy: $method->createdBy
        );

        return $this->paymentMethodRepo->save($updatedMethod);
    }

    public function delete(int $id, int $orgId): void
    {
        $this->getById($id, $orgId);
        $this->paymentMethodRepo->delete($id, $orgId);
    }

    public function list(int $orgId): array
    {
        return $this->paymentMethodRepo->findAll($orgId);
    }
}

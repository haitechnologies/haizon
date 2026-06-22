<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;
use App\Model\CustomerAddress;
use App\Repository\CustomerAddressRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

class CustomerAddressService
{
    private CustomerAddressRepository $addressRepo;
    private Database $db;

    public function __construct(CustomerAddressRepository $addressRepo, Database $db)
    {
        $this->addressRepo = $addressRepo;
        $this->db = $db;
    }

    public function upsert(int $customerId, string $type, array $data, int $orgId, int $userId): CustomerAddress
    {
        $this->validateCustomerExists($customerId, $orgId);

        $existing = $this->addressRepo->findByCustomerAndType($customerId, $orgId, $type);

        if ($existing === null) {
            return $this->create($customerId, $type, $data, $orgId, $userId);
        }

        return $this->update($existing, $data, $orgId, $userId);
    }

    private function create(int $customerId, string $type, array $data, int $orgId, int $userId): CustomerAddress
    {
        $this->validateAddressData($data);

        $address = new CustomerAddress(
            id: null,
            organizationId: $orgId,
            type: $type,
            customerId: $customerId,
            attention: !empty($data['attention']) ? trim((string)$data['attention']) : null,
            country: (int)($data['country'] ?? 0),
            addressLine1: !empty($data['address_line1']) ? trim((string)$data['address_line1']) : null,
            addressLine2: !empty($data['address_line2']) ? trim((string)$data['address_line2']) : null,
            city: !empty($data['city']) ? trim((string)$data['city']) : null,
            state: !empty($data['state']) ? trim((string)$data['state']) : null,
            zipcode: !empty($data['zipcode']) ? trim((string)$data['zipcode']) : null,
            phone: !empty($data['phone']) ? trim((string)$data['phone']) : null,
            fax: !empty($data['fax']) ? trim((string)$data['fax']) : null,
            createdBy: $userId,
        );

        return $this->addressRepo->save($address);
    }

    private function update(CustomerAddress $existing, array $data, int $orgId, int $userId): CustomerAddress
    {
        $updatedAddress = new CustomerAddress(
            id: $existing->id,
            organizationId: $existing->organizationId,
            type: $existing->type,
            customerId: $existing->customerId,
            attention: isset($data['attention']) ? (!empty($data['attention']) ? trim((string)$data['attention']) : null) : $existing->attention,
            country: isset($data['country']) ? (int)$data['country'] : $existing->country,
            addressLine1: isset($data['address_line1']) ? (!empty($data['address_line1']) ? trim((string)$data['address_line1']) : null) : $existing->addressLine1,
            addressLine2: isset($data['address_line2']) ? (!empty($data['address_line2']) ? trim((string)$data['address_line2']) : null) : $existing->addressLine2,
            city: isset($data['city']) ? (!empty($data['city']) ? trim((string)$data['city']) : null) : $existing->city,
            state: isset($data['state']) ? (!empty($data['state']) ? trim((string)$data['state']) : null) : $existing->state,
            zipcode: isset($data['zipcode']) ? (!empty($data['zipcode']) ? trim((string)$data['zipcode']) : null) : $existing->zipcode,
            phone: isset($data['phone']) ? (!empty($data['phone']) ? trim((string)$data['phone']) : null) : $existing->phone,
            fax: isset($data['fax']) ? (!empty($data['fax']) ? trim((string)$data['fax']) : null) : $existing->fax,
            publish: isset($data['publish']) ? (bool)$data['publish'] : $existing->publish,
            isActive: isset($data['is_active']) ? (bool)$data['is_active'] : $existing->isActive,
            createdAt: $existing->createdAt,
            createdBy: $existing->createdBy,
            updatedBy: $userId,
        );

        return $this->addressRepo->save($updatedAddress);
    }

    private function validateCustomerExists(int $customerId, int $orgId): void
    {
        $sql = "SELECT id FROM `{DB::CUSTOMERS}` WHERE id = :id AND organization_id = :org_id LIMIT 1";
        $row = $this->db->fetchOne($sql, ['id' => $customerId, 'org_id' => $orgId]);
        if ($row === null) {
            throw new NotFoundException("Customer with ID {$customerId} not found.");
        }
    }

    private function validateAddressData(array $data): void
    {
        if (empty($data['address_line1'])) {
            throw new ValidationException(['address_line1' => 'Address Line 1 is required.']);
        }
        if (empty($data['city'])) {
            throw new ValidationException(['city' => 'City is required.']);
        }
        if (empty($data['country']) || (int)$data['country'] <= 0) {
            throw new ValidationException(['country' => 'Please select a country.']);
        }
    }
}

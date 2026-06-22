<?php

declare(strict_types=1);

namespace App\Model;

/**
 * User DTO
 *
 * Readonly data transfer object representing a user (employee) record.
 */
readonly class User
{
    public function __construct(
        public ?int $id,
        public bool $canAccessSystem,
        public bool $isActive,
        public int $roleId,
        public string $email,
        public ?string $password,
        public string $fullName,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $mobile = null,
        public ?string $contact1 = null,
        public ?string $contact2 = null,
        public ?string $address = null,
        public ?string $dob = null,
        public ?int $departmentId = null,
        public ?string $lastLogin = null,
        public ?string $photo = null,
        public bool $publish = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public int $createdBy = 0
    ) {
    }

    /**
     * Convert DTO to legacy array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'can_access_system' => $this->canAccessSystem ? 1 : 0,
            'is_active' => $this->isActive ? 1 : 0,
            'role_id' => $this->roleId,
            'email' => $this->email,
            'password' => $this->password,
            'full_name' => $this->fullName,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'mobile' => $this->mobile,
            'contact1' => $this->contact1,
            'contact2' => $this->contact2,
            'address' => $this->address,
            'dob' => $this->dob,
            'department_id' => $this->departmentId,
            'last_login' => $this->lastLogin,
            'photo' => $this->photo,
            'publish' => $this->publish ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}

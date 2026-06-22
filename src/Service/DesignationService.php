<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Designation;
use App\Repository\DesignationRepository;
use App\Repository\UserRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * Designation Service
 *
 * Implements business logic for managing designations.
 */
class DesignationService
{
    private DesignationRepository $designationRepo;
    private UserRepository $userRepo;

    public function __construct(DesignationRepository $designationRepo, UserRepository $userRepo)
    {
        $this->designationRepo = $designationRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Retrieve a designation by ID
     *
     * @param int $id
     * @return Designation
     * @throws NotFoundException
     */
    public function getById(int $id): Designation
    {
        $designation = $this->designationRepo->find($id);
        if ($designation === null) {
            throw new NotFoundException("Designation with ID {$id} not found.");
        }
        return $designation;
    }

    /**
     * Create a new designation
     *
     * @param string $name
     * @param int $organizationId
     * @param int $createdBy
     * @return Designation
     * @throws ValidationException
     */
    public function create(string $name, int $organizationId, int $createdBy): Designation
    {
        $name = trim($name);
        if ($name === '') {
            throw new ValidationException(['designation' => 'Designation is mandatory.']);
        }

        if ($this->designationRepo->existsByName($name)) {
            throw new ValidationException([
                'designation' => 'Designation already exists. Please enter a different one.'
            ]);
        }

        $designation = new Designation(
            id: null,
            organizationId: $organizationId,
            designation: $name,
            publish: true,
            createdBy: $createdBy
        );

        return $this->designationRepo->save($designation);
    }

    /**
     * Update an existing designation
     *
     * @param int $id
     * @param string $name
     * @param bool $publish
     * @return Designation
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function update(int $id, string $name, bool $publish): Designation
    {
        $designation = $this->getById($id);
        $name = trim($name);

        if ($name === '') {
            throw new ValidationException(['designation' => 'Designation is mandatory.']);
        }

        if ($this->designationRepo->existsByName($name, $id)) {
            throw new ValidationException(['designation' => 'Duplicate Designation. Please enter different.']);
        }

        $updatedDesignation = new Designation(
            id: $designation->id,
            organizationId: $designation->organizationId,
            designation: $name,
            publish: $publish,
            createdAt: $designation->createdAt,
            updatedAt: null,
            createdBy: $designation->createdBy
        );

        return $this->designationRepo->save($updatedDesignation);
    }

    /**
     * Delete a designation by ID
     *
     * @param int $id
     * @throws NotFoundException
     */
    public function delete(int $id): void
    {
        // Fetch to ensure it exists
        $this->getById($id);

        if ($this->userRepo->hasUsersInDesignation($id)) {
            throw new ValidationException(['designation' => 'Designation is associated with rows in Users Table.']);
        }

        $this->designationRepo->delete($id);
    }

    /**
     * List all designations for an organization
     *
     * @param int $organizationId
     * @return Designation[]
     */
    public function list(int $organizationId): array
    {
        return $this->designationRepo->findAll($organizationId);
    }
}

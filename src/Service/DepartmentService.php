<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Department;
use App\Repository\DepartmentRepository;
use App\Repository\UserRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * Department Service
 * 
 * Implements business logic for managing departments.
 */
class DepartmentService
{
    private DepartmentRepository $deptRepo;
    private UserRepository $userRepo;

    public function __construct(DepartmentRepository $deptRepo, UserRepository $userRepo)
    {
        $this->deptRepo = $deptRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Retrieve a department by ID
     *
     * @param int $id
     * @return Department
     * @throws NotFoundException
     */
    public function getById(int $id): Department
    {
        $dept = $this->deptRepo->find($id);
        if ($dept === null) {
            throw new NotFoundException("Department with ID {$id} not found.");
        }
        return $dept;
    }

    /**
     * Create a new department
     *
     * @param string $name
     * @param int $organizationId
     * @param int $createdBy
     * @return Department
     * @throws ValidationException
     */
    public function create(string $name, int $organizationId, int $createdBy): Department
    {
        $name = trim($name);
        if ($name === '') {
            throw new ValidationException(['department' => 'Department name is mandatory.']);
        }

        if ($this->deptRepo->existsByName($name)) {
            throw new ValidationException(['department' => 'Department already exists. Please enter a different one.']);
        }

        $dept = new Department(
            id: null,
            organizationId: $organizationId,
            department: $name,
            publish: true,
            createdBy: $createdBy
        );

        return $this->deptRepo->save($dept);
    }

    /**
     * Update an existing department name and/or status
     *
     * @param int $id
     * @param string $name
     * @param bool $publish
     * @return Department
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function update(int $id, string $name, bool $publish): Department
    {
        $dept = $this->getById($id);
        $name = trim($name);

        if ($name === '') {
            throw new ValidationException(['department' => 'Department is mandatory.']);
        }

        if ($this->deptRepo->existsByName($name, $id)) {
            throw new ValidationException(['department' => 'Duplicate Department. Please enter different.']);
        }

        $updatedDept = new Department(
            id: $dept->id,
            organizationId: $dept->organizationId,
            department: $name,
            publish: $publish,
            createdAt: $dept->createdAt,
            updatedAt: null,
            createdBy: $dept->createdBy
        );

        return $this->deptRepo->save($updatedDept);
    }

    /**
     * Delete a department by ID
     *
     * @param int $id
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function delete(int $id): void
    {
        $dept = $this->getById($id);

        if ($this->userRepo->hasUsersInDepartment($id)) {
            throw new ValidationException(['department' => 'Department is associated with rows in Users Table.']);
        }

        $this->deptRepo->delete($id);
    }

    /**
     * List all departments for an organization
     *
     * @param int $organizationId
     * @return Department[]
     */
    public function list(int $organizationId): array
    {
        return $this->deptRepo->findAll($organizationId);
    }
}

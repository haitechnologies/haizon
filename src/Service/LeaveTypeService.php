<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\LeaveType;
use App\Repository\LeaveTypeRepository;
use App\Repository\LeaveRequestRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * LeaveType Service
 *
 * Implements business logic for managing leave types.
 */
class LeaveTypeService
{
    private LeaveTypeRepository $typeRepo;
    private LeaveRequestRepository $requestRepo;

    public function __construct(LeaveTypeRepository $typeRepo, LeaveRequestRepository $requestRepo)
    {
        $this->typeRepo = $typeRepo;
        $this->requestRepo = $requestRepo;
    }

    /**
     * Get a leave type by ID
     */
    public function getById(int $id, int $organizationId): LeaveType
    {
        $type = $this->typeRepo->find($id, $organizationId);
        if ($type === null) {
            throw new NotFoundException("Leave type with ID {$id} not found.");
        }
        return $type;
    }

    /**
     * Create a new leave type
     */
    public function create(string $leaveType, int $maxPerYear, bool $paid, int $organizationId): LeaveType
    {
        $leaveType = trim($leaveType);
        if ($leaveType === '') {
            throw new ValidationException(['leave_type' => 'Leave Type name is mandatory.']);
        }

        if ($this->typeRepo->existsByName($leaveType, $organizationId)) {
            throw new ValidationException(['leave_type' => 'Leave Type already exists. Please enter a different one.']);
        }

        $type = new LeaveType(
            id: null,
            organizationId: $organizationId,
            leaveType: $leaveType,
            maxPerYear: $maxPerYear,
            paid: $paid
        );

        return $this->typeRepo->save($type);
    }

    /**
     * Update an existing leave type
     */
    public function update(int $id, string $leaveType, int $maxPerYear, bool $paid, int $organizationId): LeaveType
    {
        $type = $this->getById($id, $organizationId);
        $leaveType = trim($leaveType);

        if ($leaveType === '') {
            throw new ValidationException(['leave_type' => 'Leave Type name is mandatory.']);
        }

        if ($this->typeRepo->existsByName($leaveType, $organizationId, $id)) {
            throw new ValidationException(['leave_type' => 'Duplicate Leave Type. Please enter different.']);
        }

        $updatedType = new LeaveType(
            id: $type->id,
            organizationId: $type->organizationId,
            leaveType: $leaveType,
            maxPerYear: $maxPerYear,
            paid: $paid,
            createdAt: $type->createdAt,
            updatedAt: null
        );

        return $this->typeRepo->save($updatedType);
    }

    /**
     * Delete a leave type by ID
     */
    public function delete(int $id, int $organizationId): void
    {
        // Check if leave type exists
        $this->getById($id, $organizationId);

        // Check if there are associated leave requests
        if ($this->requestRepo->hasRequestsForType($id, $organizationId)) {
            throw new ValidationException(['leave_type' => 'Leave Type is associated with Leave Requests.']);
        }

        $this->typeRepo->delete($id, $organizationId);
    }

    /**
     * List all leave types for an organization
     *
     * @return LeaveType[]
     */
    public function list(int $organizationId): array
    {
        return $this->typeRepo->findAll($organizationId);
    }
}

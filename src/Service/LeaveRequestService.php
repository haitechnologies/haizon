<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\LeaveRequest;
use App\Repository\LeaveRequestRepository;
use App\Repository\LeaveTypeRepository;
use App\Repository\UserRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * LeaveRequest Service
 *
 * Implements business logic for managing leave requests.
 */
class LeaveRequestService
{
    private LeaveRequestRepository $requestRepo;
    private LeaveTypeRepository $typeRepo;
    private UserRepository $userRepo;

    public function __construct(
        LeaveRequestRepository $requestRepo,
        LeaveTypeRepository $typeRepo,
        UserRepository $userRepo
    ) {
        $this->requestRepo = $requestRepo;
        $this->typeRepo = $typeRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Get a leave request by ID
     */
    public function getById(int $id, int $organizationId): LeaveRequest
    {
        $request = $this->requestRepo->find($id, $organizationId);
        if ($request === null) {
            throw new NotFoundException("Leave request with ID {$id} not found.");
        }
        return $request;
    }

    /**
     * Create a new leave request
     */
    public function create(array $data, int $organizationId): LeaveRequest
    {
        $errors = $this->validate($data, $organizationId);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $request = new LeaveRequest(
            id: null,
            organizationId: $organizationId,
            employeeId: (int)$data['employee_id'],
            leaveTypeId: (int)$data['leave_type_id'],
            startDate: trim((string)$data['start_date']),
            endDate: trim((string)$data['end_date']),
            totalDays: (float)$data['total_days'],
            reason: !empty($data['reason']) ? trim((string)$data['reason']) : null,
            status: trim((string)($data['status'] ?? 'pending')),
            approvedBy: !empty($data['approved_by']) ? (int)$data['approved_by'] : null
        );

        return $this->requestRepo->save($request);
    }

    /**
     * Update an existing leave request
     */
    public function update(int $id, array $data, int $organizationId): LeaveRequest
    {
        $request = $this->getById($id, $organizationId);

        $errors = $this->validate($data, $organizationId);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $updatedRequest = new LeaveRequest(
            id: $request->id,
            organizationId: $request->organizationId,
            employeeId: (int)$data['employee_id'],
            leaveTypeId: (int)$data['leave_type_id'],
            startDate: trim((string)$data['start_date']),
            endDate: trim((string)$data['end_date']),
            totalDays: (float)$data['total_days'],
            reason: !empty($data['reason']) ? trim((string)$data['reason']) : null,
            status: trim((string)($data['status'] ?? $request->status)),
            approvedBy: !empty($data['approved_by']) ? (int)$data['approved_by'] : null,
            createdAt: $request->createdAt,
            updatedAt: null
        );

        return $this->requestRepo->save($updatedRequest);
    }

    /**
     * Delete a leave request
     */
    public function delete(int $id, int $organizationId): void
    {
        $this->getById($id, $organizationId);
        $this->requestRepo->delete($id, $organizationId);
    }

    /**
     * List all leave requests for an organization
     *
     * @return LeaveRequest[]
     */
    public function list(int $organizationId): array
    {
        return $this->requestRepo->findAll($organizationId);
    }

    /**
     * Validate leave request parameters
     */
    private function validate(array $data, int $organizationId): array
    {
        $errors = [];

        $employeeId = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;
        if ($employeeId <= 0) {
            $errors['employee_id'] = 'Employee is mandatory.';
        } else {
            $user = $this->userRepo->find($employeeId);
            if ($user === null) {
                $errors['employee_id'] = 'Selected employee does not exist.';
            }
        }

        $leaveTypeId = isset($data['leave_type_id']) ? (int)$data['leave_type_id'] : 0;
        if ($leaveTypeId <= 0) {
            $errors['leave_type_id'] = 'Leave Type is mandatory.';
        } else {
            $leaveType = $this->typeRepo->find($leaveTypeId, $organizationId);
            if ($leaveType === null) {
                $errors['leave_type_id'] = 'Selected Leave Type does not exist in this organization.';
            }
        }

        $startDate = isset($data['start_date']) ? trim((string)$data['start_date']) : '';
        if ($startDate === '') {
            $errors['start_date'] = 'Start Date is mandatory.';
        }

        $endDate = isset($data['end_date']) ? trim((string)$data['end_date']) : '';
        if ($endDate === '') {
            $errors['end_date'] = 'End Date is mandatory.';
        }

        if ($startDate !== '' && $endDate !== '') {
            if (strtotime($startDate) > strtotime($endDate)) {
                $errors['end_date'] = 'End Date cannot be before Start Date.';
            }
        }

        $totalDays = isset($data['total_days']) ? (float)$data['total_days'] : 0.0;
        if ($totalDays <= 0) {
            $errors['total_days'] = 'Total Days must be greater than 0.';
        }

        $status = isset($data['status']) ? trim((string)$data['status']) : 'pending';
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $errors['status'] = 'Invalid status value.';
        }

        return $errors;
    }
}
